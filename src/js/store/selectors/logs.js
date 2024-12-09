export function getLogs( state ) {
	return state.logs.items;
}

export function getLogsStatus( state ) {
	return state.logs.status;
}

export function getLogsCurrentPage( state ) {
	return state.logs.pagination.page;
}

export function getLogsTotalPages( state ) {
	return state.logs.pagination.totalPages;
}

export function getLogsTotalItems( state ) {
	return state.logs.pagination.totalItems;
}
