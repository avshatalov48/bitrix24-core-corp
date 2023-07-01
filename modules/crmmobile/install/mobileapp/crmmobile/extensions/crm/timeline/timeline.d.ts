type TimelineProps = {
	tabId: string,
	uid: string,
	entity: TimelineEntityProps,
	scheduled: TimelineItemProps[],
	pinned: TimelineItemProps[],
	history: {
		items: TimelineItemProps[],
		pagination: TimelinePagination,
	},
};

type TimelineUserProps = {
	detailUrl?: string,
	imageUrl: string,
	title: string,
	userId: number,
};

type TimelineEntityProps = {
	id: number,
	typeId: number,
	categoryId: number | null,
	title: string | null,
	detailPageUrl: string,
	isEditable: boolean,
	pushTag: string | null,
	documentGeneratorProvider: string | null,
	isDocumentPreviewerAvailable: boolean,
	isGoToChatAvailable: boolean,
};

type TimelineItemProps = {
	id?: number,
	layout?: TimelineLayoutSchema,
	timestamp?: number,
	type?: string,
	sort?: number[],
	languageId?: string,
	canBeReloaded?: boolean,
	showMarketBanner: boolean,
} | TimelineItemCompatibleProps;

type TimelineItemCompatibleProps = {
	ASSOCIATED_ENTITY?: {},
	ASSOCIATED_ENTITY_ID?: string,
	ASSOCIATED_ENTITY_TYPE_ID?: string,
	CREATED?: string,
	CREATED_SERVER?: string,
};

type TimelinePagination = {
	offsetId: number,
	offsetTime: string,
};

type TimelineLayoutSchema = {
	icon?: {
		code: string,
		backgroundColorToken: string,
		counterType?: string,
	},
	header?: {},
	body?: {},
	footer?: {},
	isLogMessage: boolean,
};

type TimelinePushActionParams = {
	action: string,
	item: TimelineItemProps,
	stream: string,
	id: string,
	params: Object | null,
};

type TimelineContextMenuItem = {
	id: string,
	title: string,
	scope?: string,
	sort?: number,
	menu?: { items: object },
	action?: object,
};

type TimelineStreamProps = {
	items: TimelineItemProps[],
	timelineScopeEventBus: TimelineEventBus,
	isEditable: boolean,
	onChange: Function,
	onBeforeDelete: Function,
	onItemAction: Function,
};

type TimelineEventBus = {
	on: Function,
	off: Function,
	emit: Function,
};

type TimelineActivityResponse = {
	typeId: number,
	associatedEntityId: number,
	activity: object,
};

type TimelineListViewItem = {
	type: string,
	key: string,
	props: object,
};

type TimelineFileListFile = {
	id: number,
	sourceFileId: number,
	name: string,
	size: number,
	viewUrl: string,
	previewUrl?: string,
	attributes: object,
	extension: string,
};
