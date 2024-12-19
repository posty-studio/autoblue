// Can be deleted once this issue is resolved:
// https://github.com/WordPress/gutenberg/issues/61171
const AdmZip = require( 'adm-zip' );
const { sync: glob } = require( 'fast-glob' );
const { dirname } = require( 'path' );
const { stdout } = require( 'process' );

const slug = 'autoblue';

stdout.write( `Creating archive for Autoblue plugin... \n\n` );

const zip = new AdmZip();
const files = glob(
	[
		'admin/**',
		'build/**',
		'includes/**',
		'languages/**',
		'public/**',
		'vendor/**',
		`${ slug }.php`,
		'composer.json',
		'uninstall.php',
		'block.json',
		'changelog.*',
		'license.*',
		'readme.txt',
	],
	{
		caseSensitiveMatch: false,
	}
);

files.forEach( ( file ) => {
	stdout.write( `  Adding \`${ file }\`.\n` );
	const zipDirectory = dirname( file );
	zip.addLocalFile( file, zipDirectory !== '.' ? zipDirectory : '' );
} );

zip.writeZip( `./${ slug }.zip` );
stdout.write( `\nDone. \`${ slug }.zip\` is ready! 🎉\n` );
