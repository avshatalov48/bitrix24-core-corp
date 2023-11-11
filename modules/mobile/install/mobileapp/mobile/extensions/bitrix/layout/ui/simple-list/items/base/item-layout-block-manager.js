/**
 * @module layout/ui/simple-list/items/base/item-layout-block-manager
 */
jn.define('layout/ui/simple-list/items/base/item-layout-block-manager', (require, exports, module) => {
	/**
	 * @class ItemLayoutBlockManager
	 */
	class ItemLayoutBlockManager
	{
		constructor(options = {})
		{
			this.options = options;
		}

		can(...params)
		{
			return params.every((param) => {
				return (this.options[param] || false);
			});
		}
	}

	module.exports = { ItemLayoutBlockManager };
});
