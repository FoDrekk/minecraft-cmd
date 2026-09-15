# Minecraft textures

Drop texture files here and the app picks them up automatically. No code
change, no manifest to regenerate, no build step.

## Naming

One file per Minecraft id, named exactly as the id:

```
assets/textures/diamond_sword.png
assets/textures/oak_planks.png
assets/textures/observer.png
assets/textures/redstone.png
```

`.png` and `.webp` are both read. If both exist for one id, the `.png`
wins. Names are normalised, so `minecraft:diamond_sword.png` and
`Diamond_Sword.png` resolve to the same id.

## Where they show up

Everything visual resolves through `lib/textures.php` and
`assets/mcvisual.js`, so a file dropped here appears everywhere that id
is rendered at once — item pickers, material chips, blueprint cells,
the Material Library, search results.

Anything with no file falls through to the colour/glyph fallback, which
is deliberately *not* dressed up to look like a real texture.

## Why the repository ships none

Mojang's textures are not redistributable, so they are not committed
here. Supply your own legitimately obtained copies — for example by
extracting them from your own Minecraft installation, or by using a
resource pack whose licence permits it.

Textures are 16×16 in vanilla Minecraft. They are rendered with
`image-rendering: pixelated`, so small source files stay crisp rather
than going blurry when scaled up.
