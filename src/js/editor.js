import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import {
	PluginPrePublishPanel,
	PluginDocumentSettingPanel,
} from '@wordpress/editor';
import { BlueskyIcon } from './icons';
import SharePanel from './components/share-panel';

const Panel = () => {
	return (
		<PluginDocumentSettingPanel
			name="bsky4wp-share-panel"
			title={ __( 'Bluesky', 'bsky-for-wp' ) }
			icon={ BlueskyIcon }
		>
			<SharePanel />
		</PluginDocumentSettingPanel>
	);
};

registerPlugin( 'bsky4wp-share-panel', {
	render: Panel,
} );

const PrePublishSharePanel = () => (
	<PluginPrePublishPanel
		title={ __( 'Bluesky', 'bsky-for-wp' ) }
		initialOpen={ true }
		icon={ BlueskyIcon }
	>
		<SharePanel />
	</PluginPrePublishPanel>
);

registerPlugin( 'bsky4wp-prepublish-share-panel', {
	render: PrePublishSharePanel,
} );
