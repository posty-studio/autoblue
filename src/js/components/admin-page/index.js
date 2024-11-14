import { __ } from '@wordpress/i18n';
import { Card, CardBody, SearchControl } from '@wordpress/components';
import { useState } from 'react';
import styles from './styles.module.scss';

const AdminPage = () => {
	const [ searchValue, setSearchValue ] = useState( '' );

	return (
		<Card className={ styles.card }>
			<CardBody>
				<SearchControl
					placeholder={ __(
						'Search for your Bluesky account',
						'bsky-for-wp'
					) }
					label={ __( 'Bluesky Account', 'bsky-for-wp' ) }
					hideLabelFromVision={ false }
					value={ searchValue }
					onChange={ ( newSearchValue ) =>
						setSearchValue( newSearchValue )
					}
				/>
			</CardBody>
		</Card>
	);
};

export default AdminPage;
