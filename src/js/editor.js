import { registerPlugin } from '@wordpress/plugins';
import SharePanel from './components/share-panel';

registerPlugin( 'bsky4wp-share-panel', {
	render: SharePanel,
} );
