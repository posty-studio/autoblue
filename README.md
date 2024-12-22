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

## Release process

Autoblue uses the [10up/action-wordpress-plugin-deploy](https://github.com/10up/action-wordpress-plugin-deploy) GitHub Action to deploy the plugin to the WordPress.org plugin repository.

All development needs to happen in the `develop` branch. The `main` branch is reserved for releases. To create a new feature, make a fix, or change something else, create a new branch from `develop`. Once the changes are ready, create a pull request to merge the changes back into `develop`.

To release a new version, follow these steps:

1. Merge the changes from `develop` into `main`.
2. Update the version number in the `autoblue.php` file (twice)
3. Add a changelog entry with the changes and new version to `readme.txt`
4. Update the `Stable tag` in `readme.txt` to the new version number.
5. Commit the changes and push them to the repository.
6. Create a new release on GitHub with the new version number and the changelog entry.
7. The GitHub Action will automatically deploy the new version to the WordPress.org plugin repository.

### Updating assets or readme without creating a new release

To update the assets (like screenshots) or readme without creating a new release (e.g. when updating the `Tested up to` line), follow these steps:

1. Checkout the `main` branch.
2. Update the assets in the `assets` directory (if required).
3. Update `readme.txt` with new information (if required).
4. Commit the changes and push them to the repository.
5. Merge the changes from `main` into `develop`.

The changes will be automatically deployed to the WordPress.org plugin repository by the [10up/action-wordpress-plugin-asset-update](https://github.com/10up/action-wordpress-plugin-asset-update) GitHub Action.
