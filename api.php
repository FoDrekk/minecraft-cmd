<?php
// ================================================
// api.php — AJAX API endpoint
// All requests return JSON
// ================================================
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lib/registry.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

function ok($data = []) {
    echo json_encode(['ok' => true] + $data);
    exit;
}
function err(string $msg) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}
function sanitize(string $s, int $max = 500): string {
    return mb_substr(trim(strip_tags($s)), 0, $max);
}
// Minecraft commands legitimately contain '<' and '>' (tellraw JSON, scoreboard
// display names, selector arguments, …). strip_tags() would silently mangle
// that content, so command text is only trimmed and length-capped here —
// output is escaped with e() wherever it is rendered as HTML (see mystuff.php).
function sanitizeCommand(string $s, int $max = 2000): string {
    return mb_substr(trim($s), 0, $max);
}

switch ($action) {

    // ── HISTORY ──────────────────────────────
    case 'history_add':
        $cmd = sanitizeCommand($_POST['command'] ?? '', 2000);
        $tab = sanitize($_POST['tab'] ?? 'give', 30);
        $ver = sanitize($_POST['version'] ?? mcCurrentVersion(), 20);
        if (!$cmd) err('Empty command');
        historyAdd($cmd, $tab, $ver);
        ok(['rows' => historyGet(30)]);

    case 'history_get':
        ok(['rows' => historyGet(30)]);

    case 'history_delete':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) err('Invalid ID');
        historyDelete($id);
        ok(['rows' => historyGet(30)]);

    case 'history_clear':
        historyClear();
        ok();

    // ── FAVOURITES ────────────────────────────
    case 'fav_add':
        $cmd  = sanitizeCommand($_POST['command'] ?? '', 2000);
        $tab  = sanitize($_POST['tab'] ?? 'give', 30);
        $note = sanitize($_POST['note'] ?? '', 255);
        if (!$cmd) err('Empty command');
        $added = favAdd($cmd, $tab, $note, [
            'name'     => sanitize($_POST['name'] ?? '', 120) ?: null,
            'category' => sanitize($_POST['category'] ?? '', 40) ?: null,
            'version'  => sanitize($_POST['version'] ?? mcCurrentVersion(), 20),
            'tags'     => sanitize($_POST['tags'] ?? '', 255) ?: null,
        ]);
        if (!$added) err('That command is already in your library');
        ok(['rows' => favGet()]);

    case 'fav_get':
        ok(['rows' => favGet(sanitize($_GET['category'] ?? '', 40))]);

    case 'fav_update':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) err('Invalid ID');
        favUpdate($id, [
            'name'     => sanitize($_POST['name'] ?? '', 120) ?: null,
            'note'     => sanitize($_POST['note'] ?? '', 255),
            'category' => sanitize($_POST['category'] ?? '', 40) ?: null,
            'tags'     => sanitize($_POST['tags'] ?? '', 255) ?: null,
        ]);
        ok(['rows' => favGet()]);

    case 'fav_delete':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) err('Invalid ID');
        favDelete($id);
        ok(['rows' => favGet()]);

    // ── KITS ──────────────────────────────────
    case 'kit_save':
        $name = sanitize($_POST['name'] ?? '', 100);
        $data = json_decode($_POST['data'] ?? '{}', true);
        if (!$name) err('Kit name required');
        if (!$data) err('Invalid kit data');
        kitSave($name, $data);
        ok(['rows' => kitGet()]);

    case 'kit_get':
        ok(['rows' => kitGet()]);

    case 'kit_delete':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) err('Invalid ID');
        kitDelete($id);
        ok(['rows' => kitGet()]);

    // ── PRESETS ───────────────────────────────
    case 'preset_save':
        $name = sanitize($_POST['name'] ?? '', 100);
        $type = sanitize($_POST['type'] ?? 'sequence', 30);
        $data = json_decode($_POST['data'] ?? '[]', true);
        if (!$name) err('Preset name required');
        presetSave($name, $type, $data);
        ok(['rows' => presetGet()]);

    case 'preset_get':
        $type = sanitize($_GET['type'] ?? '', 30);
        ok(['rows' => presetGet($type)]);

    case 'preset_delete':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) err('Invalid ID');
        presetDelete($id);
        ok(['rows' => presetGet()]);

    // ── PALETTES ──────────────────────────────
    case 'palette_save':
        $name  = sanitize($_POST['name'] ?? '', 100);
        $style = sanitize($_POST['style'] ?? '', 40);
        $data  = json_decode($_POST['data'] ?? '{}', true);
        if (!$name) err('Give the palette a name first');
        if (!is_array($data) || !$data) err('That palette has no blocks in it');
        paletteSave($name, $style, $data);
        ok(['rows' => paletteGet()]);

    case 'palette_get':
        ok(['rows' => paletteGet()]);

    case 'palette_delete':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) err('Invalid ID');
        paletteDelete($id);
        ok(['rows' => paletteGet()]);

    // ── COMMAND DOCTOR ────────────────────────
    case 'doctor':
        require_once __DIR__ . '/lib/doctor.php';
        $cmd = mb_substr(trim($_POST['command'] ?? $_GET['command'] ?? ''), 0, 4000);
        $ver = sanitize($_POST['version'] ?? $_GET['version'] ?? mcCurrentVersion(), 20);
        try {
            ok(['result' => doctorAnalyse($cmd, $ver)]);
        } catch (Throwable $e) {
            error_log('Doctor failed: ' . $e->getMessage());
            err('That command could not be analysed. It may use syntax the Doctor does not understand yet.');
        }

    case 'explain':
        require_once __DIR__ . '/lib/doctor.php';
        $cmd = mb_substr(trim($_POST['command'] ?? $_GET['command'] ?? ''), 0, 4000);
        $ver = sanitize($_POST['version'] ?? $_GET['version'] ?? mcCurrentVersion(), 20);
        try {
            ok(['result' => doctorExplain($cmd, $ver)]);
        } catch (Throwable $e) {
            error_log('Explain failed: ' . $e->getMessage());
            err('That command could not be explained. Check it in the Doctor tab first.');
        }

    // ── SURPRISE ME ───────────────────────────
    case 'surprise':
        require_once __DIR__ . '/lib/data/ideas.php';
        require_once __DIR__ . '/lib/data/farms.php';
        require_once __DIR__ . '/lib/data/tips.php';
        require_once __DIR__ . '/lib/data/palettes.php';
        require_once __DIR__ . '/lib/data/challenges.php';

        $pool = [];

        $ideaId = array_rand(ideasAll());
        $idea   = ideasAll()[$ideaId];
        $pool[] = ['kind' => 'Build idea', 'title' => $idea['title'],
                   'body' => $idea['concept'] . ' (' . $idea['size'] . ')',
                   'href' => 'knowledge.php?t=ideas&idea=' . rawurlencode($ideaId)];

        $farmId = array_rand(farmsAll());
        $farm   = farmsAll()[$farmId];
        $pool[] = ['kind' => 'Farm', 'title' => $farm['title'],
                   'body' => $farm['output'],
                   'href' => 'farms.php?farm=' . rawurlencode($farmId)];

        $tipId  = array_rand(tipsAll());
        $tip    = tipsAll()[$tipId];
        $pool[] = ['kind' => 'Building tip', 'title' => $tip['title'],
                   'body' => $tip['summary'] . ' ' . $tip['try'],
                   'href' => 'knowledge.php?t=tips#tip-' . rawurlencode($tipId)];

        $pal    = paletteRandom();
        $names  = [];
        foreach ($pal['blocks'] as $role => $blockId) {
            $b = blocksList()[$blockId] ?? null;
            $names[] = ucfirst($role) . ': ' . ($b['name'] ?? $blockId);
        }
        $pool[] = ['kind' => 'Block palette', 'title' => ucfirst($pal['style']) . ' palette',
                   'body' => implode(' · ', $names),
                   'href' => 'knowledge.php?t=palette&style=' . rawurlencode($pal['style'])];

        $ch = commandChallenges()[array_rand(commandChallenges())];
        $pool[] = ['kind' => 'Command challenge', 'title' => 'Can you build this command?',
                   'body' => $ch['task'], 'href' => $ch['tool']];

        $bc = buildChallenges()[array_rand(buildChallenges())];
        $pool[] = ['kind' => 'Build challenge', 'title' => 'Try this', 'body' => $bc,
                   'href' => 'knowledge.php?t=tips'];

        ok(['pick' => $pool[array_rand($pool)]]);

    // ── GLOBAL SEARCH ─────────────────────────
    case 'search':
        $q = sanitize($_GET['q'] ?? $_POST['q'] ?? '', 120);
        ok(['rows' => registry_search($q)]);

    // ── STATUS ────────────────────────────────
    case 'status':
        ok(dbStatus());

    default:
        err('Unknown action');
}
