import { createRoot } from 'react-dom/client';
import domReady from '@wordpress/dom-ready';
import AdminPage from './components/admin-page';

domReady( () => {
	const rootElement = document.getElementById( 'autoblue' );
	if ( rootElement ) {
		createRoot( rootElement ).render( <AdminPage /> );
	}
} );
