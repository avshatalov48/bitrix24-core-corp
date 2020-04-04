export type Tunnel = {
	srcCategory: string,
	srcStage: string,
	dstCategory: string,
	dstStage: string,
	robot: {[key: string]: any},
};

type Stage = {
	COLOR: string,
	ENTITY_ID: string,
	ID: string,
	NAME: string,
	NAME_INIT: string,
	SEMANTICS: 'F' | 'P' | 'S',
	SORT: string,
	STATUS_ID: string,
	SYSTEM: 'Y' | 'N',
	TUNNELS: Array<Tunnel>,
	CATEGORY_ID: string | number,
};

export default Stage;