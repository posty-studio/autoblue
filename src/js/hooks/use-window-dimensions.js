import { useState, useEffect } from '@wordpress/element';

const useWindowDimensions = () => {
	const [ windowDimensions, setWindowDimensions ] = useState( {
		width: window.innerWidth,
		height: window.innerHeight,
	} );

	useEffect( () => {
		const handleResize = () => {
			setWindowDimensions( {
				width: window.innerWidth,
				height: window.innerHeight,
			} );
		};
		window.addEventListener( 'resize', handleResize );
		return () => window.removeEventListener( 'resize', handleResize );
	}, [] );

	return windowDimensions;
};

export default useWindowDimensions;
