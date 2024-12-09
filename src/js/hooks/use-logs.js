import { useSelect, useDispatch } from '@wordpress/data';
import { STORE_NAME } from '../store';

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

	return {
		logs,
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
