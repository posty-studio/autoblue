import apiFetch from '@wordpress/api-fetch';
import { REFRESH_LOGS, CLEAR_LOGS } from './../constants';

export const refreshLogs =
	() =>
	async ( { dispatch } ) => {
		try {
			const logs = await apiFetch( {
				path: '/autoblue/v1/logs',
			} );

			dispatch( {
				type: REFRESH_LOGS,
				logs,
			} );

			return logs;
		} catch ( error ) {
			throw error;
		}
	};

export const clearLogs =
	() =>
	async ( { dispatch } ) => {
		try {
			const success = await apiFetch( {
				path: '/autoblue/v1/logs',
				method: 'DELETE',
			} );

			if ( success ) {
				dispatch( {
					type: CLEAR_LOGS,
				} );
			}

			return success;
		} catch ( error ) {
			throw error;
		}
	};
