export type NotifyManagerOptions = {
	isAvailable: boolean,
	isAdmin: boolean,
}

export type NotifyPanelOptions = {
	isAvailable: boolean,
	type: 'license-expired',
	color?: 'blue' | 'red',
	params: LicenseNotifyPanelParams,
}

export type LicenseNotificationPopupOptions = {
	isAdmin: boolean,
	isAvailable: boolean,
	isPortalWithPartner: boolean,
	urlBuyWithPartner: string,
	urlDefaultBuy: string,
	urlArticle: string,
	expireDate: number,
	blockDate: number,
	isExpired: boolean,
	type: 'almost-expired' | 'expired',
}

export type LicenseNotifyPanelParams = {
	blockDate: number,
	urlBuy: string,
	urlArticle: string,
}