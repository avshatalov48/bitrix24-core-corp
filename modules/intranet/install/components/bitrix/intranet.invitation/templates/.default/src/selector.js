import {Type, Dom} from "main.core";
import {TagSelector} from 'ui.entity-selector';

export class Selector
{
	constructor(params)
	{
		this.options = params.options;
		this.entities = [];

		this.#prepareOptions();
	}

	#prepareOptions(): void
	{
		for (const type in this.options)
		{
			if (!this.options.hasOwnProperty(type))
			{
				continue;
			}

			if (type === "project" && !!this.options[type])
			{
				const options = {
					fillRecentTab: true,
					'!type': ['collab'],
					createProjectLink: this.options.showCreateButton ?? true,
				};
				if (
					Object.prototype.hasOwnProperty.call(this.options, 'projectLimitExceeded')
					&& Object.prototype.hasOwnProperty.call(this.options, 'projectLimitFeatureId')
				)
				{
					options.lockProjectLink = this.options.projectLimitExceeded;
					options.lockProjectLinkFeatureId = this.options.projectLimitFeatureId;
				}

				const optionValue = {
					id: 'project',
					options,
				};

				if (this.options[type] === "extranet")
				{
					optionValue["options"]["extranet"] = true;
				}
				this.entities.push(optionValue);
			}
		}
	}

	renderTo(target: HTMLElement)
	{
		this.tagSelector = this.renderTagSelector();

		if (Type.isDomNode(target))
		{
			this.tagSelector.renderTo(target);
		}
	}

	renderTagSelector(): TagSelector
	{
		const preselectedItems = [];

		if (this.options?.projectId > 0)
		{
			preselectedItems.push(['project', this.options.projectId]);
		}

		return new TagSelector({
			dialogOptions: {
				preselectedItems,
				entities: this.entities,
				context: 'INTRANET_INVITATION',
			},
		});
	}

	getItems()
	{
		let departments = [];
		let projects = [];
		const tagSelectorItems = this.tagSelector.getDialog().getSelectedItems();

		tagSelectorItems.forEach(item => {
			const id = parseInt(item.getId());
			const type = item.getEntityId();

			if (type === "department")
			{
				departments.push(id);
			}
			else if (type === "project")
			{
				projects.push(id);
			}
		});

		return {
			departments: departments,
			projects: projects
		};
	}
}