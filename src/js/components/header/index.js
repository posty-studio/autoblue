import { __ } from '@wordpress/i18n';
import { Logo } from './../../icons';
import styles from './styles.module.scss';

const Header = () => {
	return (
		<div className={ styles.header }>
			{ Logo }
			<span className={ styles.version }>{ autoblue.version }</span>
		</div>
	);
};

export default Header;
