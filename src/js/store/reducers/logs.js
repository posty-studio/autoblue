import { REFRESH_LOGS, CLEAR_LOGS } from './../constants';

const DEFAULT_STATE = {
	items: [],
};

export default function logs( state = DEFAULT_STATE, action ) {
	switch ( action.type ) {
		case REFRESH_LOGS:
			return {
				...state,
				items: action.logs,
			};
		case CLEAR_LOGS:
			return {
				...state,
				items: [],
			};
		default:
			return state;
	}
}
