# Posty Starter Plugin

## Quick Start

1. Clone this repository into your `wp-content/plugins` directory.
2. Run `rm -rf .git` to remove the Git repo of the starter plugin.
3. Run `npm install` to install the dependencies.
4. Run `node bin/setup.mjs` and follow the prompts to set up the plugin.
5. Activate the plugin in the WordPress admin.

To make it a repo:

1. `git init`
2. `git add .`
3. `git commit -m "Initial commit"`
4. `gh repo create` (if you have the GitHub CLI installed)

To delete the setup script:

1. `rm bin/setup.mjs && npm uninstall --save-dev @inquirer/prompts`

## Installation with Laravel Herd

1. `wp core download`
2. `wp core config --dbname=example --dbuser=root --dbpass=` (replace example with your project name)
3. `wp db create`
4. `wp core install --url=example.test --title=Example --admin_user=admin --admin_password=password --admin_email=mail@example.com` (replace example with your project name)
5. `wp site empty --yes`
6. `wp search-replace http https`
7. `herd secure`
8. `wp rewrite structure '/%postname%'` (optional)
9. `wp config set WP_DEBUG true --raw`
10. `wp config set WP_DEBUG_LOG true --raw`
11. `wp config set SCRIPT_DEBUG true --raw`
12. `wp config set WP_ENVIRONMENT_TYPE local`
13. Follow the Quick Start instructions above.

## Development

1. Run `npm run start` to start the development server.

## Build

1. Run `npm run build` to build the plugin.
