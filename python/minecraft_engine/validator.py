"""Validation checks over a scanned asset set.

## Identity model

A Minecraft visual asset's real identity is **(asset type, canonical
id)**, not the bare id/filename alone — the same word legitimately
names different assets in different categories: `item/book` (the
inventory icon) and `gui/book` (the writable-book screen texture) are
two real, unrelated assets that happen to share an English word,
not two copies of one thing. Every check below keys on (source,
id, type) — never on id in isolation — for exactly this reason. A
same-word collision *across* types is at most worth a human glancing
at (WARNING), never proof of a defect on its own; a real problem is
always expressed as two things claiming the identical (id, type).

Each check is independent and returns plain ValidationIssue objects —
nothing here mutates a scan or "fixes" anything; this module only
reports. Three severities:

  error    the generated data would be wrong or misleading
           (a malformed id, two files silently colliding on the exact
           same (id, type) identity, a version id that doesn't exist)
  warning  potentially suspicious but plausibly valid coexistence
           (an id reused across texture categories the checker has no
           specific reason to already trust — flagged for a human to
           glance at, not blocked)
  info     an expected, already-understood relationship, or a
           definition with no same-id texture in this source (which
           can legitimately reference a different id's texture)
"""
from __future__ import annotations

from dataclasses import dataclass
from pathlib import PurePosixPath
from typing import Iterable

from . import mcid
from .assets import AssetRecord
from .versions import VersionTable

_DEFINITION_TYPES = {"item_definition", "blockstate", "block_model", "item_model",
                      "equipment_definition", "particle_definition"}
_STRICT_ID = __import__("re").compile(r"^[a-z0-9_]+$")

_SEVERITY_ORDER = {"error": 0, "warning": 1, "info": 2}


@dataclass(frozen=True)
class ValidationIssue:
    severity: str   # 'error' | 'warning' | 'info'
    code: str
    message: str
    id: str = ""
    type: str = ""
    source: str = ""

    def as_dict(self) -> dict:
        return {"severity": self.severity, "code": self.code, "message": self.message,
                "id": self.id, "type": self.type, "source": self.source}


def check_duplicate_ids(records: Iterable[AssetRecord]) -> list[ValidationIssue]:
    """More than one file claiming the exact same (id, type) — the
    real canonical identity — within one source. This is a genuine
    problem, not a filename coincidence: the second file silently
    loses in any 'first match wins' manifest, so a build could ship
    the wrong art with no warning."""
    seen: dict[tuple[str, str, str], list[str]] = {}
    for r in records:
        seen.setdefault((r.source, r.id, r.type), []).append(r.path)
    issues = []
    for (source, asset_id, asset_type), paths in seen.items():
        if len(paths) > 1:
            issues.append(ValidationIssue(
                "error", "duplicate_id",
                f"{len(paths)} files resolve to the same (id, type): {', '.join(sorted(paths))}",
                asset_id, asset_type, source))
    return issues


def check_malformed_ids(records: Iterable[AssetRecord]) -> list[ValidationIssue]:
    """The raw filename (before normalisation) contains characters
    outside a real Minecraft id's alphabet — normalise() silently
    sanitises these, which is the right thing for resolving a texture,
    but silently accepting a mangled filename into generated data
    without ever flagging it would hide a genuinely malformed source
    file."""
    issues = []
    for r in records:
        stem = PurePosixPath(r.path).stem.lower()
        if not _STRICT_ID.match(stem):
            issues.append(ValidationIssue(
                "error", "malformed_id",
                f"filename '{stem}' has characters outside [a-z0-9_] — normalised to '{r.id}'",
                r.id, r.type, r.source))
    return issues


