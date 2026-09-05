<?php
// ================================================
// lib/data/game.php — game reference data
// ------------------------------------------------
// Lists the command builders choose from. Entries carry a `min`
// rank where they were added, so the UI never offers an argument
// the selected version does not understand.
// ================================================
require_once __DIR__ . '/../mc.php';

/** Keep entries whose `min` rank the selected version satisfies. */
function gameFilter(array $rows, string $version): array
{
    $rank = mcVersion($version)['rank'];
    $out  = [];
    foreach ($rows as $id => $row) {
        if (is_array($row)) {
            if (($row['min'] ?? 0) > $rank) continue;
            $out[$id] = $row;
        } else {
            $out[$id] = ['label' => $row];
        }
    }
    return $out;
}

// ── STATUS EFFECTS ───────────────────────────────
function gameEffects(): array
{
    return [
        'speed'             => ['label' => 'Speed',              'group' => 'Helpful'],
        'haste'             => ['label' => 'Haste',              'group' => 'Helpful'],
        'strength'          => ['label' => 'Strength',           'group' => 'Helpful'],
        'jump_boost'        => ['label' => 'Jump Boost',         'group' => 'Helpful'],
        'regeneration'      => ['label' => 'Regeneration',       'group' => 'Helpful'],
        'resistance'        => ['label' => 'Resistance',         'group' => 'Helpful'],
        'fire_resistance'   => ['label' => 'Fire Resistance',    'group' => 'Helpful'],
        'water_breathing'   => ['label' => 'Water Breathing',    'group' => 'Helpful'],
        'invisibility'      => ['label' => 'Invisibility',       'group' => 'Helpful'],
        'night_vision'      => ['label' => 'Night Vision',       'group' => 'Helpful'],
        'health_boost'      => ['label' => 'Health Boost',       'group' => 'Helpful'],
        'absorption'        => ['label' => 'Absorption',         'group' => 'Helpful'],
        'saturation'        => ['label' => 'Saturation',         'group' => 'Helpful'],
        'slow_falling'      => ['label' => 'Slow Falling',       'group' => 'Helpful'],
        'conduit_power'     => ['label' => 'Conduit Power',      'group' => 'Helpful'],
        'dolphins_grace'    => ['label' => "Dolphin's Grace",    'group' => 'Helpful'],
        'hero_of_the_village' => ['label' => 'Hero of the Village', 'group' => 'Helpful'],
        'luck'              => ['label' => 'Luck',               'group' => 'Helpful'],
        'instant_health'    => ['label' => 'Instant Health',     'group' => 'Instant'],
        'instant_damage'    => ['label' => 'Instant Damage',     'group' => 'Instant'],
        'slowness'          => ['label' => 'Slowness',           'group' => 'Harmful'],
        'mining_fatigue'    => ['label' => 'Mining Fatigue',     'group' => 'Harmful'],
        'nausea'            => ['label' => 'Nausea',             'group' => 'Harmful'],
        'blindness'         => ['label' => 'Blindness',          'group' => 'Harmful'],
        'hunger'            => ['label' => 'Hunger',             'group' => 'Harmful'],
        'weakness'          => ['label' => 'Weakness',           'group' => 'Harmful'],
        'poison'            => ['label' => 'Poison',             'group' => 'Harmful'],
        'wither'            => ['label' => 'Wither',             'group' => 'Harmful'],
        'glowing'           => ['label' => 'Glowing',            'group' => 'Harmful'],
        'levitation'        => ['label' => 'Levitation',         'group' => 'Harmful'],
        'unluck'            => ['label' => 'Bad Luck',           'group' => 'Harmful'],
        'bad_omen'          => ['label' => 'Bad Omen',           'group' => 'Harmful'],
        'darkness'          => ['label' => 'Darkness',           'group' => 'Harmful'],
        'trial_omen'        => ['label' => 'Trial Omen',         'group' => 'Harmful', 'min' => 55],
        'raid_omen'         => ['label' => 'Raid Omen',          'group' => 'Harmful', 'min' => 55],
        'wind_charged'      => ['label' => 'Wind Charged',       'group' => 'Harmful', 'min' => 55],
        'weaving'           => ['label' => 'Weaving',            'group' => 'Harmful', 'min' => 55],
        'oozing'            => ['label' => 'Oozing',             'group' => 'Harmful', 'min' => 55],
        'infested'          => ['label' => 'Infested',           'group' => 'Harmful', 'min' => 55],
    ];
}

