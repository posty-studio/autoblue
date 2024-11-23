import { __ } from '@wordpress/i18n';
import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	Spinner,
	SearchControl,
	__experimentalVStack as VStack,
} from '@wordpress/components';
import AccountInfo from '../account-info';
import styles from './styles.module.scss';

const AccountSearch = ( { onSelect } ) => {
	const [ searchValue, setSearchValue ] = useState( '' );
	const [ loading, setLoading ] = useState( false );
	const [ results, setResults ] = useState( [] );
	const [ hasSearched, setHasSearched ] = useState( false );
	let debounceTimer = null;

	const abortControllerRef = useRef( null );

	const fetchResults = useCallback( async ( query ) => {
		if ( query.length > 0 ) {
			setLoading( true );

			if ( abortControllerRef.current ) {
				abortControllerRef.current.abort();
			}

			abortControllerRef.current = new AbortController();

			try {
				const response = await apiFetch( {
					path: `/autoblue/v1/search?q=${ encodeURIComponent(
						query
					) }`,
					signal: abortControllerRef.current.signal,
				} );

				if ( ! response.actors ) {
					throw new Error( 'Unexpected response from the server' );
				}

				setResults( response.actors );
				setHasSearched( true );
			} catch ( error ) {
				if ( error.name !== 'AbortError' ) {
					setResults( [] );
					setHasSearched( true );
				}
			} finally {
				if ( abortControllerRef.current?.signal.aborted !== true ) {
					setLoading( false );
				}
			}
		} else {
			setResults( [] );
			setHasSearched( false );
		}
	}, [] );

	useEffect( () => {
		if ( debounceTimer ) {
			clearTimeout( debounceTimer );
		}
		debounceTimer = setTimeout( () => {
			fetchResults( searchValue );
		}, 300 );

		return () => {
			clearTimeout( debounceTimer );
			if ( abortControllerRef.current ) {
				abortControllerRef.current.abort();
			}
		};
	}, [ searchValue, fetchResults ] );

	const getResultContent = () => {
		if ( loading ) {
			return (
				<div className={ styles.empty }>
					<Spinner />
				</div>
			);
		}

		if ( results.length === 0 && searchValue.length > 0 && hasSearched ) {
			return (
				<div className={ styles.empty }>
					{ __(
						'No accounts found. Please try another search.',
						'autoblue'
					) }
				</div>
			);
		}

		if ( results.length > 0 ) {
			return results.map( ( { did, handle, displayName, avatar } ) => (
				<Button
					key={ did }
					className={ styles.button }
					onClick={ () =>
						onSelect( {
							did,
							meta: { handle, name: displayName, avatar },
						} )
					}
				>
					<AccountInfo
						account={ {
							did,
							meta: {
								handle,
								name: displayName,
								avatar,
							},
						} }
						className={ styles.account }
					/>
				</Button>
			) );
		}

		return null;
	};

	const resultContent = getResultContent();

	return (
		<div>
			<SearchControl
				__nextHasNoMarginBottom
				placeholder={ __( 'Search for a Bluesky account', 'autoblue' ) }
				label={ __( 'Bluesky Account', 'autoblue' ) }
				value={ searchValue }
				onChange={ ( newSearchValue ) =>
					setSearchValue( newSearchValue )
				}
			/>
			{ resultContent && (
				<div>
					<VStack spacing={ 0 } className={ styles.results }>
						{ resultContent }
					</VStack>
				</div>
			) }
		</div>
	);
};

export default AccountSearch;
