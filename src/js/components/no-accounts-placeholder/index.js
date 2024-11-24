import { __ } from '@wordpress/i18n';
import {
	__experimentalText as Text,
	Button,
	Card,
	CardBody,
	__experimentalVStack as VStack,
} from '@wordpress/components';
import useNewAccountModal from './../new-account-modal';
import styles from './styles.module.scss';

const NoAccountsPlaceholder = () => {
	const { renderModal, openModal } = useNewAccountModal();

	return (
		<Card>
			<CardBody className={ styles.container }>
				<VStack alignment="center" spacing={ 4 }>
					<Text>{ __( 'No account connected.', 'autoblue' ) }</Text>
					<Button variant="primary" onClick={ () => openModal() }>
						{ __( 'Connect a Bluesky account', 'autoblue' ) }
					</Button>
					{ renderModal() }
				</VStack>
			</CardBody>
		</Card>
	);
};

export default NoAccountsPlaceholder;
