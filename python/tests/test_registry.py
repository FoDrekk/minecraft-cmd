import tempfile
import unittest
from pathlib import Path

from minecraft_engine.assets import AssetRecord
from minecraft_engine.registry import texture_status, write_generated
from minecraft_engine.versions import from_dict

VTABLE = from_dict({
    "versions": {
        "1.20.1": {"label": "1.20.1", "rank": 35, "edition": "java", "syntax": "legacy_nbt", "name": "", "released": ""},
        "1.21.1": {"label": "1.21.1", "rank": 55, "edition": "java", "syntax": "component_json", "name": "", "released": ""},
    },
    "features": {"mace": 55, "modern_textures": 50},
    "default_version": "1.21.1",
})

MANIFEST = {
    "item": {"found": {"mace": "mace.png", "apple": "apple.png"}, "modern": {}},
    "block": {"found": {"stone": "stone.png"}, "modern": {"candle": "candle.png"}},
}


class TextureStatusTests(unittest.TestCase):
    def test_found_when_available_and_no_version_gate(self):
        status = texture_status("apple", "item", 0, "1.20.1", VTABLE, MANIFEST)
        self.assertEqual(status["status"], "found")
        self.assertEqual(status["path"], "assets/textures/item/apple.png")

    def test_unavailable_for_version_when_min_rank_not_met(self):
        # mace itself has a texture, but doesn't exist as an item until
        # rank 55 — mirrors the real mace/1.20.1 case in the live app.
        status = texture_status("mace", "item", 55, "1.20.1", VTABLE, MANIFEST)
        self.assertEqual(status["status"], "unavailable_for_version")
        self.assertIsNone(status["path"])

    def test_found_once_version_meets_min_rank(self):
        status = texture_status("mace", "item", 55, "1.21.1", VTABLE, MANIFEST)
        self.assertEqual(status["status"], "found")

    def test_missing_when_no_texture_file_at_all(self):
        status = texture_status("nonexistent_item", "item", 0, "1.20.1", VTABLE, MANIFEST)
        self.assertEqual(status["status"], "missing")
        self.assertIsNone(status["path"])

    def test_modern_variant_preferred_when_version_supports_it(self):
        status = texture_status("candle", "block", 0, "1.21.1", VTABLE, MANIFEST)
        self.assertEqual(status["path"], "assets/textures/block/_modern/candle.png")

    def test_base_texture_used_when_version_predates_modern_textures(self):
        status = texture_status("candle", "block", 0, "1.20.1", VTABLE, MANIFEST)
        # 1.20.1 predates modern_textures (rank 50) and there is no base
        # "candle" entry in this fixture manifest's block.found map, so
        # it correctly falls through to missing rather than the modern one.
        self.assertNotEqual(status["path"], "assets/textures/block/_modern/candle.png")

    def test_base_texture_still_resolves_for_an_id_with_no_modern_override(self):
        status = texture_status("stone", "block", 0, "1.21.1", VTABLE, MANIFEST)
        self.assertEqual(status["path"], "assets/textures/block/stone.png")


class WriteGeneratedDeterminismTests(unittest.TestCase):
    def test_running_twice_on_the_same_input_produces_byte_identical_output(self):
        records = [
            AssetRecord("stone", "block_texture", "block/stone.png", "1.20.1"),
            AssetRecord("apple", "item_texture", "item/apple.png", "1.20.1"),
            AssetRecord("dirt", "block_texture", "block/dirt.png", "1.20.1"),
        ]
        with tempfile.TemporaryDirectory() as tmp:
            out1, out2 = Path(tmp) / "run1", Path(tmp) / "run2"
            written1 = write_generated(out1, VTABLE, records)
            written2 = write_generated(out2, VTABLE, records)
            self.assertEqual(set(written1), set(written2))
            for name in written1:
                self.assertEqual((out1 / name).read_bytes(), (out2 / name).read_bytes(),
                                  f"{name} differed between runs")

    def test_input_order_does_not_affect_output(self):
        records_forward = [
            AssetRecord("stone", "block_texture", "block/stone.png", "1.20.1"),
            AssetRecord("dirt", "block_texture", "block/dirt.png", "1.20.1"),
        ]
        records_reversed = list(reversed(records_forward))
        with tempfile.TemporaryDirectory() as tmp:
            out1, out2 = Path(tmp) / "fwd", Path(tmp) / "rev"
            write_generated(out1, VTABLE, records_forward)
            write_generated(out2, VTABLE, records_reversed)
            self.assertEqual((out1 / "blocks.json").read_bytes(), (out2 / "blocks.json").read_bytes())

    def test_writes_only_inside_the_given_out_dir(self):
        records = [AssetRecord("stone", "block_texture", "block/stone.png", "1.20.1")]
        with tempfile.TemporaryDirectory() as tmp:
            out = Path(tmp) / "nested" / "generated"
            written = write_generated(out, VTABLE, records)
            for path in written.values():
                self.assertTrue(str(path).startswith(str(out)))


if __name__ == "__main__":
    unittest.main()
