import { ApplyButton, ButtonColor, CancelButton } from 'ui.buttons';
import { Dialog } from 'ui.entity-selector';
import { UI } from 'ui.notification';
import { Binder } from './binder';
import { Loc, Type } from 'main.core';

export class EntitySelector
{
	#dialogProp: ?Dialog = null;
	#binder: Binder;
	#target: ?HTMLElement;

	constructor(binder: Binder, target: ?HTMLElement = null)
	{
		if (binder instanceof Binder)
		{
			this.#binder = binder;
		}

		if (Type.isDomNode(target) || Type.isNil(target))
		{
			this.#target = target;
		}

		if (!this.#binder)
		{
			console.error('Invalid constructor params:', { binder: binder });

			throw new Error('Invalid constructor params');
		}
	}

	get #dialog(): Dialog
	{
		if (this.#dialogProp)
		{
			return this.#dialogProp;
		}

		const applyButton = new ApplyButton({
			color: ButtonColor.SUCCESS,
			onclick: () => {
				void this.hide();

				this.#linking();
			},
		});
		const cancelButton = new CancelButton({
			onclick: () => {
				void this.hide();
			},
		});

		const childEntityTypeName = BX.CrmEntityType.resolveName(this.#binder.getChildEntityTypeId());

		this.#dialogProp = new Dialog({
			targetNode: this.#target,
			enableSearch: true,
			context: `crm.binder.entity-selector.for-${childEntityTypeName}`,
			entities: [{
				id: childEntityTypeName.startsWith(BX.CrmEntityType.dynamicTypeNamePrefix) ? 'DYNAMIC' : childEntityTypeName,
				dynamicLoad: true,
				dynamicSearch: true,
				options: {
					entityTypeId: this.#binder.getChildEntityTypeId(),
					sourceTypeId: this.#binder.getParenEntityTypeId(),
					notLinkedOnly: true,
					withoutRecentItems: true,
					withoutGlobalRecentItems: true,
				},
			}],
			footer: [applyButton.render(), cancelButton.render()],
			footerOptions: {
				containerStyles: {
					display: 'flex',
					'justify-content': 'center',
				},
			},
			tagSelectorOptions: {
				textBoxWidth: 565, // same as default dialog width
			},
		});

		// this.#dialogProp.subscribe('Item:onSelect', this.#handleItemSelect.bind(this));

		return this.#dialogProp;
	}

	#linking(): void
	{
		const data = [];

		if (this.#dialog.getSelectedItems().length === 0)
		{
			UI.Notification.Center.notify({
				content: Loc.getMessage('ENTITY_BINDER_ITEMS_NOT_SELECTED'),
				autoHideDelay: 5000,
			});

			return;
		}

		this.#dialog.getSelectedItems().forEach((item) => {
			data.push(item.getId());
		});

		this.#binder.bind(data);
	}

	show(): Promise
	{
		return new Promise((resolve) => {
			this.#dialog.subscribeOnce('onShow', resolve);

			this.#dialog.show();
		});
	}

	hide(): Promise
	{
		return new Promise((resolve) => {
			this.#dialog.subscribeOnce('onHide', resolve);

			this.#dialog.hide();
		});
	}

	destroy(): Promise
	{
		return new Promise((resolve) => {
			this.#dialog.destroy();

			resolve();
		});
	}
}