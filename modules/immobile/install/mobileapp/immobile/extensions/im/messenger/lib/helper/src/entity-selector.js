/**
 * @module im/messenger/lib/helper/entity-selector
 */
jn.define('im/messenger/lib/helper/entity-selector', (require, exports, module) => {
	const { EntitySelectorElementType } = require('im/messenger/const');
	/**
	 * @class EntitySelectorHelper
	 */
	class EntitySelectorHelper
	{
		/**
		 * @param {Array<{id: number|string, type: string}>} entityList
		 */
		static getMemberList(entityList)
		{
			return entityList.map((element) => this.getEntityElement(element));
		}

		/**
		 * @param {Array<{id: number|string, type: string}>} entityList
		 */
		static getUserList(entityList)
		{
			const result = [];
			for (const entity of entityList)
			{
				if (entity.type === EntitySelectorElementType.user)
				{
					result.push([entity.type, entity.id]);
				}
			}

			return result;
		}

		/**
		 * @param {Array<number>} userList
		 */
		static createUserList(userList)
		{
			return userList.map((userId) => this.createUserElement(userId));
		}

		static createUserElement(userId)
		{
			return this.getEntityElement({ type: EntitySelectorElementType.user, id: userId });
		}

		/**
		 * @param {{id: number|string, type: string}} entity
		 */
		static getEntityElement(entity)
		{
			return [entity.type, entity.id];
		}
	}

	module.exports = { EntitySelectorHelper };
});
