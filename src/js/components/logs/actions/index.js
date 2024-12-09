import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import {
	Button,
	Dropdown,
	RadioControl,
	__experimentalConfirmDialog as ConfirmDialog, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	__experimentalHStack as HStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { trash, cog, check } from '@wordpress/icons';
import { RefreshIcon } from '../../../icons';
import useLogs from './../../../hooks/use-logs';
import styles from './styles.module.scss';

const Actions = () => {
	const {
		refreshLogs,
		clearLogs,
		isRefreshingLogs,
		isClearingLogs,
		totalItems,
		level,
		setLevel,
	} = useLogs();

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
				confirmButtonText={ __( 'Clear logs', 'autoblue' ) }
			>
				{ __( 'Are you sure you want to clear all logs?', 'autoblue' ) }
			</ConfirmDialog>
			<HStack alignment="right">
				<Dropdown
					contentClassName={ styles.dropdown }
					popoverProps={ { placement: 'bottom-end' } }
					renderToggle={ ( { isOpen, onToggle } ) => (
						<Button
							label={ __( 'View options', 'autoblue' ) }
							onClick={ onToggle }
							aria-expanded={ isOpen }
							icon={ cog }
							size="compact"
						/>
					) }
					renderContent={ () => (
						<RadioControl
							label={ __( 'Log Level', 'autoblue' ) }
							help={ __(
								'Choose the level of logs to save. This will not affect existing logs.',
								'autoblue'
							) }
							onChange={ setLevel }
							options={ [
								{
									description: __(
										'Everything will be logged. Useful for debugging.',
										'autoblue'
									),
									label: __( 'Debug', 'autoblue' ),
									value: 'debug',
								},
								{
									description: __(
										'Errors, warnings and info messages will be logged.',
										'autoblue'
									),
									label: __(
										'Info (recommended)',
										'autoblue'
									),
									value: 'info',
								},
								{
									description: __(
										'Only critical errors will be logged.',
										'autoblue'
									),
									label: __( 'Error', 'autoblue' ),
									value: 'error',
								},
								{
									description: __(
										'Nothing will be logged.',
										'autoblue'
									),
									label: __( 'Off', 'autoblue' ),
									value: 'off',
								},
							] }
							selected={ level }
						/>
					) }
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
					disabled={
						isRefreshingLogs || isClearingLogs || totalItems === 0
					}
				/>
			</HStack>
		</>
	);
};

export default Actions;
