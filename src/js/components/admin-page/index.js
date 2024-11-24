import { __ } from '@wordpress/i18n';
import {
	BaseControl,
	Card,
	CardBody,
	__experimentalText as Text,
	__experimentalVStack as VStack,
} from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import styles from './styles.module.scss';
import Header from './../header';
import NoAccountsPlaceholder from './../no-accounts-placeholder';
import AccountList from './../account-list';
import SettingToggle from './../setting-toggle';
import useAccounts from './../../hooks/use-accounts';
import useSettings from './../../hooks/use-settings';

const AdminPage = () => {
	const { hasAccounts } = useAccounts();
	const { isEnabled, setIsEnabled } = useSettings();

	return (
		<>
			<Header />
			<div className={ styles.container }>
				<VStack spacing={ 5 } className={ styles.wrapper }>
					<BaseControl
						__nextHasNoMarginBottom
						label={ __( 'Bluesky account', 'autoblue' ) }
						id="autoblue-accounts"
					>
						{ hasAccounts ? (
							<AccountList />
						) : (
							<NoAccountsPlaceholder />
						) }
					</BaseControl>
					{ hasAccounts && (
						<BaseControl
							__nextHasNoMarginBottom
							label={ __( 'Settings', 'autoblue' ) }
							id="autoblue-settings"
						>
							<Card>
								<CardBody className={ styles.card }>
									<VStack spacing={ 2 }>
										<SettingToggle
											label={ __(
												'Automatically share posts to Bluesky',
												'autoblue'
											) }
											help={ __(
												'When enabled, new posts will be automatically shared to Bluesky. You can change this on a per-post basis.',
												'autoblue'
											) }
											checked={ isEnabled }
											onChange={ setIsEnabled }
										/>
									</VStack>
								</CardBody>
							</Card>
						</BaseControl>
					) }
					<Text variant="muted">
						{ createInterpolateElement(
							__(
								'Autoblue is created by <a>Daniel Post</a>. Not officially affiliated with or endorsed by Bluesky.',
								'autoblue'
							),
							{
								a: (
									// eslint-disable-next-line jsx-a11y/anchor-has-content
									<a
										href="https://danielpost.com?ref=autoblue_admin"
										target="_blank"
										rel="noreferrer"
									/>
								),
							}
						) }
					</Text>
				</VStack>
			</div>
		</>
	);
};

export default AdminPage;
