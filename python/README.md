# minecraft_engine — the offline data engine

## Why this exists

Minecraft CMD is, and stays, a PHP application with a JavaScript
frontend. This package does not change that. It exists because turning
a supplied Minecraft resource archive into something the app can trust
— "does this texture actually exist for this version, and is it the
same artwork as the one already in the repo" — is a data-processing
problem (scan thousands of files, normalize ids, decode and compare
images), and Python's standard library plus Pillow are a better fit
for that than writing it in PHP or in a Playwright-driven JS script.

Python runs **once, offline, at build/maintenance time** — a developer
invokes it by hand when a new archive shows up. It is never started by
a web request, never imports or is imported by any PHP/JS file, and
never touches the app's runtime database (`data/minecraft_cmd.sqlite`).

## Who owns what

| Layer | Owns |
|---|---|
| **PHP** | The entire web application: pages, the version registry (`lib/mc.php`), the game-data registries (`lib/data/*.php`), the texture resolver (`lib/textures.php`), command generation. This is unchanged by this package. |
| **JavaScript** | The frontend: builders, visual tiles, the client-side mirror of the version/texture resolver (`assets/mccmd.js`). Also unchanged. |
| **Python** (`python/minecraft_engine/`) | Reading Minecraft resource archives and turning them into small, validated JSON files under `data/generated/`. Nothing else. It does not decide what the app looks like — it only describes what a given archive *contains*. |

## What "generated data" means — and doesn't

