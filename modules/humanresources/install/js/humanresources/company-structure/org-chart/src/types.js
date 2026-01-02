import { EntityTypes, type NodeColorSettingsType } from 'humanresources.company-structure.utils';

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
	entityType: EntityTypes.department | EntityTypes.team | EntityTypes.company;
	teamColor?: NodeColorSettingsType;
};

type Point = {
	x: number;
	y: number;
};

type MountedDepartment = {
	id: string;
	parentId: string;
	startPoint?: Point;
	endPoint?: Point;
	html?: HTMLElement;
	parentsPath?: number[];
};

type TreeData = {
	expandedNodes: Array<number>;
};

type ConnectorsData = {
	...MountedDepartment,
	show: boolean;
	highlighted: boolean;
};

type TreeNodeData = {
	childrenOffset: number;
	childrenMounted: boolean;
	showInfo: boolean;
	showDnd: boolean;
};

type FirstPopupData = {
	show: boolean;
	title: string;
	description: string;
	subDescription: string;
	features: string[];
};

type DragAndDropData = {
	draggedId: number;
	targetId: number;
	affectedItems: HTMLElement[];
	direction: 1 | -1,
	insertion: 'reorder' | 'sibling' | 'inside',
};

export type {
	TreeItem,
	TreeData,
	ConnectorsData,
	MountedDepartment,
	TreeNodeData,
	ChartData,
	FirstPopupData,
	Head,
	DragAndDropData,
};
