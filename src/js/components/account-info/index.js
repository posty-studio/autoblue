import clsx from 'clsx';
import { useState, useEffect } from 'react';
import apiFetch from '@wordpress/api-fetch';
import styles from './styles.module.scss';
import { Spinner } from '@wordpress/components';

const AccountInfo = ( { did, handle, displayName, avatar, className } ) => {
	const [ userData, setUserData ] = useState( {
		handle,
		displayName,
		avatar,
	} );
	const [ isLoading, setIsLoading ] = useState( false );

	useEffect( () => {
		const fetchUserData = async () => {
			if ( ! handle || ! displayName ) {
				try {
					setIsLoading( true );
					const response = await apiFetch( {
						path: `/bsky4wp/v1/account?did=${ did }`,
					} );

					setUserData( {
						handle: response.handle || handle,
						displayName: response.displayName || displayName,
						avatar: response.avatar || avatar,
					} );
				} catch ( error ) {
					console.error( 'Error fetching user data:', error );
				} finally {
					setIsLoading( false );
				}
			}
		};

		fetchUserData();
	}, [ did, handle, displayName, avatar ] );

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
