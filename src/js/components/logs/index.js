import { __ } from '@wordpress/i18n';
import {
	Card,
	CardBody,
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	__experimentalHStack as HStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import useLogs from './../../hooks/use-logs';
import styles from './styles.module.scss';
import ContextModal from './context-modal';
import Actions from './actions';
import Pagination from './pagination';
import Item from './item';
import Table from './table';

const Logs = () => {
	const { logs, refreshLogs } = useLogs();
	const firstRender = useRef( true );
	const [ selectedLogContext, setSelectedLogContext ] = useState( null );

	const handleContextView = ( log ) => {
		setSelectedLogContext( log );
	};

	const refreshLogsOnMount = useCallback( async () => {
		try {
			await refreshLogs();
		} catch ( error ) {}
	}, [ refreshLogs ] );

	useEffect( () => {
		if ( firstRender.current ) {
			refreshLogsOnMount();
			firstRender.current = false;
		}
	}, [ refreshLogsOnMount ] );

	return (
		<>
			{ selectedLogContext && (
				<ContextModal
					context={ selectedLogContext }
					onRequestClose={ () => setSelectedLogContext( null ) }
				/>
			) }

			<VStack spacing={ 2 }>
				<HStack alignment="edge" className={ styles.header }>
					<Pagination />
					<Actions />
				</HStack>
				<Card>
					<CardBody className={ styles.card }>
						<VStack spacing={ 2 }>
							<Table>
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
											<Item
												key={ log.id }
												log={ log }
												onButtonClick={
													handleContextView
												}
											/>
										) ) }
								</tbody>
							</Table>
							{ ! logs.length && (
								<div className={ styles.empty }>
									{ __( 'No logs (yet).', 'autoblue' ) }
								</div>
							) }
						</VStack>
					</CardBody>
				</Card>
			</VStack>
		</>
	);
};

export default Logs;
