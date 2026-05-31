import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import {
	TabPanel,
	SnackbarList,
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import { Logo } from './../../icons';
import Settings from './../settings';
import Logs from './../logs';
import Records from './../records';
import useSettings from './../../hooks/use-settings';
import styles from './styles.module.scss';

const TABS = [ 'settings', 'logs', 'records' ];

const Notices = () => {
	const { removeNotice } = useDispatch( noticesStore );
	const notices = useSelect( ( select ) =>
		select( noticesStore ).getNotices()
	);
	const snackbarNotices = notices.filter(
		( { type } ) => type === 'snackbar'
	);

	return (
		<SnackbarList
			className={ styles.notices }
			notices={ snackbarNotices }
			onRemove={ removeNotice }
		/>
	);
};

const AdminPage = () => {
	const { isStandardSiteEnabled } = useSettings();

	const getInitialTab = () => {
		const hash = window.location.hash.replace( '#', '' );
		return TABS.includes( hash ) ? hash : 'settings';
	};

	const [ activeTab, setActiveTab ] = useState( getInitialTab() );

	const handleTabChange = ( tabName ) => {
		setActiveTab( tabName );
		window.location.hash = tabName;
	};

	const tabs = [
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
	];

	if ( isStandardSiteEnabled ) {
		tabs.push( {
			name: 'records',
			title: __( 'Records', 'autoblue' ),
			className: 'autoblue-records',
		} );
	}

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
				tabs={ tabs }
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
								{ tab.name === 'records' && <Records /> }
							</VStack>
						</div>
					);
				} }
			</TabPanel>

			<Notices />
		</>
	);
};

export default AdminPage;
