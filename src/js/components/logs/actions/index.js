import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import {
	Button,
	__experimentalConfirmDialog as ConfirmDialog, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	__experimentalHStack as HStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { trash, cog, check } from '@wordpress/icons';
import { RefreshIcon } from '../../../icons';
import useLogs from './../../../hooks/use-logs';
import styles from './styles.module.scss';

const Actions = () => {
	const { refreshLogs, clearLogs, isRefreshingLogs, isClearingLogs } =
		useLogs();

	const [ isClearLogsConfirmDialogOpen, setIsClearLogsConfirmDialogOpen ] =
		useState( false );

	const isBusy = isRefreshingLogs || isClearingLogs;

	const handleConfirmClearLogs = () => {
		if ( ! isBusy ) {
			clearLogs();
			setIsClearLogsConfirmDialogOpen( false );
		}
	};

	const handleRefreshLogs = () => {
		if ( ! isBusy ) {
			refreshLogs();
		}
	};

	return (
		<>
			<ConfirmDialog
				isOpen={ isClearLogsConfirmDialogOpen }
				onConfirm={ handleConfirmClearLogs }
				onCancel={ () => setIsClearLogsConfirmDialogOpen( false ) }
				confirmButtonText={ __( 'Clear Logs', 'autoblue' ) }
			>
				{ __( 'Are you sure you want to clear all logs?', 'autoblue' ) }
			</ConfirmDialog>
			<HStack alignment="right">
				<Button
					label={ __( 'View options', 'autoblue' ) }
					icon={ cog }
					size="compact"
				/>
				<Button
					label={ __( 'Refresh logs', 'autoblue' ) }
					onClick={ handleRefreshLogs }
					disabled={ isRefreshingLogs || isClearingLogs }
					className={ clsx( {
						[ styles.refresh ]: true,
						[ styles.refreshing ]: isRefreshingLogs,
					} ) }
					icon={ RefreshIcon }
					size="compact"
				/>
				<Button
					label={ __( 'Clear logs', 'autoblue' ) }
					onClick={ () => setIsClearLogsConfirmDialogOpen( true ) }
					isDestructive
					icon={ trash }
					size="compact"
				/>
			</HStack>
		</>
	);
};

export default Actions;
