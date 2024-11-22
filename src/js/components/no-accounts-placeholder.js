import { __ } from '@wordpress/i18n';
import {
	__experimentalText as Text,
	Button,
	Card,
	CardBody,
	__experimentalVStack as VStack,
} from '@wordpress/components';
import useNewAccountModal from './new-account-modal';

const NoAccountsPlaceholder = () => {
	const { renderModal, openModal } = useNewAccountModal();

	return (
		<Card>
			<CardBody>
				<VStack alignment="center" spacing={ 4 }>
					<Text>
						{ __( 'No account connected.', 'bsky-for-wp' ) }
					</Text>
					<Button
						variant="primary"
						size="large"
						onClick={ () => openModal() }
					>
						{ __( 'Connect a Bluesky account', 'bsky-for-wp' ) }
					</Button>
					{ renderModal() }
				</VStack>
			</CardBody>
		</Card>
	);
};

export default NoAccountsPlaceholder;
