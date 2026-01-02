/**
 * @module im/messenger/controller/sidebar-v2/tabs/base/src/list-item-model
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/base/src/list-item-model', (require, exports, module) => {
	class SidebarTabListItemModel
	{
		constructor(data = {})
		{
			this.data = data;
		}

		/**
		 * @public
		 * @return {number}
		 */
		getId()
		{
			return this.data.id;
		}

		/**
		 * @public
		 * @return {string}
		 */
		getType()
		{
			return 'default';
		}

		/**
		 * @public
		 * @return {string}
		 */
		getKey()
		{
			return `key_${this.getId()}`;
		}

		/**
		 * @public
		 * @return {object}
		 */
		getData()
		{
			return this.data;
		}

		/**
		 * @public
		 * @return {{data: *, type: string, key: string}}
		 */
		toListView()
		{
			return {
				id: this.getId(),
				data: this.getData(),
				type: this.getType(),
				key: this.getKey(),
			};
		}
	}

	module.exports = { SidebarTabListItemModel };
});
