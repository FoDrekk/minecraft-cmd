/* ================================================
   textures.js — CSS pixel-art Minecraft textures
   ------------------------------------------------
   Recognizable item/block visuals using pure CSS.
   No external images required. Each texture is a
   pixel grid rendered via CSS box-shadow.
   ================================================ */

(function() {
    window.MC = window.MC || {};

    // Helper to build [x, y, color] from string grids
    function build(lines, map) {
        const tuples = [];
        for (let y = 0; y < lines.length; y++) {
            const line = lines[y];
            for (let x = 0; x < line.length; x++) {
                const char = line[x];
                if (char !== ' ' && map[char]) {
                    tuples.push([x + 1, y + 1, map[char]]);
                }
            }
        }
        return tuples;
    }

    // Common Colors
    const C = {
        dia: '#62DBD8',
        diaD: '#2A9D8F',
        iron: '#D8D8D8',
        ironD: '#A0A0A0',
        neth: '#4A3F4D',
        nethD: '#2A242B',
        gold: '#FCE566',
        goldD: '#D4AF37',
        wood: '#866526',
        woodD: '#5C4017',
        woodL: '#BC9862',
        stone: '#808080',
        stoneD: '#555555',
        dirt: '#8B6B4B',
        dirtD: '#6B5335',
        grass: '#7CBD6B',
        obs: '#1B0B2E',
        red: '#FF0000',
        redD: '#B80000',
        water: '#3F76E4',
        lava: '#D4641F'
    };

    const TEXTURES = {};

    // SWORDS (16x16 grid space, drawing roughly diagonal)
    const swordGrid = [
        "              DD",
        "             DLD",
        "            DLD ",
        "           DLD  ",
        "          DLD   ",
        "         DLD    ",
        "        DLD     ",
        "       DLD      ",
        "      DLD       ",
        "     DLD        ",
        "  WW DLD WW     ",
        "   WWWDWWW      ",
        "    WWW         ",
        "   WW WW        ",
        "  W    WW       ",
        "                "
    ];
    
    TEXTURES['diamond_sword'] = build(swordGrid, { 'D': C.diaD, 'L': C.dia, 'W': C.wood });
    TEXTURES['iron_sword'] = build(swordGrid, { 'D': C.ironD, 'L': C.iron, 'W': C.wood });
    TEXTURES['netherite_sword'] = build(swordGrid, { 'D': C.nethD, 'L': C.neth, 'W': C.wood });

    // PICKAXES
    const pickGrid = [
        "  DDDDDDDDDDDD  ",
        "  DDLLLLLLLLDD  ",
        "  D  W DD W  D  ",
        "     WW  WW     ",
        "     WW  WW     ",
        "      WWWW      ",
        "       WW       ",
        "       WW       ",
        "       WW       ",
        "       WW       ",
        "       WW       ",
        "       WW       ",
        "       WW       ",
        "       WW       ",
        "                ",
        "                "
    ];
    TEXTURES['diamond_pickaxe'] = build(pickGrid, { 'D': C.diaD, 'L': C.dia, 'W': C.wood });
    TEXTURES['iron_pickaxe'] = build(pickGrid, { 'D': C.ironD, 'L': C.iron, 'W': C.wood });
    TEXTURES['netherite_pickaxe'] = build(pickGrid, { 'D': C.nethD, 'L': C.neth, 'W': C.wood });

    // AXES
    const axeGrid = [
        "  DDDD          ",
        " DDDLLD         ",
        " DDLLLD         ",
        "  DLLWW         ",
        "   D WW         ",
        "     WW         ",
        "     WW         ",
        "     WW         ",
        "     WW         ",
        "     WW         ",
        "     WW         ",
        "     WW         ",
        "     WW         ",
        "                ",
        "                ",
        "                "
    ];
    TEXTURES['diamond_axe'] = build(axeGrid, { 'D': C.diaD, 'L': C.dia, 'W': C.wood });
    TEXTURES['iron_axe'] = build(axeGrid, { 'D': C.ironD, 'L': C.iron, 'W': C.wood });

    // BLOCKS (16x16 full coverage mostly)
    function fullBlock(c1, c2, c3) {
        const grid = [];
        for(let i=0; i<16; i++) {
            let row = "";
            for(let j=0; j<16; j++) {
                if(i===0 || j===0 || i===15 || j===15) row += '1'; // Border
                else if((i+j)%3===0) row += '2'; // Noise
                else row += '3'; // Fill
            }
            grid.push(row);
        }
        return build(grid, { '1': c1, '2': c2, '3': c3 });
    }

    TEXTURES['dirt'] = fullBlock(C.dirtD, C.dirtD, C.dirt);
    TEXTURES['stone'] = fullBlock(C.stoneD, C.stoneD, C.stone);
    TEXTURES['cobblestone'] = fullBlock(C.stoneD, C.stone, C.stoneD);
    TEXTURES['oak_planks'] = fullBlock(C.woodD, C.woodL, C.wood);
    TEXTURES['obsidian'] = fullBlock('#000000', '#2E114D', C.obs);
    
    // ORES
    function oreBlock(baseD, base, spot) {
        const grid = [];
        for(let i=0; i<16; i++) {
            let row = "";
            for(let j=0; j<16; j++) {
                if(i===0 || j===0 || i===15 || j===15) row += '1';
                else if((i*3 + j*7)%11 < 2) row += '4'; // Ore spots
                else if((i+j)%3===0) row += '2'; 
                else row += '3'; 
            }
            grid.push(row);
        }
        return build(grid, { '1': baseD, '2': baseD, '3': base, '4': spot });
    }

    TEXTURES['diamond_ore'] = oreBlock(C.stoneD, C.stone, C.dia);
    TEXTURES['iron_ore'] = oreBlock(C.stoneD, C.stone, '#D8B49E');
    TEXTURES['gold_ore'] = oreBlock(C.stoneD, C.stone, C.gold);
    TEXTURES['redstone_ore'] = oreBlock(C.stoneD, C.stone, C.red);

    // GRASS BLOCK
    const grassGrid = [];
    for(let i=0; i<16; i++) {
        let row = "";
        for(let j=0; j<16; j++) {
            if(i < 4) row += '1'; // Top grass
            else if(i === 4 && j%2===0) row += '1'; // Grass fringe
            else if(i > 4 && (i+j)%3===0) row += '3'; // Dirt noise
            else row += '2'; // Dirt
        }
        grassGrid.push(row);
    }
    TEXTURES['grass_block'] = build(grassGrid, { '1': C.grass, '2': C.dirt, '3': C.dirtD });

    // MISC
    const torchGrid = [
        "                ",
        "                ",
        "       YY       ",
        "      YOOY      ",
        "      YOOY      ",
        "       WW       ",
        "       WW       ",
        "       WW       ",
        "       WW       ",
        "       WW       ",
        "       WW       ",
        "       WW       ",
        "       WW       ",
        "       WW       ",
        "                ",
        "                "
    ];
    TEXTURES['torch'] = build(torchGrid, { 'Y': C.gold, 'O': C.lava, 'W': C.wood });

    const appleGrid = [
        "                ",
        "       WW       ",
        "      WY W      ",
        "    YYYYYYYY    ",
        "   YYYYYYYYYY   ",
        "  YYYYYYYYYYYY  ",
        "  YYYYYYYYYYYY  ",
        "  YYYYYYYYYYYY  ",
        "  YYYYYYYYYYYY  ",
        "  YYYYYYYYYYYY  ",
        "   YYYYYYYYYY   ",
        "    YYYYYYYY    ",
        "     YYYYYY     ",
        "                ",
        "                ",
        "                "
    ];
    TEXTURES['golden_apple'] = build(appleGrid, { 'Y': C.gold, 'W': C.woodD });
    
    // Add alias/fallback mapping
    const aliases = {
        'grass': 'grass_block',
        'diamond_helmet': 'diamond_sword',
        'diamond_chestplate': 'diamond_sword',
        'iron_helmet': 'iron_sword',
        'netherite_helmet': 'netherite_sword',
        'bow': 'torch',
        'crossbow': 'torch',
        'trident': 'diamond_sword',
        'shield': 'oak_planks',
        'fishing_rod': 'torch',
        'glass': 'diamond_ore',
        'sand': 'oak_planks',
        'gravel': 'cobblestone',
        'water': 'obsidian',
        'lava': 'obsidian',
        'sugar_cane': 'grass_block',
        'observer': 'stone',
        'piston': 'stone',
        'hopper': 'iron_ore',
        'chest': 'oak_planks',
        'redstone_block': 'redstone_ore',
        'crafting_table': 'oak_planks',
        'furnace': 'cobblestone',
        'enchanting_table': 'obsidian',
        'anvil': 'iron_ore',
        'brewing_stand': 'cobblestone'
    };

    function has(itemId) {
        return !!(TEXTURES[itemId] || (aliases[itemId] && TEXTURES[aliases[itemId]]));
    }

    function render(itemId, sizeVariant) {
        let tex = TEXTURES[itemId];
        if (!tex && aliases[itemId]) {
            tex = TEXTURES[aliases[itemId]];
        }
        
        // Size mapping
        let pxSize = 2; // default
        switch(sizeVariant) {
            case 'sm': pxSize = 1.5; break; // 24px
            case 'md': pxSize = 2; break;   // 32px
            case 'lg': pxSize = 3; break;   // 48px
            case 'xl': pxSize = 4; break;   // 64px
            default: 
                if (typeof sizeVariant === 'number') {
                    pxSize = sizeVariant / 16;
                }
        }

        if (!tex) {
            // Fallback square
            return `<div class="mc-tex-missing" style="width: ${16*pxSize}px; height: ${16*pxSize}px; background: #FF00FF; outline: 1px solid #000; box-sizing: border-box; display: inline-block;"></div>`;
        }

        // Generate box shadow string
        let shadow = tex.map(p => `${p[0]}px ${p[1]}px ${p[2]}`).join(', ');

        return `<div class="mc-tex" style="
            width: 1px; 
            height: 1px; 
            box-shadow: ${shadow};
            transform: scale(${pxSize});
            transform-origin: top left;
            margin-right: ${16*pxSize - 1}px;
            margin-bottom: ${16*pxSize - 1}px;
            display: inline-block;
        "></div>`;
    }

    MC.tex = {
        render: render,
        has: has,
        list: function() { 
            return Object.keys(TEXTURES).concat(Object.keys(aliases));
        }
    };

})();
