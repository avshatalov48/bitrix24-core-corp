/**
 * @module settings-v2/manager
 */
jn.define('settings-v2/manager', (require, exports, module) => {
	const { Pages } = require('settings-v2/structure');
	const { SettingsPageId } = require('settings-v2/const');
	const { SettingsPage } = require('settings-v2/ui/page');
	const { Type } = require('type');

	/**
	 * @class SettingsManager
	 */
	class SettingsManager
	{
		/**
		 * @param {SettingsPageId} settingsPageId
		 * @param {PageManager} parentWidget
		 * @param {Object} pageParams
		 */
		async open({ settingsPageId = SettingsPageId.ROOT, pageParams = {}, parentWidget = PageManager })
		{
			if (!Pages[settingsPageId])
			{
				throw new Error(`Invalid settingsPageId: "${settingsPageId}" Expected one of enum SettingsPageId`);
			}

			const settingsPage = await this.preparePage(Pages[settingsPageId], pageParams);

			return this.#openWidget({
				parentWidget,
				settingsPage,
			});
		}

		async preparePage(settingsPage, pageParams)
		{
			const settingsData = await settingsPage.requestSettingsData?.(pageParams) ?? null;

			return {
				...settingsPage,
				...pageParams,
				items: this.prepareItems(settingsPage.items, settingsData),
			};
		}

		prepareItems(items, settingsData)
		{
			const preparedItems = items.map((item) => {
				if (item.prepareItems)
				{
					item.items = item.prepareItems(settingsData);
				}

				return item;
			});

			return this.filterItems(preparedItems, settingsData);
		}

		filterItems(items, settingsData)
		{
			items.forEach((item) => {
				if (Type.isArrayFilled(item.items))
				{
					item.items = this.filterItems(item.items, settingsData);
				}
			});

			return items.filter((item) => {
				if (item.prefilter)
				{
					return item.prefilter(settingsData);
				}

				return true;
			});
		}

		async #openWidget({ settingsPage, parentWidget = PageManager })
		{
			const { items, title } = settingsPage;
			const currentWidget = await parentWidget.openWidget('layout', {
				titleParams: {
					text: title,
					type: 'dialog',
				},
			});

			currentWidget.showComponent(
				new SettingsPage({
					items,
					openPage: this.openPage(currentWidget),
				}),
			);
		}

		openPage = (currentWidget) => (settingsPageId, pageParams) => {
			void new SettingsManager().open({
				settingsPageId,
				parentWidget: currentWidget,
				pageParams,
			});
		};
	}

	module.exports = {
		openSettings: ({ settingsPageId, parentWidget }) => (new SettingsManager()).open({
			settingsPageId,
			parentWidget,
		}),
	};
});
