/**
 * @module im/messenger/lib/clear-database-handler
 */
jn.define('im/messenger/lib/clear-database-handler', (require, exports, module) => {
	const { EventType } = require('im/messenger/const');
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');

	function clearDatabaseHandler()
	{
		try
		{
			serviceLocator.get('core').getRepository().drop();
			BX.postComponentEvent(EventType.messenger.clearDatabaseResult, [{
				success: true,
				errorText: '',
			}]);

			// reboot is mandatory in the current architecture
			Application.relogin();
		}
		catch (error)
		{
			BX.postComponentEvent(EventType.messenger.clearDatabaseResult, [{
				success: false,
				errorText: `im.messenger: drop database error: ${error.name}: ${error.message}`,
			}]);
		}
	}

	module.exports = {
		clearDatabaseHandler,
	};
});
