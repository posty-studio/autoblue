import { __ } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import {
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	ToggleControl,
	TextareaControl,
	BaseControl,
	Button,
	Notice,
} from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import AccountInfo from './../account-info';
import PublishedPostPanel from './../published-post-panel';
import useConnectAccountModal from './../connect-account-modal';
import useAccounts from './../../hooks/use-accounts';
import useSettings from './../../hooks/use-settings';
import styles from './styles.module.scss';

const SharePanel = () => {
	const { accounts } = useAccounts();
	const { renderModal, openModal } = useConnectAccountModal();
	const { editPost } = useDispatch( 'core/editor' );
	const { isStandardSiteEnabled, isRootInstall } = useSettings();

	const { postStatus, isEnabled, customMessage, publishDocument } = useSelect(
		( select ) => {
			const { getEditedPostAttribute } = select( 'core/editor' );

			return {
				postStatus: getEditedPostAttribute( 'status' ),
				isEnabled: getEditedPostAttribute( 'meta' )?.autoblue_enabled,
				customMessage:
					getEditedPostAttribute( 'meta' )?.autoblue_custom_message,
				publishDocument:
					getEditedPostAttribute( 'meta' )?.autoblue_publish_document,
			};
		},
		[]
	);

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

	const setPublishDocument = ( value ) => {
		editPost( {
			meta: { autoblue_publish_document: value },
		} );
	};

	const hasBrokenConnection = accounts.some( ( a ) => a.needs_reauth );
	const effectiveIsEnabled = isEnabled && ! hasBrokenConnection;
	const standardSiteDisabledReason = ! isRootInstall
		? __(
				'standard.site is not available on subdirectory installs.',
				'autoblue'
		  )
		: ! isStandardSiteEnabled
		? __(
				'Enable standard.site publishing in Autoblue settings.',
				'autoblue'
		  )
		: hasBrokenConnection
		? __(
				'Reconnect your Bluesky account to publish documents.',
				'autoblue'
		  )
		: null;
	const standardSiteToggleEnabled =
		isStandardSiteEnabled && isRootInstall && ! hasBrokenConnection;

	return (
		<VStack spacing={ 3 }>
			{ hasBrokenConnection && (
				<Notice status="warning" isDismissible={ false }>
					{ createInterpolateElement(
						__(
							'Your Bluesky connection has expired. <a>Reconnect in Settings →</a>',
							'autoblue'
						),
						{
							a: (
								// eslint-disable-next-line jsx-a11y/anchor-has-content
								<a href="admin.php?page=autoblue" />
							),
						}
					) }
				</Notice>
			) }
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Share to Bluesky', 'autoblue' ) }
				checked={ effectiveIsEnabled }
				onChange={ setIsEnabled }
				disabled={ hasBrokenConnection }
			/>
			{ effectiveIsEnabled && (
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __(
						'Publish as a standard.site document',
						'autoblue'
					) }
					help={
						standardSiteDisabledReason ||
						__(
							'Stores the full article on your AT Protocol PDS for discovery by other readers.',
							'autoblue'
						)
					}
					checked={ standardSiteToggleEnabled && !! publishDocument }
					onChange={ setPublishDocument }
					disabled={ ! standardSiteToggleEnabled }
				/>
			) }
			{ effectiveIsEnabled && (
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
