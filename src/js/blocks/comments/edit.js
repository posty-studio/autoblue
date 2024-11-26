import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	InnerBlocks,
	InspectorControls,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

const Edit = ( { attributes: { url }, setAttributes } ) => {
	const blockProps = useBlockProps();

	if ( ! false ) {
		// return <QueryPlaceholder blockProps={ blockProps } />;
		return <div>QueryPlaceholder</div>;
	}

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Autoblue Comments', 'better-loop' ) }
					initialOpen
				>
					<TextControl
						label={ __( 'URL', 'better-loop' ) }
						help={ __(
							'Bluesky post URL to fetch comments from.',
							'better-loop'
						) }
						value={ url }
						onChange={ ( value ) =>
							setAttributes( { url: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>HI</div>
		</>
	);
};

export default Edit;