// ── GAMERULES ────────────────────────────────────
// `type` drives the input control: bool or int.
function gameRules(): array
{
    return [
        'keepInventory'                  => ['label' => 'Keep inventory on death', 'type' => 'bool', 'default' => 'false', 'group' => 'Player',
                                             'desc' => 'Players keep everything they were carrying when they die.'],
        'doDaylightCycle'                => ['label' => 'Day/night cycle',         'type' => 'bool', 'default' => 'true',  'group' => 'World',
                                             'desc' => 'Turn off to freeze time where it is. Set the time first.'],
        'doWeatherCycle'                 => ['label' => 'Weather cycle',           'type' => 'bool', 'default' => 'true',  'group' => 'World',
                                             'desc' => 'Turn off to lock the current weather.'],
        'doMobSpawning'                  => ['label' => 'Natural mob spawning',    'type' => 'bool', 'default' => 'true',  'group' => 'Mobs',
                                             'desc' => 'Turning this off does not remove mobs that already exist.'],
        'mobGriefing'                    => ['label' => 'Mobs can change blocks',  'type' => 'bool', 'default' => 'true',  'group' => 'Mobs',
                                             'desc' => 'Creeper craters, enderman block stealing, villager farming — all off when false.'],
        'doFireTick'                     => ['label' => 'Fire spreads',            'type' => 'bool', 'default' => 'true',  'group' => 'World'],
        'doTileDrops'                    => ['label' => 'Blocks drop items',       'type' => 'bool', 'default' => 'true',  'group' => 'World'],
        'doMobLoot'                      => ['label' => 'Mobs drop loot',          'type' => 'bool', 'default' => 'true',  'group' => 'Mobs'],
        'doEntityDrops'                  => ['label' => 'Entities drop items',     'type' => 'bool', 'default' => 'true',  'group' => 'Mobs'],
        'fallDamage'                     => ['label' => 'Fall damage',             'type' => 'bool', 'default' => 'true',  'group' => 'Player'],
        'fireDamage'                     => ['label' => 'Fire damage',             'type' => 'bool', 'default' => 'true',  'group' => 'Player'],
        'drowningDamage'                 => ['label' => 'Drowning damage',         'type' => 'bool', 'default' => 'true',  'group' => 'Player'],
        'freezeDamage'                   => ['label' => 'Freeze damage',           'type' => 'bool', 'default' => 'true',  'group' => 'Player'],
        'naturalRegeneration'            => ['label' => 'Health regenerates',      'type' => 'bool', 'default' => 'true',  'group' => 'Player'],
        'doImmediateRespawn'             => ['label' => 'Skip the death screen',   'type' => 'bool', 'default' => 'false', 'group' => 'Player'],
        'showDeathMessages'              => ['label' => 'Show death messages',     'type' => 'bool', 'default' => 'true',  'group' => 'Player'],
        'announceAdvancements'           => ['label' => 'Announce advancements',   'type' => 'bool', 'default' => 'true',  'group' => 'Player'],
        'sendCommandFeedback'            => ['label' => 'Command feedback in chat','type' => 'bool', 'default' => 'true',  'group' => 'Commands',
                                             'desc' => 'Turn off to stop command blocks spamming chat.'],
        'commandBlockOutput'             => ['label' => 'Command block output',    'type' => 'bool', 'default' => 'true',  'group' => 'Commands'],
        'logAdminCommands'               => ['label' => 'Log admin commands',      'type' => 'bool', 'default' => 'true',  'group' => 'Commands'],
        'doLimitedCrafting'              => ['label' => 'Recipes must be unlocked','type' => 'bool', 'default' => 'false', 'group' => 'Player'],
        'doInsomnia'                     => ['label' => 'Phantoms spawn',          'type' => 'bool', 'default' => 'true',  'group' => 'Mobs'],
        'doPatrolSpawning'               => ['label' => 'Pillager patrols spawn',  'type' => 'bool', 'default' => 'true',  'group' => 'Mobs'],
        'doTraderSpawning'               => ['label' => 'Wandering traders spawn', 'type' => 'bool', 'default' => 'true',  'group' => 'Mobs'],
        'doWardenSpawning'               => ['label' => 'Wardens spawn',           'type' => 'bool', 'default' => 'true',  'group' => 'Mobs'],
        'disableRaids'                   => ['label' => 'Disable raids',           'type' => 'bool', 'default' => 'false', 'group' => 'Mobs'],
        'universalAnger'                 => ['label' => 'Universal anger',         'type' => 'bool', 'default' => 'false', 'group' => 'Mobs'],
        'forgiveDeadPlayers'             => ['label' => 'Forgive dead players',    'type' => 'bool', 'default' => 'true',  'group' => 'Mobs'],
        'doVinesSpread'                  => ['label' => 'Vines spread',            'type' => 'bool', 'default' => 'true',  'group' => 'World'],
        'lavaSourceConversion'           => ['label' => 'Lava makes new sources',  'type' => 'bool', 'default' => 'false', 'group' => 'World'],
        'waterSourceConversion'          => ['label' => 'Water makes new sources', 'type' => 'bool', 'default' => 'true',  'group' => 'World'],
        'blockExplosionDropDecay'        => ['label' => 'Block explosion drop decay','type' => 'bool','default' => 'true',  'group' => 'World'],
        'mobExplosionDropDecay'          => ['label' => 'Mob explosion drop decay','type' => 'bool', 'default' => 'true',  'group' => 'World'],
        'tntExplosionDropDecay'          => ['label' => 'TNT explosion drop decay','type' => 'bool', 'default' => 'false', 'group' => 'World'],
        'projectilesCanBreakBlocks'      => ['label' => 'Projectiles break blocks','type' => 'bool', 'default' => 'true',  'group' => 'World'],
        'globalSoundEvents'              => ['label' => 'Global sound events',     'type' => 'bool', 'default' => 'true',  'group' => 'World'],
        'spectatorsGenerateChunks'       => ['label' => 'Spectators load chunks',  'type' => 'bool', 'default' => 'true',  'group' => 'World'],
        'reducedDebugInfo'               => ['label' => 'Reduced debug info (F3)', 'type' => 'bool', 'default' => 'false', 'group' => 'Player'],
        'disableElytraMovementCheck'     => ['label' => 'Disable elytra check',    'type' => 'bool', 'default' => 'false', 'group' => 'Player'],
        'randomTickSpeed'                => ['label' => 'Random tick speed',       'type' => 'int',  'default' => '3',     'group' => 'World',
                                             'desc' => 'How fast crops grow and fire spreads. 3 is normal; very high values will lag the game.'],
        'maxEntityCramming'              => ['label' => 'Max entity cramming',     'type' => 'int',  'default' => '24',    'group' => 'Mobs',
                                             'desc' => 'Set to 0 to disable cramming damage entirely.'],
        'playersSleepingPercentage'      => ['label' => 'Sleep percentage needed', 'type' => 'int',  'default' => '100',   'group' => 'Player',
                                             'desc' => 'Percentage of players who must sleep to skip the night. 0 means one player is enough.'],
        'spawnRadius'                    => ['label' => 'Spawn radius',            'type' => 'int',  'default' => '10',    'group' => 'World'],
        'maxCommandChainLength'          => ['label' => 'Max command chain length','type' => 'int',  'default' => '65536', 'group' => 'Commands'],
        'commandModificationBlockLimit'  => ['label' => 'Fill/clone block limit',  'type' => 'int',  'default' => '32768', 'group' => 'Commands',
                                             'desc' => 'The most blocks one /fill or /clone may change.'],
        'maxCommandForkCount'            => ['label' => 'Max command fork count',  'type' => 'int',  'default' => '65536', 'group' => 'Commands', 'min' => 40],
        'playersNetherPortalDefaultDelay'=> ['label' => 'Nether portal delay',     'type' => 'int',  'default' => '80',    'group' => 'Player',   'min' => 40],
        'playersNetherPortalCreativeDelay'=>['label' => 'Nether portal delay (creative)','type'=>'int','default' => '1',   'group' => 'Player',   'min' => 40],
        'enderPearlsVanishOnDeath'       => ['label' => 'Ender pearls vanish on death','type'=>'bool','default' => 'true', 'group' => 'Player',   'min' => 50],
        'spawnChunkRadius'               => ['label' => 'Spawn chunk radius',      'type' => 'int',  'default' => '2',     'group' => 'World',    'min' => 50],
        'disablePlayerMovementCheck'     => ['label' => 'Disable movement check',  'type' => 'bool', 'default' => 'false', 'group' => 'Player',   'min' => 50],
        'allowFireTicksAwayFromPlayer'   => ['label' => 'Fire ticks away from players','type'=>'bool','default'=> 'false', 'group' => 'World',    'min' => 60],
    ];
}

