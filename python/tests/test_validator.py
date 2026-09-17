import unittest

from minecraft_engine.assets import AssetRecord
from minecraft_engine.validator import (
    check_cross_category_ids,
    check_duplicate_ids,
    check_malformed_ids,
    check_orphan_definitions,
    check_version_references,
    validate,
)
from minecraft_engine.versions import from_dict

VTABLE = from_dict({
    "versions": {"1.20.1": {"label": "1.20.1", "rank": 35, "edition": "java", "syntax": "legacy_nbt", "name": "", "released": ""}},
    "features": {},
})


def rec(id_, type_, path, source="S"):
    return AssetRecord(id=id_, type=type_, path=path, source=source)


class DuplicateIdTests(unittest.TestCase):
    def test_two_files_same_id_and_type_flagged_as_error(self):
        # This is the REAL duplicate case the validator must still
        # catch hard: same asset type + same canonical id, two files.
        recs = [rec("stone", "block_texture", "block/stone.png"),
                rec("stone", "block_texture", "block/stone.webp")]
        issues = check_duplicate_ids(recs)
        self.assertEqual(len(issues), 1)
        self.assertEqual(issues[0].code, "duplicate_id")
        self.assertEqual(issues[0].severity, "error")

    def test_same_id_different_type_not_flagged(self):
        # block vs item textures for the same id is the app's normal,
        # intentional split — not a duplicate.
        recs = [rec("dirt", "block_texture", "block/dirt.png"),
                rec("dirt", "item_texture", "item/dirt.png")]
        self.assertEqual(check_duplicate_ids(recs), [])

    def test_unique_ids_not_flagged(self):
        recs = [rec("stone", "block_texture", "block/stone.png"),
                rec("dirt", "block_texture", "block/dirt.png")]
        self.assertEqual(check_duplicate_ids(recs), [])


class MalformedIdTests(unittest.TestCase):
    def test_clean_filename_not_flagged(self):
        recs = [rec("diamond_sword", "item_texture", "item/diamond_sword.png")]
        self.assertEqual(check_malformed_ids(recs), [])

    def test_punctuation_in_filename_flagged(self):
        recs = [rec("diamond_sword", "item_texture", "item/diamond sword!!.png")]
        issues = check_malformed_ids(recs)
        self.assertEqual(len(issues), 1)
        self.assertEqual(issues[0].severity, "error")


class CrossCategoryIdTests(unittest.TestCase):
    """A Minecraft asset's real identity is (type, id), not id alone —
    the same word legitimately names unrelated assets in different
    categories. None of these examples should ever become a hard
    ERROR just because two files share a filename."""

    def test_block_and_item_split_is_info_not_error(self):
        # The app's own well-documented placed-appearance vs.
        # inventory-icon split.
        recs = [rec("dirt", "block_texture", "block/dirt.png"),
                rec("dirt", "item_texture", "item/dirt.png")]
        issues = check_cross_category_ids(recs)
        self.assertEqual(len(issues), 1)
        self.assertEqual(issues[0].severity, "info")

    def test_entity_and_item_split_is_info_not_error(self):
        # Render skin (e.g. an in-air trident model) vs. inventory icon
        # — the second well-established, certain pattern.
        recs = [rec("trident", "entity_texture", "entity/trident.png"),
                rec("trident", "item_texture", "item/trident.png")]
        issues = check_cross_category_ids(recs)
        self.assertEqual(len(issues), 1)
        self.assertEqual(issues[0].severity, "info")

    def test_item_and_gui_split_is_warning_not_error(self):
        # item/book (inventory icon) vs gui/book (writable-book screen
        # texture) — two real, unrelated assets, not a conflict.
        recs = [rec("book", "item_texture", "textures/item/book.png"),
                rec("book", "gui_texture", "textures/gui/book.png")]
        issues = check_cross_category_ids(recs)
        self.assertEqual(len(issues), 1)
        self.assertEqual(issues[0].severity, "warning")
        self.assertNotEqual(issues[0].severity, "error")

    def test_mob_effect_and_painting_split_is_warning_not_error(self):
        # mob_effect/wither (status effect icon) vs painting/wither (a
        # real Minecraft painting titled "Wither") — coincidental name
        # reuse across two completely unrelated asset kinds.
        recs = [rec("wither", "mob_effect_texture", "textures/mob_effect/wither.png"),
                rec("wither", "painting_texture", "textures/painting/wither.png")]
        issues = check_cross_category_ids(recs)
        self.assertEqual(len(issues), 1)
        self.assertEqual(issues[0].severity, "warning")
        self.assertNotEqual(issues[0].severity, "error")

    def test_block_and_environment_split_is_warning_not_error(self):
        # block/snow (the snow block) vs environment/snow (the falling
        # snow weather overlay) — different assets, same word.
        recs = [rec("snow", "block_texture", "textures/block/snow.png"),
                rec("snow", "environment_texture", "textures/environment/snow.png")]
        issues = check_cross_category_ids(recs)
        self.assertEqual(len(issues), 1)
        self.assertEqual(issues[0].severity, "warning")
        self.assertNotEqual(issues[0].severity, "error")

    def test_none_of_these_are_ever_reported_as_error(self):
        # Blanket regression guard: whatever the severity split looks
        # like, a filename-only cross-category collision must never
        # reach ERROR — only a real (type, id) duplicate can.
        recs = [
            rec("book", "item_texture", "item/book.png"),
            rec("book", "gui_texture", "gui/book.png"),
            rec("wither", "mob_effect_texture", "mob_effect/wither.png"),
            rec("wither", "painting_texture", "painting/wither.png"),
            rec("snow", "block_texture", "block/snow.png"),
            rec("snow", "environment_texture", "environment/snow.png"),
        ]
        issues = check_cross_category_ids(recs)
        self.assertTrue(issues, "expected these collisions to still be reported, just not as errors")
        self.assertTrue(all(i.severity != "error" for i in issues))

    def test_three_way_collision_still_a_single_warning(self):
        recs = [rec("thing", "block_texture", "block/thing.png"),
                rec("thing", "misc_texture", "misc/thing.png"),
                rec("thing", "gui_texture", "gui/thing.png")]
        issues = check_cross_category_ids(recs)
        self.assertEqual(len(issues), 1)
        self.assertEqual(issues[0].severity, "warning")

    def test_single_category_id_not_flagged(self):
        recs = [rec("stone", "block_texture", "block/stone.png")]
        self.assertEqual(check_cross_category_ids(recs), [])


