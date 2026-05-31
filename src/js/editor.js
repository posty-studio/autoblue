import { registerPlugin } from '@wordpress/plugins';
import {
	PluginPrePublishPanel,
	PluginPostPublishPanel,
	PluginDocumentSettingPanel,
} from '@wordpress/editor';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { LogoImage } from './icons';
import SharePanel from './components/share-panel';
import PublishedPostPanel from './components/published-post-panel';

const useIsEnabled = () => {
	const currentPostType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);
	const [ enabledPostTypes ] = useEntityProp(
		'root',
		'site',
		'autoblue_enabled_post_types'
	);
	const resolved = Array.isArray( enabledPostTypes )
		? enabledPostTypes
		: [ 'post' ];
	return resolved.includes( currentPostType );
};

const Panel = () => {
	if ( ! useIsEnabled() ) {
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
	if ( ! useIsEnabled() ) {
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
	if ( ! useIsEnabled() ) {
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
