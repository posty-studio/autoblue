import { __, sprintf } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { store as noticesStore } from '@wordpress/notices';
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
	return async function ( { dispatch, registry } ) {
		const { createSuccessNotice, createErrorNotice } =
			registry.dispatch( noticesStore );

		try {
			const response = await apiFetch( {
				path: '/autoblue/v1/connections',
				method: 'POST',
				data: { did, app_password: appPassword },
			} );

			dispatch( addAccountToState( response ) );

			let { handle, name } = response.meta;
			handle = handle ? `@${ handle }` : '';
			const account = `${ name } ${ handle }`;

			createSuccessNotice(
				// translators: %s is the account name and handle
				sprintf( __( 'Account "%s" connected', 'autoblue' ), account ),
				{
					id: 'autoblue/account/added',
					type: 'snackbar',
				}
			);
		} catch ( error ) {
			createErrorNotice( __( 'Failed to connect account', 'autoblue' ), {
				id: 'autoblue/account/error',
				type: 'snackbar',
			} );

			throw error;
		}
	};
}

export function deleteAccount( did ) {
	return async function ( { dispatch, registry } ) {
		const { createSuccessNotice, createErrorNotice } =
			registry.dispatch( noticesStore );

		try {
			const response = await apiFetch( {
				path: '/autoblue/v1/connections',
				method: 'DELETE',
				data: { did },
			} );

			dispatch( deleteAccountFromState( did ) );

			createSuccessNotice( __( 'Account disconnected', 'autoblue' ), {
				id: 'autoblue/account/deleted',
				type: 'snackbar',
			} );

			console.log( response );

			return response;
		} catch ( error ) {
			createErrorNotice(
				__( 'Failed to disconnect account', 'autoblue' ),
				{
					id: 'autoblue/account/error',
					type: 'snackbar',
				}
			);
			throw error;
		}
	};
}
