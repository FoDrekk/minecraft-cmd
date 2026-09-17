"""The Minecraft version model — read from PHP, never re-typed here.

lib/mc.php's MC_VERSIONS is the app's one existing source of truth for
"which Minecraft versions exist, in what order, with what syntax era".
This module does not duplicate that table: it loads it, either by
shelling out to tools/export_mc_data.php (the live source) or from a
previously-exported JSON snapshot (for environments with no PHP
interpreter, e.g. a Python-only CI job). Either way, if lib/mc.php
changes, the next load reflects it automatically — there is nothing
here to keep in sync by hand.
"""
from __future__ import annotations

import json
import subprocess
from dataclasses import dataclass
from pathlib import Path

_REPO_ROOT = Path(__file__).resolve().parents[2]
_DEFAULT_BRIDGE_SCRIPT = _REPO_ROOT / "tools" / "export_mc_data.php"


class VersionSourceError(RuntimeError):
    """Raised when the PHP version table can't be loaded at all."""


@dataclass(frozen=True)
class Version:
    id: str
    label: str
    name: str
    edition: str
    syntax: str
    rank: int
    released: str

    @property
    def is_java(self) -> bool:
        return self.edition != "bedrock"


class VersionTable:
    """Every known version plus the feature-gate table, keyed exactly
    as lib/mc.php keys them (version id -> Version, feature name -> min
    rank) — the same shape MC_FEATURES/mcHas() use on the PHP side."""

    def __init__(self, versions: dict[str, Version], features: dict[str, int],
                 default_version: str | None = None):
        self.versions = versions
        self.features = features
        self.default_version = default_version

    def __len__(self) -> int:
        return len(self.versions)

    def __contains__(self, version_id: str) -> bool:
        return version_id in self.versions

    def get(self, version_id: str) -> Version | None:
        return self.versions.get(version_id)

    def rank_of(self, version_id: str) -> int:
        """0 for an unknown id, matching mcVersion()'s "unknown falls
        back gracefully" spirit rather than raising for a typo'd id
        encountered while scanning free-form archive data."""
        v = self.versions.get(version_id)
        return v.rank if v else 0

    def has_feature(self, version_id: str, feature: str) -> bool:
        """Mirrors mcHas($versionId, $feature) in lib/mc.php."""
        min_rank = self.features.get(feature)
        if min_rank is None:
            return False
        return self.rank_of(version_id) >= min_rank

    def sorted_by_rank(self, newest_first: bool = True) -> list[Version]:
        return sorted(self.versions.values(), key=lambda v: v.rank, reverse=newest_first)

    def lowest_satisfying(self, min_rank: int, java_only: bool = True) -> Version | None:
        """The oldest version whose rank meets min_rank — what a UI
        uses to say "needs Minecraft <this> or newer"."""
        candidates = [v for v in self.versions.values() if v.rank >= min_rank and (not java_only or v.is_java)]
        return min(candidates, key=lambda v: v.rank) if candidates else None


def from_dict(data: dict) -> VersionTable:
    versions = {
        vid: Version(
            id=vid,
            label=row.get("label", vid),
            name=row.get("name", ""),
            edition=row.get("edition", "java"),
            syntax=row.get("syntax", ""),
            rank=int(row.get("rank", 0)),
            released=row.get("released", ""),
        )
        for vid, row in data.get("versions", {}).items()
    }
    features = {name: int(rank) for name, rank in data.get("features", {}).items()}
    return VersionTable(versions, features, data.get("default_version"))


def from_json_file(path: str | Path) -> VersionTable:
    with open(path, encoding="utf-8") as f:
        return from_dict(json.load(f))


def from_php(php_binary: str = "php", script: str | Path | None = None,
             timeout: float = 10.0) -> VersionTable:
    """Load the live table by running tools/export_mc_data.php.

    This is the normal path — it's how the engine stays honest that
    PHP, not Python, owns version data. Raises VersionSourceError with
    a clear message if PHP isn't on PATH or the script fails, rather
    than silently falling back to something invented.
    """
    script_path = Path(script) if script else _DEFAULT_BRIDGE_SCRIPT
    if not script_path.is_file():
        raise VersionSourceError(f"bridge script not found: {script_path}")
    try:
        result = subprocess.run(
            [php_binary, str(script_path)],
            capture_output=True, text=True, timeout=timeout, check=False,
        )
    except FileNotFoundError as e:
        raise VersionSourceError(
            f"'{php_binary}' not found — install PHP, or pass a pre-exported "
            "JSON snapshot to from_json_file() instead"
        ) from e
    if result.returncode != 0:
        raise VersionSourceError(f"{script_path} exited {result.returncode}: {result.stderr.strip()}")
    try:
        data = json.loads(result.stdout)
    except json.JSONDecodeError as e:
        raise VersionSourceError(f"{script_path} did not print valid JSON: {e}") from e
    return from_dict(data)
