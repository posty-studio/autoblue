import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import {
	TabPanel,
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { Logo } from './../../icons';
import Settings from './../settings';
import Logs from './../logs';
import styles from './styles.module.scss';

const TABS = [ 'settings', 'logs' ];

const AdminPage = () => {
	const getInitialTab = () => {
		const hash = window.location.hash.replace( '#', '' );
		return TABS.includes( hash ) ? hash : 'settings';
	};

	const [ activeTab, setActiveTab ] = useState( getInitialTab() );

	const handleTabChange = ( tabName ) => {
		setActiveTab( tabName );
		window.location.hash = tabName;
	};

	return (
		<>
			<div className={ styles.header }>
				{ Logo }
				<span className={ styles.version }>{ autoblue.version }</span>
			</div>
			<TabPanel
				className={ styles.tabs }
				initialTabName={ activeTab }
				onSelect={ handleTabChange }
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
							<VStack
								spacing={ 5 }
								className={ clsx(
									styles.wrapper,
									styles[ tab.name ]
								) }
							>
								{ tab.name === 'settings' && <Settings /> }
								{ tab.name === 'logs' && <Logs /> }
							</VStack>
						</div>
					);
				} }
			</TabPanel>
		</>
	);
};

export default AdminPage;
