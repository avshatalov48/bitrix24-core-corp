import { Reflection } from 'main.core';

let instance = null;

/**
 * @memberOf BX.Crm.Autorun
 */
export class ProcessRegistry
{
	#gridIdToProcessCountMap: Map<string, number> = new Map();

	static get Instance(): ProcessRegistry
	{
		if ((window.top !== window) && Reflection.getClass('top.BX.Crm.Autorun.ProcessRegistry'))
		{
			return window.top.BX.Crm.Autorun.ProcessRegistry.Instance;
		}

		if (instance === null)
		{
			instance = new ProcessRegistry();
		}

		return instance;
	}

	isProcessRunning(gridId: string): boolean
	{
		return this.getProcessCount(gridId) > 0;
	}

	getProcessCount(gridId: string): number
	{
		return this.#gridIdToProcessCountMap.get(gridId) ?? 0;
	}

	registerProcessRun(gridId: string): void
	{
		const currentCount = this.#gridIdToProcessCountMap.get(gridId) ?? 0;

		this.#gridIdToProcessCountMap.set(gridId, currentCount + 1);
	}

	registerProcessStop(gridId: string): void
	{
		const currentCount = this.#gridIdToProcessCountMap.get(gridId) ?? 0;

		let newCount = currentCount - 1;
		if (newCount < 0)
		{
			newCount = 0;
		}

		this.#gridIdToProcessCountMap.set(gridId, newCount);
	}

	static isProcessRunning(gridId: string): boolean
	{
		return this.Instance.isProcessRunning(gridId);
	}
}
