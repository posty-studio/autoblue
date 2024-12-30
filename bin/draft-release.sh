#!/usr/bin/env bash

set -eo pipefail

# Print help and exit.
function usage {
	cat <<-EOH
		usage: $0 <version>

		  Draft a new release for the plugin. <version> should be the new version,
			e.g. 2.1.0.
	EOH
	exit 1
}

if [[ $# -eq 0 ]]; then
	usage
fi

set -eo pipefail

CURRENT_BRANCH="$(git rev-parse --abbrev-ref HEAD)"
if [[ "$CURRENT_BRANCH" != "main" ]]; then
	echo "Not currently checked out to main!"
	exit 1
fi

if [[ -n "$(git status --porcelain)" ]]; then
	echo "Working directory not clean, make sure you're working from a clean checkout and try again."
	exit 1
fi

BASE=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
NEW_VERSION=$1
PREV_VERSION=$(grep -o "Version: [0-9.]*" "$BASE/autoblue.php" | cut -d' ' -f2)

# Get commit messages since last version
COMMITS=$(git log --pretty=format:"* %s" "${PREV_VERSION}"..HEAD)

# Update autoblue.php versions
sed -i '' "s/\* Version: .*/\* Version: $NEW_VERSION/" "$BASE/autoblue.php"
sed -i '' "s/define( 'AUTOBLUE_VERSION', '.*' )/define( 'AUTOBLUE_VERSION', '$NEW_VERSION' )/" "$BASE/autoblue.php"

# Update readme.txt
sed -i '' "s/Stable tag: .*/Stable tag: $NEW_VERSION/" "$BASE/readme.txt"
sed -i '' "/== Changelog ==/a\\
\\
= $NEW_VERSION =\\
$COMMITS
" "$BASE/readme.txt"
