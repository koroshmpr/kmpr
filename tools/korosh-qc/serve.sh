#!/bin/sh
# Serves the harness on http://127.0.0.1:8765/harness.html (no WordPress needed).
# The game bundle is loaded straight from themes/kmpr/public/js/games — run `npm run production:games` first.
cd "$(dirname "$0")"
ln -sfn ../../themes/kmpr theme
mkdir -p out
exec python3 -m http.server 8765 --bind 127.0.0.1
