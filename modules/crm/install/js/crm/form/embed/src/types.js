export type EmbedOptions = {
	onCloseComplete: function;
	widgetsCount: number;
	activeMenuItemId: ?string;
	sliderOptions: Object;
};

export type WidgetOptions = {
	widgetsCount: number,
}

export type WidgetsList = {
	[key: number]: Widget,
}

export type WidgetsData = {
	formName: string,
	formType: string,
	showMoreLink: boolean,
	previewLink: string | null,
	helpCenterUrl: string,
	helpCenterId: number,
	url: {
		allWidgets: string,
	},
	widgets: WidgetsList,
};

export type LinesList = {
	[key: number]: Line,
}

export type OpenlinesData = {
	formName: string,
	showMoreLink: boolean,
	previewLink: string | null,
	helpCenterUrl: string,
	helpCenterId: number,
	url: {
		allLines: string,
	},
	lines: LinesList,
};

export type Widget = {
	checked: boolean,
	id: number,
	name: string,
	relatedFormIds: number[],
	relatedFormNames: {
		[key: number]: string,
	},
}

export type Line = {
	checked: boolean,
	id: number,
	name: string,
	formEnabled: boolean,
	formId: number | null,
	formName: string,
	formDelay: boolean,
}

export type EmbedDict = {
	viewOptions: {
		delays: EmbedDictPhrase[],
		positions: EmbedDictPhrase[],
		types: EmbedDictPhrase[],
		verticals: EmbedDictPhrase[],
	},
};

export type EmbedData = {
	dict: EmbedDict,
	embed: {
		pubLink: string,
		previewLink: string,
		scripts: {
			auto: {old: string, text: string},
			click: {old: string, text: string},
			inline: {old: string, text: string},
		},
		viewOptions: EmbedDataOptions,
		viewValues: EmbedDataValues,
		helpCenterUrl: string,
		helpCenterId: number,
	},
};

export type EmbedDataValues = {
	[key: string]: Object,
};

export type EmbedDataOptions = {
	[key: string]: {
		[key: string]: Array,
	},
};

export type EmbedDictPhrase = {
	id: string,
	name: string,
}