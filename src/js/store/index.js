import { createReduxStore, register } from '@wordpress/data';
import reducer from './reducers';
import selectors from './selectors';
import actions from './actions';
import resolvers from './resolvers';

export const STORE_NAME = 'bsky4wp';
console.log( BSKY4WP );
const store = createReduxStore( STORE_NAME, {
	reducer,
	selectors,
	actions,
	resolvers,
	initialState: BSKY4WP.initialState,
} );

register( store );
