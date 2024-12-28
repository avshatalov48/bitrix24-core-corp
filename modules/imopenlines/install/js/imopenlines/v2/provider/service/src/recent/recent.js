import { Core } from 'im.v2.application.core';
import { runAction } from 'im.v2.lib.rest';
import { RestMethod, StatusGroup } from 'imopenlines.v2.const';

import type { RecentRestResult } from '../types/rest';

export class RecentService
{
	firstPageIsLoaded: boolean = false;
	#itemsPerPage: number = 50;
	#isLoading: boolean = false;
	#hasMoreItemsToLoad: boolean = true;
	#sortPointer: number | Date = 0;
	#lastStatusGroup: string = '';

	async loadFirstPage(): Promise
	{
		this.#isLoading = true;

		const result = await this.#requestItems({ firstPage: true });

		this.firstPageIsLoaded = true;

		return result;
	}

	loadNextPage(): Promise
	{
		if (this.#isLoading || !this.#hasMoreItemsToLoad)
		{
			return Promise.resolve();
		}

		this.#isLoading = true;

		return this.#requestItems();
	}

	hasMoreItemsToLoad(): boolean
	{
		return this.#hasMoreItemsToLoad;
	}

	async #requestItems({ firstPage = false } = {}): Promise
	{
		const queryParams = {
			data: {
				cursor: {
					sortPointer: firstPage ? null : this.#sortPointer,
					statusGroup: firstPage ? null : this.#lastStatusGroup,
				},
				limit: this.#itemsPerPage,
			},
		};

		const result = await runAction(RestMethod.linesV2RecentList, queryParams)
			.catch((error) => {
				console.error('Imol.OpenlinesList: page request error', error);
			});

		const { messages, recentItems, sessions, hasNextPage } = result;

		if (!hasNextPage)
		{
			this.#hasMoreItemsToLoad = false;
		}

		this.#isLoading = false;

		if (recentItems.length === 0)
		{
			return Promise.resolve();
		}

		this.#lastStatusGroup = this.#getLastStatusGroup(sessions, recentItems);

		if (this.#lastStatusGroup === StatusGroup.answered)
		{
			this.#sortPointer = this.#getLastDate(messages, recentItems);
		}
		else
		{
			this.#sortPointer = recentItems[recentItems.length - 1].sessionId;
		}

		return this.#updateModel(result);
	}

	#updateModel(restResult: RecentRestResult): Promise
	{
		const { users, chats, messages, files, recentItems, sessions } = restResult;

		const usersPromise = Core.getStore().dispatch('users/set', users);
		const dialoguesPromise = Core.getStore().dispatch('chats/set', chats);
		const messagesPromise = Core.getStore().dispatch('messages/store', messages);
		const filesPromise = Core.getStore().dispatch('files/set', files);
		const openLinesPromise = Core.getStore().dispatch('recentOpenLines/set', recentItems);
		const sessionsPromise = Core.getStore().dispatch('sessions/set', sessions);

		return Promise.all([
			usersPromise,
			dialoguesPromise,
			messagesPromise,
			filesPromise,
			openLinesPromise,
			sessionsPromise,
		]);
	}

	#getLastDate(messages: Array, recentItems: Array): string
	{
		const lastItemMessageId = recentItems[recentItems.length - 1].messageId;

		return messages.find((message) => message.id === lastItemMessageId).date;
	}

	#getLastStatusGroup(sessions: Array, recentItems: Array): string
	{
		const lastItemSessionId = recentItems[recentItems.length - 1].sessionId;

		return sessions.find((session) => session.id === lastItemSessionId).status;
	}
}
