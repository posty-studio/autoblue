import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import { dateI18n, getSettings } from '@wordpress/date';
import { useState } from '@wordpress/element';
import {
	BaseControl,
	Card,
	CardBody,
	Tooltip,
	Icon,
	Modal,
	Button,
	__experimentalText as Text,
	__experimentalVStack as VStack,
} from '@wordpress/components';
import useLogs from './../../hooks/use-logs';
import styles from './styles.module.scss';
import {
	info,
	warning,
	code,
	cancelCircleFilled,
	page,
} from '@wordpress/icons';

const getIconFromLevel = ( level ) => {
	switch ( level ) {
		case 'info':
			return info;
		case 'warning':
			return warning;
		case 'error':
			return cancelCircleFilled;
		case 'debug':
			return code;
		default:
			return info;
	}
};

const convertBackticks = ( str ) => {
	return str
		.split( '`' )
		.map( ( part, index ) =>
			index % 2 === 0 ? part : <code key={ index }>{ part }</code>
		);
};

const LogLevel = ( { level, showText = false } ) => {
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
const Logs = () => {
	const { logs } = useLogs();
	const [ selectedLogContext, setSelectedLogContext ] = useState( null );
	const { formats } = getSettings();

	const handleContextView = ( log ) => {
		setSelectedLogContext( log );
	};

	const LogItem = ( log ) => {
		const { level, context, message, created_at: createdAt } = log;
		return (
			<tr className={ clsx( styles.item, styles[ level ] ) }>
				<td>
					<LogLevel level={ level } />
				</td>
				<td>{ convertBackticks( message ) }</td>
				<td>
					<Tooltip text={ dateI18n( 'c', createdAt ) }>
						<Text variant="muted">
							<time
								dateTime={ dateI18n( 'c', createdAt ) }
								className={ styles.time }
							>
								{ dateI18n(
									formats?.datetime || 'c',
									createdAt
								) }
							</time>
						</Text>
					</Tooltip>
				</td>
				<td>
					{ context && Object.keys( context ).length && (
						<Button
							onClick={ () => handleContextView( log ) }
							variant="secondary"
							icon={ page }
							label={ __( 'View Context', 'autoblue' ) }
						/>
					) }
				</td>
			</tr>
		);
	};

	return (
		<>
			{ selectedLogContext && (
				<Modal
					title={ __( 'Context', 'autoblue' ) }
					onRequestClose={ () => setSelectedLogContext( null ) }
					className={ styles.modal }
				>
					<VStack spacing={ 4 }>
						<table className={ styles.table }>
							<tbody>
								<tr>
									<th>{ __( 'Level', 'autoblue' ) }</th>
									<td>
										<LogLevel
											level={ selectedLogContext.level }
											showText
										/>
									</td>
								</tr>
								<tr>
									<th>{ __( 'Message', 'autoblue' ) }</th>
									<td>
										{ convertBackticks(
											selectedLogContext.message
										) }
									</td>
								</tr>
								<tr>
									<th>{ __( 'Date', 'autoblue' ) }</th>
									<td>
										<VStack spacing={ 1 }>
											<span>
												{ dateI18n(
													formats?.datetime || 'c',
													selectedLogContext.created_at
												) }
											</span>
											<Text variant="muted">
												{ dateI18n(
													'c',
													selectedLogContext.created_at
												) }
											</Text>
										</VStack>
									</td>
								</tr>
								{ Object.entries(
									selectedLogContext.context
								).map( ( [ key, value ] ) => (
									<tr key={ key }>
										<th>{ key }</th>
										<td>
											<pre>
												{ typeof value === 'object'
													? JSON.stringify(
															value,
															null,
															2
													  )
													: String( value ) }
											</pre>
										</td>
									</tr>
								) ) }
							</tbody>
						</table>
					</VStack>
				</Modal>
			) }

			<BaseControl
				__nextHasNoMarginBottom
				label={ __( 'Logs', 'autoblue' ) }
				id="autoblue-logs"
			>
				<Card>
					<CardBody className={ styles.card }>
						<VStack spacing={ 2 }>
							{ logs.length ? (
								<table className={ styles.table }>
									<thead>
										<tr>
											<th>
												{ __( 'Level', 'autoblue' ) }
											</th>
											<th>
												{ __( 'Message', 'autoblue' ) }
											</th>
											<th>
												{ __( 'Time', 'autoblue' ) }
											</th>
											<th>
												{ __( 'Context', 'autoblue' ) }
											</th>
										</tr>
									</thead>
									<tbody>
										{ logs.map( ( log ) => (
											<LogItem
												key={ log.id }
												{ ...log }
											/>
										) ) }
									</tbody>
								</table>
							) : (
								<div>
									{ __( 'No logs found.', 'autoblue' ) }
								</div>
							) }
						</VStack>
					</CardBody>
				</Card>
			</BaseControl>
		</>
	);
};

export default Logs;