// ── ENTITIES ─────────────────────────────────────
function gameEntities(): array
{
    return [
        // Passive
        'allay' => ['label' => 'Allay', 'group' => 'Passive'],
        'axolotl' => ['label' => 'Axolotl', 'group' => 'Passive'],
        'bat' => ['label' => 'Bat', 'group' => 'Passive'],
        'bee' => ['label' => 'Bee', 'group' => 'Passive'],
        'camel' => ['label' => 'Camel', 'group' => 'Passive'],
        'cat' => ['label' => 'Cat', 'group' => 'Passive'],
        'chicken' => ['label' => 'Chicken', 'group' => 'Passive'],
        'cow' => ['label' => 'Cow', 'group' => 'Passive'],
        'donkey' => ['label' => 'Donkey', 'group' => 'Passive'],
        'fox' => ['label' => 'Fox', 'group' => 'Passive'],
        'frog' => ['label' => 'Frog', 'group' => 'Passive'],
        'glow_squid' => ['label' => 'Glow Squid', 'group' => 'Passive'],
        'horse' => ['label' => 'Horse', 'group' => 'Passive'],
        'llama' => ['label' => 'Llama', 'group' => 'Passive'],
        'mooshroom' => ['label' => 'Mooshroom', 'group' => 'Passive'],
        'ocelot' => ['label' => 'Ocelot', 'group' => 'Passive'],
        'parrot' => ['label' => 'Parrot', 'group' => 'Passive'],
        'pig' => ['label' => 'Pig', 'group' => 'Passive'],
        'rabbit' => ['label' => 'Rabbit', 'group' => 'Passive'],
        'sheep' => ['label' => 'Sheep', 'group' => 'Passive'],
        'sniffer' => ['label' => 'Sniffer', 'group' => 'Passive'],
        'squid' => ['label' => 'Squid', 'group' => 'Passive'],
        'strider' => ['label' => 'Strider', 'group' => 'Passive'],
        'turtle' => ['label' => 'Turtle', 'group' => 'Passive'],
        'villager' => ['label' => 'Villager', 'group' => 'Passive'],
        'wandering_trader' => ['label' => 'Wandering Trader', 'group' => 'Passive'],
        'wolf' => ['label' => 'Wolf', 'group' => 'Passive'],
        // Hostile
        'blaze' => ['label' => 'Blaze', 'group' => 'Hostile'],
        'bogged' => ['label' => 'Bogged', 'group' => 'Hostile', 'min' => 55],
        'breeze' => ['label' => 'Breeze', 'group' => 'Hostile', 'min' => 55],
        'cave_spider' => ['label' => 'Cave Spider', 'group' => 'Hostile'],
        'creeper' => ['label' => 'Creeper', 'group' => 'Hostile'],
        'drowned' => ['label' => 'Drowned', 'group' => 'Hostile'],
        'elder_guardian' => ['label' => 'Elder Guardian', 'group' => 'Hostile'],
        'enderman' => ['label' => 'Enderman', 'group' => 'Hostile'],
        'endermite' => ['label' => 'Endermite', 'group' => 'Hostile'],
        'evoker' => ['label' => 'Evoker', 'group' => 'Hostile'],
        'ghast' => ['label' => 'Ghast', 'group' => 'Hostile'],
        'guardian' => ['label' => 'Guardian', 'group' => 'Hostile'],
        'hoglin' => ['label' => 'Hoglin', 'group' => 'Hostile'],
        'husk' => ['label' => 'Husk', 'group' => 'Hostile'],
        'magma_cube' => ['label' => 'Magma Cube', 'group' => 'Hostile'],
        'phantom' => ['label' => 'Phantom', 'group' => 'Hostile'],
        'piglin' => ['label' => 'Piglin', 'group' => 'Hostile'],
        'piglin_brute' => ['label' => 'Piglin Brute', 'group' => 'Hostile'],
        'pillager' => ['label' => 'Pillager', 'group' => 'Hostile'],
        'ravager' => ['label' => 'Ravager', 'group' => 'Hostile'],
        'shulker' => ['label' => 'Shulker', 'group' => 'Hostile'],
        'silverfish' => ['label' => 'Silverfish', 'group' => 'Hostile'],
        'skeleton' => ['label' => 'Skeleton', 'group' => 'Hostile'],
        'slime' => ['label' => 'Slime', 'group' => 'Hostile'],
        'spider' => ['label' => 'Spider', 'group' => 'Hostile'],
        'stray' => ['label' => 'Stray', 'group' => 'Hostile'],
        'vex' => ['label' => 'Vex', 'group' => 'Hostile'],
        'vindicator' => ['label' => 'Vindicator', 'group' => 'Hostile'],
        'warden' => ['label' => 'Warden', 'group' => 'Hostile'],
        'witch' => ['label' => 'Witch', 'group' => 'Hostile'],
        'wither_skeleton' => ['label' => 'Wither Skeleton', 'group' => 'Hostile'],
        'zoglin' => ['label' => 'Zoglin', 'group' => 'Hostile'],
        'zombie' => ['label' => 'Zombie', 'group' => 'Hostile'],
        'zombie_villager' => ['label' => 'Zombie Villager', 'group' => 'Hostile'],
        'zombified_piglin' => ['label' => 'Zombified Piglin', 'group' => 'Hostile'],
        // Bosses & special
        'ender_dragon' => ['label' => 'Ender Dragon', 'group' => 'Boss'],
        'wither' => ['label' => 'Wither', 'group' => 'Boss'],
        // Utility
        'armor_stand' => ['label' => 'Armour Stand', 'group' => 'Utility'],
        'item_frame' => ['label' => 'Item Frame', 'group' => 'Utility'],
        'glow_item_frame' => ['label' => 'Glow Item Frame', 'group' => 'Utility'],
        'minecart' => ['label' => 'Minecart', 'group' => 'Utility'],
        'chest_minecart' => ['label' => 'Chest Minecart', 'group' => 'Utility'],
        'hopper_minecart' => ['label' => 'Hopper Minecart', 'group' => 'Utility'],
        'tnt' => ['label' => 'Primed TNT', 'group' => 'Utility'],
        'boat' => ['label' => 'Boat', 'group' => 'Utility'],
        'iron_golem' => ['label' => 'Iron Golem', 'group' => 'Utility'],
        'snow_golem' => ['label' => 'Snow Golem', 'group' => 'Utility'],
        'lightning_bolt' => ['label' => 'Lightning Bolt', 'group' => 'Utility'],
        'experience_orb' => ['label' => 'Experience Orb', 'group' => 'Utility'],
        'text_display' => ['label' => 'Text Display', 'group' => 'Utility', 'min' => 30],
        'block_display' => ['label' => 'Block Display', 'group' => 'Utility', 'min' => 30],
        'item_display' => ['label' => 'Item Display', 'group' => 'Utility', 'min' => 30],
        'interaction' => ['label' => 'Interaction', 'group' => 'Utility', 'min' => 30],
        'marker' => ['label' => 'Marker', 'group' => 'Utility'],
    ];
}

