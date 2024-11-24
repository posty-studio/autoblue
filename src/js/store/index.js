import { createReduxStore, register, select } from '@wordpress/data';
import reducer from './reducers';
import selectors from './selectors';
import actions from './actions';
import resolvers from './resolvers';

export const STORE_NAME = 'autoblue';

const storeConfig = {
	reducer,
	selectors,
	actions,
	resolvers,
	initialState: autoblue.initialState,
};

let existingStore;
try {
	existingStore = select( STORE_NAME );
} catch ( e ) {}

if ( ! existingStore ) {
	const store = createReduxStore( STORE_NAME, storeConfig );
	register( store );
}
