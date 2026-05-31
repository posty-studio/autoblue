import { __ } from '@wordpress/i18n';
import { useEffect, useState, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import {
	BaseControl,
	Button,
	Card,
	CardBody,
	Notice,
	Spinner,
	__experimentalText as Text,
	__experimentalVStack as VStack,
} from '@wordpress/components';
import styles from './styles.module.scss';

const extractDid = ( atUri = '' ) => {
	const match = atUri.match( /^at:\/\/(did:[^/]+)/ );
	return match ? match[ 1 ] : '';
};

const blobThumbnailUrl = ( did, blob ) => {
	const cid = blob?.ref?.$link;
	if ( ! did || ! cid ) return null;
	return `https://cdn.bsky.app/img/feed_thumbnail/plain/${ did }/${ cid }@jpeg`;
};

const Records = () => {
	const [ publication, setPublication ] = useState( null );
	const [ documents, setDocuments ] = useState( [] );
	const [ cursor, setCursor ] = useState( null );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ isLoadingMore, setIsLoadingMore ] = useState( false );
	const [ error, setError ] = useState( null );

	const fetchPage = useCallback( async ( nextCursor = '' ) => {
		const path = nextCursor
			? `/autoblue/v1/standard/records?cursor=${ encodeURIComponent(
					nextCursor
			  ) }`
			: '/autoblue/v1/standard/records';
		return apiFetch( { path } );
	}, [] );

	useEffect( () => {
		let cancelled = false;
		( async () => {
			try {
				const response = await fetchPage( '' );
				if ( cancelled ) return;
				setPublication( response.publication ?? null );
				setDocuments( response.documents ?? [] );
				setCursor( response.cursor ?? null );
			} catch ( e ) {
				if ( cancelled ) return;
				setError(
					e?.message ||
						__( 'Failed to load records.', 'autoblue' )
				);
			} finally {
				if ( ! cancelled ) {
					setIsLoading( false );
				}
			}
		} )();
		return () => {
			cancelled = true;
		};
	}, [ fetchPage ] );

	const loadMore = async () => {
		if ( ! cursor || isLoadingMore ) return;
		setIsLoadingMore( true );
		try {
			const response = await fetchPage( cursor );
			setDocuments( ( prev ) => [
				...prev,
				...( response.documents ?? [] ),
			] );
			setCursor( response.cursor ?? null );
		} catch ( e ) {
			setError(
				e?.message ||
					__( 'Failed to load more documents.', 'autoblue' )
			);
		} finally {
			setIsLoadingMore( false );
		}
	};

	if ( isLoading ) {
		return <Spinner />;
	}

	if ( error ) {
		return (
			<Notice status="error" isDismissible={ false }>
				{ error }
			</Notice>
		);
	}

	const publicationDid = extractDid( publication?.uri ?? '' );
	const publicationIconUrl = blobThumbnailUrl(
		publicationDid,
		publication?.value?.icon
	);

	return (
		<>
			<BaseControl
				__nextHasNoMarginBottom
				label={ __( 'Publication', 'autoblue' ) }
				id="autoblue-records-publication"
			>
				<Card>
					<CardBody>
						{ publication ? (
							<div className={ styles.row }>
								{ publicationIconUrl && (
									<img
										className={ styles.icon }
										src={ publicationIconUrl }
										alt=""
									/>
								) }
								<VStack spacing={ 1 }>
									<Text weight="600">
										{ publication.value?.name ||
											__( '(no name)', 'autoblue' ) }
									</Text>
									{ publication.value?.description && (
										<Text variant="muted">
											{ publication.value.description }
										</Text>
									) }
									<Text variant="muted">
										{ publication.uri }
									</Text>
								</VStack>
							</div>
						) : (
							<Text variant="muted">
								{ __(
									'No publication record found on the PDS yet. It will be created on the next publish.',
									'autoblue'
								) }
							</Text>
						) }
					</CardBody>
				</Card>
			</BaseControl>

			<BaseControl
				__nextHasNoMarginBottom
				label={ __( 'Documents', 'autoblue' ) }
				id="autoblue-records-documents"
			>
				{ documents.length === 0 ? (
					<Card>
						<CardBody>
							<Text variant="muted">
								{ __(
									'No document records on the PDS yet.',
									'autoblue'
								) }
							</Text>
						</CardBody>
					</Card>
				) : (
					<VStack spacing={ 3 }>
						<Card>
							<CardBody>
								<VStack spacing={ 3 }>
									{ documents.map( ( doc ) => {
										const did = extractDid(
											doc.uri ?? ''
										);
										const coverUrl = blobThumbnailUrl(
											did,
											doc.value?.coverImage
										);
										return (
											<div
												key={ doc.uri }
												className={ styles.row }
											>
												{ coverUrl && (
													<img
														className={
															styles.cover
														}
														src={ coverUrl }
														alt=""
													/>
												) }
												<VStack spacing={ 1 }>
													<Text weight="600">
														{ doc.value?.title ||
															__(
																'(untitled)',
																'autoblue'
															) }
													</Text>
													{ doc.value
														?.description && (
														<Text variant="muted">
															{
																doc.value
																	.description
															}
														</Text>
													) }
													<Text variant="muted">
														{ doc.uri }
													</Text>
													{ doc.value
														?.publishedAt && (
														<Text variant="muted">
															{ new Date(
																doc.value.publishedAt
															).toLocaleString() }
														</Text>
													) }
												</VStack>
											</div>
										);
									} ) }
								</VStack>
							</CardBody>
						</Card>
						{ cursor && (
							<Button
								variant="secondary"
								onClick={ loadMore }
								disabled={ isLoadingMore }
							>
								{ isLoadingMore
									? __( 'Loading…', 'autoblue' )
									: __( 'Load more', 'autoblue' ) }
							</Button>
						) }
					</VStack>
				) }
			</BaseControl>
		</>
	);
};

export default Records;
