export type DocumentSettings = {
	registrationNumber: string,
	creationDate: Date,
	signingDate: Date,
};

export type TemplateDocumentUid = string;

export type DocumentSettingsByTemplateDocumentUid = Record<TemplateDocumentUid, DocumentSettings>;
