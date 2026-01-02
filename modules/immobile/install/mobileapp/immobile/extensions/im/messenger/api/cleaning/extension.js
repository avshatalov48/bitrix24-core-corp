/**
 * @module im/messenger/api/cleaning
 */
jn.define('im/messenger/api/cleaning', (require, exports, module) => {
	const { Type } = require('type');
	const { EntityReady } = require('entity-ready');
	const {
		EventType,
		ComponentCode,
		WaitingEntity,
	} = require('im/messenger/const');
	const { createPromiseWithResolvers } = require('im/messenger/lib/utils');

	/**
	 * @description Clear the messenger database and restart the application
	 */
	async function clearDatabaseAndRestart()
	{
		await EntityReady.wait(WaitingEntity.chat);

		const {
			promise,
			resolve,
			reject,
		} = createPromiseWithResolvers();

		registerClearDatabaseResultHandler(resolve, reject);
		sendClearDatabaseEvent();

		return promise;
	}

	function sendClearDatabaseEvent()
	{
		BX.postComponentEvent(EventType.messenger.clearDatabase, [], ComponentCode.imMessenger);
	}

	function registerClearDatabaseResultHandler(successHandler, errorHandler)
	{
		const handler = ({ errorText }) => {
			BX.removeCustomEvent(EventType.messenger.clearDatabaseResult, handler);
			if (Type.isStringFilled(errorText))
			{
				errorHandler(new Error(errorText));

				return;
			}

			successHandler();
		};

		BX.addCustomEvent(EventType.messenger.clearDatabaseResult, handler);

		return handler;
	}

	module.exports = {
		clearDatabaseAndRestart,
	};
});
