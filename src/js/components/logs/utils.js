import {
	info,
	warning,
	code,
	cancelCircleFilled,
	check,
} from '@wordpress/icons';

export const getIconFromLevel = ( level ) => {
	switch ( level ) {
		case 'success':
			return check;
		case 'info':
			return info;
		case 'warning':
			return warning;
		case 'error':
			return cancelCircleFilled;
		case 'debug':
			return code;
		default:
			return info;
	}
};

export const processUrls = ( str ) => {
	const parts = str.split( /(https:\/\/bsky\.app\/profile\/[^\s]+)/g );
	return parts.map( ( part, index ) => {
		const isBskyProfileUrl = /^https:\/\/bsky\.app\/profile\/[^\s]+$/.test(
			part
		);
		return isBskyProfileUrl ? (
			<a
				key={ `url-${ index }` }
				href={ part }
				target="_blank"
				rel="noreferrer"
			>
				{ part }
			</a>
		) : (
			part
		);
	} );
};

export const convertBackticks = ( content ) => {
	if ( typeof content === 'string' ) {
		const parts = content.split( '`' );
		return parts.map( ( part, index ) =>
			index % 2 === 0 ? (
				part
			) : (
				<code key={ `code-${ index }` }>{ part }</code>
			)
		);
	}
	return content;
};

export const parseMessage = ( message ) => {
	const urlProcessed = processUrls( message );
	return urlProcessed
		.map( ( part ) => {
			if ( typeof part === 'string' ) {
				return convertBackticks( part );
			}
			return part;
		} )
		.flat();
};
