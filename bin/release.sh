#!/usr/bin/env bash

# Strict error handling
set -euo pipefail
IFS=$'\n\t'

readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
readonly PLUGIN_FILE="autoblue.php"
readonly README_FILE="readme.txt"

usage() {
	cat <<-EOF
		    Usage: $(basename "$0") <version>

		    Draft a new release for the plugin.
		    Arguments:
		        version     New version number (e.g., 2.1.0)
		                    Must follow semantic versioning (X.Y.Z)

		    Examples:
		        $(basename "$0") 2.1.0
	EOF
	exit 1
}

version_gt() {
	test "$(printf '%s\n' "$@" | sort -V | head -n 1)" != "$1"
}

validate_version() {
	local new_version=$1
	local prev_version=$2

	if ! [[ $new_version =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
		echo "Error: Version must follow semantic versioning (X.Y.Z)"
		exit 1
	fi

	if ! version_gt "$new_version" "$prev_version"; then
		echo "Error: New version ($new_version) must be higher than current version ($prev_version)"
		exit 1
	fi
}

check_prerequisites() {
	if ! command -v git >/dev/null 2>&1; then
		echo "Error: git is required but not installed"
		exit 1
	fi

	local current_branch
	current_branch="$(git rev-parse --abbrev-ref HEAD)"
	if [[ "$current_branch" != "main" ]]; then
		echo "Error: Not currently checked out to main! (on branch: $current_branch)"
		exit 1
	fi

	if [[ -n "$(git status --porcelain)" ]]; then
		echo "Error: Working directory not clean, make sure you're working from a clean checkout and try again."
		exit 1
	fi
}

checkout_branch() {
	local new_version=$1
	local branch_name="release/v$new_version"

	if git rev-parse --verify "$branch_name" >/dev/null 2>&1; then
		echo "Error: Branch $branch_name already exists"
		exit 1
	fi

	git checkout -b "$branch_name"
}

update_files() {
	local new_version=$1
	local prev_version=$2
	local plugin_path="$SCRIPT_DIR/$PLUGIN_FILE"
	local readme_path="$SCRIPT_DIR/$README_FILE"

	echo "Updating files from version $prev_version to $new_version..."

	local commits=$(git log --pretty=format:"* %s" "v${prev_version}"..HEAD)

	sed -i.bak \
		-e "s/\* Version: .*/\* Version: $new_version/" \
		-e "s/define( 'AUTOBLUE_VERSION', '.*' )/define( 'AUTOBLUE_VERSION', '$new_version' )/" \
		"$plugin_path"

	{
		sed -n '1,/== Changelog ==/p' "$readme_path"
		echo
		echo "= $new_version ="
		echo "$commits"
		echo
		sed -n '/== Changelog ==/,$p' "$readme_path" | tail -n +2
	} >"$readme_path.new"

	sed -i.bak "s/Stable tag: .*/Stable tag: $new_version/" "$readme_path.new"

	mv "$readme_path.new" "$readme_path"
	rm -f "$plugin_path.bak" "$readme_path.bak" "$readme_path.new.bak"

	echo "Successfully updated files"
}

main() {
	if [[ $# -ne 1 ]]; then
		usage
	fi

	local new_version=$1

	check_prerequisites

	local prev_version
	if ! prev_version=$(grep -o "Version: [0-9.]*" "$SCRIPT_DIR/$PLUGIN_FILE" | cut -d' ' -f2); then
		echo "Error: Could not determine current version"
		exit 1
	fi

	checkout_branch "$new_version"
	validate_version "$new_version" "$prev_version"
	update_files "$new_version" "$prev_version"

	echo "Success! Release draft created for version $new_version"
	echo "Please review the changes before committing"
}

main "$@"
