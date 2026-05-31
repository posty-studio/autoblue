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
	__experimentalPaletteEdit as PaletteEdit,
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
	const savedIconId = publicationOverrides.icon_id ?? null;
	const savedThemeBackground = publicationOverrides.theme_background ?? '';
	const savedThemeForeground = publicationOverrides.theme_foreground ?? '';
	const savedThemeAccent = publicationOverrides.theme_accent ?? '';
	const savedThemeAccentFg = publicationOverrides.theme_accent_foreground ?? '';

	const [ draftName, setDraftName ] = useState( savedName );
	const [ draftDescription, setDraftDescription ] = useState( savedDescription );
	const [ draftIconId, setDraftIconId ] = useState( savedIconId );
	const [ draftIconUrl, setDraftIconUrl ] = useState(
		standardSitePublication.iconOverrideUrl || ''
	);
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
		setDraftIconId( savedIconId );
	}, [ savedIconId ] );
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
		( draftIconId || null ) !== ( savedIconId || null ) ||
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
		if ( draftIconId ) {
			next.icon_id = Number( draftIconId );
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

	const openIconPicker = () => {
		if ( ! window.wp?.media ) {
			return;
		}
		const frame = window.wp.media( {
			title: __( 'Select publication icon', 'autoblue' ),
			button: { text: __( 'Use this icon', 'autoblue' ) },
			library: { type: 'image' },
			multiple: false,
		} );
		frame.on( 'select', () => {
			const attachment = frame
				.state()
				.get( 'selection' )
				.first()
				.toJSON();
			setDraftIconId( attachment.id );
			setDraftIconUrl(
				attachment.sizes?.thumbnail?.url ||
					attachment.sizes?.medium?.url ||
					attachment.url ||
					''
			);
		} );
		frame.open();
	};

	const handleRemoveIcon = () => {
		setDraftIconId( null );
		setDraftIconUrl( '' );
	};

	const currentIconUrl =
		draftIconUrl || standardSitePublication.siteIconUrl || '';

	const themePalette = [
		{
			slug: 'background',
			name: __( 'Background', 'autoblue' ),
			color: draftThemeBackground || 'transparent',
		},
		{
			slug: 'foreground',
			name: __( 'Foreground', 'autoblue' ),
			color: draftThemeForeground || 'transparent',
		},
		{
			slug: 'accent',
			name: __( 'Accent', 'autoblue' ),
			color: draftThemeAccent || 'transparent',
		},
		{
			slug: 'accent_foreground',
			name: __( 'Accent foreground', 'autoblue' ),
			color: draftThemeAccentFg || 'transparent',
		},
	];

	const themeSetters = {
		background: setDraftThemeBackground,
		foreground: setDraftThemeForeground,
		accent: setDraftThemeAccent,
		accent_foreground: setDraftThemeAccentFg,
	};

	const handleThemeChange = ( nextPalette ) => {
		if ( ! Array.isArray( nextPalette ) ) return;
		nextPalette.forEach( ( entry ) => {
			const setter = themeSetters[ entry.slug ];
			if ( ! setter ) return;
			const raw = entry.color || '';
			// Transparent (our sentinel for "no override") maps back to empty.
			setter( raw === 'transparent' ? '' : raw );
		} );
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
											<BaseControl
												__nextHasNoMarginBottom
												label={ __(
													'Publication icon',
													'autoblue'
												) }
												help={
													draftIconId
														? __(
																'Custom icon set. Remove to fall back to the WordPress site icon.',
																'autoblue'
														  )
														: __(
																'Falls back to the WordPress site icon. Should be at least 256×256.',
																'autoblue'
														  )
												}
												id="autoblue-publication-icon"
											>
												<HStack
													spacing={ 3 }
													alignment="left"
												>
													{ currentIconUrl ? (
														<img
															src={
																currentIconUrl
															}
															alt=""
															className={
																styles.iconPreview
															}
														/>
													) : (
														<div
															className={
																styles.iconPlaceholder
															}
														/>
													) }
													<Button
														variant="secondary"
														onClick={
															openIconPicker
														}
													>
														{ draftIconId
															? __(
																	'Replace icon',
																	'autoblue'
															  )
															: __(
																	'Choose icon',
																	'autoblue'
															  ) }
													</Button>
													{ draftIconId && (
														<Button
															variant="tertiary"
															isDestructive
															onClick={
																handleRemoveIcon
															}
														>
															{ __(
																'Remove',
																'autoblue'
															) }
														</Button>
													) }
												</HStack>
											</BaseControl>
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
											<PaletteEdit
												colors={ themePalette }
												onChange={ handleThemeChange }
												canOnlyChangeValues
												paletteLabel={ __(
													'Theme colors',
													'autoblue'
												) }
												popoverProps={ {
													offset: 8,
													placement: 'bottom-start',
												} }
											/>
											<Text variant="muted">
												{ __(
													'Theme colors are used by standard.site readers to style your publication.',
													'autoblue'
												) }
											</Text>
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
