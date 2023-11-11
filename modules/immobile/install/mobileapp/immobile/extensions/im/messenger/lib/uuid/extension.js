/**
 * @module im/messenger/lib/uuid
 */
jn.define('im/messenger/lib/uuid', (require, exports, module) => {
	const { Uuid } = require('utils/uuid');

	/**
	 * @class UuidManager
	 */
	class UuidManager
	{
		constructor()
		{
			this.actionIds = new Set();
		}

		getActionUuid()
		{
			const uuid = Uuid.getV4();
			this.actionIds.add(uuid);

			return uuid;
		}

		hasActionUuid(uuid)
		{
			return this.actionIds.has(uuid);
		}

		removeActionUuid(uuid)
		{
			this.actionIds.delete(uuid);
		}
	}

	module.exports = { UuidManager: new UuidManager() };
});