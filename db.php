<?php
// ================================================
// db.php — Database connection and storage
// ------------------------------------------------
// MySQL is used when it is available (the normal XAMPP setup).
// If it is not, the app falls back to a local SQLite file so every
// feature still works — nothing here ever hard-fails a page.
// ================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'minecraft_cmd');
define('DB_USER', 'root');       // XAMPP default
define('DB_PASS', '');           // XAMPP default (empty)
define('DB_CHAR', 'utf8mb4');

define('DB_SQLITE_PATH', __DIR__ . '/data/minecraft_cmd.sqlite');

define('APP_USER', 'nizkbiits'); // Default username

function getDB(): ?PDO
{
    static $pdo = null, $tried = false;
    if ($tried) return $pdo;
    $tried = true;

    $opts = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    // 1. MySQL (preferred — matches the shipped database.sql)
    try {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHAR);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts);
        dbEnsureSchema($pdo);
        return $pdo;
    } catch (PDOException $e) {
        error_log('MySQL unavailable, falling back to SQLite: ' . $e->getMessage());
    }

    // 2. SQLite fallback so the app still works out of the box
    try {
        $dir = dirname(DB_SQLITE_PATH);
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $pdo = new PDO('sqlite:' . DB_SQLITE_PATH, null, null, $opts);
        $pdo->exec('PRAGMA journal_mode = WAL');
        dbEnsureSchema($pdo);
        return $pdo;
    } catch (PDOException $e) {
        error_log('SQLite fallback failed: ' . $e->getMessage());
        $pdo = null;
        return null;
    }
}

function dbDriver(?PDO $db = null): string
{
    $db = $db ?: getDB();
    return $db ? $db->getAttribute(PDO::ATTR_DRIVER_NAME) : '';
}

/**
 * Create anything that is missing, and add columns introduced after the
 * original database.sql shipped. Safe to run on every connection.
 */
function dbEnsureSchema(PDO $db): void
{
    static $done = false;
    if ($done) return;
    $done = true;

    $sqlite = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
    $pk   = $sqlite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT UNSIGNED AUTO_INCREMENT PRIMARY KEY';
    $now  = $sqlite ? "DATETIME DEFAULT CURRENT_TIMESTAMP" : "DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP";
    $json = $sqlite ? 'TEXT' : 'JSON';
    $eng  = $sqlite ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';

    try {
        $db->exec("CREATE TABLE IF NOT EXISTS command_history (
            id $pk,
            username VARCHAR(50) NOT NULL DEFAULT 'nizkbiits',
            command TEXT NOT NULL,
            tab VARCHAR(30) NOT NULL DEFAULT 'give',
            created_at $now
        )$eng");

        $db->exec("CREATE TABLE IF NOT EXISTS favourites (
            id $pk,
            username VARCHAR(50) NOT NULL DEFAULT 'nizkbiits',
            command TEXT NOT NULL,
            tab VARCHAR(30) NOT NULL DEFAULT 'give',
            note VARCHAR(255) DEFAULT NULL,
            created_at $now
        )$eng");

        $db->exec("CREATE TABLE IF NOT EXISTS user_kits (
            id $pk,
            username VARCHAR(50) NOT NULL DEFAULT 'nizkbiits',
            kit_name VARCHAR(100) NOT NULL,
            kit_data $json NOT NULL,
            created_at $now,
            updated_at $now,
            UNIQUE (username, kit_name)
        )$eng");

        $db->exec("CREATE TABLE IF NOT EXISTS user_presets (
            id $pk,
            username VARCHAR(50) NOT NULL DEFAULT 'nizkbiits',
            preset_name VARCHAR(100) NOT NULL,
            preset_type VARCHAR(30) NOT NULL DEFAULT 'sequence',
            preset_data $json NOT NULL,
            created_at $now,
            UNIQUE (username, preset_name)
        )$eng");

        // Saved block palettes (Knowledge → Palette Builder)
        $db->exec("CREATE TABLE IF NOT EXISTS user_palettes (
            id $pk,
            username VARCHAR(50) NOT NULL DEFAULT 'nizkbiits',
            palette_name VARCHAR(100) NOT NULL,
            style VARCHAR(40) DEFAULT NULL,
            palette_data $json NOT NULL,
            created_at $now,
            UNIQUE (username, palette_name)
        )$eng");

        // Saved builds (Knowledge → Build Ideas) — an idea plus the
        // palette the player picked for it, so "My Stuff" remembers
        // which build they were planning and with what materials.
        $db->exec("CREATE TABLE IF NOT EXISTS user_builds (
            id $pk,
            username VARCHAR(50) NOT NULL DEFAULT 'nizkbiits',
            build_name VARCHAR(100) NOT NULL,
            idea_id VARCHAR(60) NOT NULL,
            palette_id VARCHAR(60) DEFAULT NULL,
            note VARCHAR(255) DEFAULT NULL,
            created_at $now,
            UNIQUE (username, build_name)
        )$eng");

        // Columns added after the first release
        dbAddColumn($db, 'favourites',      'name',       "VARCHAR(120) DEFAULT NULL");
        dbAddColumn($db, 'favourites',      'category',   "VARCHAR(40) DEFAULT NULL");
        dbAddColumn($db, 'favourites',      'mc_version', "VARCHAR(20) DEFAULT NULL");
        dbAddColumn($db, 'favourites',      'tags',       "VARCHAR(255) DEFAULT NULL");
        dbAddColumn($db, 'command_history', 'mc_version', "VARCHAR(20) DEFAULT NULL");
    } catch (PDOException $e) {
        error_log('Schema check failed: ' . $e->getMessage());
    }
}

