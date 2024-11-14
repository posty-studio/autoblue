import { __ } from '@wordpress/i18n';
import {
	__experimentalUnitControl as UnitControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	ToggleControl,
	BaseControl,
} from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { BlueskyIcon } from '../../icons';

const SharePanel = () => {
	const { editPost } = useDispatch( 'core/editor' );
	const { editEntityRecord } = useDispatch( 'core' );
	const {
		postId,
		postType,
		abv,
		flagship,
		upcoming,
		setLocationsManually,
		locations,
		selectedLocations,
	} = useSelect( ( select ) => {
		const { getCurrentPostId, getCurrentPostType, getEditedPostAttribute } =
			select( 'core/editor' );
		const { getEntityRecords } = select( 'core' );
		const _postId = getCurrentPostId();
		return {
			postId: _postId,
			postType: getCurrentPostType(),
			abv: getEditedPostAttribute( 'meta' )?.lbd_beer_abv,
			flagship: getEditedPostAttribute( 'meta' )?.lbd_beer_flagship,
			upcoming: getEditedPostAttribute( 'meta' )?.lbd_beer_upcoming,
			setLocationsManually:
				getEditedPostAttribute( 'meta' )
					?.lbd_beer_set_locations_manually,
			locations: getEntityRecords( 'taxonomy', 'lbd-beer-location', {
				context: 'view',
				per_page: 100,
				orderby: 'name',
				order: 'asc',
			} ),
			selectedLocations:
				getEditedPostAttribute( 'lbd-beer-location' ) || [],
		};
	}, [] );

	if ( postType !== 'post' ) {
		return null;
	}

	const updateABV = ( value ) => {
		value = parseFloat( value );

		if ( isNaN( value ) ) {
			value = 0;
		}

		editPost( { meta: { lbd_beer_abv: value } } );
	};

	const updateLocation = ( locationId, isSelected ) => {
		const newSelectedLocations = isSelected
			? [ ...selectedLocations, locationId ]
			: selectedLocations.filter( ( id ) => id !== locationId );

		editEntityRecord( 'postType', 'lbd-beer', postId, {
			'lbd-beer-location': newSelectedLocations,
		} );
	};

	console.log( BlueskyIcon );

	return (
		<PluginDocumentSettingPanel
			name="bsky4wp-share-panel"
			title={ __( 'Bluesky', 'bsky-for-wp' ) }
			icon={ BlueskyIcon }
		>
			<VStack spacing={ 5 }>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Flagship Beer', 'bsky-for-wp' ) }
					checked={ flagship }
					onChange={ ( value ) =>
						editPost( { meta: { lbd_beer_flagship: value } } )
					}
				/>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Coming Soon', 'bsky-for-wp' ) }
					checked={ upcoming }
					onChange={ ( value ) =>
						editPost( { meta: { lbd_beer_upcoming: value } } )
					}
				/>
				<UnitControl
					__nextHasNoMarginBottom
					label={ __( 'ABV', 'bsky-for-wp' ) }
					value={ abv }
					onChange={ updateABV }
					min={ 0 }
					max={ 100 }
					step={ 0.1 }
					units={ [ { value: '%', label: '%' } ] }
				/>
				{ locations && (
					<>
						<BaseControl
							__nextHasNoMarginBottom
							label={ __( 'On Tap', 'lbd' ) }
							id="on-tap"
							help={
								! setLocationsManually
									? 'Last updated: 8 mins ago'
									: null
							}
						>
							{ locations.map( ( location ) => (
								<ToggleControl
									key={ location.id }
									label={ location.name }
									disabled={ ! setLocationsManually }
									checked={ selectedLocations.includes(
										location.id
									) }
									onChange={ ( isChecked ) =>
										updateLocation( location.id, isChecked )
									}
								/>
							) ) }
						</BaseControl>
						<BaseControl
							__nextHasNoMarginBottom
							label={ __( 'Options', 'lbd' ) }
							id="options"
						>
							<ToggleControl
								__nextHasNoMarginBottom
								label={ __( 'Set Manually', 'lbd' ) }
								help={ __(
									'Set the "On Tap" status manually, instead of retrieving it from Toast.',
									'lbd'
								) }
								checked={ setLocationsManually }
								onChange={ ( isChecked ) =>
									editPost( {
										meta: {
											lbd_beer_set_locations_manually:
												isChecked,
										},
									} )
								}
							/>
						</BaseControl>
					</>
				) }
			</VStack>
		</PluginDocumentSettingPanel>
	);
};

export default SharePanel;
