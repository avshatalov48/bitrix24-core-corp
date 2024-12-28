export type LoadedDocumentData = {
	blankId: number;
	entityId: number;
	entityType: string;
	entityTypeId: number;
	id: number;
	initiator: string;
	langId: string;
	parties: number;
	resultFileId: number;
	scenario: string;
	status: string;
	title: string;
	uid: string;
	version: number;
	providerCode: ProviderCodeType;
};
export type Communication = {
	ID: number;
	TYPE: 'EMAIL' | 'PHONE';
	VALUE: string;
	VALUE_TYPE: string;
};
export type BlockData = {
	text?: string;
	field?: string;
	hasFields?: boolean;
	presetId?: number;
	__view?: { crmNumeratorUrl?: string; base64?: string; };
	fileId?: number;
};
type BlockSettings = {
	positon: {
		top: string;
		left: string;
		width: string;
		widthPx: number;
		height: string;
		heightPx: number;
	},
	style: { [$Keys<CSSStyleDeclaration>]: string; }
};
export type LoadedBlock = {
	code: string;
	data: BlockData;
	id: number;
	party: number;
	type: string;
	position: BlockSettings['position'];
	style: BlockSettings['style'];
};

export type Role = 'assignee' | 'signer' | 'editor' | 'reviewer';
export const MemberRole: $ReadOnly<{ [key: Role]: Role }> = Object.freeze({
	assignee: 'assignee',
	signer: 'signer',
	editor: 'editor',
	reviewer: 'reviewer',
});

export type SetupMember = {
	entityType: string;
	entityId: number;
	party: number;
	role?: Role;
};
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

export type Company = {
	id: ?number,
	title: ?string,
	rqInn: ?number,
	registerUrl: ?string,
	providers: ?Array<Provider>,
};

export type Provider = {
	code: ProviderCodeType,
	uid: ?string,
	timestamp: ?number,
	virtual: boolean,
	autoRegister: boolean,
	name: ?string;
	description: ?string,
	iconUrl: ?string,
	expires: ?number,
	externalProviderId: ?string,
};

export type ProviderCodeType = 'goskey' | 'ses-com' | 'ses-ru' | 'external';

export type B2eCompanyList = { companies: Array<Company>, showTaxId: boolean };

export type CountMember = {
	entityType: string;
	entityId: number;
};
