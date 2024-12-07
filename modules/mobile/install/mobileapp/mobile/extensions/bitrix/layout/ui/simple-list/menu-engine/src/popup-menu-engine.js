/**
 * @module layout/ui/simple-list/menu-engine/src/popup-menu-engine
 */
jn.define('layout/ui/simple-list/menu-engine/src/popup-menu-engine', (require, exports, module) => {
	const { UIMenu } = require('layout/ui/menu');
	const { BaseMenuEngine } = require('layout/ui/simple-list/menu-engine/src/base-menu-engine');
	const { AnalyticsLabel } = require('analytics-label');
	const { Type } = require('type');
	const { NotifyManager } = require('notify-manager');

	class PopupMenuEngine extends BaseMenuEngine
	{
		constructor(options)
		{
			super();
			this.options = options;
			this.menu = null;
			this.layoutWidget = null;
			this.parent = options.parent;
			this.parentId = options.parentId;
			this.updateItemHandler = options.updateItemHandler;
			this.analyticsLabel = options.analyticsLabel;
			this.isProcessing = false;
			this.isDisabled = options.isDisabled || false;
		}

		show(actions, options)
		{
			if (this.isProcessing)
			{
				return;
			}

			if (this.isDisabled)
			{
				return;
			}

			this.menu = new UIMenu(this.prepareItems(actions));

			this.menu.show(options);
		}

		close(callback)
		{
			this.menu?.hide();

			if (callback)
			{
				callback();
			}
		}

		/**
		 * @private
		 * @param {{
		 *     id: string,
		 *     title: string,
		 *     onClickCallback: function,
		 *     isDestructive?: boolean,
		 *     sectionCode?: string,
		 *     data?: { svgUri?: string, outlineIconUri?: string },
		 *     showActionLoader?: boolean,
		 * }[]} actions
		 * @return {object[]}
		 */
		prepareItems(actions)
		{
			return actions.map((item) => ({
				...item,
				testId: item.id,
				iconUrl: item.data?.outlineIconUri || item.data?.svgUri,
				analyticsLabel: this.analyticsLabel,
				updateItemHandler: this.updateItemHandler,
				showActionLoader: item.showActionLoader || false,
				onItemSelected: (...args) => this.handleItemSelected(item, ...args),
			}));
		}

		handleItemSelected(item, ...args)
		{
			const ensureMenuClosed = (handler) => {
				handler();
			};

			const needProcessing = item.needProcessing ?? true;
			if (!needProcessing)
			{
				item.onClickCallback(
					item.id,
					this.parentId,
					{ parentWidget: this.layoutWidget, ensureMenuClosed, parent: this.parent },
					...args,
				);
				this.sendAnalytics();

				return;
			}

			this.isProcessing = true;

			if (item.showActionLoader)
			{
				NotifyManager.showLoadingIndicator();
			}

			let promise = item.onClickCallback(
				item.id,
				this.parentId,
				{ parentWidget: this.layoutWidget, ensureMenuClosed, parent: this.parent },
				...args,
			);

			this.sendAnalytics();

			if (!(promise instanceof Promise))
			{
				promise = Promise.resolve();
			}

			promise
				.then((result) => this.onItemMenuActionSucceed(item, result))
				.catch((error) => this.onItemMenuActionFail(item, error));
		}

		onItemMenuActionSucceed(item, { action, id, params } = {})
		{
			this.isProcessing = false;
			if (item.showActionLoader)
			{
				NotifyManager.hideLoadingIndicator(true);
			}

			if (action && this.updateItemHandler)
			{
				this.updateItemHandler(action, id, params);
			}
		}

		onItemMenuActionFail({ item, errors } = {})
		{
			this.isProcessing = false;
			if (item.showActionLoader)
			{
				NotifyManager.hideLoadingIndicator(false);
			}

			if (errors && errors.length > 0)
			{
				this.showErrors(errors);
			}
		}

		showErrors(errors, callback = null)
		{
			navigator.notification.alert(
				errors.map((error) => error.message).join('\n'),
				callback,
				'',
			);
		}

		sendAnalytics()
		{
			if (Type.isPlainObject(this.analyticsLabel))
			{
				AnalyticsLabel.send({
					event: 'context-menu-click',
					id: this.parentId,
					...this.analyticsLabel,
				});
			}
		}
	}

	module.exports = { PopupMenuEngine };
});
