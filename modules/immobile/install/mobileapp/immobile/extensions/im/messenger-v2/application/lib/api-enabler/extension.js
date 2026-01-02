/**
 * @module im/messenger-v2/application/lib/api-enabler
 */
jn.define('im/messenger-v2/application/lib/api-enabler', (require, exports, module) => {
	const { MemoryStorage } = require('native/memorystore');
	const { EntityReady } = require('entity-ready');
	const { WaitingEntity } = require('im/messenger/const');

	async function enableMessengerApi()
	{
		const memoryStorage = new MemoryStorage('immobile::external-api');
		await memoryStorage.set('apiVersion', 2);

		EntityReady.addCondition(WaitingEntity.externalApi, () => true);
		EntityReady.ready(WaitingEntity.externalApi);
	}

	module.exports = { enableMessengerApi };
});
