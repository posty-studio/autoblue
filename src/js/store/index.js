import { createReduxStore, register } from '@wordpress/data';
import reducer from './reducers';
import selectors from './selectors';
import actions from './actions';
import resolvers from './resolvers';

export const STORE_NAME = 'autoblue';
console.log( Autoblue );
const store = createReduxStore( STORE_NAME, {
	reducer,
	selectors,
	actions,
	resolvers,
	initialState: Autoblue.initialState,
} );

register( store );
