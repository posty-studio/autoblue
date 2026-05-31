import { __ } from '@wordpress/i18n';
import { useEffect, useState, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import {
	Button,
	Card,
	CardBody,
	Notice,
	Spinner,
	__experimentalText as Text,
	__experimentalVStack as VStack,
} from '@wordpress/components';

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

	return (
		<VStack spacing={ 5 }>
			<Card>
				<CardBody>
					<VStack spacing={ 2 }>
						<Text weight="600">
							{ __( 'Publication', 'autoblue' ) }
						</Text>
						{ publication ? (
							<>
								<Text>
									{ publication.value?.name ||
										__(
											'(no name)',
											'autoblue'
										) }
								</Text>
								{ publication.value?.description && (
									<Text variant="muted">
										{ publication.value.description }
									</Text>
								) }
								<Text variant="muted">
									{ publication.uri }
								</Text>
							</>
						) : (
							<Text variant="muted">
								{ __(
									'No publication record found on the PDS yet. It will be created on the next publish.',
									'autoblue'
								) }
							</Text>
						) }
					</VStack>
				</CardBody>
			</Card>

			<Card>
				<CardBody>
					<VStack spacing={ 3 }>
						<Text weight="600">
							{ __( 'Documents', 'autoblue' ) }
							{ ' ' }
							<Text variant="muted">
								({ documents.length })
							</Text>
						</Text>
						{ documents.length === 0 ? (
							<Text variant="muted">
								{ __(
									'No document records on the PDS yet.',
									'autoblue'
								) }
							</Text>
						) : (
							<VStack spacing={ 2 }>
								{ documents.map( ( doc ) => (
									<Card key={ doc.uri } size="small">
										<CardBody>
											<VStack spacing={ 1 }>
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
												<Text variant="muted">
													{ doc.uri }
												</Text>
												{ doc.value?.publishedAt && (
													<Text variant="muted">
														{ new Date(
															doc.value.publishedAt
														).toLocaleString() }
													</Text>
												) }
											</VStack>
										</CardBody>
									</Card>
								) ) }
							</VStack>
						) }
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
				</CardBody>
			</Card>
		</VStack>
	);
};

export default Records;
