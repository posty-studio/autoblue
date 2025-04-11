import { __ } from '@wordpress/i18n';
import {
	TextControl,
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import useShares from './../../hooks/use-shares';
import ShareStatus from './../share-status';

const PublishedPostPanel = () => {
	const { editPost } = useDispatch( 'core/editor' );
	const { shares, isSharingEnabled } = useShares();

	const { postUrl } = useSelect( ( select ) => {
		const { getEditedPostAttribute } = select( 'core/editor' );

		return {
			postUrl: getEditedPostAttribute( 'meta' )?.autoblue_post_url,
		};
	}, [] );

	const setPostUrl = ( value ) => {
		editPost( {
			meta: { autoblue_post_url: value },
		} );
	};

	return (
		<VStack spacing={ 4 }>
			<ShareStatus />
			{ ( ! isSharingEnabled || shares.length > 0 ) && (
				<TextControl
					label={ __( 'Bluesky Post URL', 'autoblue' ) }
					value={ postUrl }
					placeholder={ shares.length > 0 ? shares[ 0 ].url : '' }
					onChange={ setPostUrl }
					help={ __(
						'If you used Autoblue to share this post, replies from that URL will show up automatically. If you did not use Autoblue or want to show replies from a different URL, you can enter it here.',
						'autoblue'
					) }
				/>
			) }
		</VStack>
	);
};

export default PublishedPostPanel;
