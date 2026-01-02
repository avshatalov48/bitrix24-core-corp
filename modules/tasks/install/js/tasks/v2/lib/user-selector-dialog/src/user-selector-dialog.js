import { EventEmitter } from 'main.core.events';
import { EntitySelectorDialog, ItemId } from 'tasks.v2.lib.entity-selector-dialog';

type Params = {
	taskId: number | string,
	dialogOptions: Object,
	preselected: [string, number][],
	events: Object,
};

export class UserSelectorDialog extends EventEmitter
{
	#params: Params;
	#dialog: EntitySelectorDialog;

	constructor(params: Params)
	{
		super();

		this.setEventNamespace('Tasks:UserSelectorDialog');

		this.#params = params;
		this.#dialog = this.#createDialog();
	}

	getDialog(): EntitySelectorDialog
	{
		return this.#dialog;
	}

	show(targetNode: HTMLElement): void
	{
		this.#dialog.getPopup();
		this.#dialog.setTargetNode(targetNode);
		this.#dialog.show();
	}

	selectItemsByIds(items: ItemId[]): void
	{
		this.#dialog.selectItemsByIds(items);
	}

	destroy(): void
	{
		this.#dialog.destroy();
	}

	#createDialog(): EntitySelectorDialog
	{
		return new EntitySelectorDialog({
			id: `tasks-user-selector-dialog-${this.#params.taskId}`,
			preselectedItems: this.#params.preselected,
			...this.#params.dialogOptions,
			events: {
				'Item:onSelect': this.#select.bind(this),
				'Item:onDeselect': this.#select.bind(this),
				onHide: this.#hide.bind(this),
				onLoad: this.#loadUsers.bind(this),
				...this.#params.events,
			},
		});
	}

	#select(): void
	{
		this.emit('select', this.#dialog.getSelectedItems());
	}

	#hide(): void
	{
		this.emit('hide', this.#dialog.getSelectedItems());
	}

	#loadUsers(): void
	{
		const selectedItems = this.#dialog.getSelectedItems();
		if (!selectedItems || selectedItems.length === 0)
		{
			return;
		}

		const users = selectedItems.map((item) => {
			return {
				id: item.getId(),
				name: item.getTitle(),
				image: item.getAvatar(),
				type: item.getEntityType(),
			};
		});

		this.emit('selectedUsersLoaded', users);
	}
}
