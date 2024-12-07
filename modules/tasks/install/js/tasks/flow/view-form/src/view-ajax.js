import { ajax } from 'main.core';

export type Entity = {
	name: string,
	avatar: string,
	url: string,
};

export type FlowFormData = {
	flow: {
		name: string,
		description: string,
		efficiency: number,
		demo: boolean,
	},
	team: Entity[],
	teamCount: number,
	creator: Entity,
	owner: Entity,
	project: Entity,
};

export type SimilarFlow = {
	id: number,
	name: string,
	createTaskUri: string,
};

export class ViewAjax
{
	#flowId: number;
	#pageSize: number = 7;
	#pageNum: number = 1;
	#pages: { [key: number]: SimilarFlow[] } = {};

	constructor(flowId: number)
	{
		this.#flowId = flowId;
	}

	async getViewFormData(): Promise
	{
		const { data } = await ajax.runAction('tasks.flow.View.Flow.get', {
			data: {
				flowId: this.#flowId,
			},
		});

		return {
			flow: data.flow,
			team: data.team.map((member) => this.#convertUserToEntity(member)),
			teamCount: data.teamCount,
			owner: this.#convertUserToEntity(data.owner),
			creator: this.#convertUserToEntity(data.creator),
			project: data.project,
		};
	}

	#convertUserToEntity(user: any): Entity
	{
		return {
			name: user.name,
			avatar: user.avatar,
			url: user.pathToProfile,
		};
	}

	async getSimilarFlows(): Promise<SimilarFlow[]>
	{
		if (this.#pages[this.#pageNum])
		{
			return {
				page: [],
				similarFlows: Object.values(this.#pages).flat(),
			};
		}

		const { data: page } = await ajax.runAction('tasks.flow.View.SimilarFlow.list', {
			data: {
				flowId: this.#flowId,
			},
			navigation: {
				page: this.#pageNum,
				size: this.#pageSize,
			},
		});

		this.#pages[this.#pageNum] = page;

		if (page.length >= this.#pageSize)
		{
			this.#pageNum++;
		}

		return {
			page,
			similarFlows: Object.values(this.#pages).flat(),
		};
	}
}