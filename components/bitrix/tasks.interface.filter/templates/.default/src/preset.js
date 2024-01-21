import { Guide } from 'ui.tour';
import { ajax, Loc } from 'main.core';

type Options = {
	filterId: number,
};

export class Preset
{
	static DELAY = 1000;

	#filterId: number;
	#filter: BX.Main.Filter;

	constructor(options: Options)
	{
		this.#filterId = options.filterId;
		this.#init();
	}

	payAttention(): void
	{
		setTimeout(() => {
			this.guide.showNextStep();
			this.#markViewed();
		}, Preset.DELAY);
	}

	#init(): void
	{
		this
			.#setFilter()
			.#setGuide();
	}

	#setFilter(): Preset
	{
		this.#filter = BX.Main.filterManager.getById(this.#filterId);
		return this;
	}

	#setGuide(): Preset
	{
		this.guide = new Guide({
			simpleMode: true,
			onEvents: true,
			steps: [
				{
					target: this.#filter.getPopupBindElement(),
					title: Loc.getMessage('TASKS_INTERFACE_FILTER_PRESETS_MOVED_TITLE'),
					text: Loc.getMessage('TASKS_INTERFACE_FILTER_PRESETS_MOVED_TEXT'),
					position: 'bottom',
					condition: {
						top: true,
						bottom: false,
						color: 'primary',
					},
				},
			],
		});

		this.guide.getPopup().setWidth(420);

		return this;
	}

	#markViewed(): void
	{
		ajax.runComponentAction('bitrix:tasks.interface.filter', 'markPresetAhaMomentViewed', {
				mode: 'class',
				data: {},
			})
			.catch(error => {
				this.#log(error);
			});
	}

	#log(error: Error): void
	{
		console.log(error);
	}
}
