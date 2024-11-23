import { __ } from '@wordpress/i18n';
import { BaseControl, __experimentalText as Text } from '@wordpress/components';
import styles from './styles.module.scss';
import Header from './../header';
import NoAccountsPlaceholder from './../no-accounts-placeholder';
import AccountList from './../account-list';
import useAccounts from './../../hooks/use-accounts';

const AdminPage = () => {
	const { hasAccounts } = useAccounts();

	return (
		<>
			<Header />
			<div className={ styles.container }>
				<div className={ styles.wrapper }>
					<BaseControl label={ __( 'Bluesky account', 'autoblue' ) }>
						{ hasAccounts ? (
							<AccountList />
						) : (
							<NoAccountsPlaceholder />
						) }
					</BaseControl>
					<Text variant="muted">
						Bluesky for WordPress is built by Daniel Post. Not
						officially affiliated with or endorsed by Bluesky.
					</Text>
				</div>
			</div>
		</>
	);
};

export default AdminPage;
