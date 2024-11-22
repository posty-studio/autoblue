import { createRoot } from 'react-dom/client';
import domReady from '@wordpress/dom-ready';
import AdminPage from './components/admin-page';
import './store';

domReady( () => {
	const rootElement = document.getElementById( 'bsky-for-wp' );
	if ( rootElement ) {
		createRoot( rootElement ).render( <AdminPage /> );
	}
} );
