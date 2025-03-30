type ChartData = {
	canvas: {
		shown: boolean;
		movingTo: boolean;
		modelTransform: {
			x: number;
			y: number;
			zoom: number;
		};
	},
	wizard: {
		shown: boolean;
		isEditMode: boolean;
		showEntitySelector: boolean;
		entity: string;
		nodeId: number;
	},
	detailPanel: {
		collapsed: boolean;
		preventSwitch: boolean;
	},
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
	description?: string;
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
	chidldrenLoaded: boolean;
	childrenOffset: number;
	childrenMounted: boolean;
	showInfo: boolean;
};

type FirstPopupData = {
	show: boolean;
	title: string;
	description: string;
	subDescription: string;
	features: string[];
};

export type {
	TreeItem,
	ConnectorData,
	TreeData,
	TreeNodeData,
	ChartData,
	FirstPopupData,
	Head,
};
