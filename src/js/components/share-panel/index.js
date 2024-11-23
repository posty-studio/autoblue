import { __ } from '@wordpress/i18n';
import {
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	ToggleControl,
	TextareaControl,
	BaseControl,
} from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { createInterpolateElement } from '@wordpress/element';
import AccountInfo from './../account-info';

const SharePanel = () => {
	const { editPost } = useDispatch( 'core/editor' );
	const { editEntityRecord } = useDispatch( 'core' );

	const { postId, postType, postTitle, autobluePostMessage } = useSelect(
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
				autobluePostMessage:
					getEditedPostAttribute( 'meta' )?.autoblue_post_message,
			};
		},
		[]
	);

	if ( postType !== 'post' ) {
		return null;
	}

	const appPassword = '';
	const accountDIDs = [];

	if ( ! appPassword || accountDIDs.length === 0 ) {
		return (
			<p>
				{ createInterpolateElement(
					__(
						'Please enter your Bluesky app password and select an account in the <a>settings page</a> to start sharing.',
						'autoblue'
					),
					{
						a: (
							<a href="/wp-admin/options-general.php?page=autoblue">
								{ __( 'settings page', 'autoblue' ) }
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
				label={ __( 'Share to Bluesky', 'autoblue' ) }
				checked={ true }
			/>
			<TextareaControl
				label={ __( 'Message', 'autoblue' ) }
				help={ __(
					'Add a message to the Bluesky post. If left empty, the post title will be used.',
					'autoblue'
				) }
				value={ autobluePostMessage }
				placeholder={ postTitle }
				maxLength={ 250 }
				onChange={ ( value ) =>
					editPost( { meta: { autoblue_post_message: value } } )
				}
			/>
			<BaseControl
				label={ __( 'Sharing to:', 'autoblue' ) }
				id="autoblue-account"
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
