import { post } from './request';
import type { Template } from './type';

export type { Template };

export class SignersListApi
{
	deleteSignersList(listId: number): Promise<void>
	{
		return post('sign.api_v1.b2e.signers.deleteList', { listId });
	}

	copySignersList(listId: number): Promise<void>
	{
		return post('sign.api_v1.b2e.signers.copyList', { listId });
	}

	deleteSignersFromList(listId: number, userIds: number[]): Promise<void>
	{
		return post('sign.api_v1.b2e.signers.deleteSignersFromList', { listId, userIds });
	}

	createList(title: string): Promise<void>
	{
		return post('sign.api_v1.b2e.signers.createList', { title });
	}

	renameList(listId: number, title: string): Promise<void>
	{
		return post('sign.api_v1.b2e.signers.renameList', { listId, title });
	}

	addSignersToList(listId: number, members: Array<Object>, excludeRejected: boolean = true): Promise<void>
	{
		return post('sign.api_v1.b2e.signers.addSignersToList', { listId, members, excludeRejected });
	}
}