class OrphanDefinitionTests(unittest.TestCase):
    def test_definition_with_matching_texture_not_flagged(self):
        recs = [rec("apple", "item_texture", "textures/item/apple.png"),
                rec("apple", "item_definition", "items/apple.json")]
        self.assertEqual(check_orphan_definitions(recs), [])

    def test_definition_with_no_matching_texture_flagged(self):
        recs = [rec("mace", "item_definition", "items/mace.json")]
        issues = check_orphan_definitions(recs)
        self.assertEqual(len(issues), 1)
        self.assertEqual(issues[0].severity, "info")

    def test_plain_textures_are_never_flagged_as_orphans(self):
        # orphan-checking only applies to definitions, not to textures
        # themselves (a texture-only archive is a legitimate layout).
        recs = [rec("stone", "block_texture", "block/stone.png")]
        self.assertEqual(check_orphan_definitions(recs), [])


class VersionReferenceTests(unittest.TestCase):
    def test_known_version_not_flagged(self):
        recs = [rec("stone", "block_texture", "block/stone.png", source="1.20.1")]
        issues = check_version_references(recs, VTABLE, {"1.20.1": "1.20.1"})
        self.assertEqual(issues, [])

    def test_unknown_version_flagged(self):
        recs = [rec("stone", "block_texture", "block/stone.png", source="mystery")]
        issues = check_version_references(recs, VTABLE, {"mystery": "9.9.9-nonexistent"})
        self.assertEqual(len(issues), 1)
        self.assertEqual(issues[0].code, "invalid_version_reference")

    def test_no_source_versions_mapping_means_no_checks(self):
        recs = [rec("stone", "block_texture", "block/stone.png", source="mystery")]
        self.assertEqual(check_version_references(recs, VTABLE, None), [])


class ValidateCombinesAllChecksTests(unittest.TestCase):
    def test_returns_sorted_issues_from_multiple_checks(self):
        _ORDER = {"error": 0, "warning": 1, "info": 2}
        recs = [
            rec("stone", "block_texture", "block/stone.png"),
            rec("stone", "block_texture", "block/stone.webp"),          # duplicate -> error
            rec("bad name!!", "item_texture", "item/bad name!!.png"),   # malformed -> error
            rec("book", "item_texture", "item/book.png"),               # cross-category -> warning
            rec("book", "gui_texture", "gui/book.png"),
            rec("mace", "item_definition", "items/mace.json"),          # orphan -> info
        ]
        issues = validate(recs)
        codes = {i.code for i in issues}
        self.assertIn("duplicate_id", codes)
        self.assertIn("malformed_id", codes)
        self.assertIn("cross_category_id_reuse", codes)
        self.assertIn("orphan_definition", codes)
        # error, then warning, then info — never a later tier before an earlier one
        ranks = [_ORDER[i.severity] for i in issues]
        self.assertEqual(ranks, sorted(ranks))
        # and the specific duplicate/malformed errors are never demoted
        error_codes = {i.code for i in issues if i.severity == "error"}
        self.assertEqual(error_codes, {"duplicate_id", "malformed_id"})


if __name__ == "__main__":
    unittest.main()
