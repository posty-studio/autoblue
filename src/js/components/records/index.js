import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import { useEffect, useState, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { dateI18n, humanTimeDiff } from '@wordpress/date';
import {
	BaseControl,
	Button,
	Card,
	CardBody,
	ColorIndicator,
	Notice,
	Spinner,
	Tooltip,
	__experimentalHStack as HStack,
	__experimentalText as Text,
	__experimentalVStack as VStack,
} from '@wordpress/components';
import { RefreshIcon } from '../../icons';
import styles from './styles.module.scss';

const extractDid = ( atUri = '' ) => {
	const match = atUri.match( /^at:\/\/(did:[^/]+)/ );
	return match ? match[ 1 ] : '';
};

const extractRkey = ( atUri = '' ) => {
	const parts = atUri.split( '/' );
	return parts.length ? parts[ parts.length - 1 ] : '';
};

const blobThumbnailUrl = ( did, blob ) => {
	const cid = blob?.ref?.$link;
	if ( ! did || ! cid ) return null;
	return `https://cdn.bsky.app/img/feed_thumbnail/plain/${ did }/${ cid }@jpeg`;
};

const pdslsUrl = ( atUri ) =>
	atUri ? `https://pdsls.dev/${ atUri }` : null;

const rgbToHex = ( rgb ) => {
	if ( ! rgb ) return null;
	const { r, g, b } = rgb;
	if (
		typeof r !== 'number' ||
		typeof g !== 'number' ||
		typeof b !== 'number'
	) {
		return null;
	}
	const toHex = ( n ) =>
		Math.max( 0, Math.min( 255, Math.round( n ) ) )
			.toString( 16 )
			.padStart( 2, '0' );
	return `#${ toHex( r ) }${ toHex( g ) }${ toHex( b ) }`;
};

const THEME_FIELDS = [
	{ key: 'background', label: 'Background' },
	{ key: 'foreground', label: 'Foreground' },
	{ key: 'accent', label: 'Accent' },
	{ key: 'accentForeground', label: 'Accent foreground' },
];

const bskyPostUrl = ( bskyPostRef ) => {
	const uri = bskyPostRef?.uri;
	if ( ! uri ) return null;
	const did = extractDid( uri );
	const rkey = extractRkey( uri );
	if ( ! did || ! rkey ) return null;
	return `https://bsky.app/profile/${ did }/post/${ rkey }`;
};

const RelativeDate = ( { iso } ) => {
	if ( ! iso ) return null;
	const date = new Date( iso );
	const absolute = dateI18n( 'F j, Y g:i a', date );
	return (
		<Text variant="muted">
			<Tooltip text={ absolute }>
				<time dateTime={ iso } tabIndex={ 0 }>
					{ humanTimeDiff( date ) }
				</time>
			</Tooltip>
		</Text>
	);
};

const Records = () => {
	const [ publication, setPublication ] = useState( null );
	const [ documents, setDocuments ] = useState( [] );
	const [ cursor, setCursor ] = useState( null );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ isLoadingMore, setIsLoadingMore ] = useState( false );
	const [ isRefreshing, setIsRefreshing ] = useState( false );
	const [ error, setError ] = useState( null );

	const fetchPage = useCallback( async ( nextCursor = '' ) => {
		const path = nextCursor
			? `/autoblue/v1/standard/records?cursor=${ encodeURIComponent(
					nextCursor
			  ) }`
			: '/autoblue/v1/standard/records';
		return apiFetch( { path } );
	}, [] );

	const loadFirstPage = useCallback( async () => {
		try {
			const response = await fetchPage( '' );
			setPublication( response.publication ?? null );
			setDocuments( response.documents ?? [] );
			setCursor( response.cursor ?? null );
			setError( null );
		} catch ( e ) {
			setError(
				e?.message || __( 'Failed to load records.', 'autoblue' )
			);
		}
	}, [ fetchPage ] );

	useEffect( () => {
		let cancelled = false;
		( async () => {
			await loadFirstPage();
			if ( ! cancelled ) setIsLoading( false );
		} )();
		return () => {
			cancelled = true;
		};
	}, [ loadFirstPage ] );

	const handleRefresh = async () => {
		if ( isRefreshing ) return;
		setIsRefreshing( true );
		await loadFirstPage();
		setIsRefreshing( false );
	};

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
			<HStack
				alignment="right"
				className={ styles.toolbar }
			>
				<Button
					label={ __( 'Refresh records', 'autoblue' ) }
					onClick={ handleRefresh }
					disabled={ isRefreshing }
					accessibleWhenDisabled
					className={ clsx( {
						[ styles.refresh ]: true,
						[ styles.refreshing ]: isRefreshing,
					} ) }
					icon={ RefreshIcon }
					size="compact"
				/>
			</HStack>

			<BaseControl
				__nextHasNoMarginBottom
				label={ __( 'Publication', 'autoblue' ) }
				id="autoblue-records-publication"
			>
				<Card>
					<CardBody className={ styles.card }>
						{ publication ? (
							<div className={ styles.row }>
								{ publicationIconUrl && (
									<img
										className={ styles.icon }
										src={ publicationIconUrl }
										alt=""
									/>
								) }
								<VStack spacing={ 1 } className={ styles.meta }>
									<Text weight="600">
										{ publication.value?.name ||
											__( '(no name)', 'autoblue' ) }
									</Text>
									{ publication.value?.description && (
										<Text variant="muted">
											{ publication.value.description }
										</Text>
									) }
									{ publication.value?.basicTheme && (
										<HStack
											spacing={ 2 }
											alignment="left"
											className={ styles.swatches }
										>
											{ THEME_FIELDS.map( ( field ) => {
												const hex = rgbToHex(
													publication.value
														.basicTheme?.[
														field.key
													]
												);
												if ( ! hex ) return null;
												return (
													<Tooltip
														key={ field.key }
														text={ `${ field.label } — ${ hex }` }
													>
														<span
															tabIndex={ 0 }
															aria-label={
																field.label
															}
														>
															<ColorIndicator
																colorValue={
																	hex
																}
															/>
														</span>
													</Tooltip>
												);
											} ) }
										</HStack>
									) }
									<HStack
										spacing={ 3 }
										alignment="left"
										className={ styles.links }
									>
										<a
											href={ pdslsUrl( publication.uri ) }
											target="_blank"
											rel="noreferrer"
										>
											{ __(
												'View raw',
												'autoblue'
											) }
										</a>
									</HStack>
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
						<CardBody className={ styles.card }>
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
						{ documents.map( ( doc ) => {
							const did = extractDid( doc.uri ?? '' );
							const coverUrl = blobThumbnailUrl(
								did,
								doc.value?.coverImage
							);
							const bskyUrl = bskyPostUrl(
								doc.value?.bskyPostRef
							);
							return (
								<Card key={ doc.uri }>
									<CardBody className={ styles.card }>
										<div className={ styles.row }>
											{ coverUrl && (
												<img
													className={ styles.cover }
													src={ coverUrl }
													alt=""
												/>
											) }
											<VStack
												spacing={ 1 }
												className={ styles.meta }
											>
												<Text weight="600">
													{ doc.value?.title ||
														__(
															'(untitled)',
															'autoblue'
														) }
												</Text>
												{ doc.value?.description && (
													<Text variant="muted">
														{
															doc.value
																.description
														}
													</Text>
												) }
												<RelativeDate
													iso={
														doc.value?.publishedAt
													}
												/>
												<HStack
													spacing={ 3 }
													alignment="left"
													className={ styles.links }
												>
													<a
														href={ pdslsUrl(
															doc.uri
														) }
														target="_blank"
														rel="noreferrer"
													>
														{ __(
															'View raw',
															'autoblue'
														) }
													</a>
													{ bskyUrl && (
														<a
															href={ bskyUrl }
															target="_blank"
															rel="noreferrer"
														>
															{ __(
																'View on Bluesky',
																'autoblue'
															) }
														</a>
													) }
												</HStack>
											</VStack>
										</div>
									</CardBody>
								</Card>
							);
						} ) }
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
