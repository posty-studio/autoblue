import { __ } from '@wordpress/i18n';
import { Button, BaseControl } from '@wordpress/components';
import styles from './styles.module.scss';
import useNewAccountModal from './../new-account-modal';
import Header from './../header';
import AccountList from './../account-list';

const AdminPage = () => {
	const { renderModal, openModal } = useNewAccountModal();

	return (
		<>
			<Header />
			<div className={ styles.container }>
				<div className={ styles.wrapper }>
					<BaseControl
						label={ __( 'Bluesky account', 'bsky-for-wp' ) }
					>
						<AccountList />
						<Button
							variant="primary"
							size="large"
							onClick={ () => openModal() }
						>
							{ __( 'Set up Bluesky account', 'bsky-for-wp' ) }
						</Button>
						{ renderModal() }
					</BaseControl>
					<p>
						Bluesky for WordPress is built by Daniel Post. Not
						affiliated with Bluesky.
					</p>
				</div>
			</div>
		</>
	);
};

export default AdminPage;
