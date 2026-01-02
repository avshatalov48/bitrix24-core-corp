import { Extension, Type } from 'main.core';

const settings = Extension.getSettings('disk.uploader.user-field-widget');

class UserFieldSettings
{
	canCreateDocuments(): boolean
	{
		return settings.get('canCreateDocuments', false);
	}

	getDocumentServices(): { [documentService: string]: { name: string, code: string } }
	{
		const documentHandlers = settings.get('documentHandlers', {});
		if (Type.isPlainObject(documentHandlers))
		{
			return documentHandlers;
		}

		return {};
	}

	getImportServices(): { [documentService: string]: { name: string, code: string } }
	{
		const importHandlers = settings.get('importHandlers', {});
		if (Type.isPlainObject(importHandlers))
		{
			return importHandlers;
		}

		return {};
	}

	canUseImportService(): boolean
	{
		return settings.get('canUseImport', true);
	}

	getImportFeatureId(): string
	{
		return settings.get('importFeatureId', '');
	}

	isBoardsEnabled(): boolean
	{
		return settings.get('isBoardsEnabled', false);
	}
}

export const userFieldSettings = new UserFieldSettings();
