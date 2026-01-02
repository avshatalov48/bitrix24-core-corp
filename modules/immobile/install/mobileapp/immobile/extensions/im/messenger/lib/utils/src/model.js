/**
 * @module im/messenger/lib/utils/model
 */
jn.define('im/messenger/lib/utils/model', (require, exports, module) => {
	const { Type } = require('type');

	class ModelUtils
	{
		/**
		 * @typedef {Object} NormalizedPayload
		 * @property {Array<*>} itemList
		 * @property {string} [actionName]
		 *
		 * @param {Array<*>|Object} payload :
		 *   - Array<Object>
		 *   - one object with {itemList: Array, actionName?: string}
		 *   - simple one object
		 * @returns {NormalizedPayload}
		 */
		static normalizeItemListPayload(payload)
		{
			const strategies = {
				array: (p) => ({ itemList: p }),
				objectWithList: (p) => ({ itemList: p.itemList, actionName: p.actionName }),
				singleObject: (p) => ({ itemList: [p] }),
				default: () => {
					console.error('ModelUtils.normalizeItemListPayload: unknown payload format', payload);

					return { itemList: [] };
				},
			};

			if (Type.isArray(payload))
			{
				return strategies.array(payload);
			}

			if (Type.isPlainObject(payload))
			{
				return payload.itemList
					? strategies.objectWithList(payload)
					: strategies.singleObject(payload);
			}

			return strategies.default();
		}
	}

	module.exports = {
		ModelUtils,
	};
});
