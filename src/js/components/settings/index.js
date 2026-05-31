import { __ } from '@wordpress/i18n';
import {
	BaseControl,
	Button,
	Card,
	CardBody,
	CheckboxControl,
	Notice,
	Spinner,
	TextControl,
	TextareaControl,
	__experimentalHStack as HStack,
	__experimentalText as Text,
	__experimentalVStack as VStack,
} from '@wordpress/components';
import {
	createInterpolateElement,
	useEffect,
	useState,
} from '@wordpress/element';
import NoAccountsPlaceholder from './../no-accounts-placeholder';
import AccountList from './../account-list';
import SettingToggle from './../setting-toggle';
import useAccounts from './../../hooks/use-accounts';
import useSettings from './../../hooks/use-settings';
import styles from './styles.module.scss';

const Settings = () => {
	const { hasAccounts } = useAccounts();
	const {
		isEnabled,
		setIsEnabled,
		enabledPostTypes,
		isLoadingPostTypes,
		availablePostTypes,
		togglePostType,
		isStandardSiteEnabled,
		isLoadingStandardSite,
		setIsStandardSiteEnabled,
		publicationOverrides,
		savePublicationOverrides,
		standardSitePublication,
		isRootInstall,
		isSaving,
	} = useSettings();

	const savedName = publicationOverrides.name ?? '';
	const savedDescription = publicationOverrides.description ?? '';
	const savedThemeBackground = publicationOverrides.theme_background ?? '';
	const savedThemeForeground = publicationOverrides.theme_foreground ?? '';
	const savedThemeAccent = publicationOverrides.theme_accent ?? '';
	const savedThemeAccentFg = publicationOverrides.theme_accent_foreground ?? '';

	const [ draftName, setDraftName ] = useState( savedName );
	const [ draftDescription, setDraftDescription ] = useState( savedDescription );
	const [ draftThemeBackground, setDraftThemeBackground ] = useState( savedThemeBackground );
	const [ draftThemeForeground, setDraftThemeForeground ] = useState( savedThemeForeground );
	const [ draftThemeAccent, setDraftThemeAccent ] = useState( savedThemeAccent );
	const [ draftThemeAccentFg, setDraftThemeAccentFg ] = useState( savedThemeAccentFg );

	// Re-sync drafts when the saved overrides change (initial load + after our own save).
	useEffect( () => {
		setDraftName( savedName );
	}, [ savedName ] );
	useEffect( () => {
		setDraftDescription( savedDescription );
	}, [ savedDescription ] );
	useEffect( () => {
		setDraftThemeBackground( savedThemeBackground );
	}, [ savedThemeBackground ] );
	useEffect( () => {
		setDraftThemeForeground( savedThemeForeground );
	}, [ savedThemeForeground ] );
	useEffect( () => {
		setDraftThemeAccent( savedThemeAccent );
	}, [ savedThemeAccent ] );
	useEffect( () => {
		setDraftThemeAccentFg( savedThemeAccentFg );
	}, [ savedThemeAccentFg ] );

	const isPublicationDirty =
		draftName !== savedName ||
		draftDescription !== savedDescription ||
		draftThemeBackground !== savedThemeBackground ||
		draftThemeForeground !== savedThemeForeground ||
		draftThemeAccent !== savedThemeAccent ||
		draftThemeAccentFg !== savedThemeAccentFg;

	const handleUpdatePublication = () => {
		const next = {};
		if ( draftName.trim() !== '' ) {
			next.name = draftName;
		}
		if ( draftDescription.trim() !== '' ) {
			next.description = draftDescription;
		}
		if ( draftThemeBackground.trim() !== '' ) {
			next.theme_background = draftThemeBackground;
		}
		if ( draftThemeForeground.trim() !== '' ) {
			next.theme_foreground = draftThemeForeground;
		}
		if ( draftThemeAccent.trim() !== '' ) {
			next.theme_accent = draftThemeAccent;
		}
		if ( draftThemeAccentFg.trim() !== '' ) {
			next.theme_accent_foreground = draftThemeAccentFg;
		}
		savePublicationOverrides( next );
	};

	return (
		<>
			<BaseControl
				__nextHasNoMarginBottom
				label={ __( 'Bluesky account', 'autoblue' ) }
				id="autoblue-accounts"
			>
				{ hasAccounts ? <AccountList /> : <NoAccountsPlaceholder /> }
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
			{ hasAccounts && availablePostTypes.length > 0 && (
				<BaseControl
					__nextHasNoMarginBottom
					label={ __( 'Post types', 'autoblue' ) }
					id="autoblue-post-types"
				>
					<Card>
						<CardBody className={ styles.card }>
							<VStack spacing={ 3 }>
								<Text variant="muted">
									{ __(
										'Choose which post types can be shared to Bluesky.',
										'autoblue'
									) }
								</Text>
								{ isLoadingPostTypes ? (
									<Spinner />
								) : (
									<VStack spacing={ 2 }>
										{ availablePostTypes.map( ( pt ) => (
											<CheckboxControl
												key={ pt.slug }
												__nextHasNoMarginBottom
												label={ pt.label }
												checked={ enabledPostTypes.includes(
													pt.slug
												) }
												onChange={ ( checked ) =>
													togglePostType(
														pt.slug,
														checked
													)
												}
											/>
										) ) }
									</VStack>
								) }
							</VStack>
						</CardBody>
					</Card>
				</BaseControl>
			) }
			{ hasAccounts && (
				<BaseControl
					__nextHasNoMarginBottom
					label={ __( 'standard.site', 'autoblue' ) }
					id="autoblue-standard-site"
				>
					<Card>
						<CardBody className={ styles.card }>
							{ ! isRootInstall ? (
								<Notice
									status="info"
									isDismissible={ false }
								>
									{ __(
										'standard.site requires WordPress to be installed at your domain root. This install lives in a subdirectory, so the feature is unavailable.',
										'autoblue'
									) }
								</Notice>
							) : (
								<VStack spacing={ 3 }>
									{ isLoadingStandardSite ? (
										<Spinner />
									) : (
										<SettingToggle
											label={ __(
												'Publish posts as standard.site documents',
												'autoblue'
											) }
											help={ __(
												'Also writes a site.standard.document record to your PDS for every post shared to Bluesky, so other AT-aware readers can discover it.',
												'autoblue'
											) }
											checked={ isStandardSiteEnabled }
											onChange={ setIsStandardSiteEnabled }
										/>
									) }
									{ isStandardSiteEnabled && (
										<VStack spacing={ 3 }>
											<TextControl
												__nextHasNoMarginBottom
												label={ __(
													'Publication name',
													'autoblue'
												) }
												value={ draftName }
												placeholder={
													standardSitePublication.siteName ||
													''
												}
												help={ __(
													'Leave empty to use the WordPress site title.',
													'autoblue'
												) }
												onChange={ setDraftName }
											/>
											<TextareaControl
												__nextHasNoMarginBottom
												label={ __(
													'Publication description',
													'autoblue'
												) }
												value={ draftDescription }
												placeholder={
													standardSitePublication.siteDesc ||
													''
												}
												help={ __(
													'Leave empty to use the WordPress site tagline.',
													'autoblue'
												) }
												onChange={ setDraftDescription }
											/>
											<TextControl
												__nextHasNoMarginBottom
												label={ __(
													'Publication URL',
													'autoblue'
												) }
												value={
													standardSitePublication.url ||
													''
												}
												readOnly
											/>
											{ standardSitePublication.publishedUri && (
												<TextControl
													__nextHasNoMarginBottom
													label={ __(
														'Publication record (AT-URI)',
														'autoblue'
													) }
													value={
														standardSitePublication.publishedUri
													}
													readOnly
												/>
											) }
											<Text variant="muted">
												{ __(
													'Theme colors are used by standard.site readers to style your publication. Leave blank to omit.',
													'autoblue'
												) }
											</Text>
											<HStack spacing={ 3 } alignment="top">
												<TextControl
													__nextHasNoMarginBottom
													label={ __(
														'Background',
														'autoblue'
													) }
													value={
														draftThemeBackground
													}
													placeholder="#ffffff"
													onChange={
														setDraftThemeBackground
													}
												/>
												<TextControl
													__nextHasNoMarginBottom
													label={ __(
														'Foreground',
														'autoblue'
													) }
													value={
														draftThemeForeground
													}
													placeholder="#111111"
													onChange={
														setDraftThemeForeground
													}
												/>
											</HStack>
											<HStack spacing={ 3 } alignment="top">
												<TextControl
													__nextHasNoMarginBottom
													label={ __(
														'Accent',
														'autoblue'
													) }
													value={ draftThemeAccent }
													placeholder="#0073aa"
													onChange={
														setDraftThemeAccent
													}
												/>
												<TextControl
													__nextHasNoMarginBottom
													label={ __(
														'Accent foreground',
														'autoblue'
													) }
													value={
														draftThemeAccentFg
													}
													placeholder="#ffffff"
													onChange={
														setDraftThemeAccentFg
													}
												/>
											</HStack>
											<HStack alignment="left">
												<Button
													variant="primary"
													onClick={
														handleUpdatePublication
													}
													disabled={
														! isPublicationDirty ||
														isSaving
													}
												>
													{ __(
														'Update publication',
														'autoblue'
													) }
												</Button>
												{ isSaving && <Spinner /> }
											</HStack>
										</VStack>
									) }
								</VStack>
							) }
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
		</>
	);
};

export default Settings;
