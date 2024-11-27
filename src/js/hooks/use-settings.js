import { useSelect, useDispatch } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';

const useSettings = () => {
	const [ isEnabled, setIsEnabledFn ] = useEntityProp(
		'root',
		'site',
		'autoblue_enabled'
	);
	const { saveEditedEntityRecord } = useDispatch( 'core' );

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
		} catch ( error ) {}
	};

	return {
		isEnabled:
			isEnabled !== undefined && isEnabled !== null
				? isEnabled
				: autoblue?.initialState?.settings?.enabled, // TODO: Add to store.
		setIsEnabled,
		isSaving,
	};
};

export default useSettings;
