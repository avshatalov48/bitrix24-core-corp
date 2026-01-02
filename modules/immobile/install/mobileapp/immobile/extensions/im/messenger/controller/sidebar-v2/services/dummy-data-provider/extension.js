/**
 * @todo tmp remove
 * @module im/messenger/controller/sidebar-v2/services/dummy-data-provider
 */
jn.define('im/messenger/controller/sidebar-v2/services/dummy-data-provider', (require, exports, module) => {
	const { SidebarDataProvider } = require('im/messenger/controller/sidebar-v2/services/data-provider');

	class SidebarTabDummyDataProvider extends SidebarDataProvider
	{
		getInitialQueryMethod()
		{
			return '';
		}

		getInitialQueryParams()
		{
			return {};
		}

		getInitialQueryHandler()
		{
			return () => {};
		}
	}

	module.exports = { SidebarTabDummyDataProvider };
});
