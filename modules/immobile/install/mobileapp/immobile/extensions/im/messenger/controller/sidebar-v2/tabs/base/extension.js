/**
 * @module im/messenger/controller/sidebar-v2/tabs/base
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/base', (require, exports, module) => {
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { SidebarBaseTabContent } = require('im/messenger/controller/sidebar-v2/tabs/base/src/content');
	const { SidebarBaseTabListContent } = require('im/messenger/controller/sidebar-v2/tabs/base/src/list-content');
	const { SidebarBaseTabDummyContent } = require('im/messenger/controller/sidebar-v2/tabs/base/src/dummy-content');
	const { SidebarTabListItemModel } = require('im/messenger/controller/sidebar-v2/tabs/base/src/list-item-model');

	/**
	 * @class SidebarBaseTab
	 */
	class SidebarBaseTab
	{
		/**
		 * @param {SidebarTabProps} props
		 */
		constructor(props)
		{
			this.dialogId = props.dialogId;
			this.dialogLocator = props.dialogLocator;

			/** @type {SidebarWidgetNavigator} */
			this.widgetNavigator = props.widgetNavigator;

			this.dataProvider = null;
			this.store = serviceLocator.get('core').getStore();
			this.dialogData = this.store.getters['dialoguesModel/getById'](this.dialogId);
		}

		/**
		 * @public
		 * @abstract
		 * @return {string}
		 */
		getId()
		{}

		/**
		 * @public
		 * @abstract
		 * @return {string}
		 */
		getTitle()
		{}

		/**
		 * @public
		 * @return {SidebarDataProvider}
		 */
		getDataProvider()
		{
			if (!this.dataProvider)
			{
				this.dataProvider = this.createDataProvider();
			}

			return this.dataProvider;
		}

		/**
		 * @public
		 * @abstract
		 * @return {function(object):SidebarBaseTabContent}
		 */
		getView()
		{}

		/**
		 * @protected
		 * @abstract
		 * @return {SidebarDataProvider}
		 */
		createDataProvider()
		{}
	}

	module.exports = {
		SidebarBaseTab,
		SidebarBaseTabContent,
		SidebarBaseTabListContent,
		SidebarBaseTabDummyContent,
		SidebarTabListItemModel,
	};
});
