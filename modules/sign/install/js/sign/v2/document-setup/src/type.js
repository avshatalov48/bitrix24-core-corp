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
	companyUid: string | null;
	createdById: number;
	blankId: number;
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
};
