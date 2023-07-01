/**
 * @module crm/timeline/stream/utils/patch
 */
jn.define('crm/timeline/stream/utils/patch', (require, exports, module) => {
	class Patch
	{
		/**
		 * @param {TimelineListViewItem[]} itemsBefore
		 * @param {TimelineListViewItem[]} itemsAfter
		 */
		constructor(itemsBefore = [], itemsAfter = [])
		{
			this.itemsBefore = itemsBefore;
			this.itemsAfter = itemsAfter;

			this.itemsBeforeKeys = {};
			this.itemsAfterKeys = {};
			this.itemsBefore.forEach((item) => {
				this.itemsBeforeKeys[item.key] = item;
			});
			this.itemsAfter.forEach((item) => {
				this.itemsAfterKeys[item.key] = item;
			});

			/** @type {TimelineListViewItem[]} */
			this.addedItems = [];

			/** @type {TimelineListViewItem[]} */
			this.removedItems = [];

			this.compare();
		}

		/**
		 * @private
		 */
		compare()
		{
			this.itemsBefore.forEach((item) => {
				if (!this.itemsAfterKeys[item.key])
				{
					this.removedItems.push(item);
				}
			});

			this.itemsAfter.forEach((item) => {
				if (!this.itemsBeforeKeys[item.key])
				{
					this.addedItems.push(item);
				}
			});
		}

		/**
		 * @public
		 * @return {TimelineListViewItem[]}
		 */
		getAddedItems()
		{
			return this.addedItems;
		}

		/**
		 * @public
		 * @return {TimelineListViewItem[]}
		 */
		getRemovedItems()
		{
			return this.removedItems;
		}

		/**
		 * @public
		 * @param {string} key
		 * @return {boolean}
		 */
		isItemMoved(key)
		{
			const indexBefore = this.itemsBefore.findIndex((itemAfter) => itemAfter.key === key);
			const indexAfter = this.itemsAfter.findIndex((itemAfter) => itemAfter.key === key);

			return (indexBefore !== indexAfter);
		}
	}

	module.exports = { Patch };
});
