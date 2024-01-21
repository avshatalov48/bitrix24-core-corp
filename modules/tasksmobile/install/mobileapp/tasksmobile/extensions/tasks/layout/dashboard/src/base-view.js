/**
 * @module tasks/layout/dashboard/base-view
 */
jn.define('tasks/layout/dashboard/base-view', (require, exports, module) => {
	/**
	 * @class TasksDashboardBaseView
	 * @abstract
	 */
	class TasksDashboardBaseView extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.bindRef = this.bindRef.bind(this);

			this.viewComponent = null;
		}

		/**
		 * @protected
		 * @param {object} ref
		 */
		bindRef(ref)
		{
			if (ref)
			{
				this.viewComponent = ref;
			}
		}

		isLoading()
		{
			if (this.viewComponent)
			{
				return this.viewComponent.isLoading();
			}

			return true;
		}

		/**
		 * @public
		 * @abstract
		 * return {KanbanStage|null}
		 */
		getActiveStage()
		{}

		/**
		 * @public
		 * @abstract
		 * return {boolean}
		 */
		isAllStagesDisplayed()
		{}

		/**
		 * @public
		 * @abstract
		 * @param {object[]} buttons
		 */
		updateTopButtons(buttons)
		{}

		/**
		 * @public
		 * @abstract
		 * @param {object} params
		 */
		reload(params = {})
		{}

		/**
		 * @public
		 * @returns {Object[]|null}
		 */
		getItems()
		{
			if (this.viewComponent)
			{
				return this.viewComponent.getItems();
			}

			return null;
		}

		/**
		 * @public
		 * @param {string|number} id
		 * @return {boolean}
		 */
		hasItem(id)
		{
			if (this.viewComponent)
			{
				return this.viewComponent.hasItem(id);
			}

			return false;
		}

		/**
		 * @public
		 * @param {string[]|number[]} ids
		 * @return {Promise}
		 */
		updateItems(ids = [])
		{
			const existingIds = ids.filter((id) => this.hasItem(id));
			if (existingIds.length === 0)
			{
				return Promise.resolve();
			}

			if (this.viewComponent)
			{
				return this.viewComponent.updateItems(existingIds);
			}

			return Promise.resolve();
		}

		/**
		 * @public
		 * @param {object[]} items
		 */
		updateItemsData(items)
		{
			const existingItems = items.filter((item) => this.hasItem(item.id));
			if (existingItems.length === 0)
			{
				return Promise.resolve();
			}

			if (this.viewComponent)
			{
				return this.viewComponent.updateItemsData(existingItems);
			}

			return Promise.resolve();
		}

		removeItems(items)
		{
			const existingItems = items.filter((item) => this.hasItem(item.id));
			if (existingItems.length === 0)
			{
				return Promise.resolve();
			}

			if (this.viewComponent)
			{
				return Promise.allSettled(items.map(({ id }) => this.viewComponent.removeItem(id)));
			}

			return Promise.resolve();
		}

		addItems(items)
		{
			const addedItems = items.filter((item) => !this.hasItem(item.id));
			if (addedItems.length === 0)
			{
				return Promise.resolve();
			}

			if (this.viewComponent)
			{
				return Promise.allSettled(addedItems.map(({ id }) => this.viewComponent.addItem(id)));
			}

			return Promise.resolve();
		}

		restoreItems(items)
		{
			const nonExistingItems = items.filter((item) => !this.hasItem(item.id));
			if (nonExistingItems.length === 0)
			{
				return Promise.resolve();
			}

			if (this.viewComponent)
			{
				return this.viewComponent.updateItemsData(nonExistingItems);
			}

			return Promise.resolve();
		}
	}

	module.exports = { TasksDashboardBaseView };
});
