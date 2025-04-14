import { registerPlugin } from '@wordpress/plugins';
import {
	PluginPrePublishPanel,
	PluginPostPublishPanel,
	PluginDocumentSettingPanel,
} from '@wordpress/editor';
import { select } from '@wordpress/data';
import { LogoImage } from './icons';
import SharePanel from './components/share-panel';
import PublishedPostPanel from './components/published-post-panel';

// TODO: Add support for other post types.
const ENABLED_POST_TYPES = [ 'post' ];

const isEnabled = () => {
	const currentPostType = select( 'core/editor' ).getCurrentPostType();
	return ENABLED_POST_TYPES.includes( currentPostType );
};

const Panel = () => {
	if ( ! isEnabled() ) {
		return null;
	}

	return (
		<PluginDocumentSettingPanel
			name="autoblue-share-panel"
			title={ 'Autoblue' }
			icon={ LogoImage }
		>
			<SharePanel />
		</PluginDocumentSettingPanel>
	);
};

const PrePublishSharePanel = () => {
	if ( ! isEnabled() ) {
		return null;
	}

	return (
		<PluginPrePublishPanel
			title={ 'Autoblue' }
			initialOpen={ true }
			icon={ LogoImage }
		>
			<SharePanel />
		</PluginPrePublishPanel>
	);
};

const PostPublishSharePanel = () => {
	if ( ! isEnabled() ) {
		return null;
	}

	return (
		<PluginPostPublishPanel
			title={ 'Autoblue' }
			initialOpen={ true }
			icon={ LogoImage }
		>
			<PublishedPostPanel />
		</PluginPostPublishPanel>
	);
};

registerPlugin( 'autoblue-share-panel', {
	render: Panel,
} );

registerPlugin( 'autoblue-prepublish-share-panel', {
	render: PrePublishSharePanel,
} );

registerPlugin( 'autoblue-postpublish-share-panel', {
	render: PostPublishSharePanel,
} );
