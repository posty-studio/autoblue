import { registerBlockType } from '@wordpress/blocks';
import './style.scss';
import Edit from './edit';
import metadata from './block.json';
import { CommentIcon as icon } from './../../icons/comments';

registerBlockType( metadata.name, {
	icon: {
		foreground: '#1285fe',
		src: icon,
	},
	edit: Edit,
} );
