import { Runtime } from 'main.core';
import { Store } from 'ui.vue3.vuex';
import { Model } from 'tasks.v2.const';
import { Core } from 'tasks.v2.core';
import { ApiClient } from 'tasks.v2.lib.api-client';
import type { CheckListModel } from 'tasks.v2.model.check-list';

import { mapDtoToModel, prepareCheckLists } from './mappers';

class CheckListService
{
	#getPromises = {};
	#updateFields: { [taskId: number]: { [checkListId: number]: Partial<CheckListModel> } } = {};
	#updatePromises: { [taskId: number]: { [checkListId: number]: Resolvable } } = {};
	#updateServerCheckListDebounced: { [taskId: number]: { [checkListId: number]: Function } } = {};

	async load(taskId: number): Promise<void>
	{
		// eslint-disable-next-line no-async-promise-executor
		this.#getPromises[taskId] ??= new Promise(async (resolve, reject) => {
			try
			{
				const data = await (new ApiClient()).post('CheckList.get', {
					task: { id: taskId },
				});

				const checkLists = data.map((it) => mapDtoToModel(it));

				await Promise.all([
					this.$store.dispatch(`${Model.CheckList}/upsertMany`, checkLists),
					this.$store.dispatch(`${Model.Tasks}/update`, {
						id: taskId,
						fields: {
							containsChecklist: checkLists.length > 0,
							checklist: checkLists.map(({ id }) => id),
						},
					}),
				]);

				resolve();
			}
			catch (error)
			{
				reject(error);
			}
		});

		return this.#getPromises[taskId];
	}

	async save(taskId: number, checklists: CheckListModel[]): Promise<void>
	{
		// eslint-disable-next-line no-async-promise-executor
		return new Promise(async (resolve, reject) => {
			try
			{
				const savedList = await (new ApiClient()).post('CheckList.save', {
					task: {
						id: taskId,
						checklist: prepareCheckLists(checklists),
					},
				});

				const checkLists = savedList.map((it) => mapDtoToModel(it));

				await Promise.all([
					this.$store.dispatch(`${Model.Interface}/setDisableCheckListAnimations`, true),
					this.$store.dispatch(`${Model.CheckList}/upsertMany`, checkLists),
					this.$store.dispatch(`${Model.Tasks}/update`, {
						id: taskId,
						fields: {
							containsChecklist: checkLists.length > 0,
							checklist: checkLists.map((item) => item.id),
						},
					}),
				]);

				void this.$store.dispatch(`${Model.Interface}/setDisableCheckListAnimations`, false);

				resolve();
			}
			catch (error)
			{
				reject(error);
			}
		});
	}

	async collapse(taskId: number, checkListId: number): Promise<void>
	{
		await (new ApiClient()).post('CheckList.collapse', {
			taskId,
			checkListId,
		});

		void this.$store.dispatch(`${Model.CheckList}/update`, {
			id: checkListId,
			fields: {
				collapsed: true,
				expanded: false,
			},
		});
	}

	async expand(taskId: number, checkListId: number): Promise<void>
	{
		await (new ApiClient()).post('CheckList.expand', {
			taskId,
			checkListId,
		});

		void this.$store.dispatch(`${Model.CheckList}/update`, {
			id: checkListId,
			fields: {
				collapsed: false,
				expanded: true,
			},
		});
	}

	async complete(taskId: number, checkListId: number): Promise<void>
	{
		await this.#updateCheckListDebounced(taskId, checkListId, { isComplete: true });
	}

	async renew(taskId: number, checkListId: number): Promise<void>
	{
		await this.#updateCheckListDebounced(taskId, checkListId, { isComplete: false });
	}

	async #updateCheckListDebounced(taskId: number, checkListId: number, fields: Partial<CheckListModel>): Promise<void>
	{
		this.#updateFields[taskId] ??= {};
		this.#updateFields[taskId][checkListId] = { ...this.#updateFields[taskId][checkListId], ...fields };

		this.#updatePromises[taskId] ??= {};
		this.#updatePromises[taskId][checkListId] ??= new Resolvable();

		this.#updateServerCheckListDebounced[taskId] ??= {};
		this.#updateServerCheckListDebounced[taskId][checkListId] ??= Runtime.debounce(this.#updateCheckList, 300, this);

		this.#updateServerCheckListDebounced[taskId][checkListId](taskId, checkListId);
		await this.#updatePromises[taskId][checkListId];
	}

	async #updateCheckList(taskId: number, checkListId: number): Promise<void>
	{
		const fields = this.#updateFields[taskId][checkListId];
		delete this.#updateFields[taskId][checkListId];

		const promise = this.#updatePromises[taskId][checkListId];
		delete this.#updatePromises[taskId][checkListId];

		try
		{
			// Update store immediately for optimistic UI
			await this.$store.dispatch(`${Model.CheckList}/update`, {
				id: checkListId,
				fields,
			});

			// Get updated checklists and save to server
			const task = await this.$store.getters[`${Model.Tasks}/getById`](taskId);
			const checklists = (await task?.checklist)
				? this.$store.getters[`${Model.CheckList}/getByIds`](task.checklist)
				: [];

			await new ApiClient().post('CheckList.save', {
				task: {
					id: taskId,
					checklist: prepareCheckLists(checklists),
				},
			});

			promise.resolve();
		}
		catch (error)
		{
			console.error('CheckListService: update error', error);
			promise.resolve();
		}
	}

	async delete(taskId: number, checkListId: number): Promise<void>
	{
		const task = await this.$store.getters[`${Model.Tasks}/getById`](taskId);
		const checklists = (await task?.checklist)
			? this.$store.getters[`${Model.CheckList}/getByIds`](task.checklist)
			: [];
		await this.save(taskId, checklists);
	}

	get $store(): Store
	{
		return Core.getStore();
	}
}

export const checkListService = new CheckListService();

function Resolvable(): Promise
{
	let resolve;
	const promise = new Promise((res) => {
		resolve = res;
	});

	promise.resolve = resolve;

	return promise;
}
