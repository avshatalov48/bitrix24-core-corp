//@flow

type SmsService = {
	name: string,
	id: string,
	isActive?: boolean,
};

type TerminalSettingsProps = {
	isSmsSendingEnabled: boolean,
	isNotificationsEnabled: boolean,
	activeSmsServices: SmsService[],
	paymentSlipLinkScheme: string,
	connectNotificationsLink: NotificationLink,
}

type NotificationLink = {
	/**
	 *  Must be 'connect_link' if already connected
	 *  or 'ui_helper' if user can change tariff to turn on notifications
	 */
	type: string,
	value: string
}

export {
	SmsService,
	TerminalSettingsProps,
	NotificationLink,
};