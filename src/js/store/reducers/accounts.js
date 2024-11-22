import { SET_ACCOUNTS, ADD_ACCOUNT, DELETE_ACCOUNT } from './../constants';

const DEFAULT_STATE = {
	items: [],
};

export default function accounts( state = DEFAULT_STATE, action ) {
	switch ( action.type ) {
		case SET_ACCOUNTS:
			return {
				...state,
				items: action.accounts,
			};
		case ADD_ACCOUNT:
			return {
				...state,
				items: [ ...state.items, action.account ],
			};
		case DELETE_ACCOUNT:
			return {
				...state,
				items: state.items.filter(
					( account ) => account.did !== action.did
				),
			};
		default:
			return state;
	}
}
