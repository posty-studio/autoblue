const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		...defaultConfig.entry(),
		admin: './src/js/admin.js',
		editor: './src/js/editor.js',
	},
};
