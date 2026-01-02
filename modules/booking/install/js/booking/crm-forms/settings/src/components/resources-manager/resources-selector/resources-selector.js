import { Type } from 'main.core';
import { Dialog } from 'ui.entity-selector';

import { EntitySelectorEntity } from 'booking.const';

import { ResourceSelectorDialogFooter } from './footer';
import './resource-selector-dialog.css';

type ResourcesSelectorOptions = {
	targetNode: HTMLElement;
	selectedIds: ?number[];
	onClose: () => void;
	onSave: ({ resourceIds: number[] }) => void;
}

export class ResourcesSelector
{
	#selector: ?Dialog = null;
	#targetNode: HTMLElement;
	#selectedIds: number[];
	#selectedItems = [];
	#onClose: Function = null;
	#onSave: Function = null;

	constructor(options: ResourcesSelectorOptions)
	{
		this.#targetNode = options.targetNode;
		this.#selectedIds = options.selectedIds || [];
		this.#selectedItems = [];
		this.#onClose = options.onClose;
		this.#onSave = options.onSave;
	}

	getSelectedItems(): []
	{
		return this.#selectedItems;
	}

	createSelector(): Dialog
	{
		this.#selector = new Dialog({
			id: 'crm-forms--booking--booking-setting--resources-selector',
			preselectedItems: this.#selectedIds.map((id: number) => [EntitySelectorEntity.Resource, id]),
			width: 400,
			enableSearch: true,
			dropdownMode: true,
			context: 'crmFormsBookingResourcesSelector',
			multiple: true,
			cacheable: true,
			showAvatars: false,
			footer: ResourceSelectorDialogFooter,
			footerOptions: {
				onSave: this.#onSave,
				onClose: this.#onClose,
			},
			entities: [
				{
					id: EntitySelectorEntity.Resource,
					dynamicLoad: true,
					dynamicSearch: true,
				},
			],
			searchOptions: {
				allowCreateItem: false,
			},
			popupOptions: {
				overlay: { opacity: 40 },
			},
			events: {
				onHide: this.hide.bind(this),
				onLoad: this.#changeSelected.bind(this),
			},
		});

		return this.#selector;
	}

	getSelectedIds(): number[]
	{
		return this.#selectedItems.map(({ id }) => id);
	}

	hide(): void
	{
		if (Type.isFunction(this.#onClose))
		{
			this.#onClose();
		}
	}

	#changeSelected(): void
	{
		this.#selectedItems = this.#selector.getSelectedItems();
	}
}
