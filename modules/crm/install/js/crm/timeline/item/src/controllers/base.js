import ConfigurableItem from '../configurable-item';
import { ajax as Ajax } from "main.core";
import { UI } from "ui.notification";

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
	getDeleteActionMethod(): string
	{
		return '';
	}

	getDeleteActionCfg(recordId: Number, ownerTypeId: Number, ownerId: Number): Object
	{
		return {
			data: {
				recordId,
				ownerTypeId,
				ownerId,
			}
		};
	}

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

	onAfterItemLayout(item: ConfigurableItem, options): void
	{
	}

	/**
	 * Will be executed before item node deleted from DOM
	 * @param item
	 */
	onBeforeItemClearLayout(item: ConfigurableItem): void
	{

	}

	/**
	 * Delete timeline record action
	 *
	 * @param recordId Timeline record ID
	 * @param ownerTypeId Owner type ID
	 * @param ownerId Owner type ID
	 * @param animationCallbacks
	 *
	 * @returns {Promise}
	 *
	 * @protected
	 */
	runDeleteAction(recordId: Number, ownerTypeId: Number, ownerId: Number, animationCallbacks: ?Object): Promise
	{
		if (animationCallbacks.onStart)
		{
			animationCallbacks.onStart();
		}

		return Ajax.runAction(
			this.getDeleteActionMethod(),
			this.getDeleteActionCfg(recordId, ownerTypeId, ownerId)
		).then(() => {
			if (animationCallbacks.onStop)
			{
				animationCallbacks.onStop();
			}
			return true;
		}, (response) =>
		{
			UI.Notification.Center.notify({
				content: response.errors[0].message,
				autoHideDelay: 5000,
			});

			if (animationCallbacks.onStop)
			{
				animationCallbacks.onStop();
			}

			return true;
		});
	}

	static isItemSupported(item: ConfigurableItem): boolean
	{
		return false;
	}
}
