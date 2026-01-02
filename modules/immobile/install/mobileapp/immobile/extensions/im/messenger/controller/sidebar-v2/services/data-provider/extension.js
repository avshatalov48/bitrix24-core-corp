/**
 * @module im/messenger/controller/sidebar-v2/services/data-provider
 */
jn.define('im/messenger/controller/sidebar-v2/services/data-provider', (require, exports, module) => {
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { getLogger } = require('im/messenger/lib/logger');

	/**
	 * @class SidebarDataProvider
	 */
	class SidebarDataProvider
	{
		constructor(props)
		{
			this.props = props;

			this.store = serviceLocator.get('core').getStore();
			this.logger = getLogger(`SidebarV2.TabDataProvider.${this.constructor.name}`);
		}

		/**
		 * @public
		 * @abstract
		 * @return {string}
		 */
		getInitialQueryMethod()
		{}

		/**
		 * @public
		 * @abstract
		 * @return {object|undefined}
		 */
		getInitialQueryParams()
		{}

		/**
		 * @public
		 * @abstract
		 * @return {function|undefined}
		 */
		getInitialQueryHandler()
		{}

		/**
		 * @public
		 * @abstract
		 * @param {number} offset
		 * @return {Promise}
		 */
		loadPage(offset = 0)
		{
			return Promise.resolve();
		}
	}

	module.exports = { SidebarDataProvider };
});
