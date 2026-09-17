"""Asset scanner — reads a Minecraft resource source and produces
normalized metadata, without inventing anything the source doesn't
actually contain.

Understands two real layouts seen in the archives supplied to this
project:

  flat category dump   <root>/block/stone.png, <root>/item/apple.png
                        (no models/blockstates — what "1.20.1 file.zip"
                        looks like)

  resource pack         <root>/assets/minecraft/textures/block/stone.png,
                         .../items/mace.json, .../blockstates/oak_door.json
                         (what "texture-pack-default1.20.5-26.2.zip"
                         looks like — pack_format 34)

A source is either a directory or a .zip archive; both are scanned
through the same AssetRecord interface so callers (diff.py,
registry.py) never need to know which.
"""
from __future__ import annotations

import re
import zipfile
from dataclasses import dataclass
from pathlib import Path, PurePosixPath
from typing import Iterable, Iterator

from . import mcid

# (kind, sub-pattern) -> asset type name. Checked in order, first match
# wins, so a "textures/<cat>/…" path (resource-pack layout) is claimed
# before the bare "<cat>/…" fallback (flat-dump layout) ever gets a
# chance to misclassify it.
_TEXTURE_CATEGORIES = (
    "block", "item", "entity", "particle", "gui", "trims", "painting",
    "mob_effect", "map", "misc", "environment", "font", "colormap", "effect",
)

_RULES: list[tuple[re.Pattern, str]] = []
for _cat in _TEXTURE_CATEGORIES:
    _RULES.append((re.compile(rf"(?:^|/)textures/{_cat}/([^/]+)\.(?:png|webp)$", re.I), f"{_cat}_texture"))
for _cat in _TEXTURE_CATEGORIES:
    _RULES.append((re.compile(rf"(?:^|/){_cat}/([^/]+)\.(?:png|webp)$", re.I), f"{_cat}_texture"))
_RULES += [
    (re.compile(r"(?:^|/)items/([^/]+)\.json$", re.I), "item_definition"),
    (re.compile(r"(?:^|/)blockstates/([^/]+)\.json$", re.I), "blockstate"),
    (re.compile(r"(?:^|/)models/block/([^/]+)\.json$", re.I), "block_model"),
    (re.compile(r"(?:^|/)models/item/([^/]+)\.json$", re.I), "item_model"),
    (re.compile(r"(?:^|/)equipment/([^/]+)\.json$", re.I), "equipment_definition"),
    (re.compile(r"(?:^|/)particles/([^/]+)\.json$", re.I), "particle_definition"),
]


@dataclass(frozen=True)
class AssetRecord:
    """One scanned asset. `id` is the normalised Minecraft id inferred
    from the filename — the scanner never guesses at game data beyond
    that (no display names, no categories, nothing not present in the
    filename/path itself)."""
    id: str
    type: str
    path: str          # path within the source, posix-style
    source: str         # caller-supplied label, e.g. "1.20.1" or the archive name
    size: int = 0

    def as_dict(self) -> dict:
        return {"id": self.id, "type": self.type, "texture": self.path,
                "source": self.source, "size": self.size}


def classify(rel_path: str) -> tuple[str, str] | None:
    """(asset_id, asset_type) for a path, or None if it doesn't match
    any known Minecraft asset shape (pack.mcmeta, lang files, etc —
    real files, just not ones this engine has a use for yet)."""
    posix = PurePosixPath(rel_path).as_posix()
    for pattern, asset_type in _RULES:
        m = pattern.search(posix)
        if m:
            return mcid.normalise(m.group(1)), asset_type
    return None


def _iter_dir(root: Path) -> Iterator[tuple[str, int]]:
    for p in root.rglob("*"):
        if p.is_file():
            yield str(p.relative_to(root)), p.stat().st_size


def _iter_zip(zf: zipfile.ZipFile) -> Iterator[tuple[str, int]]:
    for info in zf.infolist():
        if not info.is_dir():
            yield info.filename, info.file_size


def scan(source_path: str | Path, source_label: str | None = None) -> list[AssetRecord]:
    """Scan a directory or .zip archive, returning every recognised
    asset as an AssetRecord. Unrecognised files are silently skipped —
    this is a targeted scan of known Minecraft asset shapes, not a
    generic file lister."""
    source_path = Path(source_path)
    label = source_label or source_path.name
    records: list[AssetRecord] = []

    if source_path.is_dir():
        entries: Iterable[tuple[str, int]] = _iter_dir(source_path)
    elif source_path.is_file():
        if not zipfile.is_zipfile(source_path):
            raise ValueError(f"not a valid .zip archive (file exists but its contents aren't a zip): {source_path}")
        with zipfile.ZipFile(source_path) as zf:
            entries = list(_iter_zip(zf))
    else:
        raise FileNotFoundError(f"source path does not exist: {source_path}")

    for rel_path, size in entries:
        found = classify(rel_path)
        if found is None:
            continue
        asset_id, asset_type = found
        if not asset_id:
            continue
        records.append(AssetRecord(id=asset_id, type=asset_type, path=PurePosixPath(rel_path).as_posix(),
                                    source=label, size=size))
    return records


def open_binary(source_path: str | Path, record: AssetRecord) -> bytes:
    """Read one asset's raw bytes back out of its source — used by
    diff.py when a pixel-level comparison is actually warranted, kept
    separate from scan() so a plain metadata scan never touches file
    contents."""
    source_path = Path(source_path)
    if source_path.is_dir():
        return (source_path / record.path).read_bytes()
    with zipfile.ZipFile(source_path) as zf:
        return zf.read(record.path)


def index_by_id(records: Iterable[AssetRecord]) -> dict[tuple[str, str], AssetRecord]:
    """(id, type) -> record, for O(1) lookups in diff/validate."""
    return {(r.id, r.type): r for r in records}
