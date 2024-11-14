import { __ } from '@wordpress/i18n';
import { SearchControl } from '@wordpress/components';
import { useState } from 'react';

const AccountSearch = () => {
	const [ searchValue, setSearchValue ] = useState( '' );

	return (
		<SearchControl
			placeholder={ __(
				'Search for your Bluesky account',
				'bsky-for-wp'
			) }
			label={ __( 'Bluesky Account', 'bsky-for-wp' ) }
			hideLabelFromVision={ false }
			value={ searchValue }
			onChange={ ( newSearchValue ) => setSearchValue( newSearchValue ) }
		/>
	);
};

export default AccountSearch;
