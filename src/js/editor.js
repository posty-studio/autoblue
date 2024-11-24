import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import {
	PluginPrePublishPanel,
	PluginPostPublishPanel,
	PluginDocumentSettingPanel,
} from '@wordpress/editor';
import { LogoImage } from './icons';
import SharePanel from './components/share-panel';
import PublishedPostPanel from './components/published-post-panel';

const Panel = () => {
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

const PrePublishSharePanel = () => (
	<PluginPrePublishPanel
		title={ 'Autoblue' }
		initialOpen={ true }
		icon={ LogoImage }
	>
		<SharePanel />
	</PluginPrePublishPanel>
);

const PostPublishSharePanel = () => (
	<PluginPostPublishPanel
		title={ 'Autoblue' }
		initialOpen={ true }
		icon={ LogoImage }
	>
		<PublishedPostPanel />
	</PluginPostPublishPanel>
);

registerPlugin( 'autoblue-share-panel', {
	render: Panel,
} );

registerPlugin( 'autoblue-prepublish-share-panel', {
	render: PrePublishSharePanel,
} );

registerPlugin( 'autoblue-postpublish-share-panel', {
	render: PostPublishSharePanel,
} );