/** Add a column only when it is missing. Works on MySQL and SQLite. */
function dbAddColumn(PDO $db, string $table, string $column, string $definition): void
{
    try {
        if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $cols = $db->query("PRAGMA table_info($table)")->fetchAll();
            foreach ($cols as $c) if (strcasecmp($c['name'], $column) === 0) return;
        } else {
            $stmt = $db->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
            $stmt->execute([$column]);
            if ($stmt->fetch()) return;
        }
        $db->exec("ALTER TABLE $table ADD COLUMN $column $definition");
    } catch (PDOException $e) {
        // A missing table is fine here — CREATE TABLE above owns that case.
    }
}

// ── HISTORY ──────────────────────────────────
function historyAdd(string $command, string $tab, string $version = ''): bool
{
    $db = getDB(); if (!$db) return false;
    try {
        $stmt = $db->prepare(
            'INSERT INTO command_history (username, command, tab, mc_version) VALUES (?, ?, ?, ?)'
        );
        return $stmt->execute([APP_USER, $command, $tab, $version ?: null]);
    } catch (PDOException $e) { return false; }
}

function historyGet(int $limit = 30): array
{
    $db = getDB(); if (!$db) return [];
    try {
        // LIMIT is inlined as a bounded integer — PDO cannot bind it with
        // emulated prepares off on MySQL.
        $limit = max(1, min(200, $limit));
        $stmt = $db->prepare(
            "SELECT id, command, tab, mc_version, created_at FROM command_history
             WHERE username = ? ORDER BY created_at DESC, id DESC LIMIT $limit"
        );
        $stmt->execute([APP_USER]);
        return $stmt->fetchAll();
    } catch (PDOException $e) { return []; }
}

function historyDelete(int $id): bool
{
    $db = getDB(); if (!$db) return false;
    try {
        $stmt = $db->prepare('DELETE FROM command_history WHERE id = ? AND username = ?');
        return $stmt->execute([$id, APP_USER]);
    } catch (PDOException $e) { return false; }
}

function historyClear(): bool
{
    $db = getDB(); if (!$db) return false;
    try {
        $stmt = $db->prepare('DELETE FROM command_history WHERE username = ?');
        return $stmt->execute([APP_USER]);
    } catch (PDOException $e) { return false; }
}

// ── COMMAND LIBRARY (favourites) ──────────────
function favAdd(string $command, string $tab, string $note = '', array $meta = []): bool
{
    $db = getDB(); if (!$db) return false;
    try {
        $chk = $db->prepare('SELECT id FROM favourites WHERE username=? AND command=?');
        $chk->execute([APP_USER, $command]);
        if ($chk->fetch()) return false;
        $stmt = $db->prepare(
            'INSERT INTO favourites (username, command, tab, note, name, category, mc_version, tags)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        return $stmt->execute([
            APP_USER, $command, $tab, $note,
            $meta['name']     ?? null,
            $meta['category'] ?? null,
            $meta['version']  ?? null,
            $meta['tags']     ?? null,
        ]);
    } catch (PDOException $e) { return false; }
}

