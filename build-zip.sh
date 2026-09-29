#!/usr/bin/env bash
set -euo pipefail
root="$(cd "$(dirname "$0")" && pwd)"
name="multaparts-apparaatpagina-registry"
out="$root/dist/${name}-0.1.0.zip"
mkdir -p "$root/dist"
rm -f "$out"
tmp="$(mktemp -d)"; trap 'rm -rf "$tmp"' EXIT
mkdir -p "$tmp/$name"
cp -R "$root"/{assets,src,templates,tests,README.md,multaparts-apparaatpagina-registry.php} "$tmp/$name/"
(cd "$tmp" && zip -qr "$out" "$name")
printf '%s\n' "$out"
