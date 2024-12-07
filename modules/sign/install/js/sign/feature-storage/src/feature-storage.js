import { Extension } from 'main.core';

const settings = Extension.getSettings('sign.feature-storage');

export class FeatureStorage
{
	static isSendDocumentByEmployeeEnabled(): boolean
	{
		return settings.get('isSendDocumentByEmployeeEnabled', false);
	}
}
