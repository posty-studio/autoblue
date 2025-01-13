// Copied from: https://github.com/bluesky-social/social-app/blob/main/src/view/com/composer/text-input/hooks/useGrapheme.tsx
import { useCallback, useMemo } from 'react';
import Graphemer from 'graphemer';

export const useGrapheme = () => {
	const splitter = useMemo( () => new Graphemer(), [] );

	const getGraphemeString = useCallback(
		( string, length ) => {
			let remainingCharacters = 0;

			if ( string.length > length ) {
				const graphemes = splitter.splitGraphemes( string );

				if ( graphemes.length > length ) {
					remainingCharacters = 0;
					string = `${ graphemes.slice( 0, length ).join( '' ) }…`;
				} else {
					remainingCharacters = length - graphemes.length;
					string = graphemes.join( '' );
				}
			} else {
				remainingCharacters = length - string.length;
			}

			return {
				string,
				remainingCharacters,
			};
		},
		[ splitter ]
	);

	return {
		getGraphemeString,
	};
};
