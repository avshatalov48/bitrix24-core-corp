type ChartData = {
	movingTo: boolean;
	modelTransform: {
		x: number;
		y: number;
		zoom: number;
	};
};

type Head = {
	id: number;
	avatar: ?string;
	name: string;
	role: string;
	url: string;
	workPosition: ?string;
};

type TreeItem = {
	id: number;
	name: string;
	heads: Array<Head>;
	userCount: number;
	parentId: number;
	children?: Array<string>;
};

type Point = {
	x: number;
	y: number;
};

type ConnectorData = {
	id: string;
	parentId: string;
	startPoint: Point;
	endPoint: Point;
	html: HTMLElement;
	offset: number;
};

type TreeData = {
	connectors: ConnectorData;
	expandedNodes: Array<string>;
};

type TreeNodeData = {
	childrenOffset: number;
	childrenMounted: boolean;
	showInfo: boolean;
};

export type {
	TreeItem,
	ConnectorData,
	TreeData,
	TreeNodeData,
	ChartData,
};
