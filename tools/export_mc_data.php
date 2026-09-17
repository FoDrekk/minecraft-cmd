<?php
// ================================================
// tools/export_mc_data.php — CLI bridge, PHP -> Python
// ------------------------------------------------
// Dumps lib/mc.php's version/feature/selector tables as JSON on
// stdout. This is the ONLY sanctioned way the Python data engine
// (python/minecraft_engine/) learns about Minecraft versions — it
// never re-types MC_VERSIONS, so the two can't drift apart.
//
// Not a web endpoint: run from the CLI only.
//   php tools/export_mc_data.php > /tmp/mc_data.json
//   php tools/export_mc_data.php | python3 -m minecraft_engine.versions -
// ================================================

require_once __DIR__ . '/../lib/mc.php';

echo json_encode([
    'versions' => MC_VERSIONS,
    'features' => MC_FEATURES,
    'selectors' => MC_SELECTORS,
    'default_version' => MC_DEFAULT_VERSION,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
