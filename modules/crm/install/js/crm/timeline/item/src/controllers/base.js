import ConfigurableItem from '../configurable-item';

export class Base
{
	onItemAction(item: ConfigurableItem, action: String, actionData: ?Object): void
	{

	}

	getContentBlockComponents(item: ConfigurableItem): Object
	{
		return {};
	}

	/**
	 * Will be executed before item node deleted from DOM
	 * @param item
	 */
	onBeforeItemClearLayout(item: ConfigurableItem): void
	{

	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return false;
	}
}
