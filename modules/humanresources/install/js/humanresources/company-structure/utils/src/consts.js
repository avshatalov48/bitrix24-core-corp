/**
 * Department type. The equivalent of Bitrix\HumanResources\Type\NodeEntityType
 */
export const EntityTypes = Object.freeze({
	department: 'DEPARTMENT',
	team: 'TEAM',
});

export const WizardApiEntityChangedDict: Record<string> = Object.freeze({
	department: 'department',
	employees: 'employees',
	bindChats: 'bindChats',
	settings: 'settings',
});

export const NodeSettingsTypes: Record<string> = {
	businessProcAuthority: 'BUSINESS_PROC_AUTHORITY',
	reportsAuthority: 'REPORTS_AUTHORITY',
};

export const ChatTypes: Record<string> = Object.freeze({
	channel: 'CHANNEL',
	chat: 'CHAT',
	collab: 'COLLAB',
});
