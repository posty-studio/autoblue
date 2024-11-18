import { __ } from '@wordpress/i18n';
import {
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	ToggleControl,
	TextareaControl,
	BaseControl,
} from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { createInterpolateElement } from '@wordpress/element';
import useSettings from './../../hooks/use-settings';
import AccountInfo from './../account-info';

const SharePanel = () => {
	const { editPost } = useDispatch( 'core/editor' );
	const { editEntityRecord } = useDispatch( 'core' );
	const { appPassword, accountDIDs } = useSettings();

	const { postId, postType, postTitle, bsky4wpPostMessage } = useSelect(
		( select ) => {
			const {
				getCurrentPostId,
				getCurrentPostType,
				getEditedPostAttribute,
			} = select( 'core/editor' );
			const _postId = getCurrentPostId();
			return {
				postId: _postId,
				postType: getCurrentPostType(),
				postTitle: getEditedPostAttribute( 'title' ),
				bsky4wpPostMessage:
					getEditedPostAttribute( 'meta' )?.bsky4wp_post_message,
			};
		},
		[]
	);

	if ( postType !== 'post' ) {
		return null;
	}

	if ( ! appPassword || accountDIDs.length === 0 ) {
		return (
			<p>
				{ createInterpolateElement(
					__(
						'Please enter your Bluesky app password and select an account in the <a>settings page</a> to start sharing.',
						'bsky-for-wp'
					),
					{
						a: (
							<a href="/wp-admin/options-general.php?page=bsky-for-wp">
								{ __( 'settings page', 'bsky-for-wp' ) }
							</a>
						),
					}
				) }
			</p>
		);
	}

	return (
		<VStack spacing={ 3 }>
			<ToggleControl
				label={ __( 'Share to Bluesky', 'bsky-for-wp' ) }
				checked={ true }
			/>
			<TextareaControl
				label={ __( 'Message', 'bsky-for-wp' ) }
				help={ __(
					'Add a message to the Bluesky post. If left empty, the post title will be used.',
					'bsky-for-wp'
				) }
				value={ bsky4wpPostMessage }
				placeholder={ postTitle }
				maxLength={ 250 }
				onChange={ ( value ) =>
					editPost( { meta: { bsky4wp_post_message: value } } )
				}
			/>
			<BaseControl
				label={ __( 'Sharing to:', 'bsky-for-wp' ) }
				id="bsky-for-wp-account"
			>
				{ accountDIDs.map( ( did ) => (
					<div key={ did }>
						<AccountInfo did={ did } />
					</div>
				) ) }
			</BaseControl>
		</VStack>
	);
};

export default SharePanel;
