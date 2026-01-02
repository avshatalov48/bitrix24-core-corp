import { Event, Tag, Loc, Type } from 'main.core';
import type { BaseEvent } from 'main.core.events';
import { BaseFooter } from 'ui.entity-selector';
import type { FooterOptions, ItemOptions, Tab } from 'ui.entity-selector';

export class ResourceSelectorDialogFooter extends BaseFooter
{
	#resources = [];
	#onClose: Function | null = null;
	#onSave: Function | null = null;

	constructor(tab: Tab, options: FooterOptions)
	{
		super(tab, options);

		this.#resources = [];

		this.#onClose = options.onClose || null;
		this.#onSave = options.onSave || null;

		this.getDialog().subscribe('Item:onSelect', this.#handleOnTagAdd.bind(this));
		this.getDialog().subscribe('Item:onDeselect', this.#handleOnTagRemove.bind(this));
	}

	render(): HTMLElement
	{
		const { footer, footerAddButton, footerCloseButton } = Tag.render`
			<div ref="footer" class="crm-forms--booking--resource-selector-dialog__footer">
				<button
					ref="footerAddButton"
					class="ui-btn ui-btn ui-btn-sm ui-btn-primary ui-btn-round crm-forms--booking--resource-selector-dialog__footer-btn-width"
				>
					${Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_RESOURCE_SELECTOR_DIALOG_ADD_BUTTON')}
				</button>
				<button ref="footerCloseButton" class="ui-btn ui-btn ui-btn-sm ui-btn-light-border ui-btn-round crm-forms--booking--resource-selector-dialog__footer-btn-width">
					${Loc.getMessage('BOOKING_CRM_FORMS_SETTINGS_RESOURCE_SELECTOR_DIALOG_CANCEL_BUTTON')}
				</button>
			</div>
		`;

		this.footerAddButton = footerAddButton;
		Event.bind(footerAddButton, 'click', this.#add.bind(this));

		this.footerCloseButton = footerCloseButton;
		Event.bind(this.footerCloseButton, 'click', this.#close.bind(this));

		return footer;
	}

	get resourcesCount(): number
	{
		return this.#resources.length;
	}

	#add(): void
	{
		const resources: ItemOptions[] = this.dialog.getSelectedItems();
		const resourceIds = resources.map((resource) => resource.id);

		if (Type.isFunction(this.#onSave))
		{
			this.#onSave({ resourceIds });
		}

		this.#close();
	}

	#close(): void
	{
		if (Type.isFunction(this.#onClose))
		{
			this.#onClose();
		}

		this.dialog?.hide();
	}

	destroyDialog(): void
	{
		Event.unbindAll(this.footerAddButton, 'click');
		Event.unbindAll(this.footerCloseButton, 'click');
		this.getDialog().destroy();
	}

	#handleOnTagAdd(event: BaseEvent): void
	{
		const { item } = event.getData();
		this.#onResourceToggle(item, true);
	}

	#handleOnTagRemove(event: BaseEvent): void
	{
		const { item } = event.getData();
		this.#onResourceToggle(item, false);
	}

	#onResourceToggle(item: ItemOptions, isSelected: boolean = false): void
	{
		if (isSelected)
		{
			this.#resources = [...this.#resources, item];
		}
		else
		{
			this.#resources = this.#resources.filter((resource) => resource.id !== item.id);
		}
	}
}
