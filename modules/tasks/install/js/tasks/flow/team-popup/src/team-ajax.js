import { ajax, AjaxError } from 'main.core';
import type { MemberData } from './team-member';

export class TeamAjax
{
	#flowId: number;

	#pageSize: number = 10;
	#pageNum: number = 1;
	#pages: { [key: number]: MemberData[] } = {};

	constructor(flowId: number)
	{
		this.#flowId = flowId;
	}

	async get(): Promise<{page: MemberData[], members: MemberData[]}>
	{
		if (this.#pages[this.#pageNum])
		{
			return {
				page: [],
				members: Object.values(this.#pages).flat(),
			};
		}

		const { data, error } = await ajax.runAction('tasks.flow.Team.list', {
			data: {
				flowData: { id: this.#flowId },
			},
			navigation: {
				page: this.#pageNum,
				size: this.#pageSize,
			},
		}).catch((response) => ({
			data: [],
			error: response.errors[0],
		}));

		if (error)
		{
			this.#consoleError('getList', error);
		}

		const members: MemberData[] = data;

		this.#pages[this.#pageNum] = members;

		if (members.length >= this.#pageSize)
		{
			this.#pageNum++;
		}

		return {
			page: members,
			members: Object.values(this.#pages).flat(),
		};
	}

	#consoleError(action: string, error: AjaxError)
	{
		// eslint-disable-next-line no-console
		console.error(`TeamPopup: ${action} error`, error);
	}
}
