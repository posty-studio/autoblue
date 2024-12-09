import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { REFRESH_LOGS, CLEAR_LOGS, SET_LOGS_STATUS } from './../constants';
import { addQueryArgs } from '@wordpress/url';
import { store as noticesStore } from '@wordpress/notices';

const DEFAULT_PAGE = 1;
const DEFAULT_PER_PAGE = 10;

export const refreshLogs =
	( { page = DEFAULT_PAGE, perPage = DEFAULT_PER_PAGE } = {} ) =>
	async ( { select, dispatch, registry } ) => {
		const { createSuccessNotice, createErrorNotice, removeNotice } =
			registry.dispatch( noticesStore );

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

			if ( isRefreshing ) {
				const notice = await createSuccessNotice(
					__( 'Logs refreshed.', 'autoblue' ),
					{
						type: 'snackbar',
					}
				);

				setTimeout( () => {
					dispatch( { type: SET_LOGS_STATUS, status: 'idle' } );
					removeNotice( notice.notice.id );
				}, 2000 );
			}

			return logs;
		} catch ( error ) {
			createErrorNotice( __( 'Failed to refresh logs.', 'autoblue' ), {
				id: 'autoblue/logs/refresh-error',
				type: 'snackbar',
			} );
			throw error;
		}
	};

export const clearLogs =
	() =>
	async ( { dispatch, registry } ) => {
		const { createSuccessNotice, createErrorNotice, removeNotice } =
			registry.dispatch( noticesStore );

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

				const notice = await createSuccessNotice(
					__( 'Logs cleared.', 'autoblue' ),
					{
						type: 'snackbar',
					}
				);

				setTimeout( () => {
					dispatch( { type: SET_LOGS_STATUS, status: 'idle' } );
					removeNotice( notice.notice.id );
				}, 2000 );
			}

			return success;
		} catch ( error ) {
			dispatch( { type: SET_LOGS_STATUS, status: 'error' } );
			createErrorNotice( __( 'Failed to clear logs.', 'autoblue' ), {
				id: 'autoblue/logs/clear-error',
				type: 'snackbar',
			} );
			throw error;
		}
	};
