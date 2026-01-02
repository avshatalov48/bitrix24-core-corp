/* eslint-disable flowtype/require-return-type */

/**
 * @module im/messenger/cache/shared-storage/recent
 */
jn.define('im/messenger/cache/shared-storage/recent', (require, exports, module) => {
	const { throttle } = require('utils/function');
	const { uniqBy } = require('utils/array');

	const { getLogger } = require('im/messenger/lib/logger');
	const { Cache } = require('im/messenger/cache/base');

	const logger = getLogger('recent--cache');

	/**
	 * @class RecentViewCache
	 */
	class RecentViewCache extends Cache
	{
		constructor(name)
		{
			super({
				name,
			});

			this.save = throttle(this.save, 2000, this);
		}

		/**
		 * @param {Array<JNListWidgetSectionItem>} sections
		 * @param {Array<RecentWidgetItem|RecentItem>} itemList
		 */
		save(sections, itemList)
		{
			const uniqueItemList = uniqBy(itemList, (item) => String(item.id));

			const state = {
				sections,
				items: uniqueItemList
					.filter((item) => item.id !== 'loadNextPage' && item.id !== 'loading' && item.sectionCode !== 'call')
					.sort(this.sortItemList)
					.slice(0, 15),
			};
			logger.log(`${this.constructor.name}.save:`, state);

			return super.save(state);
		}

		sortItemList(a, b)
		{
			if (a.sectionCode !== 'pinned' && b.sectionCode === 'pinned')
			{
				return 1;
			}

			if (a.sectionCode === 'pinned' && b.sectionCode !== 'pinned')
			{
				return -1;
			}

			if (a.sortValues?.order && b.sortValues?.order)
			{
				return b.sortValues.order - a.sortValues.order;
			}

			return 0;
		}
	}

	module.exports = {
		RecentViewCache,
	};
});
