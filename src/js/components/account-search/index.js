import { __ } from '@wordpress/i18n';
import { useState, useEffect, useCallback } from 'react';
import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	Spinner,
	SearchControl,
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import AccountInfo from '../account-info';
import styles from './styles.module.scss';

const AccountSearch = ( { onSelect } ) => {
	const [ searchValue, setSearchValue ] = useState( '' );
	const [ loading, setLoading ] = useState( false );
	const [ results, setResults ] = useState( [] );
	let debounceTimer = null;

	const fetchResults = useCallback( async ( query ) => {
		if ( query.length > 0 ) {
			setLoading( true );
			try {
				const response = await apiFetch( {
					path: `/bsky4wp/v1/search?q=${ encodeURIComponent(
						query
					) }`,
				} );

				if ( ! response.actors ) {
					throw new Error( 'Unexpected response from the server' );
				}

				setResults( response.actors );
			} catch ( error ) {
				console.error( 'Error fetching results:', error );
			} finally {
				setLoading( false );
			}
		} else {
			setResults( [] );
		}
	}, [] );

	useEffect( () => {
		if ( debounceTimer ) {
			clearTimeout( debounceTimer );
		}
		debounceTimer = setTimeout( () => {
			fetchResults( searchValue );
		}, 300 );

		return () => clearTimeout( debounceTimer );
	}, [ searchValue, fetchResults ] );

	console.log( results );

	return (
		<div>
			<SearchControl
				__nextHasNoMarginBottom
				placeholder={ __(
					'Search for your Bluesky account',
					'bsky-for-wp'
				) }
				label={ __( 'Bluesky Account', 'bsky-for-wp' ) }
				hideLabelFromVision={ false }
				value={ searchValue }
				onChange={ ( newSearchValue ) =>
					setSearchValue( newSearchValue )
				}
			/>
			<div>
				{ loading || ( results && results.length ) ? (
					<VStack spacing={ 0 } className={ styles.results }>
						{ loading && (
							<div className={ styles.spinner }>
								<Spinner />
							</div>
						) }
						{ results &&
							! loading &&
							results.length &&
							results.map(
								( { did, handle, displayName, avatar } ) => (
									<Button
										key={ did }
										className={ styles.button }
										onClick={ () => onSelect( did ) }
									>
										<AccountInfo
											did={ did }
											handle={ handle }
											displayName={ displayName }
											avatar={ avatar }
										/>
									</Button>
								)
							) }
					</VStack>
				) : null }
			</div>
		</div>
	);
};

export default AccountSearch;
