import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { Button, Spinner } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import clsx from 'clsx';
import styles from './styles.module.scss';

const ALLOWED_MEDIA_TYPES = [ 'image' ];

const instructions = (
	<p>
		{ __(
			'To edit the featured image, you need permission to upload media.'
		) }
	</p>
);

const MediaPicker = () => {
	const { editPost } = useDispatch( 'core/editor' );

	const onUpdateMedia = ( media ) => {
		editPo;
	};
	return (
		// { media && (
		// 					<div
		// 						id={ `editor-post-featured-image-${ featuredImageId }-describedby` }
		// 						className="hidden"
		// 					>
		// 						{ getImageDescription( media ) }
		// 					</div>
		// 				) }
		<MediaUploadCheck fallback={ instructions }>
			<MediaUpload
				title={ __( 'Image', 'autoblue' ) }
				onSelect={ onUpdateMedia }
				unstableFeaturedImageFlow
				allowedTypes={ ALLOWED_MEDIA_TYPES }
				modalClass="editor-post-featured-image__media-modal"
				render={ ( { open } ) => (
					<div className="editor-post-featured-image__container">
						{ isMissingMedia ? (
							<Notice status="warning" isDismissible={ false }>
								{ __(
									'Could not retrieve the featured image data.'
								) }
							</Notice>
						) : (
							<Button
								__next40pxDefaultSize
								ref={ returnFocus }
								className={
									! featuredImageId
										? 'editor-post-featured-image__toggle'
										: 'editor-post-featured-image__preview'
								}
								onClick={ open }
								aria-label={
									! featuredImageId
										? null
										: __(
												'Edit or replace the featured image'
										  )
								}
								aria-describedby={
									! featuredImageId
										? null
										: `editor-post-featured-image-${ featuredImageId }-describedby`
								}
								aria-haspopup="dialog"
								disabled={ isLoading }
								accessibleWhenDisabled
							>
								{ !! featuredImageId && media && (
									<img
										className="editor-post-featured-image__preview-image"
										src={ mediaSourceUrl }
										alt={ getImageDescription( media ) }
									/>
								) }
								{ ( isLoading ||
									isRequestingFeaturedImageMedia ) && (
									<Spinner />
								) }
								{ ! featuredImageId &&
									! isLoading &&
									( postType?.labels?.set_featured_image ||
										DEFAULT_SET_FEATURE_IMAGE_LABEL ) }
							</Button>
						) }
						{ !! featuredImageId && (
							<HStack
								className={ clsx(
									'editor-post-featured-image__actions',
									{
										'editor-post-featured-image__actions-missing-image':
											isMissingMedia,
										'editor-post-featured-image__actions-is-requesting-image':
											isRequestingFeaturedImageMedia,
									}
								) }
							>
								<Button
									__next40pxDefaultSize
									className="editor-post-featured-image__action"
									onClick={ open }
									aria-haspopup="dialog"
									variant={
										isMissingMedia ? 'secondary' : undefined
									}
								>
									{ __( 'Replace' ) }
								</Button>
								<Button
									__next40pxDefaultSize
									className="editor-post-featured-image__action"
									onClick={ () => {
										onRemoveImage();
										// Signal that the toggle button should be focused,
										// when it is rendered. Can't focus it directly here
										// because it's rendered conditionally.
										returnsFocusRef.current = true;
									} }
									variant={
										isMissingMedia ? 'secondary' : undefined
									}
									isDestructive={ isMissingMedia }
								>
									{ __( 'Remove' ) }
								</Button>
							</HStack>
						) }
						<DropZone onFilesDrop={ onDropFiles } />
					</div>
				) }
				value={ featuredImageId }
			/>
		</MediaUploadCheck>
	);
};