function favGet(string $category = ''): array
{
    $db = getDB(); if (!$db) return [];
    try {
        $sql = 'SELECT id, command, tab, note, name, category, mc_version, tags, created_at
                FROM favourites WHERE username = ?';
        $args = [APP_USER];
        if ($category !== '') { $sql .= ' AND category = ?'; $args[] = $category; }
        $sql .= ' ORDER BY created_at DESC, id DESC';
        $stmt = $db->prepare($sql);
        $stmt->execute($args);
        return $stmt->fetchAll();
    } catch (PDOException $e) { return []; }
}

function favUpdate(int $id, array $meta): bool
{
    $db = getDB(); if (!$db) return false;
    try {
        $stmt = $db->prepare(
            'UPDATE favourites SET name=?, note=?, category=?, tags=? WHERE id=? AND username=?'
        );
        return $stmt->execute([
            $meta['name'] ?? null, $meta['note'] ?? '', $meta['category'] ?? null,
            $meta['tags'] ?? null, $id, APP_USER,
        ]);
    } catch (PDOException $e) { return false; }
}

function favDelete(int $id): bool
{
    $db = getDB(); if (!$db) return false;
    try {
        $stmt = $db->prepare('DELETE FROM favourites WHERE id=? AND username=?');
        return $stmt->execute([$id, APP_USER]);
    } catch (PDOException $e) { return false; }
}

// ── KITS ─────────────────────────────────────
function kitSave(string $name, array $data): bool
{
    $db = getDB(); if (!$db) return false;
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    try {
        if (dbDriver($db) === 'sqlite') {
            $stmt = $db->prepare(
                'INSERT INTO user_kits (username, kit_name, kit_data) VALUES (?,?,?)
                 ON CONFLICT(username, kit_name) DO UPDATE SET kit_data=excluded.kit_data, updated_at=CURRENT_TIMESTAMP'
            );
        } else {
            $stmt = $db->prepare(
                'INSERT INTO user_kits (username, kit_name, kit_data) VALUES (?,?,?)
                 ON DUPLICATE KEY UPDATE kit_data=VALUES(kit_data), updated_at=NOW()'
            );
        }
        return $stmt->execute([APP_USER, $name, $json]);
    } catch (PDOException $e) { return false; }
}

function kitGet(): array
{
    $db = getDB(); if (!$db) return [];
    try {
        $stmt = $db->prepare(
            'SELECT id, kit_name, kit_data, updated_at FROM user_kits
             WHERE username=? ORDER BY updated_at DESC'
        );
        $stmt->execute([APP_USER]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) $r['kit_data'] = json_decode($r['kit_data'], true);
        return $rows;
    } catch (PDOException $e) { return []; }
}

function kitDelete(int $id): bool
{
    $db = getDB(); if (!$db) return false;
    try {
        $stmt = $db->prepare('DELETE FROM user_kits WHERE id=? AND username=?');
        return $stmt->execute([$id, APP_USER]);
    } catch (PDOException $e) { return false; }
}

// ── PRESETS ───────────────────────────────────
function presetSave(string $name, string $type, array $data): bool
{
    $db = getDB(); if (!$db) return false;
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    try {
        if (dbDriver($db) === 'sqlite') {
            $stmt = $db->prepare(
                'INSERT INTO user_presets (username, preset_name, preset_type, preset_data) VALUES (?,?,?,?)
                 ON CONFLICT(username, preset_name) DO UPDATE SET preset_data=excluded.preset_data'
            );
        } else {
            $stmt = $db->prepare(
                'INSERT INTO user_presets (username, preset_name, preset_type, preset_data) VALUES (?,?,?,?)
                 ON DUPLICATE KEY UPDATE preset_data=VALUES(preset_data)'
            );
        }
        return $stmt->execute([APP_USER, $name, $type, $json]);
    } catch (PDOException $e) { return false; }
}

function presetGet(string $type = ''): array
{
    $db = getDB(); if (!$db) return [];
    try {
        $sql  = 'SELECT id, preset_name, preset_type, preset_data, created_at FROM user_presets WHERE username=?';
        $args = [APP_USER];
        if ($type !== '') { $sql .= ' AND preset_type=?'; $args[] = $type; }
        $sql .= ' ORDER BY created_at DESC';
        $stmt = $db->prepare($sql);
        $stmt->execute($args);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) $r['preset_data'] = json_decode($r['preset_data'], true);
        return $rows;
    } catch (PDOException $e) { return []; }
}

function presetDelete(int $id): bool
{
    $db = getDB(); if (!$db) return false;
    try {
        $stmt = $db->prepare('DELETE FROM user_presets WHERE id=? AND username=?');
        return $stmt->execute([$id, APP_USER]);
    } catch (PDOException $e) { return false; }
}

