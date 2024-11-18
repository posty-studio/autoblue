import { __ } from '@wordpress/i18n';
import {
	Card,
	CardBody,
	Button,
	BaseControl,
	TextControl,
	Spinner,
	NoticeList,
	__experimentalHeading as Heading,
} from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import styles from './styles.module.scss';
import AccountSearch from '../account-search';
import AccountInfo from '../account-info';
import useSettings from './../../hooks/use-settings';

const Notices = () => {
	const { removeNotice } = useDispatch( noticesStore );
	const notices = useSelect( ( select ) =>
		select( noticesStore ).getNotices()
	);

	if ( notices.length === 0 ) {
		return null;
	}

	return <NoticeList notices={ notices } onRemove={ removeNotice } />;
};

const AdminPage = () => {
	let {
		accountDIDs,
		setAccountDIDs,
		appPassword,
		setAppPassword,
		isDirty,
		isSaving,
		saveSettings,
	} = useSettings();

	console.log( {
		accountDIDs,
		appPassword,
		isDirty,
		isSaving,
		saveSettings,
	} );

	accountDIDs = [];

	const handleSubmit = ( event ) => {
		event.preventDefault();
		saveSettings();
	};

	return (
		<div className={ styles.container }>
			<Heading level={ 1 } className={ styles.heading }>
				{ __( 'Bluesky for WordPress ', 'bsky-for-wp' ) }
			</Heading>
			<Notices />
			<Card className={ styles.card }>
				<CardBody>
					<div className={ styles.wrapper }>
						{ accountDIDs.length > 0 ? (
							<BaseControl
								label={ __( 'Bluesky Account', 'bsky-for-wp' ) }
								id="bsky-for-wp-account"
							>
								{ accountDIDs.map( ( did ) => (
									<div
										className={ styles.account }
										key={ did }
									>
										<AccountInfo did={ did } />
										<Button
											icon="no-alt"
											isDestructive
											onClick={ () =>
												setAccountDIDs( [] )
											}
											label={ __(
												'Remove Account',
												'bsky-for-wp'
											) }
										/>
									</div>
								) ) }
							</BaseControl>
						) : (
							<AccountSearch
								onSelect={ ( did ) =>
									setAccountDIDs( [ ...accountDIDs, did ] )
								}
							/>
						) }
						<TextControl
							__nextHasNoMarginBottom
							label={ __( 'App Password', 'bsky-for-wp' ) }
							placeholder={ __(
								'abcd-6mdy-ue4z-j7mo',
								'bsky-for-wp'
							) }
							help={ createInterpolateElement(
								__(
									'You can create a new app password in your <a>Bluesky account settings</a>.',
									'bsky-for-wp'
								),
								{
									a: (
										<a
											href="https://bsky.app/settings/app-passwords"
											target="_blank"
											rel="noreferrer"
										/>
									),
								}
							) }
							value={ appPassword }
							onChange={ ( newAppPassword ) =>
								setAppPassword( newAppPassword )
							}
						/>
					</div>
				</CardBody>
			</Card>
			<Button
				variant="primary"
				type="submit"
				onClick={ handleSubmit }
				disabled={ ! isDirty || isSaving }
			>
				{ isSaving ? (
					<>
						<Spinner />
						{ __( 'Saving…', 'bsky-for-wp' ) }
					</>
				) : (
					__( 'Save', 'bsky-for-wp' )
				) }
			</Button>
		</div>
	);
};

export default AdminPage;
