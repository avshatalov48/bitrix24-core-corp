import { Extension } from 'main.core';

export class Languages
{
	getLanguages()
	{
		return Extension.getSettings('intranet.languages').languages;
	}
}