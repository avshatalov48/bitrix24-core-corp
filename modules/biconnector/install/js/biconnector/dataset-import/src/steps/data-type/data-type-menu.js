import { Menu } from 'main.popup';
import { DataTypeDescriptions } from '../../types/data-types';

type DataTypeMenuOptions = {
	selectedType: string,
	bindElement: HTMLElement,
	onSelect: Function,
	onClose: Function,
};

export class DataTypeMenu
{
	#selectedType: string;
	#bindElement: HTMLElement;
	#onSelect: Function;
	#onClose: Function;

	constructor(options: DataTypeMenuOptions)
	{
		this.#selectedType = options.selectedType;
		this.#bindElement = options.bindElement;
		this.#onSelect = options.onSelect;
		this.#onClose = options.onClose;
	}

	getMenu(): Menu
	{
		const items = [];
		Object.entries(DataTypeDescriptions).forEach(([key, value]) => {
			items.push({
				html: `
					<div class="ui-icon-set ${value.icon}"></div>
					<span class="format-table__dropdown-item-text">${value.title}</span>
					${key === this.#selectedType ? '<div class="format-table__dropdown-item-selected ui-icon-set --check"></div>' : ''}
				`,
				onclick: (event, item) => {
					this.#onSelect(key);
					item.getMenuWindow().close();
				},
				className: `format-table__dropdown-item${key === this.#selectedType ? ' format-table__dropdown-item--active' : ''}`,
			});
		});

		return new Menu({
			className: 'format-table__dropdown-popup',
			bindElement: this.#bindElement,
			items,
			events: {
				onClose: this.#onClose,
			},
		});
	}
}
