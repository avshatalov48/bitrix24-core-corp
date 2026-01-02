export const HelpDesk: { [key: string]: HelpDeskCode } = Object.freeze({
	Intersection: { code: '23712054', anchorCode: 'inte' },
	ResourceIntegrationSettings: { code: '23661822', anchorCode: 'calen' },
	ResourceType: { code: '23661822', anchorCode: 'reso' },
	ResourceSchedule: { code: '23661822', anchorCode: 'show' },
	ResourceWorkTime: { code: '23661822', anchorCode: 'sche' },
	ResourceSlotLength: { code: '23661822', anchorCode: 'dur' },
	ResourceNotificationInfo: { code: '23661926', anchorCode: 'mess' },
	ResourceNotificationConfirmation: { code: '23661926', anchorCode: 'conf' },
	ResourceNotificationReminder: { code: '23661926', anchorCode: 'remi' },
	ResourceNotificationLate: { code: '23661926', anchorCode: 'late' },
	ResourceNotificationFeedback: { code: '23661926', anchorCode: 'feed' },
	AhaSelectResources: { code: '23661972', anchorCode: 'filt' },
	AhaResourceWorkload: { code: '23661972', anchorCode: 'cont' },
	AhaResourceIntersection: { code: '23712054', anchorCode: 'inte' },
	AhaAddResource: { code: '23661822', anchorCode: '' },
	AhaMessageTemplate: { code: '23661926', anchorCode: '' },
	BookingActionsDeal: { code: '23661964', anchorCode: 'deal' },
	BookingActionsMessage: { code: '23661964', anchorCode: 'remind' },
	BookingActionsConfirmation: { code: '23661964', anchorCode: 'appr' },
	BookingActionsVisit: { code: '23661964', anchorCode: 'visit' },
	CrmFormsPopup: { code: '25366370', anchorCode: '' },
	WaitListDescription: { code: '24846212', anchorCode: '' },
});

type HelpDeskCode = {
	code: string,
	anchorCode: string,
}
