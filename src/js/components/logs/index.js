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
	ButtonGroup,
	Spinner,
	__experimentalConfirmDialog as ConfirmDialog,
	__experimentalText as Text,
	__experimentalVStack as VStack,
	__experimentalHStack as HStack,
} from '@wordpress/components';
import useLogs from './../../hooks/use-logs';
import styles from './styles.module.scss';
import {
	info,
	warning,
	code,
	cancelCircleFilled,
	check,
	page,
	trash,
	cog,
} from '@wordpress/icons';
import { RefreshIcon } from '../../icons';

const getIconFromLevel = ( level ) => {
	switch ( level ) {
		case 'success':
			return check;
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

const processUrls = ( str ) => {
	const parts = str.split( /(https:\/\/bsky\.app\/profile\/[^\s]+)/g );
	return parts.map( ( part, index ) => {
		const isBskyProfileUrl = /^https:\/\/bsky\.app\/profile\/[^\s]+$/.test(
			part
		);
		return isBskyProfileUrl ? (
			<a
				key={ `url-${ index }` }
				href={ part }
				target="_blank"
				rel="noreferrer"
			>
				{ part }
			</a>
		) : (
			part
		);
	} );
};

const convertBackticks = ( content ) => {
	if ( typeof content === 'string' ) {
		const parts = content.split( '`' );
		return parts.map( ( part, index ) =>
			index % 2 === 0 ? (
				part
			) : (
				<code key={ `code-${ index }` }>{ part }</code>
			)
		);
	}
	return content;
};

const parseMessage = ( message ) => {
	// First split and process for URLs
	const urlProcessed = processUrls( message );

	// Then process each part for backticks
	return urlProcessed
		.map( ( part, index ) => {
			if ( typeof part === 'string' ) {
				return convertBackticks( part );
			}
			return part;
		} )
		.flat();
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
	const { logs, refreshLogs, clearLogs, isRefreshingLogs, isClearingLogs } =
		useLogs();
	const [ selectedLogContext, setSelectedLogContext ] = useState( null );
	const { formats } = getSettings();
	const [ isClearLogsConfirmDialogOpen, setIsClearLogsConfirmDialogOpen ] =
		useState( false );

	const handleContextView = ( log ) => {
		setSelectedLogContext( log );
	};

	const handleConfirmClearLogs = () => {
		if ( isClearingLogs || isRefreshingLogs ) {
			return;
		}

		clearLogs();
		setIsClearLogsConfirmDialogOpen( false );
	};

	const handleRefreshLogs = () => {
		if ( isRefreshingLogs || isClearingLogs ) {
			return;
		}

		refreshLogs();
	};

	const LogItem = ( log ) => {
		const { level, context, message, created_at: createdAt } = log;

		return (
			<tr className={ clsx( styles.item, styles[ level ] ) }>
				<td>
					<LogLevel level={ level } />
				</td>
				<td>{ parseMessage( message ) }</td>
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
					<Button
						onClick={ () => handleContextView( log ) }
						variant="secondary"
						icon={ page }
						label={ __( 'View Context', 'autoblue' ) }
					/>
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
										{ parseMessage(
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
								{ Object.entries( {
									...selectedLogContext.context,
									...selectedLogContext.extra,
								} ).map( ( [ key, value ] ) => (
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

			<ConfirmDialog
				isOpen={ isClearLogsConfirmDialogOpen }
				onConfirm={ handleConfirmClearLogs }
				onCancel={ () => setIsClearLogsConfirmDialogOpen( false ) }
				confirmButtonText={ __( 'Clear Logs', 'autoblue' ) }
			>
				{ __( 'Are you sure you want to clear all logs?', 'autoblue' ) }
			</ConfirmDialog>

			<BaseControl
				__nextHasNoMarginBottom
				id="autoblue-logs"
				label={
					<div className={ styles.label }>
						<span>
							{ __( 'Logs', 'autoblue' ) }
							&nbsp;
						</span>
						<HStack alignment="right">
							{ ( isRefreshingLogs || isClearingLogs ) && (
								<Spinner style={ { marginTop: 0 } } />
							) }
							<Button
								variant="secondary"
								icon={ cog }
								size="compact"
							>
								{ __( 'Settings', 'autoblue' ) }
							</Button>
							<ButtonGroup>
								<Button
									onClick={ handleRefreshLogs }
									variant="secondary"
									icon={ RefreshIcon }
									size="compact"
								>
									{ __( 'Refresh', 'autoblue' ) }
								</Button>
								<Button
									onClick={ () =>
										setIsClearLogsConfirmDialogOpen( true )
									}
									variant="secondary"
									isDestructive
									icon={ trash }
									size="compact"
								>
									{ __( 'Clear', 'autoblue' ) }
								</Button>
							</ButtonGroup>
						</HStack>
					</div>
				}
			>
				<Card>
					<CardBody className={ styles.card }>
						<VStack spacing={ 2 }>
							<table className={ styles.table }>
								<thead>
									<tr>
										<th>{ __( 'Level', 'autoblue' ) }</th>
										<th>{ __( 'Message', 'autoblue' ) }</th>
										<th>{ __( 'Time', 'autoblue' ) }</th>
										<th>{ __( 'Context', 'autoblue' ) }</th>
									</tr>
								</thead>
								<tbody>
									{ logs.length > 0 &&
										logs.map( ( log ) => (
											<LogItem
												key={ log.id }
												{ ...log }
											/>
										) ) }
								</tbody>
							</table>
							{ ! logs.length && (
								<div className={ styles.empty }>
									{ __( 'No logs (yet).', 'autoblue' ) }
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
