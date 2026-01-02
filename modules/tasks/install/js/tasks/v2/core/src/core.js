import { Extension } from 'main.core';
import { Builder, BuilderModel, Store } from 'ui.vue3.vuex';

import { CheckList } from 'tasks.v2.model.check-list';
import { CrmItems } from 'tasks.v2.model.crm-items';
import { Epics } from 'tasks.v2.model.epics';
import { Flows } from 'tasks.v2.model.flows';
import { Groups } from 'tasks.v2.model.groups';
import { Interface } from 'tasks.v2.model.interface';
import { Stages } from 'tasks.v2.model.stages';
import { Tasks } from 'tasks.v2.model.tasks';
import { Users } from 'tasks.v2.model.users';

import { PullManager } from 'tasks.v2.provider.pull.pull-manager';

import type { CoreParams } from './types';
export type { CoreParams };

class CoreApplication
{
	#params: CoreParams = Extension.getSettings('tasks.v2.core');

	#store: Store;
	#builder: Builder;
	#initPromise: Promise;
	#pullManager: ?PullManager = null;

	getParams(): CoreParams
	{
		return this.#params;
	}

	getStore(): Store
	{
		return this.#store;
	}

	init(): Promise<void>
	{
		// eslint-disable-next-line no-async-promise-executor
		this.#initPromise ??= new Promise(async (resolve) => {
			this.#store = await this.#initStore();
			this.#initPull();
			resolve();
		});

		return this.#initPromise;
	}

	async addDynamicModel(vuexBuilderModel: BuilderModel): Promise<void>
	{
		if (!(this.#builder instanceof Builder))
		{
			throw new TypeError('Builder has not been init');
		}

		if (this.#store.hasModule(vuexBuilderModel.getName()))
		{
			return;
		}

		await this.#builder.addDynamicModel(vuexBuilderModel);
	}

	removeDynamicModel(vuexModelName: string): void
	{
		if (this.#builder instanceof Builder && this.#store.hasModule(vuexModelName))
		{
			this.#builder.removeDynamicModel(vuexModelName);
		}
	}

	async #initStore(): Promise<Store>
	{
		this.#builder = Builder.init();

		this.#builder
			.addModel(CheckList.create())
			.addModel(CrmItems.create())
			.addModel(Epics.create())
			.addModel(Flows.create())
			.addModel(Groups.create())
			.addModel(Interface.createWithVariables(this.#params))
			.addModel(Stages.create())
			.addModel(Tasks.create())
			.addModel(Users.create())
		;

		const builderResult = await this.#builder.build();

		return builderResult.store;
	}

	#initPull(): void
	{
		this.#pullManager = new PullManager({
			currentUserId: this.#params.currentUserId,
		});

		this.#pullManager.initQueueManager();
	}
}

export const Core = new CoreApplication();
