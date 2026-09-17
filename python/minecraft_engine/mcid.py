"""Minecraft ID normalization and validation.

The single place the whole engine agrees on "what does a bare id look
like". Deliberately mirrors lib/textures.php's textureNormaliseId() in
the PHP app byte-for-byte, so an id normalised here and one normalised
there always match — this package has no authority to invent its own
notion of identity for something PHP already owns.
"""
from __future__ import annotations

import re

_NAMESPACE_PREFIX = re.compile(r"^minecraft:")
_NON_ID_CHARS = re.compile(r"[^a-z0-9_]+")

# A real Minecraft resource location: `namespace:path`, or a bare path
# (namespace defaults to "minecraft"). Both segments are lowercase
# [a-z0-9_.-], and path may contain '/' for nested ids
# (block/oak_stairs). This is deliberately permissive about what a
# *path* can contain (Mojang's own ids use '.', e.g. some entity
# variants) while staying strict about structure.
_SEGMENT = r"[a-z0-9_.\-]+"
_NAMESPACED_ID = re.compile(rf"^(?:{_SEGMENT}:)?{_SEGMENT}(?:/{_SEGMENT})*$")


def normalise(raw_id: str) -> str:
    """`minecraft:diamond_sword` / `Diamond Sword` -> `diamond_sword`.

    Mirrors lib/textures.php's textureNormaliseId(): lowercase, strip
    a leading `minecraft:` namespace, collapse every run of non
    [a-z0-9_] into a single underscore, then trim leading/trailing
    underscores.
    """
    s = raw_id.strip().lower()
    s = _NAMESPACE_PREFIX.sub("", s)
    s = _NON_ID_CHARS.sub("_", s)
    return s.strip("_")


def is_valid_namespaced_id(raw_id: str) -> bool:
    """Does this look like a real Minecraft resource location?

    Used by validator.py to flag malformed ids — something that isn't
    even shaped like `namespace:path/to/thing` (empty, stray
    whitespace, illegal characters) rather than a game-data question
    ("does this id actually exist").
    """
    s = raw_id.strip()
    if not s:
        return False
    return bool(_NAMESPACED_ID.match(s.lower()))


def split_namespace(raw_id: str) -> tuple[str, str]:
    """`minecraft:diamond_sword` -> ('minecraft', 'diamond_sword').

    A bare id with no `:` is treated as the implicit `minecraft`
    namespace, matching how the game itself resolves unprefixed ids.
    """
    s = raw_id.strip()
    if ":" in s:
        ns, _, path = s.partition(":")
        return ns.lower(), path.lower()
    return "minecraft", s.lower()
