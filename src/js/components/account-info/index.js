import { useState, useEffect, useRef } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { Spinner } from '@wordpress/components';
import clsx from 'clsx';
import styles from './styles.module.scss';

const useAccountInfo = (
	did,
	initialHandle,
	initialDisplayName,
	initialAvatar
) => {
	const [ userData, setUserData ] = useState( {
		handle: initialHandle,
		displayName: initialDisplayName,
		avatar: initialAvatar,
	} );
	const [ isLoading, setIsLoading ] = useState( false );
	const fetchController = useRef( null );

	useEffect( () => {
		const fetchUserData = async () => {
			if ( initialHandle && initialDisplayName ) {
				return;
			}

			if ( fetchController.current ) {
				fetchController.current.abort();
			}

			fetchController.current = new AbortController();

			try {
				setIsLoading( true );
				const response = await apiFetch( {
					path: `/bsky4wp/v1/account?did=${ did }`,
					signal: fetchController.current.signal,
				} );

				setUserData( {
					handle: response.handle || initialHandle,
					displayName: response.displayName || initialDisplayName,
					avatar: response.avatar || initialAvatar,
				} );
			} catch ( error ) {
				if ( error.name !== 'AbortError' ) {
					console.error( 'Error fetching user data:', error );
				}
			} finally {
				setIsLoading( false );
			}
		};

		fetchUserData();

		return () => {
			if ( fetchController.current ) {
				fetchController.current.abort();
			}
		};
	}, [ did, initialHandle, initialDisplayName, initialAvatar ] );

	return { userData, isLoading };
};

const AccountInfo = ( { did, handle, displayName, avatar, className } ) => {
	const { userData, isLoading } = useAccountInfo(
		did,
		handle,
		displayName,
		avatar
	);

	if ( isLoading ) {
		return (
			<div className={ styles.loading }>
				<Spinner />
			</div>
		);
	}

	return (
		<div className={ clsx( styles.wrapper, className ) }>
			<figure className={ styles.avatar }>
				{ userData.avatar && (
					<img src={ userData.avatar } alt={ userData.displayName } />
				) }
			</figure>
			<div className={ styles.info }>
				<span className={ styles.name }>{ userData.displayName }</span>
				<span className={ styles.handle }>@{ userData.handle }</span>
			</div>
		</div>
	);
};

export default AccountInfo;
