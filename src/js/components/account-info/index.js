import { __ } from '@wordpress/i18n';
import { useState, useEffect, useRef } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { Spinner, Button } from '@wordpress/components';
import clsx from 'clsx';
import styles from './styles.module.scss';

const useAccountInfo = ( did, meta ) => {
	const [ userData, setUserData ] = useState( {
		handle: meta.handle || '',
		displayName: meta.name || '',
		avatar: meta.avatar || '',
	} );
	const [ isLoading, setIsLoading ] = useState( false );
	const fetchController = useRef( null );

	useEffect( () => {
		const fetchUserData = async () => {
			if ( userData.handle ) {
				return;
			}

			if ( fetchController.current ) {
				fetchController.current.abort();
			}

			fetchController.current = new AbortController();

			try {
				setIsLoading( true );
				const response = await apiFetch( {
					path: `/autoblue/v1/account?did=${ did }`,
					signal: fetchController.current.signal,
				} );

				setUserData( {
					handle: response.handle || userData.handle,
					displayName: response.displayName || userData.displayName,
					avatar: response.avatar || userData.avatar,
				} );
			} catch ( error ) {
				console.error( 'Error fetching user data:', error );
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
	}, [ did, userData ] );

	return { userData, isLoading };
};

const AccountInfo = ( {
	account: { did, meta = {} },
	className,
	onDelete = null,
	deleteLabel = null,
	size = 'medium',
} ) => {
	const { userData, isLoading } = useAccountInfo( did, meta );
	const [ isImageLoaded, setIsImageLoaded ] = useState( false );

	className = clsx(
		styles.wrapper,
		className,
		size === 'small' && styles.small,
		size === 'large' && styles.large
	);

	if ( isLoading ) {
		return (
			<div className={ className }>
				<div className={ styles.loading }>
					<Spinner />
				</div>
			</div>
		);
	}

	return (
		<div className={ className }>
			<div className={ styles.meta }>
				<figure className={ styles.avatar }>
					{ userData.avatar && (
						<img
							src={ userData.avatar }
							alt={ userData.displayName }
							style={ {
								display: isImageLoaded ? 'block' : 'none',
							} }
							onLoad={ () => setIsImageLoaded( true ) }
							onError={ () => setIsImageLoaded( false ) }
						/>
					) }
				</figure>
				<div className={ styles.info }>
					<span className={ styles.name }>
						{ userData.displayName }
					</span>
					<span className={ styles.handle }>
						@{ userData.handle }
					</span>
				</div>
			</div>
			{ onDelete && (
				<Button
					icon={ ! deleteLabel ? 'no-alt' : undefined }
					isDestructive
					variant={ ! deleteLabel ? undefined : 'secondary' }
					onClick={ onDelete }
					label={ deleteLabel || __( 'Delete', 'autoblue' ) }
				>
					{ deleteLabel || null }
				</Button>
			) }
		</div>
	);
};

export default AccountInfo;
