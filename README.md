# Minecraft CMD

A companion toolkit for Minecraft Java Edition — build commands, plan
builds, fix what is broken, and learn the farms. It runs as a small PHP
site you keep open on a second screen while you play.

## Running it

**With XAMPP (or any PHP + MySQL setup)**

1. Drop the folder in `htdocs/`.
2. Import `database.sql` in phpMyAdmin (optional — see below).
3. Open `http://localhost/minecraft-cmd/`.

**With nothing but PHP**

```bash
php -S localhost:8000
```

The database is optional. If MySQL is not reachable, the app falls back
to a SQLite file at `data/minecraft_cmd.sqlite` and creates the tables
itself, so saving still works. `db.php` also adds any missing columns on
connect, so an older database is upgraded in place rather than needing a
re-import.

## What is in it

| Section | What it does |
| --- | --- |
| **Home** | Quick actions, what you were last working on, and Surprise Me |
| **Tools** | Every generator, with a live filter |
| **Commands** | give, clear, gamemode, effect, experience, teleport, time, weather, gamerule, difficulty, locate, summon, kill |
| **Build** | fill, clear area, replace, setblock, clone, area calculator, coordinate helper, build planner |
| **Doctor** | Paste a broken command, get the problem, the reason and a fix |
| **Knowledge** | 138 blocks, palette builder, 23 building tips, 14 build ideas |
| **Farms** | 12 step-by-step guides with material checklists and troubleshooting |
| **My Stuff** | Saved commands, history, palettes and kits |

Global search (`/` or `Ctrl-K`) matches on intent, not just names —
"make everyone creative" finds the gamemode builder, "clear trees" finds
Clear Area.

## Version awareness

Minecraft command syntax changed twice in ways that break old commands,
so the version selector in the top-right drives every generator. There
are three syntax eras for items:

| Versions | Form | Example |
| --- | --- | --- |
| ≤ 1.20.4 | NBT in braces | `diamond_sword{Enchantments:[{id:"minecraft:sharpness",lvl:5s}]}` |
| 1.20.5 – 1.21.4 | Components, text as JSON strings | `diamond_sword[enchantments={levels:{sharpness:5}}]` |
| 1.21.5 + | Components, text as SNBT | `diamond_sword[enchantments={sharpness:5}]` |

1.21.5 was the break: NBT gained heterogeneous lists, so text components
stopped being stored as JSON strings, the `levels` wrapper was dropped
from `enchantments`, and per-component `show_in_tooltip` was replaced by
the single `tooltip_display` component.

Bedrock is offered as an edition, and tools that genuinely cannot do
something there say so rather than emitting Java syntax that will be
rejected.

## Layout

```
index.php commands.php build.php doctor.php     pages
knowledge.php farms.php mystuff.php tools.php
kit.php sequencer.php nbt.php title.php         the original generators
firework.php scoreboard.php sign.php book.php

lib/mc.php          version registry and syntax profiles
lib/ui.php          shared render helpers
lib/registry.php    the catalogue behind global search
lib/snbt.php        SNBT parser and writer
lib/doctor.php      command analysis and explanation
lib/data/           game data and written content

assets/mccmd.js     shared runtime — versions, SNBT, item building, copy
assets/*.js         per-page behaviour

api.php             JSON endpoint
db.php              MySQL with a SQLite fallback
style.php nav.php   shared shell
```

`lib/mc.php` is the single source of truth for versions. It is exported
to JavaScript by `lib/ui.php`, so the client never keeps its own copy.

## Tests

```bash
./tests/run.sh
```

Lints every PHP and JS file, then runs the Playwright suites against a
local server: the command builders, the build tools, the Doctor and
Explainer, every page end to end, and the original generators across all
three syntax eras.

## Adding things

- **A tool** — add it to `registry_tools()` in `lib/registry.php` and it
  appears in the Tools hub, on Home and in global search.
- **A block, tip, build idea or farm** — add it to the matching file in
  `lib/data/`. Search picks it up through the `*_search_entries()`
  function already defined there.
- **A Minecraft version** — add it to `MC_VERSIONS` in `lib/mc.php` with
  a `rank` (chronological, since 26.x sorts after 1.21.x) and a syntax
  profile. Every generator follows automatically.
