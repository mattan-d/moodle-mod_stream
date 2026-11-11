#!/bin/bash
# On agent stop: if the repo has uncommitted changes, ask the agent to commit and push.
set -euo pipefail

input=$(cat || true)

# Only continue when the agent is truly stopping (not already a follow-up loop if status says so).
status=$(echo "$input" | python3 -c 'import json,sys
try:
  d=json.load(sys.stdin)
except Exception:
  d={}
print(d.get("status") or d.get("stop_reason") or "")' 2>/dev/null || true)

# Detect dirty worktree relative to project root.
root=$(git rev-parse --show-toplevel 2>/dev/null || pwd)
cd "$root"

dirty=$(git status --porcelain 2>/dev/null || true)
if [[ -z "$dirty" ]]; then
  echo '{}'
  exit 0
fi

# Avoid infinite loops: if the last user/follow-up already asked for commit+push, do nothing.
# Cursor may pass loop count; only follow up a couple of times.
loop_count=$(echo "$input" | python3 -c 'import json,sys
try:
  d=json.load(sys.stdin)
except Exception:
  d={}
print(d.get("loop_count") or d.get("followup_count") or 0)' 2>/dev/null || echo 0)

if [[ "${loop_count}" =~ ^[0-9]+$ ]] && (( loop_count >= 2 )); then
  echo '{}'
  exit 0
fi

python3 - <<'PY'
import json
msg = (
  "There are uncommitted changes in this repo. "
  "Generate a concise commit message from the diff, create a git commit, "
  "and push to the remote. Follow the project auto-commit-push rule. "
  "Do not ask for confirmation."
)
print(json.dumps({"followup_message": msg}))
PY

exit 0
