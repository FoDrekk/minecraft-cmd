"""Ties assets.py and versions.py together: asset status, the app's
own live texture manifest (for cross-referencing, never for editing),
and the generated-data writer.

IMPORTANT BOUNDARY: this module reads assets/textures/{item,block}/ to
know what the running app already ships, and it WRITES ONLY under a
caller-given output directory (data/generated/ by convention). It
never writes into assets/, lib/, or lib/data/ — those stay
hand-maintained PHP, and nothing here has the authority to replace
them. See python/README.md for why.
"""
from __future__ import annotations

import json
from pathlib import Path
from typing import Iterable

from . import mcid
from .assets import AssetRecord
from .versions import VersionTable

_REPO_ROOT = Path(__file__).resolve().parents[2]
_APP_TEXTURE_DIRS = {
    "item": _REPO_ROOT / "assets" / "textures" / "item",
    "block": _REPO_ROOT / "assets" / "textures" / "block",
}
_IMAGE_EXTS = (".png", ".webp")


def app_texture_manifest() -> dict[str, dict[str, str]]:
    """What the live PHP app currently ships, read the same way
    lib/textures.php's texturesManifest() does: bare filename -> id,
    first extension found wins, plus the `_modern/` override layer
    kept as its own sub-key. Read-only — this never writes here."""
    manifest: dict[str, dict[str, str]] = {}
    for kind, directory in _APP_TEXTURE_DIRS.items():
        found: dict[str, str] = {}
        modern: dict[str, str] = {}
        if directory.is_dir():
            for entry in sorted(directory.iterdir()):
                if entry.is_file() and entry.suffix.lower() in _IMAGE_EXTS:
                    found.setdefault(mcid.normalise(entry.stem), entry.name)
            modern_dir = directory / "_modern"
            if modern_dir.is_dir():
                for entry in sorted(modern_dir.iterdir()):
                    if entry.is_file() and entry.suffix.lower() in _IMAGE_EXTS:
                        modern.setdefault(mcid.normalise(entry.stem), entry.name)
        manifest[kind] = {"found": found, "modern": modern}
    return manifest


def texture_status(
    asset_id: str, kind: str, min_rank: int, version_id: str | None,
    version_table: VersionTable, manifest: dict[str, dict[str, str]] | None = None,
) -> dict:
    """FOUND / MISSING / UNAVAILABLE_FOR_VERSION for one id — the same
    three states lib/textures.php's textureStatus() answers, computed
    the same way: existence-for-version first (registry data), then
    file presence (the app's shipped texture manifest)."""
    manifest = manifest if manifest is not None else app_texture_manifest()
    norm = mcid.normalise(asset_id)
    version_rank = version_table.rank_of(version_id) if version_id else max(
        (v.rank for v in version_table.versions.values()), default=0)

    if min_rank > version_rank:
        return {"status": "unavailable_for_version", "path": None}

    kind_manifest = manifest.get(kind, {"found": {}, "modern": {}})
    has_modern = version_table.has_feature(version_id, "modern_textures") if version_id else False
    if has_modern and norm in kind_manifest.get("modern", {}):
        return {"status": "found", "path": f"assets/textures/{kind}/_modern/{kind_manifest['modern'][norm]}"}
    if norm in kind_manifest.get("found", {}):
        return {"status": "found", "path": f"assets/textures/{kind}/{kind_manifest['found'][norm]}"}
    return {"status": "missing", "path": None}


def _asset_dict(record: AssetRecord) -> dict:
    # Delegates to AssetRecord.as_dict() rather than re-shaping the
    # record by hand, minus `size` (an internal scan detail with no
    # use downstream yet) to keep the generated files' shape minimal.
    d = record.as_dict()
    del d["size"]
    return d


def build_asset_document(records: Iterable[AssetRecord]) -> dict[str, list[dict]]:
    """Group scanned records by broad asset kind (the leading part of
    their type, e.g. 'block_texture' -> 'block'), sorted deterministically
    (by id, then type) so the same input always serializes identically —
    required for the "running it twice gives the same output" guarantee."""
    grouped: dict[str, list[dict]] = {}
    for r in records:
        bucket = r.type.split("_", 1)[0]
        grouped.setdefault(bucket, []).append(_asset_dict(r))
    for bucket in grouped:
        grouped[bucket].sort(key=lambda d: (d["id"], d["type"], d["texture"]))
    return grouped


def write_generated(
    out_dir: str | Path,
    version_table: VersionTable,
    records: Iterable[AssetRecord],
    diff_entries: Iterable[dict] | None = None,
) -> dict[str, Path]:
    """Write the generated/*.json files. Deterministic: sorted keys,
    sorted lists, no timestamps or non-reproducible fields in the
    payload. Refuses to write anywhere but out_dir — callers decide
    where that is, but this function does not default to, or ever
    touch, assets/ or lib/."""
    out_dir = Path(out_dir)
    out_dir.mkdir(parents=True, exist_ok=True)
    written: dict[str, Path] = {}

    def dump(name: str, payload) -> None:
        path = out_dir / name
        path.write_text(json.dumps(payload, indent=2, sort_keys=True, ensure_ascii=False) + "\n", encoding="utf-8")
        written[name] = path

    versions_payload = {
        vid: {"label": v.label, "name": v.name, "edition": v.edition, "syntax": v.syntax,
              "rank": v.rank, "released": v.released}
        for vid, v in version_table.versions.items()
    }
    dump("versions.json", {"versions": versions_payload, "features": version_table.features,
                            "default_version": version_table.default_version})

    grouped = build_asset_document(records)
    dump("blocks.json", grouped.get("block", []))
    dump("items.json", grouped.get("item", []))
    dump("entities.json", grouped.get("entity", []))

    all_assets = [r for r in records]
    dump("textures.json", [_asset_dict(r) for r in sorted(
        (r for r in all_assets if r.type.endswith("_texture")),
        key=lambda r: (r.id, r.type))])

    if diff_entries is not None:
        dump("version-diff.json", sorted(diff_entries, key=lambda d: (d["id"], d["type"])))

    return written
