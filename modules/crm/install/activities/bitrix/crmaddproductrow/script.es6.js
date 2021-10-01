import { Reflection, Type, Event } from 'main.core';
import { Dialog } from 'ui.entity-selector';

const namespace = Reflection.namespace('BX.Crm.Activity');

class CrmAddProductRowActivity
{
	productNode;
	productSettings;

	#selector;

	constructor(options)
	{
		if (Type.isPlainObject(options))
		{
			const form = document.forms[options.formName];

			if (!Type.isNil(form))
			{
				this.productNode = form['product_id'];
			}

			if (options.productProperty && Type.isPlainObject(options.productProperty.Settings))
			{
				this.productSettings = options.productProperty.Settings;
			}
		}
	}

	init(): void
	{
		if (this.productNode && this.productSettings)
		{
			Event.bind(this.productNode, 'click', this.#onProductClick.bind(this));
		}
	}

	#onProductClick(): void
	{
		this.#getProductSelector().show();
	}

	#getProductSelector()
	{
		if (!this.#selector)
		{
			this.#selector = new Dialog({
				context: 'catalog-products',
				entities: [
					{
						id: 'product',
						options: {
							iblockId: this.productSettings.iblockId,
							basePriceId: this.productSettings.basePriceId,
						}
					}
				],
				targetNode: this.productNode,
				height: 300,
				multiple: false,
				dropdownMode: true,
				enableSearch: true,
				events: {
					'Item:onBeforeSelect': (event) =>
					{
						event.preventDefault();
						this.productNode.value = event.getData().item.getId();
					}
				}
			});
		}

		return this.#selector;
	}
}

namespace.CrmAddProductRowActivity = CrmAddProductRowActivity;