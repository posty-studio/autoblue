import clsx from 'clsx';
import { Icon } from '@wordpress/components';
import { getIconFromLevel } from '../utils';
import styles from './styles.module.scss';

const Level = ( { level, showText = false } ) => {
	return (
		<div className={ clsx( styles.level, styles[ level ] ) }>
			<Icon
				icon={ getIconFromLevel( level ) }
				className={ styles.icon }
			/>
			{ showText && level }
		</div>
	);
};

export default Level;
