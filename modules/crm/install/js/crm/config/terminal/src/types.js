// @flow

type SmsService = {
	name: string,
	id: string,
	isActive?: boolean,
};

type TerminalPaysystem = {
	id: number,
	title: string,
	image: string,
	type: string,
	active: boolean,
};

type TerminalSettingsProps = {
	isSmsSendingEnabled: boolean,
	isNotificationsEnabled: boolean,
	activeSmsServices: SmsService[],
	paymentSlipLinkScheme: string,
	connectNotificationsLink: NotificationLink,
	hasPaysystemsPermission: boolean,
	isLinkPaymentEnabled: boolean,
	availablePaysystems: TerminalPaysystem[],
	terminalDisabledPaysystems: number[],
	isRuZone: boolean,
	isSbpEnabled: boolean,
	isSbpConnected: boolean,
	isSberQrEnabled: boolean,
	isSberQrConnected: boolean,
	sbpConnectPath: string,
	sberQrConnectPath: string,
};

type NotificationLink = {
	/**
	 *  Must be 'connect_link' if already connected
	 *  or 'ui_helper' if user can change tariff to turn on notifications
	 */
	type: string,
	value: string
};

export {
	SmsService,
	TerminalSettingsProps,
	NotificationLink,
	TerminalPaysystem,
};
