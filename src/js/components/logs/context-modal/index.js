import { dateI18n, getSettings } from '@wordpress/date';
import { __ } from '@wordpress/i18n';
import {
	Modal,
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	__experimentalText as Text, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { parseMessage } from './../utils';
import Level from './../level';
import Table from './../table';
import styles from './styles.module.scss';

const ContextModal = ( { context, onRequestClose } ) => {
	const { formats } = getSettings();

	return (
		<Modal
			title={ __( 'Context', 'autoblue' ) }
			onRequestClose={ onRequestClose }
			className={ styles.modal }
		>
			<VStack spacing={ 4 }>
				<Table>
					<tbody>
						<tr>
							<th>{ __( 'Level', 'autoblue' ) }</th>
							<td>
								<Level level={ context.level } showText />
							</td>
						</tr>
						<tr>
							<th>{ __( 'Message', 'autoblue' ) }</th>
							<td>{ parseMessage( context.message ) }</td>
						</tr>
						<tr>
							<th>{ __( 'Date', 'autoblue' ) }</th>
							<td>
								<VStack spacing={ 1 }>
									<span>
										{ dateI18n(
											formats?.datetime || 'c',
											context.created_at
										) }
									</span>
									<Text variant="muted">
										{ dateI18n( 'c', context.created_at ) }
									</Text>
								</VStack>
							</td>
						</tr>
						{ Object.entries( {
							...context.context,
							...context.extra,
						} ).map( ( [ key, value ] ) => (
							<tr key={ key }>
								<th>{ key }</th>
								<td>
									<pre>
										{ typeof value === 'object'
											? JSON.stringify( value, null, 2 )
											: String( value ) }
									</pre>
								</td>
							</tr>
						) ) }
					</tbody>
				</Table>
			</VStack>
		</Modal>
	);
};

export default ContextModal;
