<?php
// ================================================
// api.php — AJAX API endpoint
// All requests return JSON
// ================================================
require_once __DIR__ . '/db.php';

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

switch ($action) {

    // ── HISTORY ──────────────────────────────
    case 'history_add':
        $cmd = sanitize($_POST['command'] ?? '', 2000);
        $tab = sanitize($_POST['tab'] ?? 'give', 30);
        if (!$cmd) err('Empty command');
        historyAdd($cmd, $tab);
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
        $cmd  = sanitize($_POST['command'] ?? '', 2000);
        $tab  = sanitize($_POST['tab'] ?? 'give', 30);
        $note = sanitize($_POST['note'] ?? '', 255);
        if (!$cmd) err('Empty command');
        $added = favAdd($cmd, $tab, $note);
        if (!$added) err('Already in favourites');
        ok(['rows' => favGet()]);

    case 'fav_get':
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

    // ── STATUS ────────────────────────────────
    case 'status':
        ok(dbStatus());

    default:
        err('Unknown action');
}
