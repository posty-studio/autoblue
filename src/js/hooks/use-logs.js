import { useSelect, useDispatch } from '@wordpress/data';
import { useState } from '@wordpress/element'; // or 'react'
import { STORE_NAME } from '../store';

const useLogs = () => {
	const [ isRefreshingLogs, setIsRefreshingLogs ] = useState( false );
	const [ isClearingLogs, setIsClearingLogs ] = useState( false );

	const { logs } = useSelect(
		( select ) => ( {
			logs: select( STORE_NAME ).getLogs(),
		} ),
		[]
	);

	const { refreshLogs: refresh, clearLogs: clear } =
		useDispatch( STORE_NAME );

	const refreshLogs = async () => {
		try {
			setIsRefreshingLogs( true );
			await refresh();
		} catch ( error ) {
			console.error( 'Failed to refresh logs:', error );
		} finally {
			setIsRefreshingLogs( false );
		}
	};

	const clearLogs = async () => {
		try {
			setIsClearingLogs( true );
			await clear();
		} catch ( error ) {
			console.error( 'Failed to clear logs:', error );
		} finally {
			setIsClearingLogs( false );
		}
	};

	return {
		logs,
		refreshLogs,
		clearLogs,
		isRefreshingLogs,
		isClearingLogs,
	};
};

export default useLogs;
