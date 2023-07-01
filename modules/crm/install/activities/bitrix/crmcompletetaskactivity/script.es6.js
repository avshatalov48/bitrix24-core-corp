import {Reflection, Dom, Text, Type} from 'main.core';

const namespace = Reflection.namespace('BX.Crm.Automation.Activity');

type StageInfo = {id: string, name: string};

export class CompleteTaskActivity
{
	#form: HTMLFormElement;
	#chosenStages: Set<string>;
	#stages: Object<string, Array<StageInfo>>;
	#categoryContainer: HTMLSelectElement;
	#stagesContainer: HTMLSelectElement;
	#isRobot: boolean;

	constructor(options: {
		formName: string,
		stages: Object<string, Array<{id: string, name: string}>>,
		chosenStages: Array<string>,
		isRobot: boolean,
	})
	{
		this.#form = document.forms.namedItem(options.formName);

		if (!Type.isArray(options.chosenStages))
		{
			options.chosenStages = [];
		}

		this.#categoryContainer = this.#form['target_category'];
		this.#stagesContainer = this.#form['target_status[]'];
		this.#chosenStages = new Set(options.chosenStages.map(stageId => String(stageId)));
		this.#isRobot = options.isRobot;

		this.#stages = {};

		if (Type.isPlainObject(options.stages))
		{
			for (const [categoryId, stages] of Object.entries(options.stages))
			{
				// Due to http://jabber.bx/view.php?id=169508
				// we have to cast types explicitly
				this.#stages[categoryId] = stages.map((stageInfo) => ({
					id: String(stageInfo.id),
					name: String(stageInfo.name),
				}));
			}
		}
		else if (Type.isArray(options.stages))
		{
			// Due to http://jabber.bx/view.php?id=169508
			// we have to cast types explicitly
			options.stages.forEach((categoryStages, categoryId) => {
				this.#stages[categoryId] = categoryStages.map((stageInfo) => ({
					id: String(stageInfo.id),
					name: String(stageInfo.name),
				}))
			})
		}
	}

	init()
	{
		if (this.#categoryContainer.options.length <= 1)
		{
			if (this.#isRobot)
			{
				Dom.remove(this.#categoryContainer.parentElement);
			}
			else
			{
				Dom.remove(this.#categoryContainer.parentElement.parentElement);
			}
		}
		else
		{
			this.updateStages();
		}
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