import apiFetch from '@wordpress/api-fetch';
import { REFRESH_LOGS, CLEAR_LOGS, SET_LOGS_STATUS } from './../constants';
import { addQueryArgs } from '@wordpress/url';

const DEFAULT_PAGE = 1;
const DEFAULT_PER_PAGE = 10;

export const refreshLogs =
	( { page = DEFAULT_PAGE, perPage = DEFAULT_PER_PAGE } = {} ) =>
	async ( { select, dispatch } ) => {
		try {
			const currentPage = select.getLogsCurrentPage();
			const isRefreshing = page === currentPage;
			dispatch( {
				type: SET_LOGS_STATUS,
				status: isRefreshing ? 'refreshing' : 'loading',
			} );

			const path = addQueryArgs( '/autoblue/v1/logs', {
				page,
				per_page: perPage,
			} );

			const response = await apiFetch( {
				path,
				method: 'GET',
				parse: true,
			} );

			const { data: logs, pagination } = response;

			dispatch( {
				type: REFRESH_LOGS,
				logs,
				pagination: {
					page: pagination.page,
					perPage: pagination.per_page,
					totalItems: pagination.total_items,
					totalPages: pagination.total_pages,
				},
			} );

			dispatch( { type: SET_LOGS_STATUS, status: 'success' } );

			setTimeout( () => {
				dispatch( { type: SET_LOGS_STATUS, status: 'idle' } );
			}, 1000 );

			return logs;
		} catch ( error ) {
			dispatch( { type: SET_LOGS_STATUS, status: 'error' } );
			throw error;
		}
	};

export const clearLogs =
	() =>
	async ( { dispatch } ) => {
		try {
			dispatch( { type: SET_LOGS_STATUS, status: 'clearing' } );

			const success = await apiFetch( {
				path: '/autoblue/v1/logs',
				method: 'DELETE',
			} );

			if ( success ) {
				dispatch( {
					type: CLEAR_LOGS,
					pagination: {
						page: DEFAULT_PAGE,
						perPage: DEFAULT_PER_PAGE,
						totalItems: 0,
						totalPages: 0,
					},
				} );

				dispatch( { type: SET_LOGS_STATUS, status: 'success' } );

				setTimeout( () => {
					dispatch( { type: SET_LOGS_STATUS, status: 'idle' } );
				}, 2000 );
			}

			return success;
		} catch ( error ) {
			dispatch( { type: SET_LOGS_STATUS, status: 'error' } );
			throw error;
		}
	};
