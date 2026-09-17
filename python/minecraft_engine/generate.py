"""CLI entry point: scan source(s), validate, diff (if two sources),
write data/generated/*.json, print a summary.

    python3 -m minecraft_engine.generate --source path/to/1.20.1 \\
        --source-label 1.20.1 --out data/generated

    python3 -m minecraft_engine.generate --source A.zip --source-label 1.20.1 \\
        --source-b B.zip --source-b-label 1.20.5-26.2 --out data/generated

This is the only place the package writes files, and it only ever
writes under --out (default data/generated/) — see registry.py's
module docstring for why that boundary matters.
"""
from __future__ import annotations

import argparse
import sys
from pathlib import Path

from . import assets, diff as diff_mod, registry, validator, versions


def _build_arg_parser() -> argparse.ArgumentParser:
    p = argparse.ArgumentParser(prog="minecraft_engine.generate", description=__doc__)
    p.add_argument("--source", required=True, help="Primary source: a directory or .zip archive")
    p.add_argument("--source-label", default=None, help="Label for --source (default: its filename)")
    p.add_argument("--source-version", default=None,
                   help="Minecraft version id --source represents (e.g. 1.20.1), for version-reference validation")
    p.add_argument("--source-b", default=None, help="Optional second source, to enable diffing")
    p.add_argument("--source-b-label", default=None, help="Label for --source-b")
    p.add_argument("--source-b-version", default=None, help="Version id --source-b represents")
    p.add_argument("--out", default=str(Path("data") / "generated"), help="Output directory for generated JSON")
    p.add_argument("--php", default="php", help="PHP binary to run tools/export_mc_data.php")
    p.add_argument("--versions-json", default=None,
                   help="Load a pre-exported versions JSON instead of shelling out to PHP")
    p.add_argument("--no-pixel-compare", action="store_true", help="Skip PIL pixel comparison in the diff, hash only")
    p.add_argument("--strict", action="store_true", help="Exit non-zero if any error-severity validation issue is found")
    return p


def main(argv: list[str] | None = None) -> int:
    args = _build_arg_parser().parse_args(argv)

    try:
        version_table = (versions.from_json_file(args.versions_json) if args.versions_json
                          else versions.from_php(php_binary=args.php))
    except versions.VersionSourceError as e:
        print(f"error: could not load the version table: {e}", file=sys.stderr)
        return 2
    print(f"loaded {len(version_table)} versions from "
          f"{'JSON snapshot' if args.versions_json else 'tools/export_mc_data.php'}")

    label_a = args.source_label or Path(args.source).name
    records = assets.scan(args.source, label_a)
    print(f"scanned {args.source} ({label_a}): {len(records)} recognised assets")

    source_versions = {}
    if args.source_version:
        source_versions[label_a] = args.source_version

    diff_entries = None
    if args.source_b:
        label_b = args.source_b_label or Path(args.source_b).name
        records_b = assets.scan(args.source_b, label_b)
        print(f"scanned {args.source_b} ({label_b}): {len(records_b)} recognised assets")
        if args.source_b_version:
            source_versions[label_b] = args.source_b_version

        entries = diff_mod.compare(args.source, args.source_b, records, records_b,
                                    pixel_compare=not args.no_pixel_compare)
        diff_entries = [e.as_dict() for e in entries]
        counts = diff_mod.summarize(entries)
        print(f"diff: {counts['added']} added, {counts['removed']} removed, "
              f"{counts['changed']} changed, {counts['unchanged']} unchanged")
        records = records + records_b

    issues = validator.validate(records, version_table, source_versions or None)
    issue_counts = validator.summarize(issues)
    print(f"validation: {issue_counts['error']} errors, {issue_counts['warning']} warnings, "
          f"{issue_counts['info']} informational")
    for issue in issues:
        if issue.severity in ("error", "warning"):
            print(f"  {issue.severity.upper()} [{issue.code}] {issue.source}:{issue.id} ({issue.type}) — {issue.message}")

    written = registry.write_generated(args.out, version_table, records, diff_entries)
    print(f"wrote {len(written)} files to {args.out}:")
    for name in sorted(written):
        print(f"  {name}")

    if args.strict and issue_counts["error"] > 0:
        return 1
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
