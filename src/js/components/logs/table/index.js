import styles from './styles.module.scss';

const Table = ( { children } ) => {
	return (
		<div className={ styles.wrapper }>
			<table className={ styles.table }>{ children }</table>
		</div>
	);
};

export default Table;