// ── /locate TARGETS ──────────────────────────────
function gameStructures(): array
{
    return [
        '#minecraft:village'      => ['label' => 'Village (any)',      'group' => 'Overworld'],
        'village_plains'          => ['label' => 'Village — Plains',   'group' => 'Overworld'],
        'village_desert'          => ['label' => 'Village — Desert',   'group' => 'Overworld'],
        'village_savanna'         => ['label' => 'Village — Savanna',  'group' => 'Overworld'],
        'village_snowy'           => ['label' => 'Village — Snowy',    'group' => 'Overworld'],
        'village_taiga'           => ['label' => 'Village — Taiga',    'group' => 'Overworld'],
        'stronghold'              => ['label' => 'Stronghold',         'group' => 'Overworld'],
        'mansion'                 => ['label' => 'Woodland Mansion',   'group' => 'Overworld'],
        'monument'                => ['label' => 'Ocean Monument',     'group' => 'Overworld'],
        'ancient_city'            => ['label' => 'Ancient City',       'group' => 'Overworld'],
        'trial_chambers'          => ['label' => 'Trial Chambers',     'group' => 'Overworld', 'min' => 55],
        'trail_ruins'             => ['label' => 'Trail Ruins',        'group' => 'Overworld'],
        'pillager_outpost'        => ['label' => 'Pillager Outpost',   'group' => 'Overworld'],
        'desert_pyramid'          => ['label' => 'Desert Pyramid',     'group' => 'Overworld'],
        'jungle_pyramid'          => ['label' => 'Jungle Temple',      'group' => 'Overworld'],
        'swamp_hut'               => ['label' => 'Witch Hut',          'group' => 'Overworld'],
        'igloo'                   => ['label' => 'Igloo',              'group' => 'Overworld'],
        'mineshaft'               => ['label' => 'Mineshaft',          'group' => 'Overworld'],
        'shipwreck'               => ['label' => 'Shipwreck',          'group' => 'Overworld'],
        'buried_treasure'         => ['label' => 'Buried Treasure',    'group' => 'Overworld'],
        '#minecraft:ruined_portal'=> ['label' => 'Ruined Portal',      'group' => 'Overworld'],
        '#minecraft:ocean_ruin'   => ['label' => 'Ocean Ruins',        'group' => 'Overworld'],
        'fortress'                => ['label' => 'Nether Fortress',    'group' => 'Nether'],
        'bastion_remnant'         => ['label' => 'Bastion Remnant',    'group' => 'Nether'],
        'nether_fossil'           => ['label' => 'Nether Fossil',      'group' => 'Nether'],
        'ruined_portal_nether'    => ['label' => 'Ruined Portal (Nether)', 'group' => 'Nether'],
        'end_city'                => ['label' => 'End City',           'group' => 'End'],
    ];
}

