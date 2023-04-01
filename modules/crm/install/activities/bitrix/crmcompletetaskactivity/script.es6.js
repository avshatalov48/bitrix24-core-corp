import {Reflection, Dom, Text} from 'main.core';

const namespace = Reflection.namespace('BX.Crm.Automation.Activity');

type StageInfo = {id: string, name: string};

export class CompleteTaskActivity
{
	#form: HTMLFormElement;
	#chosenStages: Set<string>;
	#stages: Object<string, Array<StageInfo>>;
	#categoryContainer: HTMLSelectElement;
	#stagesContainer: HTMLSelectElement;

	constructor(options: {
		formName: string,
		stages: Object<string, Array<{id: string, name: string}>>,
		chosenStages: Array<string>,
	})
	{
		this.#form = document.forms.namedItem(options.formName);

		this.#categoryContainer = this.#form['target_category'];
		this.#stagesContainer = this.#form['target_status[]'];
		this.#stages = options.stages;
		this.#chosenStages = new Set(options.chosenStages);
	}

	init()
	{
		this.updateStages();
	}

	updateStages()
	{
		Dom.clean(this.#stagesContainer);
		this.renderStages();
	}

	render()
	{
		if (this.#categoryContainer)
		{
			this.#categoryContainer.onchange = this.updateStages.bind(this);
		}
	}

	renderStages()
	{
		if (this.#stages.hasOwnProperty(this.#categoryContainer.value))
		{
			const stages = this.#stages[this.#categoryContainer.value];

			stages.forEach(({id, name}) => {
				const option = new Option(name, id, false, this.#chosenStages.has(id))

				this.#stagesContainer.append(option);
			});
		}
		else
		{
			for (const categoryOption of this.#categoryContainer.options)
			{
				const categoryId = categoryOption.value;
				const categoryName = categoryOption.text;

				if (this.#stages.hasOwnProperty(categoryId))
				{
					this.#stages[categoryId].forEach(({id, name}) => {
						const stageName = Text.encode(`${categoryName} / ${name}`);
						const option = new Option(stageName, id, false, this.#chosenStages.has(id));

						this.#stagesContainer.append(option);
					});
				}
			}
		}
	}
}

namespace.CompleteTaskActivity = CompleteTaskActivity;