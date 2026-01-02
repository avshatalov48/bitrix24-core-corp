import { Loc, Type, Tag, Dom } from 'main.core';
import { TagSelector } from 'ui.entity-selector';
import { BaseEvent, EventEmitter } from 'main.core.events';
import './style.css';

export type DepartmentControlType = {
	rootDepartment: Object,
	departmentList: any | [],
	title: string,
	description: string,
}

export default class DepartmentControl extends EventEmitter
{
	#tagSelector: TagSelector;
	#rootDepartment: Object;
	#departmentList: Array;
	#title: string;
	#description: string;

	constructor(options: DepartmentControlType)
	{
		super();
		this.setEventNamespace('BX.Intranet.DepartmentControl');
		this.#rootDepartment = Type.isNil(options?.rootDepartment) ? null : options?.rootDepartment;
		this.#departmentList = Type.isArray(options?.departmentList) ? options?.departmentList : [];
		this.#title = options.title ?? Loc.getMessage('INTRANET_INVITE_DIALOG_DEPARTMENT_CONTROL_LABEL');
		this.#description = options.description ?? Loc.getMessage('INTRANET_INVITE_DIALOG_DEPARTMENT_CONTROL_DESCRIPTION');

		this.#tagSelector = this.#initTagSelector(options);
	}

	#initTagSelector(options: DepartmentControlType): TagSelector
	{
		return new TagSelector({
			tagTextColor: '#1E8D36',
			tagBgColor: '#D4FDB0',
			items: this.getDefaultItems(),
			events: {
				onBeforeTagRemove: (event: BaseEvent) => {
					const selector = event.getTarget();
					const { tag } = event.getData();
					if (selector.getTags().length === 1 && this.#isRootItem(tag))
					{
						event.preventDefault();
					}
				},
				onAfterTagAdd: this.onAfterTagChange.bind(this),
				onAfterTagRemove: this.onAfterTagChange.bind(this),
			},
			dialogOptions: {
				context: 'INVITATION_STRUCTURE',
				width: 350,
				enableSearch: true,
				multiple: true,
				entities: [
					{
						id: 'structure-node',
						options: {
							selectMode: 'departmentsOnly',
							restricted: 'inviteUser',
						},
					},
				],
				events: {
					'Item:onBeforeDeselect': (event: BaseEvent) => {
						const dialog: Dialog = event.getTarget();
						const selectedItems: Item[] = dialog.getSelectedItems();
						if (selectedItems.length === 1 && this.#isRootItem(selectedItems[0]))
						{
							event.preventDefault();
						}
					},
					'Item:onDeselect': (event: BaseEvent) => {
						const dialog: Dialog = event.getTarget();
						const selectedItems: Item[] = dialog.getSelectedItems();
						if (selectedItems.length <= 0)
						{
							const item = dialog.getItem(['structure-node', options?.rootDepartment.id]);
							item?.select();
						}
					},
					onLoad: (event: BaseEvent) => {
						const dialog: Dialog = event.getTarget();
						dialog.selectTab('structure-departments-tab');
					},
				},
			},
		});
	}

	getDefaultItems(): Array
	{
		let items = [];

		if (
			!Type.isNil(this.#rootDepartment)
			&& Type.isArray(this.#departmentList)
			&& this.#departmentList.length > 0
		)
		{
			items = this.#departmentList.map((department) => {
				return {
					id: parseInt(department.id, 10),
					avatar: '/bitrix/js/humanresources/entity-selector/src/images/company.svg',
					entityId: 'structure-node',
					textColor: '#006E7C',
					bgColor: '#DDF6F9',
					title: department.name,
					customData: {
						accessCode: department.accessCode,
					},
				};
			});
		}
		else if (!Type.isNil(this.#rootDepartment))
		{
			const rootDep = {
				id: parseInt(this.#rootDepartment.id, 10),
				avatar: '/bitrix/js/humanresources/entity-selector/src/images/company.svg',
				entityId: 'structure-node',
				textColor: '#006E7C',
				bgColor: '#DDF6F9',
				title: this.#rootDepartment.name,
				customData: {
					accessCode: this.#rootDepartment.accessCode,
				},
			};
			items.push(rootDep);
		}

		return items;
	}

	reset()
	{
		this.#tagSelector.removeTags();
		this.getDefaultItems().forEach((item) => {
			this.#tagSelector.addTag(item);
		});
	}

	#isRootItem(item: Object): boolean
	{
		const itemId = Type.isNil(item?.id) ? null : parseInt(item?.id, 10);
		const rootId = Type.isNil(this.#rootDepartment?.id) ? null : parseInt(this.#rootDepartment?.id, 10);

		return !Type.isNil(itemId) && !Type.isNil(rootId) && itemId === rootId;
	}

	renderTo(container: HTMLElement): void
	{
		Dom.append(this.render(), container);
	}

	getValues(): Array
	{
		const tagSelectorItems = this.#tagSelector.getDialog().getSelectedItems();
		const collection = [];
		tagSelectorItems.forEach((item) => {
			const departmentId = parseInt(item?.id, 10);
			if (departmentId > 0)
			{
				collection.push(departmentId);
			}
		});

		return collection;
	}

	render(): HTMLElament
	{
		const title = Tag.render`<label class="department-control__dialog-title">${this.#title}</label>`;
		const description = Tag.render`<div class="department-control__dialog-description">${this.#description}</div>`;
		const fieldContainer = Tag.render`<div></div>`;
		this.#tagSelector.renderTo(fieldContainer);

		return Tag.render`<div>${title}${description}${fieldContainer}</div>`;
	}

	onAfterTagChange(event: BaseEvent): void
	{
		const selector = event.getTarget();
		this.emit('onChange', { tags: selector.getTags() });
	}
}
