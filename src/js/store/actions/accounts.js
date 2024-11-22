import apiFetch from '@wordpress/api-fetch';
import { SET_ACCOUNTS, ADD_ACCOUNT, DELETE_ACCOUNT } from './../constants';

export function setAccounts( accounts ) {
	return {
		type: SET_ACCOUNTS,
		accounts,
	};
}

export function addAccountToState( account ) {
	return {
		type: ADD_ACCOUNT,
		account,
	};
}

export function deleteAccountFromState( did ) {
	return {
		type: DELETE_ACCOUNT,
		did,
	};
}

export function addAccount( did, appPassword ) {
	return async function ( { dispatch, select } ) {
		const response = await apiFetch( {
			path: '/bsky4wp/v1/accounts',
			method: 'POST',
			data: { did, app_password: appPassword },
		} );

		dispatch( addAccountToState( response ) );
	};
}

export function* deleteAccount( did ) {
	try {
		// yield setAccountsLoading( true );
		const response = yield apiFetch( {
			path: '/bsky4wp/v1/accounts',
			method: 'DELETE',
			data: { did },
		} );

		if ( response.error ) {
			throw new Error( response.error );
		}

		// Remove the account directly from the state
		yield deleteAccountFromState( did );

		return response;
	} catch ( error ) {
		// yield setAccountsError( error );
		throw error;
	} finally {
		// yield setAccountsLoading( false );
	}
}
