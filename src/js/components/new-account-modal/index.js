import { __ } from '@wordpress/i18n';
import { useState, createInterpolateElement } from '@wordpress/element';
import {
	Modal,
	TextControl,
	Button,
	Spinner,
	Icon,
	__experimentalHStack as HStack,
	__experimentalVStack as VStack,
} from '@wordpress/components';
import AccountInfo from '../account-info';
import AccountSearch from '../account-search';
import useAccounts from '../../hooks/use-accounts';
import { warning } from '@wordpress/icons';
import styles from './styles.module.scss';

const NewAccountModal = ( {
	isOpen,
	onClose,
	selectedAccount,
	onSelectAccount,
	appPassword,
	onAppPasswordChange,
	onAddAccount,
	status,
	errorMessage,
} ) => {
	if ( ! isOpen ) return null;

	console.log( { status, errorMessage } );

	return (
		<Modal
			title={ __( 'Connect Bluesky account', 'bsky-for-wp' ) }
			onRequestClose={ onClose }
			focusOnMount="firstContentElement"
			size="medium"
		>
			<VStack spacing={ 4 }>
				{ selectedAccount ? (
					<AccountInfo
						account={ selectedAccount }
						onDelete={ () => onSelectAccount( null ) }
					/>
				) : (
					<AccountSearch onSelect={ onSelectAccount } />
				) }
				<TextControl
					__nextHasNoMarginBottom
					label={ __( 'App Password', 'bsky-for-wp' ) }
					help={ createInterpolateElement(
						__(
							'You can create a new app password in your <a>Bluesky account settings</a>.',
							'bsky-for-wp'
						),
						{
							a: (
								<a
									href="https://bsky.app/settings/app-passwords"
									target="_blank"
									rel="noreferrer"
								/>
							),
						}
					) }
					value={ appPassword }
					onChange={ onAppPasswordChange }
				/>
				<HStack alignment="left">
					<Button
						__next40pxDefaultSize
						variant="primary"
						onClick={ onAddAccount }
						disabled={ status === 'loading' }
					>
						{ __( 'Connect Account', 'bsky-for-wp' ) }
					</Button>
					{ status === 'loading' && <Spinner /> }
				</HStack>
				{ status !== 'loading' && errorMessage && (
					<HStack alignment="left" className={ styles.error }>
						<Icon icon={ warning } />
						<p>{ errorMessage }</p>
					</HStack>
				) }
			</VStack>
		</Modal>
	);
};

const useNewAccountModal = ( initialIsOpen = false ) => {
	const [ isOpen, setIsOpen ] = useState( initialIsOpen );
	const [ selectedAccount, setSelectedAccount ] = useState( null );
	const [ appPassword, setAppPassword ] = useState( '' );
	const [ status, setStatus ] = useState( 'idle' );
	const [ errorMessage, setErrorMessage ] = useState( '' );
	const { addAccount } = useAccounts();

	const openModal = () => setIsOpen( true );
	const closeModal = () => {
		setIsOpen( false );
		setSelectedAccount( null );
		setAppPassword( '' );
	};

	const handleAppPasswordChange = ( newAppPassword ) => {
		setAppPassword( newAppPassword );
	};

	const handleAddAccount = async () => {
		if ( ! selectedAccount ) {
			setErrorMessage(
				__( 'Please pick an account to add.', 'bsky4wp' )
			);
			return;
		}

		if ( ! appPassword ) {
			setErrorMessage( __( 'Please enter an app password.', 'bsky4wp' ) );
			return;
		}

		setStatus( 'loading' );

		try {
			await addAccount( selectedAccount.did, appPassword );
			setStatus( 'success' );
			setErrorMessage( '' );
			closeModal();
		} catch ( error ) {
			setStatus( 'error' );
			setErrorMessage(
				__(
					'Something went wrong. Make sure the app password is correct.',
					'bsky4wp'
				)
			);
		}
	};

	const renderModal = () => (
		<NewAccountModal
			isOpen={ isOpen }
			onClose={ closeModal }
			selectedAccount={ selectedAccount }
			onSelectAccount={ setSelectedAccount }
			appPassword={ appPassword }
			onAppPasswordChange={ handleAppPasswordChange }
			onAddAccount={ handleAddAccount }
			status={ status }
			errorMessage={ errorMessage }
		/>
	);

	return {
		renderModal,
		isOpen,
		openModal,
		closeModal,
	};
};

export default useNewAccountModal;
