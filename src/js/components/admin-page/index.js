import { __ } from '@wordpress/i18n';
import {
	TabPanel,
	__experimentalVStack as VStack,
} from '@wordpress/components';
import { Logo } from './../../icons';
import Settings from './../settings';
import Logs from './../logs';
import styles from './styles.module.scss';

const AdminPage = () => {
	return (
		<>
			<div className={ styles.header }>
				{ Logo }
				<span className={ styles.version }>{ autoblue.version }</span>
			</div>
			<TabPanel
				className={ styles.tabs }
				tabs={ [
					{
						name: 'settings',
						title: __( 'Settings', 'autoblue' ),
						className: 'autoblue-settings',
					},
					{
						name: 'logs',
						title: __( 'Logs', 'autoblue' ),
						className: 'autoblue-logs',
					},
				] }
			>
				{ ( tab ) => {
					return (
						<div className={ styles.container }>
							<VStack spacing={ 5 } className={ styles.wrapper }>
								{ /* Todo fix this */ }
								{ tab.name === 'settings' && <Logs /> }
								{ tab.name === 'logs' && <Settings /> }
							</VStack>
						</div>
					);
				} }
			</TabPanel>
		</>
	);
};

export default AdminPage;
