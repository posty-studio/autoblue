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

	return <div { ...blockProps }>HI</div>;
};

export default Edit;