// ── PALETTES ──────────────────────────────────
function paletteSave(string $name, string $style, array $data): bool
{
    $db = getDB(); if (!$db) return false;
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    try {
        if (dbDriver($db) === 'sqlite') {
            $stmt = $db->prepare(
                'INSERT INTO user_palettes (username, palette_name, style, palette_data) VALUES (?,?,?,?)
                 ON CONFLICT(username, palette_name) DO UPDATE SET palette_data=excluded.palette_data, style=excluded.style'
            );
        } else {
            $stmt = $db->prepare(
                'INSERT INTO user_palettes (username, palette_name, style, palette_data) VALUES (?,?,?,?)
                 ON DUPLICATE KEY UPDATE palette_data=VALUES(palette_data), style=VALUES(style)'
            );
        }
        return $stmt->execute([APP_USER, $name, $style, $json]);
    } catch (PDOException $e) { return false; }
}

function paletteGet(): array
{
    $db = getDB(); if (!$db) return [];
    try {
        $stmt = $db->prepare(
            'SELECT id, palette_name, style, palette_data, created_at FROM user_palettes
             WHERE username=? ORDER BY created_at DESC'
        );
        $stmt->execute([APP_USER]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) $r['palette_data'] = json_decode($r['palette_data'], true);
        return $rows;
    } catch (PDOException $e) { return []; }
}

function paletteDelete(int $id): bool
{
    $db = getDB(); if (!$db) return false;
    try {
        $stmt = $db->prepare('DELETE FROM user_palettes WHERE id=? AND username=?');
        return $stmt->execute([$id, APP_USER]);
    } catch (PDOException $e) { return false; }
}

// ── SAVED BUILDS ──────────────────────────────
function buildSave(string $name, string $ideaId, ?string $paletteId, string $note = ''): bool
{
    $db = getDB(); if (!$db) return false;
    try {
        if (dbDriver($db) === 'sqlite') {
            $stmt = $db->prepare(
                'INSERT INTO user_builds (username, build_name, idea_id, palette_id, note) VALUES (?,?,?,?,?)
                 ON CONFLICT(username, build_name) DO UPDATE SET idea_id=excluded.idea_id, palette_id=excluded.palette_id, note=excluded.note'
            );
        } else {
            $stmt = $db->prepare(
                'INSERT INTO user_builds (username, build_name, idea_id, palette_id, note) VALUES (?,?,?,?,?)
                 ON DUPLICATE KEY UPDATE idea_id=VALUES(idea_id), palette_id=VALUES(palette_id), note=VALUES(note)'
            );
        }
        return $stmt->execute([APP_USER, $name, $ideaId, $paletteId ?: null, $note ?: null]);
    } catch (PDOException $e) { return false; }
}

function buildGet(): array
{
    $db = getDB(); if (!$db) return [];
    try {
        $stmt = $db->prepare(
            'SELECT id, build_name, idea_id, palette_id, note, created_at FROM user_builds
             WHERE username=? ORDER BY created_at DESC'
        );
        $stmt->execute([APP_USER]);
        return $stmt->fetchAll();
    } catch (PDOException $e) { return []; }
}

function buildDelete(int $id): bool
{
    $db = getDB(); if (!$db) return false;
    try {
        $stmt = $db->prepare('DELETE FROM user_builds WHERE id=? AND username=?');
        return $stmt->execute([$id, APP_USER]);
    } catch (PDOException $e) { return false; }
}

// ── DB STATUS ─────────────────────────────────
function dbStatus(): array
{
    $db = getDB();
    if (!$db) {
        return [
            'connected' => false,
            'driver'    => '',
            'error'     => 'No database available. MySQL could not be reached and the local SQLite file could not be created — check that the data/ folder is writable.',
        ];
    }
    try {
        $count = function (string $t) use ($db): int {
            $s = $db->prepare("SELECT COUNT(*) AS n FROM $t WHERE username=?");
            $s->execute([APP_USER]);
            return (int)$s->fetch()['n'];
        };
        return [
            'connected'  => true,
            'driver'     => dbDriver($db),
            'history'    => $count('command_history'),
            'favourites' => $count('favourites'),
            'kits'       => $count('user_kits'),
            'palettes'   => $count('user_palettes'),
            'builds'     => $count('user_builds'),
        ];
    } catch (Exception $e) {
        return ['connected' => false, 'driver' => dbDriver($db), 'error' => 'Database tables could not be read.'];
    }
}
