export type DocumentInitiatedType = 'employee' | 'company';
export const DocumentInitiated: Readonly<Record<string, DocumentInitiatedType>> = Object.freeze({
	employee: 'employee',
	company: 'company',
});

export type DocumentModeType = 'document' | 'template';
export const DocumentMode: Readonly<Record<string, DocumentModeType>> = Object.freeze({
	document: 'document',
	template: 'template',
});

export type MemberRoleType = 'assignee' | 'signer' | 'editor' | 'reviewer';
export const MemberRole: $ReadOnly<{ [key: MemberRoleType]: MemberRoleType }> = Object.freeze({
	assignee: 'assignee',
	signer: 'signer',
	editor: 'editor',
	reviewer: 'reviewer',
});

export type MemberStatusType = 'done' | 'wait' | 'ready' | 'refused' | 'stopped' | 'stoppable_ready' | 'processing';
export const MemberStatus: Readonly<Record<string, MemberStatusType>> = Object.freeze({
	done: 'done',
	wait: 'wait',
	ready: 'ready',
	refused: 'refused',
	stopped: 'stopped',
	stoppableReady: 'stoppable_ready',
	processing: 'processing',
});

export type ProviderCodeType = 'goskey' | 'ses-com' | 'ses-ru' | 'external';

export const ProviderCode: Readonly<Record<string, ProviderCodeType>> = Object.freeze({
	goskey: 'goskey',
	sesCom: 'ses-com',
	sesRu: 'ses-ru',
	external: 'external',
});

export type ReminderType = 'none' | 'oncePerDay' | 'twicePerDay' | 'threeTimesPerDay';
export const Reminder: $ReadOnly<{ [key: ReminderType]: ReminderType }> = Object.freeze({
	none: 'none',
	oncePerDay: 'oncePerDay',
	twicePerDay: 'twicePerDay',
	threeTimesPerDay: 'threeTimesPerDay',
});