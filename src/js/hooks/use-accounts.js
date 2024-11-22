import { useSelect, useDispatch } from '@wordpress/data';
import { STORE_NAME } from '../store';

const useAccounts = () => {
	const { accounts } = useSelect(
		( select ) => ( {
			accounts: select( STORE_NAME ).getAccounts(),
		} ),
		[]
	);

	console.log( { accounts } );

	const { addAccount, deleteAccount } = useDispatch( STORE_NAME );

	const hasAccounts = accounts.length > 0;

	return {
		accounts,
		hasAccounts,
		addAccount,
		deleteAccount,
	};
};

export default useAccounts;
