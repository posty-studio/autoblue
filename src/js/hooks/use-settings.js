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
		isSaving,
	};
};

export default useSettings;
