import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import {
	Button,
	__experimentalText as Text, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	Tooltip,
} from '@wordpress/components';
import { dateI18n, getSettings } from '@wordpress/date';
import { page } from '@wordpress/icons';
import { parseMessage } from './../utils';
import Level from './../level';
import styles from './styles.module.scss';

const Item = ( { log, onButtonClick } ) => {
	const { level, message, created_at: createdAt } = log;
	const { formats } = getSettings();

	return (
		<tr className={ clsx( styles.item, styles[ level ] ) }>
			<td>
				<Level level={ level } />
			</td>
			<td>{ parseMessage( message ) }</td>
			<td>
				<Tooltip text={ dateI18n( 'c', createdAt ) }>
					<Text variant="muted">
						<time
							dateTime={ dateI18n( 'c', createdAt ) }
							className={ styles.time }
						>
							{ dateI18n( formats?.datetime || 'c', createdAt ) }
						</time>
					</Text>
				</Tooltip>
			</td>
			<td>
				<Button
					onClick={ () => onButtonClick( log ) }
					variant="secondary"
					icon={ page }
					label={ __( 'View Context', 'autoblue' ) }
				/>
			</td>
		</tr>
	);
};

export default Item;
