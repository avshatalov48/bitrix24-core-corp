export type GeneralOptions = {
	buttonWrapper: HTMLElement,
	'main-button': MainButtonOptions,
	license: LicenseParams,
	'purchase-history': PurchaseHistoryParams,
}

export type ConfigOptions = {
	html: HTMLElement | Promise,
	flex?: number,
	secondary?: boolean,
	minHeight?: string,
	sizeLoader?: number,
	marginBottom?: number,
	backgroundColor?: string,
}

export type PopupOptions = {
	target: HTMLElement,
	content: GeneralOptions,
}

export type MainButtonOptions = {
	wrapper: HTMLElement,
	isLicenseExpired: boolean,
	className: string,
	text: string,
}

export type LicenseParams = {
	isExpired: boolean,
	isAlmostExpired: boolean,
	isAlmostBlocked: boolean,
	name: string,
	messages: {
		expired: string,
		block: string,
		remainder: string,
	},
	button: {
		link: string,
		text: string,
	},
	more: {
		link: string,
		text: string,
	},
}

export type PurchaseHistoryParams = {
	link: string,
	text: string,
};

export type LicenseContentOptions = LicenseParams;
export type PurchaseHistoryContentOptions = PurchaseHistoryParams;