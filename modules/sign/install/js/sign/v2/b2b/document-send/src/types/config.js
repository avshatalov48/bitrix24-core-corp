import type { Analytics } from 'sign.v2.analytics';

export type DocumentSendConfig = {
	region: string,
	languages: {[key: string]: { NAME: string; IS_BETA: boolean; }},
	documentMode: 'document' | 'template',
	analytics?: Analytics,
	needSkipEditorStep: boolean,
};
export type DocumentData = {
	uid: string;
	title: string;
	blocks: Array<{ party: number; }>;
};
