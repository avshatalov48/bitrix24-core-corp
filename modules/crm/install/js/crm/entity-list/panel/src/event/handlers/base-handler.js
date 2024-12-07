import type { ProgressBarRepository } from 'crm.autorun';
import type { SettingsCollection } from 'main.core.collections';

/**
 * @abstract
 */
export class BaseHandler
{
	/**
	 * @abstract
	 */
	static getEventName(): string
	{
		throw new Error('not implemented');
	}

	injectDependencies(progressBarRepo: ProgressBarRepository, extensionSettings: SettingsCollection): void
	{}

	/**
	 * @abstract
	 */
	execute(grid: BX.Main.grid, selectedIds: number[], forAll: boolean): void
	{
		throw new Error('not implemented');
	}
}
