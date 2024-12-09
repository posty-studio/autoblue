import {
	REFRESH_LOGS,
	CLEAR_LOGS,
	SET_LOGS_STATUS,
	SET_LOGS_PAGE,
} from './../constants';

const DEFAULT_STATE = {
	status: 'idle', // 'idle' | 'refreshing' | 'loading' | 'clearing' | 'success' | 'error'
};

export default function logs( state = DEFAULT_STATE, action ) {
	switch ( action.type ) {
		case REFRESH_LOGS:
			return {
				...state,
				items: action.logs,
				pagination: {
					...state.pagination,
					...action.pagination,
				},
			};
		case CLEAR_LOGS:
			return {
				...state,
				items: [],
				pagination: {
					...state.pagination,
					...action.pagination,
				},
			};
		case SET_LOGS_STATUS:
			return {
				...state,
				status: action.status,
			};
		case SET_LOGS_PAGE:
			return {
				...state,
				pagination: {
					...state.pagination,
					page: action.page,
				},
			};
		default:
			return state;
	}
}
