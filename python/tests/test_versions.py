import json
import shutil
import tempfile
import unittest
from pathlib import Path

from minecraft_engine import versions

SAMPLE = {
    "versions": {
        "1.20.1": {"label": "1.20.1", "name": "Trails & Tales", "edition": "java",
                    "syntax": "legacy_nbt", "rank": 35, "released": "2023-06-12"},
        "1.21.1": {"label": "1.21.1", "name": "Tricky Trials", "edition": "java",
                    "syntax": "component_json", "rank": 55, "released": "2024-08-08"},
        "bedrock": {"label": "Bedrock", "name": "Current release", "edition": "bedrock",
                     "syntax": "bedrock", "rank": 10, "released": ""},
    },
    "features": {"mace": 55, "modern_textures": 50},
    "default_version": "1.21.1",
}


class FromDictTests(unittest.TestCase):
    def setUp(self):
        self.table = versions.from_dict(SAMPLE)

    def test_loads_all_versions(self):
        self.assertEqual(len(self.table), 3)
        self.assertIn("1.20.1", self.table)

    def test_rank_of_known_version(self):
        self.assertEqual(self.table.rank_of("1.21.1"), 55)

    def test_rank_of_unknown_version_is_zero(self):
        self.assertEqual(self.table.rank_of("9.9.9-nonexistent"), 0)

    def test_has_feature_true_above_min_rank(self):
        self.assertTrue(self.table.has_feature("1.21.1", "mace"))

    def test_has_feature_false_below_min_rank(self):
        self.assertFalse(self.table.has_feature("1.20.1", "mace"))

    def test_has_feature_unknown_feature_is_false(self):
        self.assertFalse(self.table.has_feature("1.21.1", "not_a_real_feature"))

    def test_lowest_satisfying_finds_oldest_qualifying_java_version(self):
        v = self.table.lowest_satisfying(50)
        self.assertEqual(v.id, "1.21.1")

    def test_lowest_satisfying_excludes_bedrock_by_default(self):
        # Bedrock's rank (10) would "satisfy" a very low bar, but a
        # "needs Java X+" label must never suggest Bedrock.
        v = self.table.lowest_satisfying(0)
        self.assertNotEqual(v.id, "bedrock")

    def test_is_java_property(self):
        self.assertTrue(self.table.get("1.20.1").is_java)
        self.assertFalse(self.table.get("bedrock").is_java)

    def test_sorted_by_rank_newest_first(self):
        ids = [v.id for v in self.table.sorted_by_rank()]
        self.assertEqual(ids, ["1.21.1", "1.20.1", "bedrock"])


class FromJsonFileTests(unittest.TestCase):
    def test_round_trips_through_a_file(self):
        with tempfile.TemporaryDirectory() as tmp:
            path = Path(tmp) / "versions.json"
            path.write_text(json.dumps(SAMPLE))
            table = versions.from_json_file(path)
            self.assertEqual(len(table), 3)
            self.assertEqual(table.rank_of("1.21.1"), 55)


@unittest.skipUnless(shutil.which("php"), "php not on PATH — skipping the live PHP bridge test")
class FromPhpIntegrationTests(unittest.TestCase):
    """Proves the actual bridge (tools/export_mc_data.php) works, not
    just the JSON-parsing half of from_php()."""

    def test_loads_the_real_lib_mc_php_table(self):
        table = versions.from_php()
        self.assertIn("1.20.1", table)
        self.assertIn("26.2", table)
        self.assertGreater(len(table), 5)
        # mace is a real, already-established feature gate in lib/mc.php
        self.assertTrue(table.has_feature("1.21.1", "mace"))
        self.assertFalse(table.has_feature("1.20.1", "mace"))


if __name__ == "__main__":
    unittest.main()
