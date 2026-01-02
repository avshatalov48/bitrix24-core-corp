import { post } from '../request';
import { TemplateFolder } from './type';

export type { TemplateFolder };

export class TemplateFolderApi
{
	create(title: string): Promise<TemplateFolder>
	{
		return post('sign.api_v1.b2e.document.templateFolder.create', { title });
	}

	rename(folderId: number, newTitle: string): Promise<TemplateFolder>
	{
		return post('sign.api_v1.b2e.document.templateFolder.rename', { folderId, newTitle });
	}

	delete(folderId: number): Promise<any>
	{
		return post('sign.api_v1.b2e.document.templateFolder.delete', { folderId });
	}

	changeVisibility(folderId: number, visibility: string): Promise<any>
	{
		return post('sign.api_v1.b2e.document.templateFolder.changeVisibility', { folderId, visibility });
	}

	getListByDepthLevel(depthLevel: number): Promise<{ id: number; title: string }[]>
	{
		return post('sign.api_v1.b2e.document.templateFolder.listByDepthLevel', { depthLevel });
	}
}
