import { ajax } from 'main.core';
import { DataSource } from './data-source';

export class ServerDataSource extends DataSource
{

	constructor()
	{
		super();
	}

	fetch(query: string): Promise
	{
		return ajax.runComponentAction(
			'bitrix:intranet.settings',
			'search',
			{
				mode: 'class',
				data: { query: query }
			}
		);
	}
}