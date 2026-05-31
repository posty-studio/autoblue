import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { store as noticesStore } from '@wordpress/notices';

const useSettings = () => {
	const [ isEnabled, setIsEnabledFn ] = useEntityProp(
		'root',
		'site',
		'autoblue_enabled'
	);
	const [ enabledPostTypes, setEnabledPostTypesFn ] = useEntityProp(
		'root',
		'site',
		'autoblue_enabled_post_types'
	);
	const [ standardSiteEnabled, setStandardSiteEnabledFn ] = useEntityProp(
		'root',
		'site',
		'autoblue_publish_documents_enabled'
	);
	const [ publicationOverrides, setPublicationOverridesFn ] = useEntityProp(
		'root',
		'site',
		'autoblue_publication_overrides'
	);
	const { saveEditedEntityRecord } = useDispatch( 'core' );
	const { createSuccessNotice, removeNotice } = useDispatch( noticesStore );

	const isSaving = useSelect( ( select ) =>
		select( 'core' ).isSavingEntityRecord( 'root', 'site' )
	);

	const setIsEnabled = async ( value ) => {
		if ( isSaving ) {
			return;
		}
		try {
			setIsEnabledFn( value );
			await saveEditedEntityRecord( 'root', 'site' );

			const notice = await createSuccessNotice(
				value
					? __( 'Automatic sharing to Bluesky enabled.', 'autoblue' )
					: __(
							'Automatic sharing to Bluesky disabled.',
							'autoblue'
					  ),
				{
					type: 'snackbar',
				}
			);

			setTimeout( () => {
				removeNotice( notice.notice.id );
			}, 2000 );
		} catch ( error ) {}
	};

	const resolvedEnabledPostTypes = Array.isArray( enabledPostTypes )
		? enabledPostTypes
		: [ 'post' ];

	const togglePostType = async ( slug, checked ) => {
		if ( isSaving ) {
			return;
		}
		const next = checked
			? Array.from( new Set( [ ...resolvedEnabledPostTypes, slug ] ) )
			: resolvedEnabledPostTypes.filter( ( s ) => s !== slug );
		try {
			setEnabledPostTypesFn( next );
			await saveEditedEntityRecord( 'root', 'site' );
		} catch ( error ) {}
	};

	const setIsStandardSiteEnabled = async ( value ) => {
		if ( isSaving ) {
			return;
		}
		try {
			setStandardSiteEnabledFn( value );
			await saveEditedEntityRecord( 'root', 'site' );
		} catch ( error ) {}
	};

	const resolvedOverrides =
		publicationOverrides && typeof publicationOverrides === 'object'
			? publicationOverrides
			: {};

	const setPublicationOverride = async ( key, value ) => {
		if ( isSaving ) {
			return;
		}
		const next = { ...resolvedOverrides };
		if ( value === null || value === '' ) {
			delete next[ key ];
		} else {
			next[ key ] = value;
		}
		try {
			setPublicationOverridesFn( next );
			await saveEditedEntityRecord( 'root', 'site' );
		} catch ( error ) {}
	};

	const standardSiteInitial =
		autoblue?.initialState?.settings?.standardSite || {};

	return {
		isEnabled:
			isEnabled !== undefined && isEnabled !== null
				? isEnabled
				: autoblue?.initialState?.settings?.enabled, // TODO: Add to store.
		setIsEnabled,
		enabledPostTypes: resolvedEnabledPostTypes,
		isLoadingPostTypes: enabledPostTypes === undefined,
		availablePostTypes:
			autoblue?.initialState?.settings?.availablePostTypes || [],
		togglePostType,
		isStandardSiteEnabled: !! standardSiteEnabled,
		isLoadingStandardSite: standardSiteEnabled === undefined,
		setIsStandardSiteEnabled,
		publicationOverrides: resolvedOverrides,
		setPublicationOverride,
		standardSitePublication: standardSiteInitial.publication || {},
		isRootInstall: standardSiteInitial.isRootInstall !== false,
		isSaving,
	};
};

export default useSettings;
