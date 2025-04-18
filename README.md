# Autoblue

Add Bluesky to your WordPress website. Automatically share new posts to Bluesky and integrate Bluesky replies with the comments on your website.

## Installation

1. Clone the repository into the `wp-content/plugins` directory of your WordPress installation.
2. Run `npm install` to install the dependencies.
3. Run `npm run build` to build the plugin.
4. Run `composer install` to install the PHP dependencies.
5. Activate the plugin in the WordPress admin.

## Development

1. Run `npm run start` to start the development server.

## Build

1. `composer install --no-dev` to install the PHP dependencies.
1. `npm run build` to build the plugin.

## Bundling

1. Run `npm run plugin-zip` to create a zip file of the plugin.

## Development process

To create a new feature, make a fix, or change something else, create a new branch from `main`. Once the changes are ready, create a pull request to merge the changes back into `main`.

All pull requests should be properly reviewed and tested before merging. Any code merged into `main` can be included in the next release at any point.

If you are working on a new feature or a larger change, consider creating a feature branch from `main`. Once the feature is ready, create a pull request to merge the feature branch back into `main`.

## Release process

Autoblue uses the [10up/action-wordpress-plugin-deploy](https://github.com/10up/action-wordpress-plugin-deploy) GitHub Action to deploy the plugin to the WordPress.org plugin repository.

To release a new version, follow these steps:

1. Switch to the `main` branch and make sure all changes are merged into it.
2. Run `bin/release.sh <version>` to create a new release. Replace `<version>` with the new version number. This creates a new branch and updates the changelog and version numbers.
3. Check the changelog in `readme.txt` and make changes if required.
4. Commit the changes, create a PR to merge the release branch into `main`, and merge the PR.
5. Create a new release on GitHub with the new version number, tag, and the changelog entry.
6. The GitHub Action will automatically deploy the new version to the WordPress.org plugin repository.
7. Add a new changelog entry on the [Autoblue changelog page](https://autoblue.co/changelog/) for the new version.

### Updating assets or readme without creating a new release

To update the assets (like screenshots) or readme without creating a new release (e.g. when updating the `Tested up to` line), follow these steps:

1. Create a new branch from `main`.
2. Update the assets in the `assets` directory (if required).
3. Update `readme.txt` with new information (if required).
4. Create a PR with the changes and merge it into `main`.

The changes will be automatically deployed to the WordPress.org plugin repository by the [10up/action-wordpress-plugin-asset-update](https://github.com/10up/action-wordpress-plugin-asset-update) GitHub Action.
