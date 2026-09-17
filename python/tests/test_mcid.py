import unittest

from minecraft_engine import mcid


class NormaliseTests(unittest.TestCase):
    def test_strips_namespace(self):
        self.assertEqual(mcid.normalise("minecraft:diamond_sword"), "diamond_sword")

    def test_lowercases(self):
        self.assertEqual(mcid.normalise("Diamond Sword"), "diamond_sword")

    def test_collapses_punctuation(self):
        self.assertEqual(mcid.normalise("oak-stairs!!.png"), "oak_stairs_png")

    def test_trims_underscores(self):
        self.assertEqual(mcid.normalise("__stone__"), "stone")

    def test_matches_php_texture_normalise_id_examples(self):
        # Same worked examples lib/textures.php's textureNormaliseId()
        # is documented against.
        cases = {
            "minecraft:diamond_sword": "diamond_sword",
            "Diamond Sword": "diamond_sword",
            "  grass_block  ": "grass_block",
        }
        for raw, expected in cases.items():
            self.assertEqual(mcid.normalise(raw), expected)


class ValidNamespacedIdTests(unittest.TestCase):
    def test_bare_id_is_valid(self):
        self.assertTrue(mcid.is_valid_namespaced_id("diamond_sword"))

    def test_namespaced_id_is_valid(self):
        self.assertTrue(mcid.is_valid_namespaced_id("minecraft:diamond_sword"))

    def test_nested_path_is_valid(self):
        self.assertTrue(mcid.is_valid_namespaced_id("block/oak_stairs"))

    def test_empty_is_invalid(self):
        self.assertFalse(mcid.is_valid_namespaced_id(""))
        self.assertFalse(mcid.is_valid_namespaced_id("   "))

    def test_illegal_characters_invalid(self):
        self.assertFalse(mcid.is_valid_namespaced_id("diamond sword!!"))
        self.assertFalse(mcid.is_valid_namespaced_id("diamond:sword:extra"))


class SplitNamespaceTests(unittest.TestCase):
    def test_explicit_namespace(self):
        self.assertEqual(mcid.split_namespace("minecraft:stone"), ("minecraft", "stone"))

    def test_implicit_namespace(self):
        self.assertEqual(mcid.split_namespace("stone"), ("minecraft", "stone"))


if __name__ == "__main__":
    unittest.main()
