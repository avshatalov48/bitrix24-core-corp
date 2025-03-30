import type { ProviderCodeType } from 'sign.type';
import { post } from '../request';
import type { Template, TemplateField, FieldValue } from './type';

export type { Template };

export class TemplateApi
{
	getList(): Promise<Template[]>
	{
		return post('sign.api_v1.b2e.document.template.list');
	}

	completeTemplate(templateUid: string): Promise<{ template: { id: number } }>
	{
		return post('sign.api_v1.b2e.document.template.complete', { uid: templateUid });
	}

	send(templateUid: string, fields: FieldValue[]): Promise<{
		employeeMember: { id: number, uid: string },
		document: { id: number, providerCode: ProviderCodeType },
	}>
	{
		return post('sign.api_v1.b2e.document.template.send', { uid: templateUid, fields });
	}

	getFields(templateUid: string): Promise<{ fields: TemplateField[] }>
	{
		return post('sign.api_v1.b2e.document.template.getFields', { uid: templateUid });
	}

	exportBlank(templateId: number): Promise<{json: string, filename: string}>
	{
		return post('sign.api_v1.b2e.document.template.export', { templateId }, true);
	}

	importBlank(serializedTemplate: string): Promise<void>
	{
		return post('sign.api_v1.b2e.document.template.import', { serializedTemplate }, true);
	}
}
