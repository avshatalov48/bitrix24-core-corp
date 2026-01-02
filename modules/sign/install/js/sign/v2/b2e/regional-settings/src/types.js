export type RegionalSettingsOptions = {
	templateMode: boolean,
	regionDocumentTypes: Array<{ code: string, description: string }>,
};

export type SourceType = 'manual' | 'hcmlink';

export type DateSettings = {
	sourceType: SourceType,
	value: Date,
	hcmLinkSettingId: number | null,
};

export type ExternalIdSettings = {
	sourceType: SourceType,
	value: string,
	hcmLinkSettingId: number | null,
};

export type DocumentSettings = {
	date: DateSettings,
	externalId: ExternalIdSettings,
	hcmLinkDocumentTypeSettingId: number | null,
	documentType: string | null,
};

export type RegionalSettingsAppDataType = {
};
