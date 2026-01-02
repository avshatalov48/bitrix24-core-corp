export type ActionsPopupAction = $ReadOnly<{
	client: 'client',
	confirmation: 'confirmation',
	deal: 'deal',
	document: 'document',
	extraResourcesInfo: 'extraResourcesInfo',
	fullForm: 'fullForm',
	info: 'info',
	message: 'message',
	visit: 'visit',
	overbooking: 'overbooking',
	remove: 'remove',
	waitList: 'waitList',
}>;

export type ActionsPopupOptions = {
	[$Values<ActionsPopupAction>]: ActionOptions,
}

export type ActionOptions = {
	hidden?: boolean,
	disabled?: boolean,
}
