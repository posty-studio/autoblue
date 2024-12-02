import { useSelect } from '@wordpress/data';
import { STORE_NAME } from '../store';

const useLogs = () => {
	const { logs } = useSelect(
		( select ) => ( {
			logs: select( STORE_NAME ).getLogs(),
		} ),
		[]
	);

	return {
		logs,
	};
};

export default useLogs;
