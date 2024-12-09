import { __, sprintf } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { STORE_NAME } from '../store';
import { store as noticesStore } from '@wordpress/notices';

const DEFAULT_PER_PAGE = 10;

const useLogs = ( { perPage = DEFAULT_PER_PAGE } = {} ) => {
	const { logs, status, page, totalPages, totalItems } = useSelect(
		( select ) => {
			const store = select( STORE_NAME );
			return {
				logs: store.getLogs(),
				status: store.getLogsStatus(),
				page: store.getLogsCurrentPage(),
				totalPages: store.getLogsTotalPages(),
				totalItems: store.getLogsTotalItems(),
			};
		},
		[]
	);

	const { createSuccessNotice, removeNotice } = useDispatch( noticesStore );

	const { refreshLogs, clearLogs } = useDispatch( STORE_NAME );

	const handleRefreshLogs = async () => {
		try {
			await refreshLogs( { page, perPage } );
		} catch ( error ) {
			console.error( 'Failed to refresh logs:', error );
		}
	};

	const handleClearLogs = async () => {
		try {
			await clearLogs();
		} catch ( error ) {
			console.error( 'Failed to clear logs:', error );
		}
	};

	const handlePageChange = async ( newPage ) => {
		try {
			await refreshLogs( { page: newPage, perPage } );
		} catch ( error ) {
			console.error( 'Failed to change page:', error );
		}
	};

	const [ logLevel, setLogLevelFn ] = useEntityProp(
		'root',
		'site',
		'autoblue_log_level'
	);
	const { saveEditedEntityRecord } = useDispatch( 'core' );

	const isSaving = useSelect( ( select ) =>
		select( 'core' ).isSavingEntityRecord( 'root', 'site' )
	);

	const setLogLevel = async ( value ) => {
		if ( isSaving ) {
			return;
		}
		try {
			setLogLevelFn( value );
			await saveEditedEntityRecord( 'root', 'site' );
			const notice = await createSuccessNotice(
				sprintf(
					// translators: %s is the log level
					__( 'Log level updated to "%s".', 'autoblue' ),
					value
				),
				{
					type: 'snackbar',
				}
			);

			setTimeout( () => {
				removeNotice( notice.notice.id );
			}, 2000 );
		} catch ( error ) {}
	};

	return {
		logs,
		level: logLevel,
		setLevel: setLogLevel,
		page,
		totalPages,
		totalItems,
		refreshLogs: handleRefreshLogs,
		clearLogs: handleClearLogs,
		setPage: handlePageChange,
		isRefreshingLogs: status === 'refreshing',
		isClearingLogs: status === 'clearing',
		isSuccess: status === 'success',
		isError: status === 'error',
	};
};

export default useLogs;
