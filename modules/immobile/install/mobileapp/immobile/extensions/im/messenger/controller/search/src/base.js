/**
 * @module im/messenger/controller/search/base
 */
jn.define('im/messenger/controller/search/base', (require, exports, module) => {

	const { Type } = require('type');
	const { DialogSelector } = require('im/messenger/controller/dialog-selector');

	class BaseSearchController
	{
		/**
		 *
		 * @param {Object} collectionView
		 * @param {Function} collectionView.setItems
		 */
		constructor(collectionView)
		{
			if (!Type.isFunction(collectionView.setItems))
			{
				throw new TypeError('The passed object has no setItems method');
			}
			this.collectionView = collectionView;
			this.adapter = this.getAdapter();
			this.selector = new DialogSelector({
				view: this.adapter,
				entities: this.getSearchEntities(),
				onRecentResult: () => {},
			});
		}

		getAdapter()
		{
			throw new Error('Implement method in child class');
		}

		getSearchEntities()
		{
			throw new Error('Implement method in child class');
		}

		open()
		{
			this.selector.open();
		}

		/**
		 *
		 * @param {string} text
		 */
		setSearchText(text)
		{
			const searchText = text.trim();
			if (searchText === '')
			{
				return;
			}

			this.adapter.onUserTypeText({ text: searchText });
		}
	}

	module.exports = { BaseSearchController };
});
