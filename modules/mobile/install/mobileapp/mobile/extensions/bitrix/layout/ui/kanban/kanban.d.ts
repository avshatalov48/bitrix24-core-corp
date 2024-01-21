type KanbanProps = {
	// unique id of current kanban instance
	id: string,
	stagesProvider: () => KanbanStage[],
	toolbar: {
		enabled: boolean,
		componentClass: KanbanToolbar,
		props: object,
	},

	// routes to interact with backend, can consist other domain-specific routes
	actions: {
		loadItems: string,
		deleteItem: string,
		updateItemStage: string,
	},

	// some base params sending to backend when call related action
	actionParams: {
		loadItems: object,
		deleteItem: object,
		updateItemStage: object,
	},

	selectItemStageId?: (item: object) => KanbanStage,
	mutateItemStage?: (item: object, stage: KanbanStage) => void,
	onMoveItemError?: (err: object) => {},

	layout: object,
	layoutMenuActions: LayoutWidgetMenuAction[],
	layoutOptions: {
		useSearch: boolean,
		useOnViewLoaded: boolean,
	},

	// layout widget header buttons
	menuButtons: object,

	itemDetailOpenHandler: Function,
	itemCounterLongClickHandler: Function,

	isShowFloatingButton: boolean,
	onFloatingButtonClick: Function,
	onFloatingButtonLongClick: Function,

	onDetailCardUpdateHandler: Function,
	onDetailCardCreateHandler: Function,
	onNotViewableHandler?: Function,
	onPanListHandler?: Function,
	initCountersHandler?: Function,
	itemActions?: KanbanItemContextMenuAction[],
	itemParams: object,
	onPrepareItemParams?: (params: object) => object,
	pull?: {
		moduleId: string,
		callback: Function,
	},
	itemLayoutOptions: object,

	cacheName: string,
	getEmptyListComponent?: () => object,

	// layout right buttons
	needInitMenu: boolean,

	ref: Function,
	analyticsLabel: object,
};

type KanbanState = {
	activeStageId: number | null,
};

type LayoutWidgetMenuAction = {
	id: string,
	title: string,
	sectionCode: string,
	iconUrl: string,
	checked: boolean,
	onItemSelected: Function,
};

type KanbanItemContextMenuAction = {
	id: string,
	type?: string,
	title: string,
	sort: number,
	sectionCode?: string,
	isDisabled?: boolean,
	showArrow: boolean,
	showActionLoader?: boolean,
	onClickCallback: Function,
	onDisableClick?: Function,
	data: {
		svgIcon?: string,
	},
};

type KanbanToolbar = {};
type KanbanToolbarProps = {
	style?: object,
	onChangeStage: Function,
};

type KanbanStage = {
	id: number,
	statusId: string, // code or slug
	name: string,
};

type KanbanBackendError = {
	code: string,
	message: string,
	customData: {
		public: boolean,
	},
};