function gameBiomes(): array
{
    return [
        'plains' => ['label' => 'Plains', 'group' => 'Overworld'],
        'forest' => ['label' => 'Forest', 'group' => 'Overworld'],
        'birch_forest' => ['label' => 'Birch Forest', 'group' => 'Overworld'],
        'dark_forest' => ['label' => 'Dark Forest', 'group' => 'Overworld'],
        'taiga' => ['label' => 'Taiga', 'group' => 'Overworld'],
        'old_growth_pine_taiga' => ['label' => 'Old Growth Pine Taiga', 'group' => 'Overworld'],
        'savanna' => ['label' => 'Savanna', 'group' => 'Overworld'],
        'desert' => ['label' => 'Desert', 'group' => 'Overworld'],
        'jungle' => ['label' => 'Jungle', 'group' => 'Overworld'],
        'bamboo_jungle' => ['label' => 'Bamboo Jungle', 'group' => 'Overworld'],
        'swamp' => ['label' => 'Swamp', 'group' => 'Overworld'],
        'mangrove_swamp' => ['label' => 'Mangrove Swamp', 'group' => 'Overworld'],
        'badlands' => ['label' => 'Badlands', 'group' => 'Overworld'],
        'cherry_grove' => ['label' => 'Cherry Grove', 'group' => 'Overworld'],
        'snowy_plains' => ['label' => 'Snowy Plains', 'group' => 'Overworld'],
        'ice_spikes' => ['label' => 'Ice Spikes', 'group' => 'Overworld'],
        'jagged_peaks' => ['label' => 'Jagged Peaks', 'group' => 'Overworld'],
        'meadow' => ['label' => 'Meadow', 'group' => 'Overworld'],
        'mushroom_fields' => ['label' => 'Mushroom Fields', 'group' => 'Overworld'],
        'deep_dark' => ['label' => 'Deep Dark', 'group' => 'Cave'],
        'lush_caves' => ['label' => 'Lush Caves', 'group' => 'Cave'],
        'dripstone_caves' => ['label' => 'Dripstone Caves', 'group' => 'Cave'],
        'warm_ocean' => ['label' => 'Warm Ocean', 'group' => 'Ocean'],
        'deep_ocean' => ['label' => 'Deep Ocean', 'group' => 'Ocean'],
        'frozen_ocean' => ['label' => 'Frozen Ocean', 'group' => 'Ocean'],
        'nether_wastes' => ['label' => 'Nether Wastes', 'group' => 'Nether'],
        'soul_sand_valley' => ['label' => 'Soul Sand Valley', 'group' => 'Nether'],
        'crimson_forest' => ['label' => 'Crimson Forest', 'group' => 'Nether'],
        'warped_forest' => ['label' => 'Warped Forest', 'group' => 'Nether'],
        'basalt_deltas' => ['label' => 'Basalt Deltas', 'group' => 'Nether'],
        'the_end' => ['label' => 'The End', 'group' => 'End'],
        'end_highlands' => ['label' => 'End Highlands', 'group' => 'End'],
    ];
}

// ── PARTICLES ────────────────────────────────────
function gameParticles(): array
{
    return [
        'flame' => ['label' => 'Flame', 'hex' => '#ff9d3a'],
        'soul_fire_flame' => ['label' => 'Soul Fire Flame', 'hex' => '#3ad2ff'],
        'smoke' => ['label' => 'Smoke', 'hex' => '#6b6b6b'],
        'large_smoke' => ['label' => 'Large Smoke', 'hex' => '#555555'],
        'campfire_cosy_smoke' => ['label' => 'Campfire Smoke', 'hex' => '#9a9a9a'],
        'cloud' => ['label' => 'Cloud', 'hex' => '#dcdcdc'],
        'heart' => ['label' => 'Heart', 'hex' => '#ff5c7a'],
        'happy_villager' => ['label' => 'Happy Villager', 'hex' => '#57d857'],
        'angry_villager' => ['label' => 'Angry Villager', 'hex' => '#9c9c9c'],
        'crit' => ['label' => 'Critical Hit', 'hex' => '#c9a227'],
        'enchanted_hit' => ['label' => 'Enchanted Hit', 'hex' => '#5b9cf6'],
        'end_rod' => ['label' => 'End Rod', 'hex' => '#f2f2f2'],
        'dragon_breath' => ['label' => 'Dragon Breath', 'hex' => '#b06be0'],
        'portal' => ['label' => 'Portal', 'hex' => '#8b4cf6'],
        'enchant' => ['label' => 'Enchanting Glyphs', 'hex' => '#8f8fd8'],
        'firework' => ['label' => 'Firework Spark', 'hex' => '#ffffff'],
        'note' => ['label' => 'Note', 'hex' => '#5dbe7a'],
        'splash' => ['label' => 'Splash', 'hex' => '#8fc7ff'],
        'bubble' => ['label' => 'Bubble', 'hex' => '#9fd8ff'],
        'drip_lava' => ['label' => 'Lava Drip', 'hex' => '#ff6a1f'],
        'drip_water' => ['label' => 'Water Drip', 'hex' => '#3a7bd5'],
        'lava' => ['label' => 'Lava', 'hex' => '#ff4400'],
        'snowflake' => ['label' => 'Snowflake', 'hex' => '#e8f4ff'],
        'ash' => ['label' => 'Ash', 'hex' => '#8a8272'],
        'white_ash' => ['label' => 'White Ash', 'hex' => '#e0dcd2'],
        'warped_spore' => ['label' => 'Warped Spore', 'hex' => '#2ba79b'],
        'crimson_spore' => ['label' => 'Crimson Spore', 'hex' => '#c05a4a'],
        'glow' => ['label' => 'Glow', 'hex' => '#4fe0c8'],
        'sculk_soul' => ['label' => 'Sculk Soul', 'hex' => '#1fb8ad'],
        'electric_spark' => ['label' => 'Electric Spark', 'hex' => '#5cc9ff'],
        'wax_on' => ['label' => 'Wax On', 'hex' => '#e8a94a'],
        'cherry_leaves' => ['label' => 'Cherry Leaves', 'hex' => '#f7b6d2'],
        'totem_of_undying' => ['label' => 'Totem', 'hex' => '#5dbe7a'],
        'explosion' => ['label' => 'Explosion', 'hex' => '#dedede'],
        'explosion_emitter' => ['label' => 'Huge Explosion', 'hex' => '#cccccc'],
    ];
}

