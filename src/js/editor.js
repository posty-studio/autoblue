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
			name="autoblue-share-panel"
			title={ __( 'Bluesky', 'autoblue' ) }
			icon={ BlueskyIcon }
		>
			<SharePanel />
		</PluginDocumentSettingPanel>
	);
};

registerPlugin( 'autoblue-share-panel', {
	render: Panel,
} );

const PrePublishSharePanel = () => (
	<PluginPrePublishPanel
		title={ __( 'Bluesky', 'autoblue' ) }
		initialOpen={ true }
		icon={ BlueskyIcon }
	>
		<SharePanel />
	</PluginPrePublishPanel>
);

registerPlugin( 'autoblue-prepublish-share-panel', {
	render: PrePublishSharePanel,
} );
