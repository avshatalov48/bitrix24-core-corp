/**
 * @module im/messenger/api/navigation
 */
jn.define('im/messenger/api/navigation', (require, exports, module) => {
	const { EventType } = require('im/messenger/const');

	const closeAll = () => {
		return new Promise((resolve, reject) => {
			const closeAllCompleteHandler = (wasClosedAll) => {
				BX.removeCustomEvent(EventType.navigation.closeAllComplete, closeAllCompleteHandler);

				if (wasClosedAll)
				{
					resolve();
				}
				else
				{
					reject(new Error('Navigation was not closed'));
				}
			};

			BX.addCustomEvent(EventType.navigation.closeAllComplete, closeAllCompleteHandler);
			BX.postComponentEvent(EventType.navigation.closeAll, []);
		});
	};

	module.exports = {
		closeAll,
	};
});
