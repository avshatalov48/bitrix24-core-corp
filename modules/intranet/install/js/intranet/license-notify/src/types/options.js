export type LicenseNotifyPanelParams = {
	blockDate?: number,
	urlBuy?: string,
	urlArticle?: string,
}

export type LicenseNotificationPopupParams = {
	isAdmin: boolean,
	isAvailable: boolean,
	isPortalWithPartner: boolean,
	urlBuyWithPartner: string,
	urlDefaultBuy: string,
	urlArticle: string,
	expireDate: number,
	blockDate: number,
	isExpired: boolean,
	isCIS: boolean,
	type: 'popup',
	popupType: string,
}

export type NotifyManagerOptions = {
	isAdmin: boolean,
	notify: {
		...LicenseNotifyPanelParams,
		...LicenseNotificationPopupParams,
	},
};
