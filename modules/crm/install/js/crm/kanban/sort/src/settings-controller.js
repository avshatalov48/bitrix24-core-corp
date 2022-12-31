import { Settings } from "./settings";
import { Reflection } from "main.core";

let instance = null;

/**
 * @memberOf BX.CRM.Kanban.Sort
 */
export class SettingsController
{
	#grid: BX.CRM.Kanban.Grid;
	#settings: Settings;

	#sortChangePromise: ?Promise = null;

	static get Instance(): SettingsController
	{
		if ((window.top !== window) && Reflection.getClass('top.BX.CRM.Kanban.Sort.SettingsController'))
		{
			return window.top.BX.CRM.Kanban.Sort.SettingsController;
		}

		if (!instance)
		{
			throw new Error('SettingsController must be inited before use');
		}

		return instance;
	}

	static init(grid: BX.CRM.Kanban.Grid, settings: Settings): void
	{
		if (instance)
		{
			console.warn('Attempt to re-init SettingsController');

			return;
		}

		instance = new SettingsController(grid, settings);
	}

	constructor(grid: BX.CRM.Kanban.Grid, settings: Settings)
	{
		if (instance)
		{
			throw new Error('SettingsController is a singleton, another instance exists already. Use Instance to access it');
		}

		if (!(grid instanceof BX.CRM.Kanban.Grid))
		{
			console.error(grid);

			throw new Error('grid should be an instance of BX.CRM.Kanban.Grid');
		}

		this.#grid = grid;

		if (!(settings instanceof Settings))
		{
			console.error(settings);

			throw new Error('settings should be an instance of Settings');
		}

		this.#settings = settings;
	}

	setCurrentSortType(sortType: string): Promise<void>
	{
		if (!this.#sortChangePromise)
		{
			this.#sortChangePromise = this.#grid.setCurrentSortType(sortType).then(() => {
				//save new current sort type
				this.#settings = new Settings(
					this.#settings.getSupportedTypes(),
					sortType,
				);

				this.#grid.reload();
			}).catch((error) => {
				console.error(error);

				throw error;
			}).finally(() => {
				this.#sortChangePromise = null;
			});
		}

		return this.#sortChangePromise;
	}

	getCurrentSettings(): Settings
	{
		return this.#settings;
	}
}
