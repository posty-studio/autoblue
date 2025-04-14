import { __ } from '@wordpress/i18n';
import {
	Spinner,
	__experimentalConfirmDialog as ConfirmDialog, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import useAccounts from './../../hooks/use-accounts';
import useWindowDimensions from '../../hooks/use-window-dimensions';
import AccountInfo from './../account-info';

const AccountList = () => {
	const { width } = useWindowDimensions();
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
					size={ width > 500 ? 'large' : 'small' }
				/>
			) ) }
		</div>
	);
};

export default AccountList;
