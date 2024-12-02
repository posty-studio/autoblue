import { useSelect, useDispatch } from '@wordpress/data';
import { STORE_NAME } from '../store';

const useAccounts = () => {
	const { accounts, isLoading, error } = useSelect(
		( select ) => ( {
			accounts: select( STORE_NAME ).getAccounts(),
		} ),
		[]
	);

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
