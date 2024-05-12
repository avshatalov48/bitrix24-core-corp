import { Loc, Text } from 'main.core';
import { Menu, MenuItemOptions, MenuManager } from 'main.popup';

export default class MenuPopup
{
	#menu: Menu = null;
	#bindElement: HTMLElement = null;
	#isTextItemFirst: boolean = false;
	#onEditorItemClick: Function = () => {};
	#onTextItemClick: Function = () => {};

	constructor({ bindElement, isTextItemFirst, onEditorItemClick, onTextItemClick })
	{
		this.#bindElement = bindElement;
		this.#isTextItemFirst = isTextItemFirst;
		this.#onEditorItemClick = onEditorItemClick;
		this.#onTextItemClick = onTextItemClick;
	}

	show(): void
	{
		this.#getMenuPopup().show();
	}

	#getMenuPopup(): Menu
	{
		if (this.#menu === null)
		{
			this.#menu = MenuManager.create({
				id: 'crm-template-editor-placeholder-selector',
				bindElement: this.#bindElement,
				autoHide: true,
				offsetLeft: 20,
				angle: true,
				closeByEsc: false,
				cacheable: false,
				items: this.#getItems(),
			});
		}

		return this.#menu;
	}

	#getItems(): Object[]
	{
		const editorItem = this.#getEditorItem();
		const textItem = this.#getTextItem();

		if (this.#isTextItemFirst)
		{
			return [
				textItem,
				editorItem,
			];
		}

		return [
			editorItem,
			textItem,
		];
	}

	#getEditorItem(): MenuItemOptions
	{
		return {
			html: this.#getItemTitle('CRM_TEMPLATE_EDITOR_SELECT_FIELD'),
			onclick: () => {
				this.#onEditorItemClick(this.#bindElement);
			},
		};
	}

	#getTextItem(): MenuItemOptions
	{
		const code = (
			this.#isTextItemFirst
				? 'CRM_TEMPLATE_EDITOR_UPDATE_TEXT'
				: 'CRM_TEMPLATE_EDITOR_CREATE_TEXT'
		);

		return {
			html: this.#getItemTitle(code),
			onclick: () => {
				this.#getMenuPopup().close();
				this.#onTextItemClick(this.#bindElement);
			},
		};
	}

	#getItemTitle(code: string): string
	{
		const placeholder = '<span class="crm-template-editor-placeholder-selector-menu-item">#ITEM_TEXT#</span>';

		return placeholder.replace('#ITEM_TEXT#', Text.encode(Loc.getMessage(code)));
	}
}
