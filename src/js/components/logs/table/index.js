import styles from './styles.module.scss';

const Table = ( { children } ) => {
	return <table className={ styles.table }>{ children }</table>;
};

export default Table;
