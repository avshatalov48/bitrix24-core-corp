import { ApiClient } from 'tasks.v2.lib.api-client';

class TaskInterfaceService
{
	#fetchPromise: Promise;

	async fetchData(): Promise<void>
	{
		// eslint-disable-next-line no-async-promise-executor
		this.#fetchPromise ??= new Promise(async (resolve, reject) => {
			try
			{
				const data = await (new ApiClient()).post('TaskInterface.get', {});

				resolve(data);
			}
			catch (error)
			{
				reject(error);
			}
		});

		return this.#fetchPromise;
	}
}

export const taskInterfaceService = new TaskInterfaceService();
