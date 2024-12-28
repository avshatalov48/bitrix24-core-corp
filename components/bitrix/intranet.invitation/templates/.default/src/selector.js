import {Type, Dom} from "main.core";
import {TagSelector} from 'ui.entity-selector';

export class Selector
{
	constructor(parent, params)
	{
		this.parent = parent;
		this.contentBlock = params.contentBlock;
		this.options = params.options;
		this.entities = [];

		this.prepareOptions();
	}

	prepareOptions()
	{
		for (let type in this.options)
		{
			if (!this.options.hasOwnProperty(type))
			{
				continue;
			}

			if (type === "department" && !!this.options[type])
			{
				this.entities.push({
					id: "department",
					options: {
						selectMode: "departmentsOnly",
						allowOnlyUserDepartments: !(!!this.options["isAdmin"] && this.options["isAdmin"] === true),
						allowSelectRootDepartment: true,
					}
				});
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

	render()
	{
		const preselectedItems = [];

		if (
			this.options.hasOwnProperty('projectId')
			&& this.options.projectId > 0
		)
		{
			preselectedItems.push(['project', this.options.projectId])
		}

		if (
			this.options.hasOwnProperty('departmentsId')
			&& Array.isArray(this.options.departmentsId)
		)
		{
			this.options.departmentsId.forEach((departmentId) => {
				preselectedItems.push(['department', departmentId])
			});
		}

		this.tagSelector = new TagSelector({
			dialogOptions: {
				preselectedItems: preselectedItems,
				entities: this.entities,
				context: 'INTRANET_INVITATION',
			},
		});

		if (Type.isDomNode(this.contentBlock))
		{
			Dom.clean(this.contentBlock);
			this.tagSelector.renderTo(this.contentBlock);
		}
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