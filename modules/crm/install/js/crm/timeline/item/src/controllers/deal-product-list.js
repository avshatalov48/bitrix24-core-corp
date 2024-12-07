import ConfigurableItem from '../configurable-item';
import type {ActionParams} from './base';
import {EventEmitter} from 'main.core.events';
import {UI} from 'ui.notification';
import {Loc} from 'main.core';
import {ActionAnimationCallbacks, Base} from './base';
import ExpandableList from '../components/content-blocks/expandable-list/list';

export class DealProductList extends Base
{
	#item: ConfigurableItem = null;
	#productsGrid: Object = null;

	getContentBlockComponents(Item: ConfigurableItem): Object
	{
		return {
			ExpandableList,
		};
	}

	onInitialize(item: ConfigurableItem): void
	{
		this.#item = item;

		EventEmitter.subscribe('onCrmEntityUpdate', () => {
			this.#item.reloadFromServer();
		});

		/**
		 * For cases when timeline block controller initialization runs after product grid initialization
		 */
		BX.Crm.EntityEditor.getDefault().tapController('PRODUCT_LIST', (controller) => {
			this.#productsGrid = controller.getProductList();
		});

		/**
		 * For cases when timeline block controller initialization runs before product grid initialization
		 */
		EventEmitter.subscribe('EntityProductListController', (event) => {
			this.#productsGrid = event.getData()[0];
		});
	}

	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{
		const {action, actionType, actionData, animationCallbacks} = actionParams;
		if (actionType !== 'jsEvent')
		{
			return;
		}

		if (action === 'ProductList:AddToDeal')
		{
			this.#addProductToDeal(actionData, animationCallbacks);
		}
	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return (
			item.getType() === 'ProductCompilation:SentToClient'
			|| item.getType() === 'Order:EncourageBuyProducts'
		);
	}

	#addProductToDeal(actionData: ?Object, animationCallbacks: ?ActionAnimationCallbacks): void
	{
		if (!(actionData && actionData.productId))
		{
			return;
		}

		if (animationCallbacks.onStart)
		{
			animationCallbacks.onStart();
		}

		BX.onCustomEvent('onAddViewedProductToDeal', [actionData.productId]);

		setTimeout(() => {
			BX.onCustomEvent('OpenEntityDetailTab', ['tab_products']);
		}, 500);

		UI.Notification.Center.notify({
			content: Loc.getMessage('CRM_TIMELINE_ENCOURAGE_BUY_PRODUCTS_PRODUCTS_ADDED_TO_DEAL'),
			autoHideDelay: 5000,
		});

		if (animationCallbacks.onStop)
		{
			animationCallbacks.onStop();
		}
	}
}