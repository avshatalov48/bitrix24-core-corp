/**
 * @module im/messenger/api/api-version
 */
jn.define('im/messenger/api/api-version', (require, exports, module) => {
	const { MemoryStorage } = require('native/memorystore');
	const { EntityReady } = require('entity-ready');
	const { WaitingEntity } = require('im/messenger/const');

	async function getApiVersion()
	{
		await EntityReady.wait(WaitingEntity.externalApi);
		const memoryStorage = new MemoryStorage('immobile::external-api');

		return memoryStorage.get('apiVersion');
	}

	module.exports = { getApiVersion };
});
