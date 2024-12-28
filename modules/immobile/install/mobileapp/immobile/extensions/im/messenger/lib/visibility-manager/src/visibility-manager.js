/**
 * @module im/messenger/lib/visibility-manager/visibility-manager
 */
jn.define('im/messenger/lib/visibility-manager/visibility-manager', (require, exports, module) => {
	const { Type } = require('type');
	const { Feature } = require('im/messenger/lib/feature');
	const { Logger } = require('im/messenger/lib/logger');

	class VisibilityManager
	{
		/**
		 * @return {VisibilityManager}
		 */
		static getInstance()
		{
			if (!this.instance)
			{
				this.instance = new this();
			}

			return this.instance;
		}

		/**
		 * @return {Promise<NavigationContext>}
		 * @private
		 */
		static getNavigationContext()
		{
			return new Promise((resolve, reject) => {
				if (!Type.isFunction(PageManager.getNavigator().getNavigationContext))
				{
					reject(new Error('getNavigationContext is not supported'));
				}

				PageManager.getNavigator().getNavigationContext()
					.then((context) => resolve(context))
					.catch((error) => reject(error))
				;
			});
		}

		/**
		 * @param {?number|string} dialogId
		 * @param {?string} dialogCode
		 * @return {Promise<boolean>}
		 */
		checkIsDialogVisible({ dialogId, dialogCode })
		{
			return new Promise((resolve) => {
				if (!Type.isStringFilled(dialogId) && !Type.isStringFilled(dialogCode))
				{
					resolve(false);

					return;
				}

				if (Application.isBackground())
				{
					resolve(false);

					return;
				}

				VisibilityManager.getNavigationContext()
					.then(async (context) => {
						const isCurrentTabVisible = context.navigationIsVisible;
						if (dialogId && !isCurrentTabVisible)
						{
							resolve(false);

							return;
						}

						const topItem = await this.getTopItemInContext(context, dialogCode);
						if (!topItem)
						{
							resolve(false);

							return;
						}
						const isDialogWidgetOnTop = topItem.name === 'chat.dialog';
						if (!isDialogWidgetOnTop)
						{
							resolve(false);

							return;
						}

						const widgetSettings = topItem.settings;
						if (!Type.isObject(widgetSettings))
						{
							resolve(false);

							return;
						}

						if (topItem.code === dialogCode || widgetSettings.dialogId === dialogId)
						{
							resolve(true);

							return;
						}

						resolve(false);
					})
					.catch((error) => {
						Logger.error(error);

						resolve(false);
					})
				;
			});
		}

		/**
		 * @param {object} context
		 * @param {?Array<object>} context.children
		 * @param {?Array<object>} context.itemsInStack
		 * @param {?string} dialogCode
		 * @return {object|null}
		 * @private
		 */
		async getTopItemInContext(context, dialogCode)
		{
			if (!Feature.isNavigationContextSupportsGetStack)
			{
				const hasItems = Type.isArrayFilled(context.itemsInStack);
				if (!hasItems)
				{
					return null;
				}

				return context.itemsInStack[context.itemsInStack.length - 1];
			}

			const hasItems = dialogCode ? Type.isArrayFilled(context.children) : Type.isArrayFilled(context.itemsInStack);
			if (!hasItems)
			{
				return null;
			}

			const stack = dialogCode
				? await context.children[context.children.length - 1].getStack()
				: await context.itemsInStack[context.itemsInStack.length - 1].getStack()
			;

			const stackKeys = Object.keys(stack);

			return stack[stackKeys[stackKeys.length - 1]];
		}
	}

	module.exports = {
		VisibilityManager,
	};
});
