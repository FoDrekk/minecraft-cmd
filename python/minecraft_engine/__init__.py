"""minecraft_engine — an offline, build-time Minecraft data engine.

This package is NOT a web backend. It never runs alongside the PHP
application, never serves HTTP, and never talks to the app's SQLite
database. Its only job: read authentic Minecraft resource archives,
turn them into small, validated JSON files under data/generated/, and
stop. PHP and JavaScript remain the entire runtime of Minecraft CMD;
if and when they want this data, they read the generated JSON like any
other static file — nothing in this package imports or is imported by
the PHP/JS layers.

See python/README.md for the full picture (why this exists, what each
module owns, how to run it).
"""

__all__ = ["mcid", "versions", "assets", "registry", "diff", "validator"]
