import { __ } from '@wordpress/i18n';
import {
	Spinner,
	__experimentalConfirmDialog as ConfirmDialog,
} from '@wordpress/components';
import { useState } from '@wordpress/element'; // Add this import
import useAccounts from './../../hooks/use-accounts';
import AccountInfo from './../account-info';

const AccountList = () => {
	const { accounts, isLoading, deleteAccount } = useAccounts();
	const [ isOpen, setIsOpen ] = useState( false );
	const [ accountToDisconnect, setAccountToDisconnect ] = useState( null );

	if ( isLoading ) {
		return <Spinner />;
	}

	const handleDisconnectClick = ( account ) => {
		setAccountToDisconnect( account );
		setIsOpen( true );
	};

	const handleConfirm = async () => {
		try {
			if ( accountToDisconnect ) {
				await deleteAccount( accountToDisconnect.did );
			}
		} catch ( error ) {
			console.error( error );
		} finally {
			setIsOpen( false );
			setAccountToDisconnect( null );
		}
	};

	const handleCancel = () => {
		setIsOpen( false );
		setAccountToDisconnect( null );
	};

	return (
		<div>
			<ConfirmDialog
				isOpen={ isOpen }
				onConfirm={ handleConfirm }
				onCancel={ handleCancel }
				confirmButtonText={ __( 'Disconnect', 'autoblue' ) }
			>
				{ __(
					'Are you sure you want to disconnect this account?',
					'autoblue'
				) }
			</ConfirmDialog>

			{ accounts.map( ( account ) => (
				<AccountInfo
					key={ account.did }
					account={ account }
					onDelete={ () => handleDisconnectClick( account ) }
					deleteLabel={ __( 'Disconnect', 'autoblue' ) }
					size="large"
				/>
			) ) }
		</div>
	);
};

export default AccountList;
