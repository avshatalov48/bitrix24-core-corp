import { DEFAULT_PROVIDER } from './messages';
import { ajax } from 'main.core';

export interface Templates
{
	FILLED_PLACEHOLDERS: any[];
	HEADER: string;
	ID: string; // json encoded string with template settings
	ORIGINAL_ID: number;
	PLACEHOLDERS: {[string]: string[]};
	PREVIEW: string;
	TITLE: string;
}

export interface FromListItem {
	id: string;
	name: string;
	channelPhone: string;
	isDefault: number;
}

export interface ProviderConfig {
	id: string;
	name: string;
	canUse: boolean;
	fromList: FromListItem[]
}

export async function fetchTemplates(entityTypeId: number, entityCategoryId: ?number): Promise<Templates[]>
{
	const resp = await BX.ajax.runAction(
		'crm.activity.sms.getTemplates',
		{
			data: {
				senderId: DEFAULT_PROVIDER,
				context: {
					module: 'crm',
					entityTypeId,
					entityCategoryId,
					entityId: null,
				},
			},
		},
	);

	return resp.data.templates;
}

export async function fetchSmsProvidersConfig(): Promise<ProviderConfig[]>
{
	const resp = await ajax.runAction(
		'crm.api.messagesender.providersConfig',
		{ data: { providerName: DEFAULT_PROVIDER } },
	);

	return resp.data || [];
}
