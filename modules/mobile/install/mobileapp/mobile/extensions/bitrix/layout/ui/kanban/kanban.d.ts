// todo cleanup, remove temp comments

type KanbanProps = {
	id: string, // unique id of current kanban instance
	initItemController: (KanbanInstance) => KanbanItemController,
	toolbar: {
		enabled: boolean,
		componentClass: KanbanToolbar,
		props: object,
	},
	entityTypeName: string, // no need this in abstract kanban? -> must remove it
	entityTypeId: number, // no need this in abstract kanban? -> must remove it
	actions: Record<string, string>, // maybe rename, or replace with Providers
	actionParams: Record<string, object>, // actions and actionParams are related, so - Providers?
	filterParams: object,

	// should Kanban know about layout and it's menu? maybe it's a separate object?
	layout: object,
	layoutMenuActions: LayoutWidgetMenuAction[],
	layoutOptions: {
		useSearch: boolean,
		useOnViewLoaded: boolean,
	},
	menuButtons: object, // is this layout widget header buttons?
	getMenuButtons: Function, // looks like this fn returns same as menuButtons option

	itemDetailOpenHandler: Function, // rename to onItemOpen?
	itemCounterLongClickHandler: Function, // rename to onItemCounterLongClick? what is item counter?

	isShowFloatingButton: boolean,
	floatingButtonClickHandler: Function, // rename to onFloatingButtonClick?
	floatingButtonLongClickHandler: Function, // rename to onFloatingButtonLongClick?

	onDetailCardUpdateHandler: Function, // when it's called? strong coupling with detail card?
	onDetailCardCreateHandler: Function, // when it's called? strong coupling with detail card?
	onNotViewableHandler: Function, // what is this?
	onPanListHandler: Function | null, // what is this?
	initCountersHandler: Function, // what is this?
	itemActions: KanbanItemContextMenuAction[],
	itemParams: object, // how it's used?
	pull: { // how it's used? do we need this in abstract kanban?
		moduleId: string,
		callback: Function,
	},
	itemLayoutOptions: object, // are they universal, or crm specific?

	cacheName: string, // extract work with cache to separate service?
	getEmptyListComponent: () => object,
	onBeforeReload: Function,

	// some external config? how it's used?
	config: {
		forbidden?: boolean,
	},

	needInitMenu: boolean, // what exact menu?
	ref: Function,
	analyticsLabel: object, // any data, just passed everywhere?
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
	type?: string, // what is this?
	title: string,
	sort: number,
	sectionCode?: string,
	isDisabled?: boolean,
	showArrow: boolean,
	showActionLoader?: boolean,
	onClickCallback: Function,
	onDisableClick?: Function, // when it's called? click on disabled item?
	data: {
		svgIcon?: string,
	},
};

type KanbanInstance = {};

type KanbanItemController = {
	setStage: (itemId: number, stageId: number) => Promise<any>,
	deleteItem: (itemId: number) => Promise<any>,
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
