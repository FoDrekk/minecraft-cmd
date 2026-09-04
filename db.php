<?php
// ================================================
// db.php — Database Connection
// ================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'minecraft_cmd');
define('DB_USER', 'root');       // XAMPP default
define('DB_PASS', '');           // XAMPP default (kosong)
define('DB_CHAR', 'utf8mb4');

define('APP_USER', 'nizkbiits'); // Default username

function getDB(): ?PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    try {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHAR
        );
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        // Return null-safe — page masih boleh jalan tanpa DB
        error_log('DB Connection failed: ' . $e->getMessage());
        return null;
    }
}

// ── HISTORY ──────────────────────────────────
function historyAdd(string $command, string $tab): bool {
    $db = getDB(); if (!$db) return false;
    try {
        $stmt = $db->prepare(
            'INSERT INTO command_history (username, command, tab) VALUES (?, ?, ?)'
        );
        return $stmt->execute([APP_USER, $command, $tab]);
    } catch (PDOException $e) { return false; }
}

function historyGet(int $limit = 30): array {
    $db = getDB(); if (!$db) return [];
    try {
        $stmt = $db->prepare(
            'SELECT id, command, tab, created_at FROM command_history
             WHERE username = ? ORDER BY created_at DESC LIMIT ?'
        );
        $stmt->execute([APP_USER, $limit]);
        return $stmt->fetchAll();
    } catch (PDOException $e) { return []; }
}

function historyDelete(int $id): bool {
    $db = getDB(); if (!$db) return false;
    try {
        $stmt = $db->prepare('DELETE FROM command_history WHERE id = ? AND username = ?');
        return $stmt->execute([$id, APP_USER]);
    } catch (PDOException $e) { return false; }
}

function historyClear(): bool {
    $db = getDB(); if (!$db) return false;
    try {
        $stmt = $db->prepare('DELETE FROM command_history WHERE username = ?');
        return $stmt->execute([APP_USER]);
    } catch (PDOException $e) { return false; }
}

// ── FAVOURITES ────────────────────────────────
function favAdd(string $command, string $tab, string $note = ''): bool {
    $db = getDB(); if (!$db) return false;
    try {
        $chk = $db->prepare('SELECT id FROM favourites WHERE username=? AND command=?');
        $chk->execute([APP_USER, $command]);
        if ($chk->fetch()) return false;
        $stmt = $db->prepare('INSERT INTO favourites (username, command, tab, note) VALUES (?, ?, ?, ?)');
        return $stmt->execute([APP_USER, $command, $tab, $note]);
    } catch (PDOException $e) { return false; }
}

function favGet(): array {
    $db = getDB(); if (!$db) return [];
    try {
        $stmt = $db->prepare(
            'SELECT id, command, tab, note, created_at FROM favourites
             WHERE username = ? ORDER BY created_at DESC'
        );
        $stmt->execute([APP_USER]);
        return $stmt->fetchAll();
    } catch (PDOException $e) { return []; }
}

function favDelete(int $id): bool {
    $db = getDB(); if (!$db) return false;
    try {
        $stmt = $db->prepare('DELETE FROM favourites WHERE id=? AND username=?');
        return $stmt->execute([$id, APP_USER]);
    } catch (PDOException $e) { return false; }
}

// ── KITS ─────────────────────────────────────
function kitSave(string $name, array $data): bool {
    $db = getDB(); if (!$db) return false;
    try {
        $stmt = $db->prepare(
            'INSERT INTO user_kits (username, kit_name, kit_data) VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE kit_data=VALUES(kit_data), updated_at=NOW()'
        );
        return $stmt->execute([APP_USER, $name, json_encode($data, JSON_UNESCAPED_UNICODE)]);
    } catch (PDOException $e) { return false; }
}

function kitGet(): array {
    $db = getDB(); if (!$db) return [];
    try {
        $stmt = $db->prepare(
            'SELECT id, kit_name, kit_data, updated_at FROM user_kits
             WHERE username=? ORDER BY updated_at DESC'
        );
        $stmt->execute([APP_USER]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['kit_data'] = json_decode($r['kit_data'], true);
        }
        return $rows;
    } catch (PDOException $e) { return []; }
}

function kitDelete(int $id): bool {
    $db = getDB(); if (!$db) return false;
    $stmt = $db->prepare('DELETE FROM user_kits WHERE id=? AND username=?');
    return $stmt->execute([$id, APP_USER]);
}

// ── PRESETS ───────────────────────────────────
function presetSave(string $name, string $type, array $data): bool {
    $db = getDB(); if (!$db) return false;
    try {
        $stmt = $db->prepare(
            'INSERT INTO user_presets (username, preset_name, preset_type, preset_data) VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE preset_data=VALUES(preset_data)'
        );
        return $stmt->execute([APP_USER, $name, $type, json_encode($data, JSON_UNESCAPED_UNICODE)]);
    } catch (PDOException $e) { return false; }
}

function presetGet(string $type = ''): array {
    $db = getDB(); if (!$db) return [];
    try {
        if ($type) {
            $stmt = $db->prepare(
                'SELECT id, preset_name, preset_type, preset_data, created_at
                 FROM user_presets WHERE username=? AND preset_type=? ORDER BY created_at DESC'
            );
            $stmt->execute([APP_USER, $type]);
        } else {
            $stmt = $db->prepare(
                'SELECT id, preset_name, preset_type, preset_data, created_at
                 FROM user_presets WHERE username=? ORDER BY created_at DESC'
            );
            $stmt->execute([APP_USER]);
        }
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['preset_data'] = json_decode($r['preset_data'], true);
        }
        return $rows;
    } catch (PDOException $e) { return []; }
}

function presetDelete(int $id): bool {
    $db = getDB(); if (!$db) return false;
    $stmt = $db->prepare('DELETE FROM user_presets WHERE id=? AND username=?');
    return $stmt->execute([$id, APP_USER]);
}

// ── DB STATUS ─────────────────────────────────
function dbStatus(): array {
    $db = getDB();
    if (!$db) return ['connected' => false, 'error' => 'Cannot connect to MySQL'];
    try {
        $db->query('SELECT 1');
        // Check if tables exist first
        $tables = $db->query("SHOW TABLES LIKE 'command_history'")->fetchAll();
        if (empty($tables)) {
            return ['connected' => true, 'tables_missing' => true, 'history' => 0, 'favourites' => 0, 'kits' => 0];
        }
        $histStmt = $db->prepare('SELECT COUNT(*) as n FROM command_history WHERE username=?');
        $histStmt->execute([APP_USER]);
        $hist = $histStmt->fetch()['n'];

        $favStmt = $db->prepare('SELECT COUNT(*) as n FROM favourites WHERE username=?');
        $favStmt->execute([APP_USER]);
        $favs = $favStmt->fetch()['n'];

        $kitStmt = $db->prepare('SELECT COUNT(*) as n FROM user_kits WHERE username=?');
        $kitStmt->execute([APP_USER]);
        $kits = $kitStmt->fetch()['n'];
        return ['connected' => true, 'tables_missing' => false, 'history' => $hist, 'favourites' => $favs, 'kits' => $kits];
    } catch (Exception $e) {
        return ['connected' => false, 'error' => $e->getMessage()];
    }
}
