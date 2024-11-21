import { Spinner } from '@wordpress/components';
import useAccounts from './../../hooks/use-accounts';
import AccountInfo from './../account-info';

const AccountList = () => {
	const { accounts, isLoading, deleteAccount } = useAccounts();

	if ( isLoading ) {
		return <Spinner />;
	}

	console.log( accounts );

	console.log( accounts.length );

	const onDeleteAccount = async ( did ) => {
		try {
			await deleteAccount( did );
		} catch ( error ) {
			console.error( error );
		}
	};

	return (
		<div>
			{ accounts.map( ( account ) => (
				<AccountInfo
					key={ account.did }
					account={ account }
					onDelete={ () => onDeleteAccount( account.did ) }
				/>
			) ) }
		</div>
	);
};

export default AccountList;
