import { __ } from '@wordpress/i18n';
import {
	__experimentalHeading as Heading, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { BlueskyIcon } from './../../icons';
import styles from './styles.module.scss';

const Header = () => {
	return (
		<div className={ styles.header }>
			{ BlueskyIcon }
			<Heading level={ 1 } className={ styles.title }>
				{ __( 'Bluesky for WordPress ', 'autoblue' ) }
			</Heading>
		</div>
	);
};

export default Header;
