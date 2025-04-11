import { store as editorStore } from '@wordpress/editor';
import { useSelect } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const POLL_INTERVAL = 1000;
const MAX_POLL_ATTEMPTS = 60;

const getUriFromAtUri = ( atUri, did ) => {
	const rkey = atUri.split( '/' ).pop();

	return `https://bsky.app/profile/${ did }/post/${ rkey }`;
};

const useShares = () => {
	const [ polledMeta, setPolledMeta ] = useState( null );
	const [ pollCount, setPollCount ] = useState( 0 );

	const postData = useSelect( ( select ) => {
		const { getCurrentPostAttribute, getCurrentPostId } =
			select( editorStore );
		const meta = getCurrentPostAttribute( 'meta' ) || {};

		return {
			isSharingEnabled: meta?.autoblue_enabled || false,
			shares: meta?.autoblue_shares || [],
			postId: getCurrentPostId(),
		};
	}, [] );

	useEffect( () => {
		const { postId, shares } = postData;
		const hasExistingShares =
			shares.length > 0 || polledMeta?.autoblue_shares?.length > 0;

		if ( ! postId || hasExistingShares || pollCount >= MAX_POLL_ATTEMPTS ) {
			return;
		}

		const pollForUpdates = async () => {
			try {
				const response = await apiFetch( {
					path: `/wp/v2/posts/${ postId }`,
					method: 'GET',
				} );

				if ( response.meta ) {
					setPolledMeta( response.meta );
					return response.meta.autoblue_shares?.length > 0;
				}
				return false;
			} catch ( error ) {
				return false;
			}
		};

		const intervalId = setInterval( async () => {
			setPollCount( ( count ) => count + 1 );

			const shouldStopPolling =
				( await pollForUpdates() ) ||
				pollCount >= MAX_POLL_ATTEMPTS - 1;

			if ( shouldStopPolling ) {
				clearInterval( intervalId );
				if ( pollCount >= MAX_POLL_ATTEMPTS - 1 ) {
					// Maybe do something here?
				}
			}
		}, POLL_INTERVAL );

		return () => clearInterval( intervalId );
	}, [ postData, polledMeta?.autoblue_shares?.length, pollCount ] );

	let currentShares = polledMeta?.autoblue_shares || postData.shares;

	// Add URL
	currentShares = currentShares.map( ( share ) => {
		return {
			...share,
			url: getUriFromAtUri( share.uri, share.did ),
		};
	} );

	return {
		shares: currentShares,
		hasShares: currentShares.length > 0,
		isSharingEnabled:
			polledMeta?.autoblue_enabled ?? postData.isSharingEnabled,
	};
};

export default useShares;
