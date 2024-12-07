import { post } from '../request';
import type { Template } from './type';

export type { Template };

export class TemplateApi
{
	getList(): Promise<Template[]>
	{
		return post('sign.api_v1.b2e.document.template.list');
	}

	completeTemplate(templateUid: string): Promise<void>
	{
		return post('sign.api_v1.b2e.document.template.complete', { uid: templateUid });
	}

	send(templateUid: string): Promise<{ employeeMember: { id: number, uid: string } }>
	{
		return post('sign.api_v1.b2e.document.template.send', { uid: templateUid });
	}
}
