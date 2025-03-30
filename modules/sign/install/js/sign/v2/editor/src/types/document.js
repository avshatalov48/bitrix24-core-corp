import type { DocumentInitiatedType } from 'sign.type';

export type MemberItem = {
	part: number,
	cid: number,
	name: string
};

export type PositionType = {
	top: number|string,
	left: number|string,
	width: number|string,
	widthPx: number|string,
	height: number|string,
	heightPx: number|string,
	page: number|string
};

export type BlockItem = {
	id?: number,
	code: string,
	part: number,
	title?: string,
	data?: any,
	position?: PositionType,
	style?: {[key: string]: string},
};

export type Config = {
	crmEntityFields: {
		[key: string]: string
	},
	crmRequisiteContactPresetId: number,
	crmRequisiteCompanyPresetId: number,
	crmOwnerTypeContact: number,
	crmOwnerTypeCompany: number,
	crmNumeratorUrl: string
};

export type DocumentOptions = {
	documentId: number,
	entityId: number,
	blankId: number,
	companyId: number,
	initiatorName: string,
	disableEdit: boolean,
	members: Array<MemberItem>,
	repositoryItems?: NodeList,
	documentLayout: HTMLElement,
	saveButton: HTMLElement,
	closeDemoContent?: HTMLElement,
	blocks?: Array<BlockItem>,
	config: Config,
	afterSaveCallback?: () => {},
	saveErrorCallback?: () => {},
	languages: {[key: string]: { NAME: string; IS_BETA: boolean; }},
	isTemplateMode: boolean,
	documentInitiatedByType?: DocumentInitiatedType,
};
