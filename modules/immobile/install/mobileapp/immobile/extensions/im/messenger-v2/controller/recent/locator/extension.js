/**
 * @module im/messenger-v2/controller/recent/locator
 */
jn.define('im/messenger-v2/controller/recent/locator', (require, exports, module) => {
	const { ServiceLocator } = require('im/messenger/lib/di/service-locator');
	/**
	 * @return {RecentLocator}
	 */
	function createLocator(id, ui)
	{
		const locator = new ServiceLocator();
		locator.add('id', id);
		locator.add('ui', ui);
		locator.add('emitter', new JNEventEmitter());

		return locator;
	}

	module.exports = { createLocator };
});