// ── SOUNDS (curated — the ones players actually use) ──
function gameSounds(): array
{
    return [
        'UI & feedback' => [
            'block.note_block.pling'     => 'Note block — Pling',
            'block.note_block.bell'      => 'Note block — Bell',
            'block.note_block.harp'      => 'Note block — Harp',
            'block.note_block.bass'      => 'Note block — Bass',
            'ui.button.click'            => 'Button click',
            'entity.experience_orb.pickup' => 'XP pickup',
            'entity.item.pickup'         => 'Item pickup',
            'block.anvil.land'           => 'Anvil land',
            'block.amethyst_block.chime' => 'Amethyst chime',
        ],
        'Dramatic' => [
            'entity.ender_dragon.growl'  => 'Ender Dragon growl',
            'entity.wither.spawn'        => 'Wither spawn',
            'entity.lightning_bolt.thunder' => 'Thunder',
            'entity.generic.explode'     => 'Explosion',
            'ambient.cave'               => 'Cave ambience',
            'entity.warden.heartbeat'    => 'Warden heartbeat',
            'block.end_portal.spawn'     => 'End portal spawn',
        ],
        'Player' => [
            'entity.player.levelup'      => 'Level up',
            'entity.player.hurt'         => 'Player hurt',
            'entity.player.burp'         => 'Burp',
            'entity.arrow.hit_player'    => 'Arrow hit',
            'entity.firework_rocket.launch' => 'Firework launch',
            'entity.firework_rocket.blast'  => 'Firework blast',
        ],
        'Mobs' => [
            'entity.villager.yes'        => 'Villager yes',
            'entity.villager.no'         => 'Villager no',
            'entity.creeper.primed'      => 'Creeper fuse',
            'entity.zombie.ambient'      => 'Zombie',
            'entity.cat.purr'            => 'Cat purr',
            'entity.wolf.howl'           => 'Wolf howl',
            'entity.enderman.teleport'   => 'Enderman teleport',
        ],
        'Music discs' => [
            'music_disc.cat'   => 'Disc — cat',
            'music_disc.blocks' => 'Disc — blocks',
            'music_disc.pigstep' => 'Disc — pigstep',
            'music_disc.otherside' => 'Disc — otherside',
        ],
    ];
}

// ── TEXT COLOURS ─────────────────────────────────
function gameColors(): array
{
    return [
        'black' => '#000000', 'dark_blue' => '#0000AA', 'dark_green' => '#00AA00',
        'dark_aqua' => '#00AAAA', 'dark_red' => '#AA0000', 'dark_purple' => '#AA00AA',
        'gold' => '#FFAA00', 'gray' => '#AAAAAA', 'dark_gray' => '#555555',
        'blue' => '#5555FF', 'green' => '#55FF55', 'aqua' => '#55FFFF',
        'red' => '#FF5555', 'light_purple' => '#FF55FF', 'yellow' => '#FFFF55',
        'white' => '#FFFFFF',
    ];
}

// ── TARGET SELECTORS ─────────────────────────────
function gameSelectors(): array
{
    return [
        '@p' => 'Nearest player',
        '@a' => 'All players',
        '@r' => 'Random player',
        '@s' => 'Yourself (whoever runs it)',
        '@e' => 'All entities',
        '@n' => 'Nearest entity',
    ];
}

