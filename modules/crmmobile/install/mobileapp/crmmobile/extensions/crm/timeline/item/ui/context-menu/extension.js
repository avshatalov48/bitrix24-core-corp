/**
 * @module crm/timeline/item/ui/context-menu
 */
jn.define('crm/timeline/item/ui/context-menu', (require, exports, module) => {
	const { TimelineButtonVisibilityFilter, TimelineButtonSorter } = require('crm/timeline/item/ui/styles');
	const { clone } = require('utils/object');
	const { ContextMenu } = require('layout/ui/context-menu');

	const nothing = () => {};

	/**
	 * @class TimelineItemContextMenu
	 */
	class TimelineItemContextMenu
	{
		/**
		 * @param {TimelineContextMenuItem[]} items
		 * @param {Function} onAction
		 * @param {boolean} isReadonly
		 */
		constructor({ items = [], onAction = nothing, isReadonly = false })
		{
			/** @type {ContextMenu|null} */
			this.menuInstance = null;

			/** @type {boolean} */
			this.isReadonly = isReadonly;

			/** @type {TimelineContextMenuItem[]} */
			this.items = this.prepareItems(items);

			/** @type {Function} */
			this.onAction = onAction;
		}

		/**
		 * @private
		 * @param {TimelineContextMenuItem[]} rawItems
		 * @return {TimelineContextMenuItem[]}
		 */
		prepareItems(rawItems = [])
		{
			return clone(rawItems)
				.filter((item) => TimelineButtonVisibilityFilter(item, this.isReadonly) && item.title !== '')
				.sort(TimelineButtonSorter)
				.map((item) => {
					if (item.menu && item.menu.items)
					{
						item.menu = this.prepareItems(Object.values(item.menu.items));
					}

					return item;
				});
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		hasItems()
		{
			return this.items.length > 0;
		}

		/**
		 * @public
		 */
		open()
		{
			return this.openItems(this.items);
		}

		/**
		 * @private
		 * @param {TimelineContextMenuItem[]} items
		 */
		openItems(items)
		{
			this.menuInstance = new ContextMenu({
				actions: this.prepareActions(items),
				testId: 'TimelineItem',
				params: {
					showCancelButton: true,
					showActionLoader: false,
				},
			});

			void this.menuInstance.show();
		}

		/**
		 * @private
		 * @param {TimelineContextMenuItem[]} items
		 * @return {object}
		 */
		prepareActions(items = [])
		{
			return items.map((item) => ({
				id: item.id || Random.getString(4),
				title: item.title,
				subTitle: '',
				// data: { svgIcon: `<svg></svg>` },
				onClickCallback: () => {
					this.menuInstance.close(() => {
						if (item.menu)
						{
							if (item.menu.length > 0)
							{
								this.openItems(item.menu);
							}

							return;
						}
						this.onItemClick(item.action);
					});

					return Promise.resolve({ closeMenu: false });
				},
			}));
		}

		onItemClick(action)
		{
			if (this.onAction)
			{
				this.onAction(action);
			}
		}
	}

	module.exports = { TimelineItemContextMenu };
});
