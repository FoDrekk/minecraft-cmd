<?php
$ITEMS = [
  'Weapons' => [
    ['wooden_sword','Wooden Sword'],['stone_sword','Stone Sword'],
    ['iron_sword','Iron Sword'],['golden_sword','Golden Sword'],
    ['diamond_sword','Diamond Sword'],['netherite_sword','Netherite Sword'],
    ['bow','Bow'],['crossbow','Crossbow'],['trident','Trident'],
    ['arrow','Arrow'],['spectral_arrow','Spectral Arrow'],
  ],
  'Tools' => [
    ['wooden_pickaxe','Wooden Pickaxe'],['stone_pickaxe','Stone Pickaxe'],
    ['iron_pickaxe','Iron Pickaxe'],['golden_pickaxe','Golden Pickaxe'],
    ['diamond_pickaxe','Diamond Pickaxe'],['netherite_pickaxe','Netherite Pickaxe'],
    ['wooden_axe','Wooden Axe'],['stone_axe','Stone Axe'],
    ['iron_axe','Iron Axe'],['golden_axe','Golden Axe'],
    ['diamond_axe','Diamond Axe'],['netherite_axe','Netherite Axe'],
    ['wooden_shovel','Wooden Shovel'],['stone_shovel','Stone Shovel'],
    ['iron_shovel','Iron Shovel'],['diamond_shovel','Diamond Shovel'],['netherite_shovel','Netherite Shovel'],
    ['wooden_hoe','Wooden Hoe'],['iron_hoe','Iron Hoe'],['diamond_hoe','Diamond Hoe'],['netherite_hoe','Netherite Hoe'],
    ['fishing_rod','Fishing Rod'],['flint_and_steel','Flint and Steel'],
    ['shears','Shears'],['compass','Compass'],['clock','Clock'],
    ['map','Map'],['spyglass','Spyglass'],['lead','Lead'],['name_tag','Name Tag'],['shield','Shield'],
  ],
  'Armour' => [
    ['leather_helmet','Leather Helmet'],['leather_chestplate','Leather Chestplate'],
    ['leather_leggings','Leather Leggings'],['leather_boots','Leather Boots'],
    ['chainmail_helmet','Chainmail Helmet'],['chainmail_chestplate','Chainmail Chestplate'],
    ['chainmail_leggings','Chainmail Leggings'],['chainmail_boots','Chainmail Boots'],
    ['iron_helmet','Iron Helmet'],['iron_chestplate','Iron Chestplate'],
    ['iron_leggings','Iron Leggings'],['iron_boots','Iron Boots'],
    ['golden_helmet','Golden Helmet'],['golden_chestplate','Golden Chestplate'],
    ['golden_leggings','Golden Leggings'],['golden_boots','Golden Boots'],
    ['diamond_helmet','Diamond Helmet'],['diamond_chestplate','Diamond Chestplate'],
    ['diamond_leggings','Diamond Leggings'],['diamond_boots','Diamond Boots'],
    ['netherite_helmet','Netherite Helmet'],['netherite_chestplate','Netherite Chestplate'],
    ['netherite_leggings','Netherite Leggings'],['netherite_boots','Netherite Boots'],
    ['elytra','Elytra'],['turtle_helmet','Turtle Helmet'],
  ],
  'Food' => [
    ['apple','Apple'],['golden_apple','Golden Apple'],['enchanted_golden_apple','Enchanted Golden Apple'],
    ['bread','Bread'],['carrot','Carrot'],['golden_carrot','Golden Carrot'],
    ['potato','Potato'],['baked_potato','Baked Potato'],['pumpkin_pie','Pumpkin Pie'],
    ['cookie','Cookie'],['cake','Cake'],['cooked_beef','Steak'],['cooked_porkchop','Cooked Porkchop'],
    ['cooked_chicken','Cooked Chicken'],['cooked_mutton','Cooked Mutton'],
    ['cooked_cod','Cooked Cod'],['cooked_salmon','Cooked Salmon'],
    ['mushroom_stew','Mushroom Stew'],['rabbit_stew','Rabbit Stew'],
    ['beetroot_soup','Beetroot Soup'],['honey_bottle','Honey Bottle'],['dried_kelp','Dried Kelp'],
  ],
  'Materials' => [
    ['diamond','Diamond'],['emerald','Emerald'],['netherite_ingot','Netherite Ingot'],
    ['gold_ingot','Gold Ingot'],['iron_ingot','Iron Ingot'],['copper_ingot','Copper Ingot'],
    ['redstone','Redstone'],['lapis_lazuli','Lapis Lazuli'],['quartz','Nether Quartz'],
    ['coal','Coal'],['amethyst_shard','Amethyst Shard'],['echo_shard','Echo Shard'],
    ['nether_star','Nether Star'],['dragon_egg','Dragon Egg'],['end_crystal','End Crystal'],
    ['beacon','Beacon'],['conduit','Conduit'],['totem_of_undying','Totem of Undying'],
  ],
];

// Flat list for JS
$ITEMS_FLAT = [];
foreach($ITEMS as $cat => $items) {
  foreach($items as $item) {
    $ITEMS_FLAT[] = $item;
  }
}

// Enchants per slot
$ENCHANTS = [
  'Sword'   => [['sharpness',5],['smite',5],['bane_of_arthropods',5],['knockback',2],['fire_aspect',2],['looting',3],['sweeping',3],['unbreaking',3],['mending',1]],
  'Pickaxe' => [['efficiency',5],['silk_touch',1],['fortune',3],['unbreaking',3],['mending',1]],
  'Axe'     => [['sharpness',5],['smite',5],['bane_of_arthropods',5],['efficiency',5],['silk_touch',1],['fortune',3],['unbreaking',3],['mending',1]],
  'Shovel'  => [['efficiency',5],['silk_touch',1],['fortune',3],['unbreaking',3],['mending',1]],
  'Bow'     => [['power',5],['punch',2],['flame',1],['infinity',1],['unbreaking',3],['mending',1]],
  'Crossbow'=> [['multishot',1],['piercing',4],['quick_charge',3],['unbreaking',3],['mending',1]],
  'Helmet'  => [['protection',4],['fire_protection',4],['blast_protection',4],['projectile_protection',4],['respiration',3],['aqua_affinity',1],['thorns',3],['unbreaking',3],['mending',1]],
  'Chestplate'=>[['protection',4],['fire_protection',4],['blast_protection',4],['projectile_protection',4],['thorns',3],['unbreaking',3],['mending',1]],
  'Leggings'=> [['protection',4],['fire_protection',4],['blast_protection',4],['projectile_protection',4],['thorns',3],['swift_sneak',3],['unbreaking',3],['mending',1]],
  'Boots'   => [['protection',4],['fire_protection',4],['feather_falling',4],['depth_strider',3],['frost_walker',2],['soul_speed',3],['thorns',3],['unbreaking',3],['mending',1]],
  'Trident' => [['channeling',1],['loyalty',3],['impaling',5],['riptide',3],['unbreaking',3],['mending',1]],
];
?>