// ── ATTRIBUTES ───────────────────────────────────
// `legacy` is the pre-1.21 id, which carried a generic./player./horse.
// prefix. gameAttributeId() picks the right one for a version.
function gameAttributes(): array
{
    return [
        'max_health'            => ['label' => 'Max health', 'legacy' => 'generic.max_health', 'group' => 'Survival',
                                    'desc' => 'Total hearts. 20 is the player default — each point is half a heart.'],
        'movement_speed'        => ['label' => 'Movement speed', 'legacy' => 'generic.movement_speed', 'group' => 'Movement',
                                    'desc' => 'Walking speed. The player default is 0.1, so small changes go a long way.'],
        'attack_damage'         => ['label' => 'Attack damage', 'legacy' => 'generic.attack_damage', 'group' => 'Combat',
                                    'desc' => 'Damage per hit before weapons and enchantments are counted.'],
        'attack_speed'          => ['label' => 'Attack speed', 'legacy' => 'generic.attack_speed', 'group' => 'Combat',
                                    'desc' => 'How fast the attack cooldown refills. The player default is 4.'],
        'attack_knockback'      => ['label' => 'Attack knockback', 'legacy' => 'generic.attack_knockback', 'group' => 'Combat',
                                    'desc' => 'Extra knockback dealt on hit.'],
        'armor'                 => ['label' => 'Armour', 'legacy' => 'generic.armor', 'group' => 'Combat',
                                    'desc' => 'Armour points. Each point is half an armour icon.'],
        'armor_toughness'       => ['label' => 'Armour toughness', 'legacy' => 'generic.armor_toughness', 'group' => 'Combat',
                                    'desc' => 'Reduces how much strong hits cut through armour.'],
        'knockback_resistance'  => ['label' => 'Knockback resistance', 'legacy' => 'generic.knockback_resistance', 'group' => 'Combat',
                                    'desc' => '0 to 1. At 1 the entity cannot be knocked back at all.'],
        'follow_range'          => ['label' => 'Follow range', 'legacy' => 'generic.follow_range', 'group' => 'Mobs',
                                    'desc' => 'How far a mob will track a target, in blocks.'],
        'luck'                  => ['label' => 'Luck', 'legacy' => 'generic.luck', 'group' => 'Survival',
                                    'desc' => 'Affects loot table rolls, most visibly when fishing.'],
        'jump_strength'         => ['label' => 'Jump strength', 'legacy' => 'horse.jump_strength', 'group' => 'Movement',
                                    'desc' => 'How high the entity jumps. Applied to horses before 1.20.5.'],
        'scale'                 => ['label' => 'Scale', 'legacy' => 'generic.scale', 'group' => 'Body', 'min' => 50,
                                    'desc' => 'Physical size. 1 is normal; the hitbox and reach scale with it.'],
        'step_height'           => ['label' => 'Step height', 'legacy' => 'generic.step_height', 'group' => 'Movement', 'min' => 50,
                                    'desc' => 'How tall a step can be walked up without jumping. Default 0.6.'],
        'gravity'               => ['label' => 'Gravity', 'legacy' => 'generic.gravity', 'group' => 'Movement', 'min' => 50,
                                    'desc' => 'Downward acceleration. Default 0.08; 0 makes the entity float.'],
        'safe_fall_distance'    => ['label' => 'Safe fall distance', 'legacy' => 'generic.safe_fall_distance', 'group' => 'Survival', 'min' => 50,
                                    'desc' => 'Blocks you can fall before taking damage. Default 3.'],
        'fall_damage_multiplier'=> ['label' => 'Fall damage multiplier', 'legacy' => 'generic.fall_damage_multiplier', 'group' => 'Survival', 'min' => 50,
                                    'desc' => 'Scales fall damage. 0 removes it entirely.'],
        'block_interaction_range'=> ['label' => 'Block reach', 'legacy' => 'player.block_interaction_range', 'group' => 'Player', 'min' => 50,
                                    'desc' => 'How far you can reach blocks. Survival default 4.5.'],
        'entity_interaction_range'=> ['label' => 'Entity reach', 'legacy' => 'player.entity_interaction_range', 'group' => 'Player', 'min' => 50,
                                    'desc' => 'How far you can reach entities. Survival default 3.'],
        'block_break_speed'     => ['label' => 'Block break speed', 'legacy' => 'player.block_break_speed', 'group' => 'Player', 'min' => 50,
                                    'desc' => 'Multiplier on mining speed.'],
        'mining_efficiency'     => ['label' => 'Mining efficiency', 'legacy' => 'player.mining_efficiency', 'group' => 'Player', 'min' => 55,
                                    'desc' => 'Bonus mining speed on blocks your tool is correct for.'],
        'sneaking_speed'        => ['label' => 'Sneaking speed', 'legacy' => 'player.sneaking_speed', 'group' => 'Player', 'min' => 55,
                                    'desc' => 'Multiplier on movement while sneaking.'],
        'submerged_mining_speed'=> ['label' => 'Underwater mining speed', 'legacy' => 'player.submerged_mining_speed', 'group' => 'Player', 'min' => 55,
                                    'desc' => 'Multiplier on mining speed underwater — what Aqua Affinity affects.'],
        'sweeping_damage_ratio' => ['label' => 'Sweeping damage ratio', 'legacy' => 'player.sweeping_damage_ratio', 'group' => 'Player', 'min' => 55,
                                    'desc' => 'Share of damage passed to sweep targets.'],
        'burning_time'          => ['label' => 'Burning time', 'legacy' => 'generic.burning_time', 'group' => 'Survival', 'min' => 55,
                                    'desc' => 'Multiplier on how long the entity stays on fire.'],
        'explosion_knockback_resistance' => ['label' => 'Explosion knockback resistance', 'legacy' => 'generic.explosion_knockback_resistance', 'group' => 'Combat', 'min' => 55,
                                    'desc' => '0 to 1. Reduces knockback from explosions only.'],
        'movement_efficiency'   => ['label' => 'Movement efficiency', 'legacy' => 'generic.movement_efficiency', 'group' => 'Movement', 'min' => 55,
                                    'desc' => 'Reduces the slowdown from blocks like soul sand.'],
        'oxygen_bonus'          => ['label' => 'Oxygen bonus', 'legacy' => 'generic.oxygen_bonus', 'group' => 'Survival', 'min' => 55,
                                    'desc' => 'Extends how long you can stay underwater — what Respiration affects.'],
        'water_movement_efficiency' => ['label' => 'Water movement efficiency', 'legacy' => 'generic.water_movement_efficiency', 'group' => 'Movement', 'min' => 55,
                                    'desc' => 'Reduces the slowdown from moving through water.'],
    ];
}

/** The attribute id a version expects — un-prefixed on 1.21.4+, prefixed before. */
function gameAttributeId(string $id, string $version): string
{
    if (mcHas($version, 'attribute_no_prefix')) return $id;
    return gameAttributes()[$id]['legacy'] ?? $id;
}

