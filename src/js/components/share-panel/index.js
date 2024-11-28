import { __ } from '@wordpress/i18n';
import {
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	ToggleControl,
	TextareaControl,
	BaseControl,
	Button,
} from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import AccountInfo from './../account-info';
import PublishedPostPanel from './../published-post-panel';
import useNewAccountModal from './../new-account-modal';
import useAccounts from './../../hooks/use-accounts';
import styles from './styles.module.scss';

const SharePanel = () => {
	const { accounts } = useAccounts();
	const { renderModal, openModal } = useNewAccountModal();
	const { editPost } = useDispatch( 'core/editor' );

	const { postType, postStatus, isEnabled, customMessage } = useSelect(
		( select ) => {
			const { getCurrentPostType, getEditedPostAttribute } =
				select( 'core/editor' );

			return {
				postType: getCurrentPostType(),
				postStatus: getEditedPostAttribute( 'status' ),
				isEnabled: getEditedPostAttribute( 'meta' )?.autoblue_enabled,
				customMessage:
					getEditedPostAttribute( 'meta' )?.autoblue_custom_message,
			};
		},
		[]
	);

	// TODO: Add support for other post types.
	if ( postType !== 'post' ) {
		return null;
	}

	if ( postStatus === 'publish' ) {
		return <PublishedPostPanel />;
	}

	if ( ! accounts.length ) {
		return (
			<VStack>
				<Button variant="secondary" onClick={ () => openModal() }>
					{ __( 'Connect a Bluesky account', 'autoblue' ) }
				</Button>
				{ renderModal() }
			</VStack>
		);
	}

	const setIsEnabled = ( value ) => {
		editPost( {
			meta: { autoblue_enabled: value },
		} );
	};

	const setCustomMessage = ( value ) => {
		editPost( {
			meta: { autoblue_custom_message: value },
		} );
	};

	return (
		<VStack spacing={ 3 }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Share to Bluesky', 'autoblue' ) }
				checked={ isEnabled }
				onChange={ setIsEnabled }
			/>
			{ isEnabled && (
				<>
					<TextareaControl
						__nextHasNoMarginBottom
						label={ __( 'Message', 'autoblue' ) }
						help={ __(
							'Add an optional message to the Bluesky post.',
							'autoblue'
						) }
						value={ customMessage }
						maxLength={ 250 }
						onChange={ setCustomMessage }
					/>
					<BaseControl
						__nextHasNoMarginBottom
						label={ __( 'Sharing to:', 'autoblue' ) }
						id="autoblue-account"
					>
						{ accounts.map( ( account ) => (
							<AccountInfo
								key={ account.did }
								account={ account }
								className={ styles.account }
								size="small"
							/>
						) ) }
					</BaseControl>
				</>
			) }
		</VStack>
	);
};

export default SharePanel;
