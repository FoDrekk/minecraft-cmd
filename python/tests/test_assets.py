import tempfile
import unittest
import zipfile
from pathlib import Path

from minecraft_engine import assets


def _touch(path: Path, content: bytes = b"\x89PNG\r\n\x1a\nfake") -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_bytes(content)


class ClassifyTests(unittest.TestCase):
    def test_flat_layout_block_texture(self):
        self.assertEqual(assets.classify("block/stone.png"), ("stone", "block_texture"))

    def test_flat_layout_item_texture(self):
        self.assertEqual(assets.classify("item/diamond_sword.png"), ("diamond_sword", "item_texture"))

    def test_resource_pack_layout_block_texture(self):
        self.assertEqual(
            assets.classify("assets/minecraft/textures/block/stone.png"),
            ("stone", "block_texture"))

    def test_resource_pack_layout_item_definition(self):
        self.assertEqual(
            assets.classify("assets/minecraft/items/mace.json"),
            ("mace", "item_definition"))

    def test_resource_pack_layout_blockstate(self):
        self.assertEqual(
            assets.classify("assets/minecraft/blockstates/oak_stairs.json"),
            ("oak_stairs", "blockstate"))

    def test_block_and_item_model_are_distinct(self):
        self.assertEqual(assets.classify("assets/minecraft/models/block/stone.json")[1], "block_model")
        self.assertEqual(assets.classify("assets/minecraft/models/item/apple.json")[1], "item_model")

    def test_unrecognised_file_returns_none(self):
        self.assertIsNone(assets.classify("pack.mcmeta"))
        self.assertIsNone(assets.classify("assets/minecraft/lang/en_us.json"))

    def test_a_top_level_folder_named_1_20_1_file_does_not_confuse_classification(self):
        # The real supplied archive's root folder is literally "1.20.1
        # file/" (with a space) — the scanner must not choke on it.
        self.assertEqual(
            assets.classify("1.20.1 file/block/dirt.png"),
            ("dirt", "block_texture"))


class ScanDirectoryTests(unittest.TestCase):
    def test_scans_a_flat_layout_directory(self):
        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            _touch(root / "block" / "stone.png")
            _touch(root / "item" / "apple.png")
            _touch(root / "pack.mcmeta", b"{}")  # not a recognised asset
            records = assets.scan(root, "test-source")
        ids = {(r.id, r.type) for r in records}
        self.assertEqual(ids, {("stone", "block_texture"), ("apple", "item_texture")})
        self.assertTrue(all(r.source == "test-source" for r in records))

    def test_scans_a_resource_pack_layout_directory(self):
        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            _touch(root / "assets" / "minecraft" / "textures" / "block" / "stone.png")
            _touch(root / "assets" / "minecraft" / "items" / "mace.json", b"{}")
            _touch(root / "assets" / "minecraft" / "blockstates" / "oak_door.json", b"{}")
            records = assets.scan(root, "test-source")
        types = {r.type for r in records}
        self.assertEqual(types, {"block_texture", "item_definition", "blockstate"})

    def test_default_label_is_the_source_name(self):
        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp) / "1.20.1 file"
            _touch(root / "block" / "stone.png")
            records = assets.scan(root)
        self.assertEqual(records[0].source, "1.20.1 file")

    def test_missing_path_raises_file_not_found(self):
        with self.assertRaises(FileNotFoundError):
            assets.scan("/nonexistent/path/that/does/not/exist")

    def test_malformed_zip_raises_value_error_not_file_not_found(self):
        # A file that exists but isn't a valid archive is a different
        # failure than a missing path — distinguished so a corrupted
        # download doesn't get misreported as "not found".
        with tempfile.TemporaryDirectory() as tmp:
            bad = Path(tmp) / "corrupt.zip"
            bad.write_bytes(b"not actually a zip file")
            with self.assertRaises(ValueError):
                assets.scan(bad)


class ScanZipTests(unittest.TestCase):
    def test_scans_a_zip_archive_the_same_way_as_a_directory(self):
        with tempfile.TemporaryDirectory() as tmp:
            zip_path = Path(tmp) / "source.zip"
            with zipfile.ZipFile(zip_path, "w") as zf:
                zf.writestr("1.20.1 file/block/stone.png", b"fake-png-bytes")
                zf.writestr("1.20.1 file/item/apple.png", b"fake-png-bytes")
            records = assets.scan(zip_path, "1.20.1")
        ids = {(r.id, r.type) for r in records}
        self.assertEqual(ids, {("stone", "block_texture"), ("apple", "item_texture")})

    def test_open_binary_reads_back_the_same_bytes_for_dir_and_zip(self):
        content = b"hello-texture-bytes"
        with tempfile.TemporaryDirectory() as tmp:
            dir_root = Path(tmp) / "dir_source"
            _touch(dir_root / "block" / "stone.png", content)
            dir_records = assets.scan(dir_root, "dir")

            zip_path = Path(tmp) / "zip_source.zip"
            with zipfile.ZipFile(zip_path, "w") as zf:
                zf.writestr("block/stone.png", content)
            zip_records = assets.scan(zip_path, "zip")

            self.assertEqual(assets.open_binary(dir_root, dir_records[0]), content)
            self.assertEqual(assets.open_binary(zip_path, zip_records[0]), content)

    def test_path_traversal_entry_names_are_never_followed(self):
        # A zip entry named e.g. "../../../etc/passwd" must never be
        # written anywhere or otherwise escape the archive — the
        # scanner only ever reads entries into memory (never extracts
        # to disk), and a traversal-shaped name simply doesn't match
        # any known asset pattern, so it's silently skipped like any
        # other unrecognised file.
        with tempfile.TemporaryDirectory() as tmp:
            zip_path = Path(tmp) / "traversal.zip"
            with zipfile.ZipFile(zip_path, "w") as zf:
                zf.writestr("../../../etc/passwd", b"malicious")
                zf.writestr("block/stone.png", b"real")
            records = assets.scan(zip_path, "trav")
        self.assertEqual(len(records), 1)
        self.assertEqual(records[0].id, "stone")

    def test_unicode_only_filename_normalises_to_empty_and_is_skipped(self):
        # A filename with no [a-z0-9_] characters at all collapses to
        # an empty id after normalisation — scan() must drop it rather
        # than emit a record with a blank id.
        with tempfile.TemporaryDirectory() as tmp:
            zip_path = Path(tmp) / "unicode.zip"
            with zipfile.ZipFile(zip_path, "w") as zf:
                zf.writestr("block/日本語.png", b"x")
            records = assets.scan(zip_path, "unicode")
        self.assertEqual(records, [])


if __name__ == "__main__":
    unittest.main()
