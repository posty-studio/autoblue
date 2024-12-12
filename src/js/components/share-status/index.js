import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { dateI18n, humanTimeDiff, getSettings } from '@wordpress/date';
import {
	Tooltip,
	Spinner,
	Icon,
	ExternalLink,
	__experimentalText as Text, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	__experimentalHStack as HStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { check } from '@wordpress/icons';
import useShares from './../../hooks/use-shares';
import styles from './styles.module.scss';

const getUriFromAtUri = ( atUri, did ) => {
	const rkey = atUri.split( '/' ).pop();

	return `https://bsky.app/profile/${ did }/post/${ rkey }`;
};

const ShareStatus = () => {
	const { shares, isSharingEnabled } = useShares();

	const { postType } = useSelect( ( select ) => {
		const { getCurrentPostType } = select( 'core/editor' );

		return {
			postType: getCurrentPostType(),
		};
	}, [] );

	// TODO: Add support for other post types.
	if ( postType !== 'post' ) {
		return null;
	}

	const { formats } = getSettings();

	if ( ! isSharingEnabled ) {
		return (
			<Text variant="muted">
				{ __( 'Sharing was disabled for this post.', 'autoblue' ) }
			</Text>
		);
	}

	if ( ! shares.length ) {
		return (
			<HStack alignment="center">
				<Spinner />
			</HStack>
		);
	}

	return (
		<div>
			{ shares.map( ( share ) => (
				<div key={ share.uri } className={ styles.share }>
					<HStack alignment="left" spacing={ 3 }>
						<Icon icon={ check } className={ styles.icon } />
						<VStack spacing={ 0 }>
							<Text>
								{ __( 'Shared to Bluesky', 'autoblue' ) }
							</Text>
							<Tooltip
								text={ dateI18n(
									formats?.datetime || 'c',
									share.date
								) }
							>
								<Text variant="muted">
									<time
										dateTime={ dateI18n( 'c', share.date ) }
									>
										{ humanTimeDiff( share.date ) }
									</time>
								</Text>
							</Tooltip>
						</VStack>
					</HStack>
					<ExternalLink
						href={ getUriFromAtUri( share.uri, share.did ) }
					>
						{ __( 'View on Bluesky', 'autoblue' ) }
					</ExternalLink>
				</div>
			) ) }
		</div>
	);
};

export default ShareStatus;
