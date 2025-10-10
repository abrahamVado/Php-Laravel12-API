#!/usr/bin/env bash
set -euo pipefail

# //1.- Resolve the base directory relative to this script to locate the requirements manifest.
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
REQUIREMENTS_FILE="${ROOT_DIR}/docs/environment/requirements.txt"

# //2.- Ensure the requirements manifest exists before continuing.
if [[ ! -f "${REQUIREMENTS_FILE}" ]]; then
  echo "Missing requirements file at ${REQUIREMENTS_FILE}" >&2
  exit 1
fi

# //3.- Optionally allow tests to skip command verification by exporting CHECK_REQUIREMENTS_SKIP_COMMANDS=1.
SKIP_COMMAND_CHECK="${CHECK_REQUIREMENTS_SKIP_COMMANDS:-0}"

# //4.- Read requirement sections and present them while verifying binaries when allowed.
current_section=""
while IFS= read -r line || [[ -n "$line" ]]; do
  trimmed="${line%%#*}"
  trimmed="${trimmed%%$'\r'}"
  if [[ -z "${trimmed// }" ]]; then
    continue
  fi
  if [[ "${trimmed}" =~ ^\[(.+)\]$ ]]; then
    current_section="${BASH_REMATCH[1]}"
    echo "\n${current_section^^}"
    echo "$(printf '%0.s-' {1..${#current_section}})"
    continue
  fi
  name="${trimmed%%=*}"
  version="${trimmed#*=}"
  printf ' - %s (required: %s)\n' "${name}" "${version}"
  if [[ "${SKIP_COMMAND_CHECK}" != "1" ]]; then
    command_name="${name%% (*}"
    if ! command -v "${command_name}" >/dev/null 2>&1; then
      echo "   -> missing executable: ${command_name}" >&2
    fi
  fi
done < "${REQUIREMENTS_FILE}"
