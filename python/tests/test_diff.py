import io
import tempfile
import unittest
import zipfile
from pathlib import Path

from minecraft_engine import assets, diff

try:
    from PIL import Image
    _HAS_PIL = True
except ImportError:
    _HAS_PIL = False


def _png_bytes(color: tuple[int, int, int, int], compress_level: int = 6) -> bytes:
    img = Image.new("RGBA", (4, 4), color)
    buf = io.BytesIO()
    img.save(buf, format="PNG", compress_level=compress_level)
    return buf.getvalue()


def _zip_with(files: dict[str, bytes]) -> Path:
    tmp = tempfile.NamedTemporaryFile(suffix=".zip", delete=False)
    with zipfile.ZipFile(tmp.name, "w") as zf:
        for path, content in files.items():
            zf.writestr(path, content)
    return Path(tmp.name)


class CompareByteIdenticalTests(unittest.TestCase):
    def test_identical_bytes_are_unchanged(self):
        a = _zip_with({"block/stone.png": b"same-bytes"})
        b = _zip_with({"block/stone.png": b"same-bytes"})
        recs_a, recs_b = assets.scan(a, "A"), assets.scan(b, "B")
        entries = diff.compare(a, b, recs_a, recs_b)
        self.assertEqual(len(entries), 1)
        self.assertEqual(entries[0].status, "unchanged")
        self.assertEqual(entries[0].detail, "byte-identical")


class CompareAddedRemovedTests(unittest.TestCase):
    def test_only_in_b_is_added(self):
        a = _zip_with({"item/apple.png": b"x"})
        b = _zip_with({"item/apple.png": b"x", "item/mace.png": b"y"})
        recs_a, recs_b = assets.scan(a, "A"), assets.scan(b, "B")
        entries = diff.compare(a, b, recs_a, recs_b)
        mace = [e for e in entries if e.id == "mace"]
        self.assertEqual(mace[0].status, "added")

    def test_only_in_a_is_removed(self):
        a = _zip_with({"item/apple.png": b"x", "item/old_thing.png": b"y"})
        b = _zip_with({"item/apple.png": b"x"})
        recs_a, recs_b = assets.scan(a, "A"), assets.scan(b, "B")
        entries = diff.compare(a, b, recs_a, recs_b)
        old = [e for e in entries if e.id == "old_thing"]
        self.assertEqual(old[0].status, "removed")

    def test_summarize_counts_each_status(self):
        a = _zip_with({"item/apple.png": b"x", "item/gone.png": b"g"})
        b = _zip_with({"item/apple.png": b"x", "item/mace.png": b"m"})
        recs_a, recs_b = assets.scan(a, "A"), assets.scan(b, "B")
        entries = diff.compare(a, b, recs_a, recs_b)
        counts = diff.summarize(entries)
        self.assertEqual(counts, {"added": 1, "removed": 1, "changed": 0, "unchanged": 1})


@unittest.skipUnless(_HAS_PIL, "Pillow not installed")
class PixelComparisonTests(unittest.TestCase):
    def test_re_encoded_but_pixel_identical_texture_is_unchanged(self):
        # Same colour, deliberately different PNG compression levels —
        # this is exactly the "~95% of overlaps differ by hash but not
        # by pixel" situation found in the two real supplied archives.
        png_a = _png_bytes((120, 80, 40, 255), compress_level=1)
        png_b = _png_bytes((120, 80, 40, 255), compress_level=9)
        self.assertNotEqual(png_a, png_b, "test setup should produce different bytes")

        a = _zip_with({"block/candle.png": png_a})
        b = _zip_with({"block/candle.png": png_b})
        recs_a, recs_b = assets.scan(a, "A"), assets.scan(b, "B")
        entries = diff.compare(a, b, recs_a, recs_b)
        self.assertEqual(entries[0].status, "unchanged")
        self.assertIn("re-encoded", entries[0].detail)

    def test_genuinely_different_pixels_is_changed(self):
        png_a = _png_bytes((120, 80, 40, 255))
        png_b = _png_bytes((10, 200, 10, 255))
        a = _zip_with({"block/candle.png": png_a})
        b = _zip_with({"block/candle.png": png_b})
        recs_a, recs_b = assets.scan(a, "A"), assets.scan(b, "B")
        entries = diff.compare(a, b, recs_a, recs_b)
        self.assertEqual(entries[0].status, "changed")

    def test_pixel_compare_can_be_disabled(self):
        png_a = _png_bytes((120, 80, 40, 255), compress_level=1)
        png_b = _png_bytes((120, 80, 40, 255), compress_level=9)
        a = _zip_with({"block/candle.png": png_a})
        b = _zip_with({"block/candle.png": png_b})
        recs_a, recs_b = assets.scan(a, "A"), assets.scan(b, "B")
        entries = diff.compare(a, b, recs_a, recs_b, pixel_compare=False)
        # Without pixel comparison, differing bytes must be reported as
        # changed even though the artwork is identical.
        self.assertEqual(entries[0].status, "changed")

    def test_non_image_definition_changed_bytes_never_pixel_compared(self):
        a = _zip_with({"assets/minecraft/items/mace.json": b'{"a":1}'})
        b = _zip_with({"assets/minecraft/items/mace.json": b'{"a":2}'})
        recs_a, recs_b = assets.scan(a, "A"), assets.scan(b, "B")
        entries = diff.compare(a, b, recs_a, recs_b)
        self.assertEqual(entries[0].status, "changed")
        self.assertNotIn("pixel", entries[0].detail)


if __name__ == "__main__":
    unittest.main()