def check_cross_category_ids(records: Iterable[AssetRecord]) -> list[ValidationIssue]:
    """The same bare id appearing in more than one *texture* category
    within one source — e.g. `item/book` and `gui/book`, or
    `mob_effect/wither` and `painting/wither`.

    This is NOT automatically wrong. Minecraft's own asset ids are
    unique only *within* a category, not globally: two genuinely
    different, unrelated assets routinely share an English word
    (a status effect and a painting; a block and its biome colormap;
    an entity's render skin and its dropped item icon). The asset's
    real identity is (type, id) together — see this module's
    docstring — so a same-id-different-type "collision" is only ever
    a coincidence in the human-readable name, never an ambiguity in
    how the app actually addresses the two assets.

    Two well-established, certain patterns (documented by the app
    itself: a block's placed appearance vs. its inventory icon, and an
    entity's render skin vs. its inventory icon) are reported as INFO
    — expected, not surprising. Every other combination is reported as
    WARNING: worth a human's glance since it's new/unrecognised to
    this checker, but never blocked as an error on filename evidence
    alone, per the false positives this produced before this file's
    identity model was corrected to (type, id)."""
    _EXPECTED_SPLITS = (
        frozenset({"block_texture", "item_texture"}),   # placed appearance vs. inventory icon
        frozenset({"entity_texture", "item_texture"}),  # render skin vs. inventory icon
    )

    by_id: dict[tuple[str, str], set[str]] = {}
    for r in records:
        if r.type.endswith("_texture"):
            by_id.setdefault((r.source, r.id), set()).add(r.type)
    issues = []
    for (source, asset_id), types in by_id.items():
        if len(types) <= 1:
            continue
        joined = ", ".join(sorted(types))
        if frozenset(types) in _EXPECTED_SPLITS:
            issues.append(ValidationIssue(
                "info", "cross_category_id_reuse",
                f"id appears in multiple texture categories ({joined}) — the "
                "expected placed-appearance/render-skin vs. inventory-icon split, not a conflict",
                asset_id, "/".join(sorted(types)), source))
        else:
            issues.append(ValidationIssue(
                "warning", "cross_category_id_reuse",
                f"id appears in multiple texture categories in the same source ({joined}) — "
                "likely two distinct, unrelated assets that happen to share a name "
                "(verify before assuming a conflict; (type, id) is this asset's real identity, not id alone)",
                asset_id, "/".join(sorted(types)), source))
    return issues


def check_orphan_definitions(records: Iterable[AssetRecord]) -> list[ValidationIssue]:
    """A definition (item/blockstate/model/equipment/particle JSON)
    with no texture of a plausible matching kind anywhere in the same
    source — informational: some definitions legitimately reference
    another id's texture (crossbow's charge-state definitions all
    point at crossbow_* item textures, not a texture of their own id),
    so this flags candidates for a human to look at, not proven bugs."""
    by_source: dict[str, dict[str, set[str]]] = {}
    for r in records:
        by_source.setdefault(r.source, {"textures": set(), "definitions": set()})
        if r.type.endswith("_texture"):
            by_source[r.source]["textures"].add(r.id)
        elif r.type in _DEFINITION_TYPES:
            by_source[r.source]["definitions"].add(r.id)

    issues = []
    for r in records:
        if r.type not in _DEFINITION_TYPES:
            continue
        ids_with_texture = by_source[r.source]["textures"]
        if r.id not in ids_with_texture:
            issues.append(ValidationIssue(
                "info", "orphan_definition",
                f"{r.type} '{r.id}' has no texture of the same id in this source "
                "(may legitimately reference a different id's texture)",
                r.id, r.type, r.source))
    return issues


def check_version_references(
    records: Iterable[AssetRecord], version_table: VersionTable,
    source_versions: dict[str, str] | None = None,
) -> list[ValidationIssue]:
    """If the caller declares which version id each source label
    represents, flag any that isn't a version lib/mc.php actually
    knows about — a typo'd or since-removed version id would otherwise
    silently rank as 0 (oldest) everywhere it's used."""
    if not source_versions:
        return []
    issues = []
    checked: set[str] = set()
    for r in records:
        if r.source in checked:
            continue
        checked.add(r.source)
        version_id = source_versions.get(r.source)
        if version_id is not None and version_id not in version_table:
            issues.append(ValidationIssue(
                "error", "invalid_version_reference",
                f"source '{r.source}' claims version '{version_id}', which is not in MC_VERSIONS",
                type=r.type, source=r.source))
    return issues


def validate(
    records: Iterable[AssetRecord],
    version_table: VersionTable | None = None,
    source_versions: dict[str, str] | None = None,
) -> list[ValidationIssue]:
    """Run every check and return the combined, sorted issue list."""
    records = list(records)
    issues: list[ValidationIssue] = []
    issues += check_duplicate_ids(records)
    issues += check_malformed_ids(records)
    issues += check_cross_category_ids(records)
    issues += check_orphan_definitions(records)
    if version_table is not None:
        issues += check_version_references(records, version_table, source_versions)
    issues.sort(key=lambda i: (_SEVERITY_ORDER[i.severity], i.code, i.id, i.type, i.source))
    return issues


def summarize(issues: Iterable[ValidationIssue]) -> dict[str, int]:
    counts = {"error": 0, "warning": 0, "info": 0}
    for i in issues:
        counts[i.severity] += 1
    return counts