`data/generated/*.json` is an **output**, not a second source of
truth. It is never read by the PHP app today (see "Current product
integration" below), and this package never writes anywhere except
the output directory you give it — never into `assets/`, `lib/`, or
`lib/data/*.php`. The hand-maintained PHP registries stay authoritative
for game data (display names, categories, hex swatches, drop tables —
none of which a filename can tell you). Generated data answers a
narrower, purely mechanical question: *for this archive, which ids
have which asset files, and how do two archives compare*.

Concretely, `data/generated/` holds:

- `versions.json` — a snapshot of `lib/mc.php`'s `MC_VERSIONS` /
  `MC_FEATURES`, as of when you last ran the engine (see below — this
  is read *from* PHP, never invented).
- `blocks.json` / `items.json` / `entities.json` — every recognised
  asset record (`{id, type, texture, source}`) for the scanned
  source(s), grouped by broad kind.
- `textures.json` — just the texture records, flattened, for a quick
  "does X have a texture file at all" lookup.
- `version-diff.json` — present only when you scan two sources:
  `added` / `removed` / `changed` / `unchanged` per id, with pixel-level
  comparison for textures (see `diff.py`).

## How to run it

```bash
cd python
pip install -r requirements.txt   # optional — enables pixel comparison in diff.py

# One source:
python3 -m minecraft_engine.generate \
  --source /path/to/1.20.1-archive.zip --source-label 1.20.1 \
  --out ../data/generated

# Two sources, to get a version diff:
python3 -m minecraft_engine.generate \
  --source /path/to/1.20.1-archive.zip --source-label 1.20.1 --source-version 1.20.1 \
  --source-b /path/to/newer-archive.zip --source-b-label 1.20.5-26.2 --source-b-version 1.21.5 \
  --out ../data/generated
```

`--source`/`--source-b` accept a directory or a `.zip` — the archives
supplied to this project (a flat category dump and a full resource
pack) use different internal layouts and both are auto-detected; see
`assets.py`'s module docstring. Nothing in this package hardcodes a
path to either specific archive — every run takes its input as a CLI
argument, so it works for any future Minecraft archive dropped into
the project the same way.

Run the tests (standard library `unittest`, no extra runner needed):

```bash
python3 -m unittest discover -s python/tests -t python
```

## Validation

`generate.py` always runs `validator.py` over the scanned assets and
prints a summary before writing anything. Every finding has one of
three severities:

- **error** — the generated data would be wrong or misleading: a
  malformed id, two files silently claiming the exact same (type, id)
  identity, or a source claiming a Minecraft version that isn't in
  `lib/mc.php`. `--strict` exits non-zero only on these.
- **warning** — a bare id reused across texture categories the checker
  has no specific reason to already trust (e.g. `item/book` vs.
  `gui/book`) — worth a glance, never blocked. See `validator.py`'s
  module docstring for why a shared filename is not, on its own, a
  conflict: an asset's real identity is (type, id), never id alone.
- **info** — an already-understood relationship (a block's placed
  texture vs. its inventory icon, an entity's render skin vs. its
  inventory icon) or a definition with no same-id texture in that
  source (which can legitimately point at a different id's texture).

Errors and warnings both print during a run; only errors affect
`--strict`'s exit code.

## Adding a new Minecraft asset source

1. Get the archive (directory or `.zip`) onto disk somewhere — this
   package never fetches assets itself, matching the app-wide rule
   that all Minecraft art comes from a legitimately supplied source,
   never downloaded automatically.
2. Run `generate.py` against it with a `--source-label` that says what
   it is (a version id, a pack name — whatever's meaningful to you).
3. Look at the printed validation summary and `data/generated/*.json`.
   If it's a genuinely new Minecraft asset shape `assets.py` doesn't
   recognise yet (a category neither supplied archive had), add a rule
   to `_RULES` in `assets.py` — that's the one place classification
   lives.

## The version bridge

`tools/export_mc_data.php` (repo root, not inside `python/`) is a tiny
CLI script — `php tools/export_mc_data.php` — that dumps `lib/mc.php`'s
tables as JSON. `versions.py` shells out to it by default. This is
deliberate: it means Python can never drift from the PHP version table,
because it never has its own copy to drift from — every run reads the
live `lib/mc.php`. If PHP genuinely isn't available (e.g. a
Python-only CI job), `versions.py` also accepts a previously-exported
JSON snapshot via `--versions-json` / `from_json_file()`.

## Current product integration: optional, read-only enrichment

`lib/generated.php` (repo root, not inside `python/`) is the one place
the PHP app reads `data/generated/*.json`. It is read-only, additive,
and never required:

- Knowledge → Materials and Knowledge → Items merge a small
  `generated: {verified, sources, changed}` summary onto each block/item
  row, from `generatedSummary()`. `verified` means "a scanned archive
  actually contained this id"; `changed` means `version-diff.json`
  marked its texture as changed between the two scanned sources. The
  UI shows a "✓ scan-verified" tag only when `verified` is true.
- Every one of those calls returns `{verified:false, sources:[],
  changed:false}` when `data/generated/` doesn't exist (the normal
  state of a fresh clone — see "Generated data is not committed"
  below), so nothing about the app's behaviour depends on this data
  having been generated.
- Nothing here decides display names, categories, hex swatches or
  styles — those stay hand-curated in `lib/data/*.php`, exactly as
  before. This package still does not modify the command generator,
  the texture resolver, or any existing registry — it is read by one
  small new file, not merged into the existing ones.

### Generated data is not committed

`data/generated/` stays under the repository's existing `/data/`
`.gitignore` entry — it is a regenerable local build artifact, the
same category as `data/minecraft_cmd.sqlite` (db.php's fallback
database) that already lives there. Three reasons:

1. The app's real texture files (`assets/textures/{item,block}/*.png`)
   are already committed and already power `lib/textures.php`'s
   resolver — generated JSON adds provenance metadata, not rendering
   capability, so there's nothing visual a fresh clone is missing.
2. A full scan of a real archive is thousands of records (the archives
   used to verify this engine produced 1,854 and 9,304 recognised
   assets respectively) — committing that wholesale would be exactly
   the "huge or unnecessary generated file" this project avoids
   elsewhere.
3. It requires a legitimately-obtained Minecraft archive to produce,
   the same restriction that already keeps textures out of the repo
   by default — committing its output would quietly launder that
   requirement away.

Because every PHP call above degrades to "unverified" rather than
failing, this is a safe default: run the engine locally against your
own archive (see "How to run it" above) to light up the verification
tags; skip it and the app works exactly as it did before this phase.
