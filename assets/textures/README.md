# Minecraft textures — drop-in folder

This app never ships Minecraft's own textures — Mojang's art is not
ours to redistribute. Every item/block tile in the UI resolves through
`lib/textures.php` (server) / `MC.textureSrc()` (client, in
`assets/mccmd.js`), and both simply check whether a file exists here.
Nothing else in the codebase decides "does this id have a texture" —
that answer lives in exactly one place.

## Adding your own (legitimately obtained) textures

Drop a PNG or WEBP file into the matching folder, named by the item or
block's canonical Minecraft id:

```
assets/textures/item/diamond_sword.png
assets/textures/item/apple.png
assets/textures/block/oak_planks.png
assets/textures/block/grass_block.png
```

This mirrors Minecraft's own resource-pack layout
(`assets/minecraft/textures/item/…` and `…/textures/block/…`), so a
file pulled straight out of a resource pack or the game's own assets
needs no renaming — just copy it in.

That's it — no manifest to edit, no code to touch. The next page load
picks it up automatically. Remove the file and the app falls back to
its existing honest placeholder (a flat colour, a generic category
icon, or an initial) for that id — it never invents a replacement
image.

## Why `item/` and `block/` are separate

Minecraft itself keeps these separate: a block's inventory icon
(`item/`) is not always identical to its placed appearance
(`block/`). Most UI surfaces here (item cards, the Enchantment Hub,
Kit/Item Builder) ask for the `item` texture — the one a player
actually sees in their inventory — while a couple of block-focused
surfaces (Farms' material chips) do the same, since a small checklist
swatch reads more like an inventory slot than a placed block face.

## Where these ids come from

Every id is the same canonical id already used throughout the app's
registries (`lib/data/items.php`, `lib/data/blocks.php`) — the same
string you'd see in a `/give` command, minus the `minecraft:` prefix.
