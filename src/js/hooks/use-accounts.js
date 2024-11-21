import apiFetch from '@wordpress/api-fetch';
import { useState, useEffect, useCallback } from '@wordpress/element';

const useAccounts = () => {
	const [ accounts, setAccounts ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( false );

	console.log( 'useAccounts hook instantiated' );
	const fetchAccounts = useCallback( async () => {
		try {
			setIsLoading( true );
			const response = await apiFetch( {
				path: '/bsky4wp/v1/accounts',
			} );

			setAccounts( response );
			setIsLoading( false );
		} catch ( error ) {
			console.error( error );
			setIsLoading( false );
		}
	}, [] );

	useEffect( () => {
		fetchAccounts();
	}, [ fetchAccounts ] );

	const addAccount = async ( did, appPassword ) => {
		const response = await apiFetch( {
			path: '/bsky4wp/v1/accounts',
			method: 'POST',
			data: {
				did,
				app_password: appPassword,
			},
		} );

		if ( response.error ) {
			throw new Error( response.error );
		}

		// TODO: Ew
		const newAccount = Array.isArray( response ) ? response[ 0 ] : response;
		setAccounts( ( prevAccounts ) => {
			const flattenedPrevAccounts = Array.isArray( prevAccounts[ 0 ] )
				? prevAccounts[ 0 ]
				: prevAccounts;

			return [ ...flattenedPrevAccounts, newAccount ];
		} );

		return response;
	};

	const deleteAccount = async ( did ) => {
		const response = await apiFetch( {
			path: '/bsky4wp/v1/accounts',
			method: 'DELETE',
			data: { did },
		} );

		if ( response.error ) {
			throw new Error( response.error );
		}

		setAccounts( ( prevAccounts ) =>
			prevAccounts.filter( ( account ) => account.did !== did )
		);

		return response;
	};

	return {
		accounts,
		isLoading,
		addAccount,
		deleteAccount,
	};
};

export default useAccounts;
