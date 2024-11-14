import { __ } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import { useState, useEffect, useCallback, useRef } from 'react';
import apiFetch from '@wordpress/api-fetch';

const useSettings = () => {
	const [ accountDIDs, setAccountDIDs ] = useState( [] );
	const [ appPassword, setAppPassword ] = useState( '' );
	const [ isDirty, setIsDirty ] = useState( false );
	const [ isSaving, setIsSaving ] = useState( false );

	const { createSuccessNotice } = useDispatch( noticesStore );

	const initialValues = useRef( { accountDIDs: [], appPassword: '' } );

	const fetchSettings = useCallback( async () => {
		try {
			const { bsky4wp_app_password, bsky4wp_account_dids } =
				await apiFetch( {
					path: '/wp/v2/settings?_fields=bsky4wp_app_password,bsky4wp_account_dids',
				} );

			setAppPassword( bsky4wp_app_password );
			setAccountDIDs( bsky4wp_account_dids );
			initialValues.current = {
				accountDIDs: bsky4wp_account_dids,
				appPassword: bsky4wp_app_password,
			};
		} catch ( error ) {
			console.error( error );
		}
	}, [] );

	useEffect( () => {
		fetchSettings();
	}, [ fetchSettings ] );

	useEffect( () => {
		const isChanged =
			appPassword !== initialValues.current.appPassword ||
			accountDIDs.length !== initialValues.current.accountDIDs.length ||
			accountDIDs.some(
				( did, index ) =>
					did !== initialValues.current.accountDIDs[ index ]
			);

		setIsDirty( isChanged );
	}, [ accountDIDs, appPassword ] );

	const saveSettings = async () => {
		setIsSaving( true );
		try {
			const { bsky4wp_app_password, bsky4wp_account_dids } =
				await apiFetch( {
					path: '/wp/v2/settings',
					method: 'POST',
					data: {
						bsky4wp_app_password: appPassword,
						bsky4wp_account_dids: accountDIDs,
					},
				} );
			setAppPassword( bsky4wp_app_password );
			setAccountDIDs( bsky4wp_account_dids );
			initialValues.current = {
				accountDIDs: bsky4wp_account_dids,
				appPassword: bsky4wp_app_password,
			};
			setIsDirty( false );
			createSuccessNotice( __( 'Settings saved.', 'bsky-for-wp' ) );
		} catch ( error ) {
			console.error( error );
		} finally {
			setIsSaving( false );
		}
	};

	return {
		accountDIDs,
		setAccountDIDs,
		appPassword,
		setAppPassword,
		isDirty,
		isSaving,
		saveSettings,
	};
};

export default useSettings;
