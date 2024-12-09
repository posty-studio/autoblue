import { __, sprintf } from '@wordpress/i18n';
import {
	Button,
	__experimentalHStack as HStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { next, previous } from '@wordpress/icons';
import useLogs from './../../../hooks/use-logs';
import styles from './styles.module.scss';

const Pagination = () => {
	const { setPage, page, totalPages, totalItems } = useLogs();

	const handlePreviousPage = () => {
		if ( page > 1 ) {
			setPage( page - 1 );
		}
	};

	const handleNextPage = () => {
		if ( page < totalPages ) {
			setPage( page + 1 );
		}
	};

	return (
		<HStack alignment="left" className={ styles.pagination }>
			<Button
				label={ __( 'Previous page', 'autoblue' ) }
				onClick={ handlePreviousPage }
				accessibleWhenDisabled
				size="compact"
				disabled={ page === 1 }
				icon={ previous }
			/>
			<Button
				label={ __( 'Next page', 'autoblue' ) }
				onClick={ handleNextPage }
				accessibleWhenDisabled
				size="compact"
				disabled={ page === totalPages || totalPages === 0 }
				icon={ next }
			/>
			<span>
				{ sprintf(
					// translators: %1$d is the first item number, %2$d is the last item number, %3$d is the total number of items)
					__( 'Showing %1$d-%2$d of %3$d logs', 'autoblue' ),
					totalItems === 0 ? 0 : ( page - 1 ) * 10 + 1,
					Math.min( page * 10, totalItems ),
					totalItems
				) }
			</span>
		</HStack>
	);
};

export default Pagination;
