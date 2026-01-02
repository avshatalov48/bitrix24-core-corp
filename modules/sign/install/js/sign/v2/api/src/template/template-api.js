import type { ProviderCodeType } from 'sign.type';
import { LoadedDocumentData } from 'sign.v2.api';
import type { CardItem } from 'sign.v2.b2e.user-party';
import { post } from '../request';
import type { Template, TemplateField, FieldValue, TemplateSelectedEntity, TemplateCreatedDocument } from './type';

export type { Template };

export class TemplateApi
{
	getList(): Promise<Template[]>
	{
		return post('sign.api_v1.b2e.document.template.list');
	}

	completeTemplate(templateUid: string, folderId: number): Promise<{ template: { id: number } }>
	{
		return post('sign.api_v1.b2e.document.template.complete', { uid: templateUid, folderId });
	}

	send(templateUid: string, fields: FieldValue[], isOnboarding: boolean = false): Promise<{
		assigneeMember: { id: number, uid: string },
		employeeMember: { id: number, uid: string },
		document: { id: number, providerCode: ProviderCodeType },
	}>
	{
		return post('sign.api_v1.b2e.document.template.send', { uid: templateUid, fields, isOnboarding });
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

	changeVisibility(templateId: number, visibility: string): Promise<any>
	{
		return post('sign.api_v1.b2e.document.template.changeVisibility', { templateId, visibility });
	}

	copy(templateId: number, folderId: number): Promise<{ template: { id: number } }>
	{
		return post('sign.api_v1.b2e.document.template.copy', { templateId, folderId });
	}

	moveToFolder(entities: Object, folderId: number): Promise<any>
	{
		return post('sign.api_v1.b2e.document.template.moveToFolder', { entities, folderId });
	}

	delete(templateId: number): Promise<void>
	{
		return post('sign.api_v1.b2e.document.template.delete', { templateId });
	}

	deleteEntities(entities: TemplateSelectedEntity): Promise<any>
	{
		return post('sign.api_v1.b2e.document.template.deleteEntities', { entities });
	}

	registerDocuments(templateIds: number[], excludeRejected: boolean = true): Promise<{items: TemplateCreatedDocument[]}>
	{
		return post('sign.api_v1.b2e.document.template.registerDocuments', { templateIds, excludeRejected });
	}

	setupSigners(documentIds: number[], signers: CardItem[], excludeRejected: boolean): Promise<{
		shouldCheckDepartmentsSync: boolean,
		documents: LoadedDocumentData[],
	}>
	{
		return post('sign.api_v1.b2e.document.template.setupSigners', { documentIds, signers, excludeRejected });
	}

	installOnboardingTemplate(): Promise<Object>
	{
		return post('sign.api_v1.b2e.document.template.installOnboardingTemplate', {});
	}
}
