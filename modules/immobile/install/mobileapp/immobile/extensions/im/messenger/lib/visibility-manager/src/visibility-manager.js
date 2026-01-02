/**
 * @module im/messenger/lib/visibility-manager/visibility-manager
 */
jn.define('im/messenger/lib/visibility-manager/visibility-manager', (require, exports, module) => {
	const { MemoryStorage } = require('native/memorystore');

	const { Type } = require('type');

	const { DialogHelper } = require('im/messenger/lib/helper');
	const { createPromiseWithResolvers } = require('im/messenger/lib/utils');
	const { MessengerParams } = require('im/messenger/lib/params');
	const { LoggerManager } = require('im/messenger/lib/logger');

	const logger = LoggerManager.getInstance().getLogger('visibility-manager');

	const VISIBILITY_MANAGER_STORAGE_NAME = 'immobileVisibilityManager';
	const VISIBLE_DIALOG_STORAGE_KEY = 'visibleDialog';
	const ACTIVE_TAB_STORAGE_KEY = 'activeTab';

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
		 * @private
		 * @return {Promise<NavigationContext>}
		 */
		static getNavigationContext()
		{
			return new Promise((resolve, reject) => {
				PageManager.getNavigator().getNavigationContext()
					.then((context) => resolve(context))
					.catch((error) => reject(error))
				;
			});
		}

		constructor()
		{
			this.storage = new MemoryStorage(VISIBILITY_MANAGER_STORAGE_NAME);
			this.stackCache = new Map();
		}

		/**
		 * @description check does the user see the dialog on the screen right now?
		 *
		 * @param {?number|string} dialogId
		 * @param {?string} dialogCode
		 * @param {?boolean} currentContextOnly
		 *
		 * @return {Promise<boolean>}
		 */
		async checkIsDialogVisible({
			dialogId,
			dialogCode,
			currentContextOnly = false,
		})
		{
			const {
				promise,
				resolve,
				reject,
			} = createPromiseWithResolvers();

			const isValidParams = (
				DialogHelper.isDialogId(dialogId)
				|| DialogHelper.isChatId(dialogId)
				|| Type.isStringFilled(dialogCode)
			);
			if (!isValidParams)
			{
				reject(new Error('VisibilityManager.checkIsDialogVisible error: invalid dialogId or dialogCode'));

				return promise;
			}

			// eslint-disable-next-line no-param-reassign
			dialogId = String(dialogId);

			if (Application.isBackground())
			{
				logger.info(`${this.constructor.name}: dialog is invisible because Application is in background. We were looking ${dialogId} ${dialogCode}`);
				resolve(false);

				return promise;
			}

			const visibleDialog = await this.getVisibleDialogInfo();
			if (!visibleDialog)
			{
				logger.info(`${this.constructor.name}: dialog is invisible because there are not visible dialog registered. We were looking ${dialogId} ${dialogCode}`);
				resolve(false);

				return promise;
			}

			if (currentContextOnly && visibleDialog.parentComponentCode !== MessengerParams.getComponentCode())
			{
				logger.info(`${this.constructor.name}: dialog is invisible because it is open in a different context. We were looking ${dialogId} ${dialogCode}`);
				resolve(false);

				return promise;
			}

			const isLastVisibleDialogMatch = visibleDialog.dialogId === dialogId || visibleDialog.dialogCode === dialogCode;
			if (!isLastVisibleDialogMatch)
			{
				logger.info(`${this.constructor.name}: dialog is invisible. We were looking ${dialogId} ${dialogCode}`);
				resolve(false);

				return promise;
			}

			const context = await this.getContext(visibleDialog.dialogCode);
			if (!context)
			{
				// getNavigationContext is an additional, but not mandatory, verification step to exclude a situation
				// where the dialog hiding or closing event did not work and a record of it remained in MemoryStorage
				// if getStack method is not supported, we just assume that the dialog is visible
				logger.warn(`${this.constructor.name}: dialog is visible but getStack check is unsupported. We were looking ${dialogId} ${dialogCode}`);
				resolve(true);

				return true;
			}

			const topItem = await this.getTopItemInContext(context, visibleDialog.dialogCode);
			if (!topItem)
			{
				logger.info(`${this.constructor.name}: dialog is invisible because navigation context is empty. We were looking ${dialogId} ${dialogCode}`);
				resolve(false);

				return promise;
			}

			const isDialogWidgetOnTop = topItem.name === 'chat.dialog';
			if (!isDialogWidgetOnTop)
			{
				logger.info(`${this.constructor.name}: dialog is invisible because the topmost widget is not chat.dialog. We were looking ${dialogId} ${dialogCode}`);
				resolve(false);

				return promise;
			}

			const widgetSettings = topItem.settings;
			if (!Type.isObject(widgetSettings))
			{
				logger.info(`${this.constructor.name}: dialog is invisible because widget has no settings. We were looking ${dialogId} ${dialogCode}`);
				resolve(false);

				return promise;
			}

			if (topItem.code === visibleDialog.dialogCode && widgetSettings.dialogId === visibleDialog.dialogId)
			{
				logger.info(`${this.constructor.name}: dialog is visible after stack check. We were looking ${dialogId} ${dialogCode}`);
				resolve(true);

				return promise;
			}

			logger.info(`${this.constructor.name}: dialog is invisible after stack check. We were looking ${dialogId} ${dialogCode}`);
			resolve(false);

			return promise;
		}

		/**
		 * @param {VisibleDialogInfo} visibleDialogInfo
		 */
		async saveVisibleDialogInfo(visibleDialogInfo)
		{
			return this.storage.set(VISIBLE_DIALOG_STORAGE_KEY, visibleDialogInfo);
		}

		/**
		 * @param {string} dialogCode
		 * @return {Promise<void>}
		 */
		async removeVisibleDialogInfoByDialogCode(dialogCode)
		{
			const visibleDialog = await this.getVisibleDialogInfo();
			if (visibleDialog?.dialogCode === dialogCode)
			{
				// false instead of null because we can't write null to MemoryStorage on Android build 3306
				await this.storage.set(VISIBLE_DIALOG_STORAGE_KEY, false);
			}
		}

		/**
		 * @return {Promise<VisibleDialogInfo|false>}
		 */
		async getVisibleDialogInfo()
		{
			return this.storage.get(VISIBLE_DIALOG_STORAGE_KEY);
		}

		// TODO: remove after MessengerV2 release
		/**
		 * @deprecated
		 * @param {ActiveTabInfo} activeTabInfo
		 */
		async saveActiveTabInfo(activeTabInfo)
		{
			return this.storage.set(ACTIVE_TAB_STORAGE_KEY, activeTabInfo);
		}

		// TODO: remove after MessengerV2 release
		/**
		 * @deprecated
		 * @return {Promise<ActiveTabInfo|null>}
		 */
		async getActiveTabInfo()
		{
			return this.storage.get(ACTIVE_TAB_STORAGE_KEY);
		}

		/**
		 * @private
		 * @param {string} dialogCode
		 */
		async getContext(dialogCode)
		{
			if (this.stackCache.has(dialogCode))
			{
				logger.warn(`${this.constructor.name}: we are looking for a dialog by cached context`);

				return this.stackCache.get(dialogCode);
			}

			try
			{
				logger.warn(`${this.constructor.name}: we are looking for a dialog by real getNavigationContext`);
				const context = await VisibilityManager.getNavigationContext();
				this.stackCache.set(dialogCode, context);

				return context;
			}
			catch (error)
			{
				logger.error(error);

				return null;
			}
		}

		/**
		 * @private
		 * @param {object} context
		 * @param {?Array<object>} context.children
		 * @param {?Array<object>} context.itemsInStack
		 * @return {object|null}
		 */
		async getTopItemInContext(context)
		{
			const hasItems = Type.isArrayFilled(context.children);
			if (!hasItems)
			{
				return null;
			}

			const stack = await context.children[context.children.length - 1].getStack();
			const stackKeys = Object.keys(stack);

			return stack[stackKeys[stackKeys.length - 1]];
		}
	}

	module.exports = {
		VisibilityManager,
	};
});
