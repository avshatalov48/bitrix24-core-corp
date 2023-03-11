import ConfigurableItem from '../configurable-item';

export type ActionParams =
{
	action: String,
	actionType: String,
	actionData: ?Object,
	response: ?Object,
	animationCallbacks: ?ActionAnimationCallbacks,
};

export type ActionAnimationCallbacks =
{
	onStart: ?function,
	onStop: ?function,
};

export class Base
{
	onInitialize(item: ConfigurableItem): void
	{

	}

	onItemAction(item: ConfigurableItem, actionParams: ActionParams): void
	{

	}

	getContentBlockComponents(item: ConfigurableItem): Object
	{
		return {};
	}

	onAfterItemRefreshLayout(item: ConfigurableItem): void
	{
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
