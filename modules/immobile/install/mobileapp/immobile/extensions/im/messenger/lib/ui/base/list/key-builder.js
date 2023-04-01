/**
 * @module im/messenger/lib/ui/base/list/key-builder
 */
jn.define('im/messenger/lib/ui/base/list/key-builder', (require, exports, module) => {

	class KeyBuilder
	{
		/**
		 *
		 * @param {Array<{id: string|number}>}itemList
		 */
		constructor(itemList = [])
		{
			this.collection = new Map();
			itemList.forEach(item => {
				this.collection.set(item.id, this.createKey(item.id));
			})
		}

		getKeyById(id)
		{
			const itemKey = this.collection.get(id);
			if (typeof itemKey !== 'undefined')
			{
				return itemKey;
			}

			this.collection.set(id, this.createKey(id));

			return this.collection.get(id);
		}

		createKey(id)
		{
			return this.collection.size.toString();
		}
	}

	module.exports = { KeyBuilder };
});