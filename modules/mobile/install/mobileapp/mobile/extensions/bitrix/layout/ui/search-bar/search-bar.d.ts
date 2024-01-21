type SearchBarProps = {
	id: string,
	cacheId?: string,
	presetId?: string,
	counterId?: string,
	searchDataAction: string,
	searchDataActionParams?: {},
	layout: object,
	onMoreButtonClick?: () => {},
	onCheckRestrictions?: () => {},
};

type SearchBarState = {
	visible: boolean,
	counters: SearchBarCounter[] | null,
	presets: SearchBarPreset[] | null,
	search: string,
	presetId: string | null,
	counterId: string | null,
	iconBackground: string,
};

type SearchBarPreset = {
	id: string,
	name: string,
	default: boolean,
	disabled?: boolean,
	unsupportedFields?: string[],
};

type SearchBarCounter = {
	typeId: number,
	typeName: string,
	title: string,
	code: string,
	color: string,
	showValue: boolean,
	value: number,
	unsupportedFields?: string[],
};

type SearchBarCounterProps = SearchBarCounter & {
	active: boolean,
	onClick: () => {},
};

type SearchBarPresetProps = SearchBarPreset & {
	active: boolean,
	onClick: () => {},
	last: boolean,
};
