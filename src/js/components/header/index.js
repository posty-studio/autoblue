import { __ } from '@wordpress/i18n';
import {
	__experimentalHeading as Heading, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { Logo } from './../../icons';
import styles from './styles.module.scss';

const Header = () => {
	return (
		<div className={ styles.header }>
			{ Logo }
			<span className={ styles.version }>{ Autoblue.version }</span>
		</div>
	);
};

export default Header;
