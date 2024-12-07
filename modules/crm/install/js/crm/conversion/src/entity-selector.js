import { Text, Type } from 'main.core';
import type { BaseEvent } from 'main.core.events';
import { ApplyButton, ButtonColor, CancelButton } from 'ui.buttons';
import { Dialog, type Item } from 'ui.entity-selector';
import { Converter } from './converter';

/**
 * @memberOf BX.Crm.Conversion
 */
export class EntitySelector
{
	#converter: Converter;
	#entityId: number;
	#dstEntityTypeIds: number[];
	#target: ?HTMLElement;

	#dialogProp: ?Dialog = null;

	constructor(converter: Converter, entityId: number, dstEntityTypeIds: number[], target: ?HTMLElement = null)
	{
		// this dont work in slider for some reason
		// if (converter instanceof Converter)
		// {
		// 	this.#converter = converter;
		// }
		this.#converter = converter;

		// eslint-disable-next-line no-param-reassign
		entityId = Text.toInteger(entityId);
		if (entityId > 0)
		{
			this.#entityId = entityId;
		}

		this.#dstEntityTypeIds = dstEntityTypeIds
			.map((x) => Text.toInteger(x))
			.filter((entityTypeId) => BX.CrmEntityType.isDefined(entityTypeId))
		;
		this.#dstEntityTypeIds.sort();

		if (Type.isDomNode(target) || Type.isNil(target))
		{
			this.#target = target;
		}

		if (!this.#converter || !this.#entityId || !Type.isArrayFilled(this.#dstEntityTypeIds))
		{
			console.error('Invalid constructor params:', { converter, entityId, dstEntityTypeIds });

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

				this.#convert();
			},
		});
		const cancelButton = new CancelButton({
			onclick: () => {
				void this.hide();
			},
		});

		this.#dialogProp = new Dialog({
			targetNode: this.#target,
			enableSearch: true,
			context: `crm.converter.entity-selector.${this.#dstEntityTypeIds.join('-')}`,
			entities: this.#dstEntityTypeIds.map((entityTypeId) => {
				return {
					id: BX.CrmEntityType.resolveName(entityTypeId),
					dynamicLoad: true,
					dynamicSearch: true,
					options: {
						showTab: true,
						excludeMyCompany: true,
					},
					searchFields: [
						{ name: 'id' },
					],
					searchCacheLimits: [
						'^\\d+$',
					],
				};
			}),
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

		this.#dialogProp.subscribe('Item:onSelect', this.#handleItemSelect.bind(this));

		return this.#dialogProp;
	}

	#convert(): void
	{
		const activeEntityTypeIds = new Set();
		const data = {};

		this.#dialog.getSelectedItems().forEach((item) => {
			activeEntityTypeIds.add(BX.CrmEntityType.resolveId(item.getEntityId().toUpperCase()));
			data[item.getEntityId()] = item.getId();
		});

		const schemeItem = this.#converter.getConfig().getScheme().getItemForEntityTypeIds([...activeEntityTypeIds]);
		if (!schemeItem)
		{
			throw new Error(`Could not find a scheme item for destinations ${[...activeEntityTypeIds].join(', ')}`);
		}
		this.#converter.getConfig().updateFromSchemeItem(schemeItem);

		this.#converter.convert(this.#entityId, data);
	}

	#handleItemSelect(event: BaseEvent): void
	{
		const { item } = event.getData();

		EntitySelector.#ensureOnlyOneItemOfEachTypeIsSelected(this.#dialog, item);
	}

	static #ensureOnlyOneItemOfEachTypeIsSelected(dialog: Dialog, justSelectedItem: Item): void
	{
		dialog.getSelectedItems().forEach((item) => {
			if (
				item.getEntityId() === justSelectedItem.getEntityId()
				&& Text.toInteger(item.getId()) !== Text.toInteger(justSelectedItem.getId())
			)
			{
				item.deselect();
			}
		});
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
			this.#dialog.unsubscribe('Item:onSelect', this.#handleItemSelect.bind(this));
			this.#dialog.destroy();

			resolve();
		});
	}

	getConverter(): Converter
	{
		return this.#converter;
	}
}