/** Modifier operations. Renamed in 1.21. */
function gameAttributeOps(string $version): array
{
    return mcHas($version, 'attribute_new_ops')
        ? [
            'add_value'            => 'Add a flat amount',
            'add_multiplied_base'  => 'Add a percentage of the base value',
            'add_multiplied_total' => 'Multiply the final total',
          ]
        : [
            'add'            => 'Add a flat amount',
            'multiply_base'  => 'Add a percentage of the base value',
            'multiply_total' => 'Multiply the final total',
          ];
}

// ── SOUND CATEGORIES ─────────────────────────────
function gameSoundSources(): array
{
    return [
        'master'  => 'Master', 'music' => 'Music', 'record' => 'Jukebox/note blocks',
        'weather' => 'Weather', 'block' => 'Blocks', 'hostile' => 'Hostile mobs',
        'neutral' => 'Friendly mobs', 'player' => 'Players', 'ambient' => 'Ambient',
        'voice'   => 'Voice/speech',
    ];
}

// ── BOSS BAR ─────────────────────────────────────
function gameBossbarColors(): array
{
    return [
        'pink'   => ['label' => 'Pink',   'hex' => '#f06fa8'],
        'blue'   => ['label' => 'Blue',   'hex' => '#5b9cf6'],
        'red'    => ['label' => 'Red',    'hex' => '#e05555'],
        'green'  => ['label' => 'Green',  'hex' => '#5dbe7a'],
        'yellow' => ['label' => 'Yellow', 'hex' => '#e8c84a'],
        'purple' => ['label' => 'Purple', 'hex' => '#9b78f0'],
        'white'  => ['label' => 'White',  'hex' => '#e8ecf4'],
    ];
}

function gameBossbarStyles(): array
{
    return [
        'progress'   => ['label' => 'Solid bar',   'segments' => 1],
        'notched_6'  => ['label' => '6 segments',  'segments' => 6],
        'notched_10' => ['label' => '10 segments', 'segments' => 10],
        'notched_12' => ['label' => '12 segments', 'segments' => 12],
        'notched_20' => ['label' => '20 segments', 'segments' => 20],
    ];
}

// ── TEAMS ────────────────────────────────────────
function gameTeamOptions(): array
{
    return [
        'displayName'            => ['label' => 'Display name',       'type' => 'text',
                                     'desc' => 'Shown in place of the team id. Takes a text component.'],
        'color'                  => ['label' => 'Colour',             'type' => 'color',
                                     'desc' => 'Colours member names, and decides the team\'s glow colour.'],
        'prefix'                 => ['label' => 'Name prefix',        'type' => 'text',
                                     'desc' => 'Text component placed before every member\'s name.'],
        'suffix'                 => ['label' => 'Name suffix',        'type' => 'text',
                                     'desc' => 'Text component placed after every member\'s name.'],
        'friendlyFire'           => ['label' => 'Friendly fire',      'type' => 'bool', 'default' => 'true',
                                     'desc' => 'Whether team members can damage each other.'],
        'seeFriendlyInvisibles'  => ['label' => 'See invisible team mates', 'type' => 'bool', 'default' => 'true',
                                     'desc' => 'Invisible team mates appear as translucent rather than gone.'],
        'nametagVisibility'      => ['label' => 'Nametag visibility', 'type' => 'visibility', 'default' => 'always',
                                     'desc' => 'Who can see member nametags through the world.'],
        'deathMessageVisibility' => ['label' => 'Death messages',     'type' => 'visibility', 'default' => 'always',
                                     'desc' => 'Who sees the chat message when a member dies.'],
        'collisionRule'          => ['label' => 'Collision',          'type' => 'collision', 'default' => 'always',
                                     'desc' => 'Which entities members push against.'],
    ];
}

function gameTeamVisibility(): array
{
    return [
        'always'             => 'Everyone',
        'never'              => 'Nobody',
        'hideForOtherTeams'  => 'Hidden from other teams',
        'hideForOwnTeam'     => 'Hidden from own team',
    ];
}

function gameTeamCollision(): array
{
    return [
        'always'         => 'Push everything',
        'never'          => 'Push nothing',
        'pushOtherTeams' => 'Push other teams only',
        'pushOwnTeam'    => 'Push own team only',
    ];
}

// ── PARTICLES WITH OPTIONS ───────────────────────
// Particles taking extra arguments. From 1.20.5 those arguments are written
// as SNBT after the id; before that they were space-separated extras placed
// between the particle name and the position.
function gameParticleOptions(): array
{
    return [
        'dust' => [
            'kind'   => 'dust',
            'legacy' => 'r g b size',
            'note'   => 'Colour and size of the dust cloud.',
        ],
        'dust_color_transition' => [
            'kind'   => 'dust_transition',
            'legacy' => 'r g b size r2 g2 b2',
            'note'   => 'Fades from one colour to another.',
        ],
        'block'        => ['kind' => 'block_state', 'legacy' => 'block', 'note' => 'Breaks apart the given block texture.'],
        'block_marker' => ['kind' => 'block_state', 'legacy' => 'block', 'note' => 'Shows a flat marker of the given block.'],
        'falling_dust' => ['kind' => 'block_state', 'legacy' => 'block', 'note' => 'Dust falling from the given block.'],
        'item'         => ['kind' => 'item',        'legacy' => 'item',  'note' => 'Breaks apart the given item texture.'],
        'sculk_charge' => ['kind' => 'roll',        'legacy' => 'roll',  'note' => 'Rotation of the charge, in radians.'],
        'shriek'       => ['kind' => 'delay',       'legacy' => 'delay', 'note' => 'Ticks to wait before the shriek appears.'],
    ];
}
