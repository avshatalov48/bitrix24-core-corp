/**
 * @module im/messenger/lib/uuid-manager
 */
jn.define('im/messenger/lib/uuid-manager', (require, exports, module) => {
	const { Uuid } = require('utils/uuid');

	/** @type {UuidManager || null} */
	let instance = null;

	/**
	 * @class UuidManager
	 */
	class UuidManager
	{
		/**
		 * @private
		 */
		constructor()
		{
			this.actionIds = new Set();
		}

		static getInstance()
		{
			instance ??= new UuidManager();

			return instance;
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

	module.exports = { UuidManager };
});
