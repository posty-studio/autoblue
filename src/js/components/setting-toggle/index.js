import { ToggleControl } from '@wordpress/components';
import styles from './styles.module.scss';

const SettingToggle = ( props ) => {
	return (
		<ToggleControl
			__nextHasNoMarginBottom
			className={ styles.toggle }
			{ ...props }
		/>
	);
};

export default SettingToggle;
