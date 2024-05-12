import { Loc } from 'main.core';
import { EventEmitter } from 'main.core.events';
import { DataSource } from './data-source';

export class Searcher
{
	static STATE_READY = 'ready';
	static STATE_WAIT = 'wait';
	static STATE_NOT_FOUND = 'not_found';

	#query: string;
	#minSymbol: number;
	#dataSource: DataSource;
	#result: Array;
	#state: string; // ready | wait;

	constructor(dataSource: DataSource, minSymbol: number = 3)
	{
		if (!(dataSource instanceof DataSource))
		{
			throw new Error('Unexpected type, expect: DataSource');
		}
		this.#dataSource = dataSource;
		this.#minSymbol = minSymbol;
		this.#result = [];
		this.#state = 'ready';
	}

	find(query: string)
	{
		if (query.length < this.#minSymbol)
		{
			return;
		}
		this.#query = query;
		this.changeState(Searcher.STATE_WAIT);
		this.#dataSource
			.fetch(this.#query)
			.then(
				this.resolve.bind(this),
				this.reject.bind(this),
			);
	}

	changeState(state: string)
	{
		if (this.#state === state)
		{
			return;
		}

		this.#state = state;
		EventEmitter.emit(
			EventEmitter.GLOBAL_TARGET,
			'BX.Intranet.Settings:searchChangeState', {
				state: this.#state,
			},
		);

	}

	getMinSymbol(): number
	{
		return this.#minSymbol;
	}

	resolve(response)
	{
		this.#result = response.data;

		this.changeState(this.#result.length > 0 ? Searcher.STATE_READY : Searcher.STATE_NOT_FOUND);
	}

	reject(response)
	{
		this.#result = [];

		this.changeState(Searcher.STATE_READY);
	}

	getResult(): Array
	{
		return this.#result;
	}

	getOthers()
	{
		return [
			{
				link: '/stream/',
				title: Loc.getMessage('INTRANET_SETTINGS_TITLE_TOOL_TEAMWORK'),
			},
			{
				link: '/tasks/config/permissions/',
				title: Loc.getMessage('INTRANET_SETTINGS_TITLE_TOOL_TASKS'),
			},
			{
				link: '/crm/configs/',
				title: Loc.getMessage('INTRANET_SETTINGS_TITLE_TOOL_CRM'),
			},
			{
				link: '/shop/documents/?inventoryManagementSource=inventory',
				title: Loc.getMessage('INTRANET_SETTINGS_TITLE_TOOL_WAREHOUSE'),
			},
			{
				link: '/sites/',
				title: Loc.getMessage('INTRANET_SETTINGS_TITLE_TOOL_SITES'),
			},
			{
				link: '/company/vis_structure.php',
				title: Loc.getMessage('INTRANET_SETTINGS_TITLE_TOOL_COMPANY'),
			},
		];
	}
}