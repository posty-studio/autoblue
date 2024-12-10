import clsx from 'clsx';
import { __ } from '@wordpress/i18n';
import {
	Button,
	Dropdown,
	RadioControl,
	__experimentalConfirmDialog as ConfirmDialog, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	__experimentalHStack as HStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	__experimentalHeading as Heading, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	__experimentalSpacer as Spacer, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	__experimentalText as Text, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import { trash, cog, closeSmall } from '@wordpress/icons';
import { RefreshIcon } from '../../../icons';
import useLogs, { LOG_LEVELS } from './../../../hooks/use-logs';
import styles from './styles.module.scss';

// Based on: https://github.com/WordPress/gutenberg/blob/trunk/packages/block-editor/src/components/inspector-popover-header/index.js
const DropdownHeader = ( { onClose } ) => {
	return (
		<VStack spacing={ 1 } className={ styles.header }>
			<HStack alignment="center">
				<Heading level={ 2 } size={ 13 }>
					{ __( 'Log level', 'autoblue' ) }
				</Heading>
				<Spacer />
				{ onClose && (
					<Button
						size="small"
						label={ __( 'Close' ) }
						icon={ closeSmall }
						onClick={ onClose }
					/>
				) }
			</HStack>
			<Text>
				{ __(
					'Choose the level of logs to save. This will not affect existing logs.',
					'autoblue'
				) }
			</Text>
		</VStack>
	);
};

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
					focusOnMount
					renderToggle={ ( { isOpen, onToggle } ) => (
						<Button
							label={ __( 'View options', 'autoblue' ) }
							onClick={ onToggle }
							aria-expanded={ isOpen }
							icon={ cog }
							size="compact"
						/>
					) }
					renderContent={ ( { onClose } ) => (
						<>
							<DropdownHeader onClose={ onClose } />
							<form>
								<RadioControl
									label={ __( 'Log level', 'autoblue' ) }
									hideLabelFromVision
									onChange={ setLevel }
									options={ LOG_LEVELS.map( ( level ) => {
										if ( level.value === 'info' ) {
											return {
												...level,
												label: `${ level.label } ${ __(
													'(recommended)',
													'autoblue'
												) }`,
											};
										}

										return level;
									} ) }
									selected={ level }
								/>
							</form>
						</>
					) }
				/>
				<Button
					label={ __( 'Refresh logs', 'autoblue' ) }
					onClick={ handleRefreshLogs }
					disabled={ isRefreshingLogs || isClearingLogs }
					accessibleWhenDisabled
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
					accessibleWhenDisabled
					disabled={
						isRefreshingLogs || isClearingLogs || totalItems === 0
					}
				/>
			</HStack>
		</>
	);
};

export default Actions;
