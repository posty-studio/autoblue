import accounts from './accounts';
import logs from './logs';
import { combineReducers } from '@wordpress/data';

export default combineReducers( {
	accounts,
	logs,
} );
