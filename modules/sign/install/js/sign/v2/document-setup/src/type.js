import type { DocumentInitiatedType } from 'sign.type';

type Block = {
	code: string;
	data: { text: string; };
	id: number;
	party: number;
	type: string;
	positon: {
		top: string;
		left: string;
		width: string;
		widthPx: number;
		height: string;
		heightPx: number;
		realDocumentWidthPx: number;
	};
	style: { [$Keys<CSSStyleDeclaration>]: string; };
};

export type DocumentDetails = {
	blocks: Array<Block>;
	companyEntityId: number | null;
	companyUid: string | null;
	createdById: number;
	blankId: number;
	previewUrl: string | null;
	entityId: number;
	entityType: string;
	entityTypeId: number;
	id: number;
	initiator: string;
	isTemplate: boolean;
	langId: string;
	parties: number;
	representativeId: number | null;
	resultFileId: number;
	scenario: string;
	status: string;
	title: string;
	uid: string;
	hcmLinkCompanyId: number | null,
	initiatedByType: DocumentInitiatedType;
	groupId: number | null;
	providerCode: string | null,
	dateSignUntil: Date,
	externalDateCreateSourceType: string | null,
	externalDateCreate: string | null,
	hcmLinkDateSettingId: number | null,
	externalIdSourceType: string | null,
	hcmLinkExternalIdSettingId: number | null,
	hcmLinkDocumentTypeSettingId: number | null,
	regionDocumentType: string | null,
};

export type ActionMode = 'create' | 'edit';
