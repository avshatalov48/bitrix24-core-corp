declare type Provider = {
	id: string,
	name: string,
	shortName: string,
	manageUrl: string,
	isConfigurable: boolean,
	canUse: boolean,
	isDemo: boolean,
	isDefault: boolean,
	isTemplatesBased: boolean,
	templates?: Array,
	fromList?: Array,
}

declare type TemplateItem = {
	ID: string,
	ORIGINAL_ID: number,
	TITLE: string,
	HEADER?: string,
	FOOTER?: string,
	PREVIEW?: string,
	PLACEHOLDERS?: Array,
	FILLED_PLACEHOLDERS?: Array,
}

export {
	Provider,
	TemplateItem,
};
