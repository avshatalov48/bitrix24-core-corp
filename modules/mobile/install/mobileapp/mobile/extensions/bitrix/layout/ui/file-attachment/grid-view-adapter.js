/**
 * @module layout/ui/file-attachment/grid-view-adapter
 */
jn.define('layout/ui/file-attachment/grid-view-adapter', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { isEqual } = require('utils/object');
	const { OptimizedGridView } = require('layout/ui/optimized-grid-view');

	/**
	 * @typedef {{ id: string|number }} ContainsId
	 * @typedef {{ id: string|number, type: string, key: string|number }} GridViewItem
	 */

	class GridViewAdapter extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			const items = (props.items || []).map((item) => this.prepareItem(item));

			this.state = {
				items,
			};

			this.gridViewRef = null;
			this.queue = [];
			this.queueProgress = false;
		}

		/**
		 * Completely disable component auto-update. Instead, we will update grid items manually.
		 * @return {boolean}
		 */
		shouldComponentUpdate()
		{
			return false;
		}

		/**
		 * Add/update grid items logic
		 * @param {object} props
		 */
		componentWillReceiveProps(props)
		{
			const items = (props.items || []).map((item) => this.prepareItem(item));
			this.queue.push({ items });
			this.processQueue();
		}

		processQueue()
		{
			if (this.queueProgress || this.queue.length === 0)
			{
				return;
			}

			this.queueProgress = true;

			const { items } = this.queue.shift();

			const { addingItems, updatingItems, removingItems, replacingItems } = this.makePatch(items);

			void this.replaceItems(replacingItems)
				.then(() => this.removeItems(removingItems))
				.then(() => this.addItems(addingItems))
				.then(() => this.updateItems(updatingItems))
				.finally(() => {
					this.queueProgress = false;
					this.processQueue();
				});
		}

		/**
		 * @private
		 * @param {GridViewItem[]} items
		 * @return {{
		 * 	addingItems: GridViewItem[],
		 * 	updatingItems: GridViewItem[],
		 * 	removingItems: GridViewItem[],
		 * 	replacingItems: [GridViewItem, GridViewItem][],
		 * 	}}
		 */
		makePatch(items)
		{
			const itemsBefore = {};
			const itemsAfter = {};

			const addingItems = [];
			const updatingItems = [];
			const removingItems = [];

			this.state.items.forEach((item) => {
				itemsBefore[item.id] = item;
			});

			items.forEach((item) => {
				itemsAfter[item.id] = item;
				if (itemsBefore[item.id])
				{
					if (!isEqual(itemsBefore[item.id], item))
					{
						updatingItems.push(item);
					}
				}
				else
				{
					addingItems.push(item);
				}
			});

			this.state.items.forEach((item) => {
				if (!itemsAfter[item.id])
				{
					removingItems.push(item);
				}
			});

			if (
				this.state.items.length === 1
				&& removingItems.length === 1
				&& addingItems.length === 1
				&& updatingItems.length === 0
			)
			{
				return {
					addingItems: [],
					updatingItems: [],
					removingItems: [],
					replacingItems: [
						[removingItems[0], addingItems[0]],
					],
				};
			}

			return {
				addingItems,
				updatingItems,
				removingItems,
				replacingItems: [],
			};
		}

		/**
		 * @param {[GridViewItem, GridViewItem][]} replacements
		 * @return {Promise}
		 */
		replaceItems(replacements)
		{
			if (replacements.length === 0 || !this.gridViewRef)
			{
				return Promise.resolve();
			}

			const sectionIndex = 0;
			const animationType = 'automatic';

			const results = replacements.map(([searchItem, replaceItem]) => new Promise((resolve) => {
				const index = this.state.items.findIndex((el) => el.id === searchItem.id);
				if (index > -1)
				{
					this.state.items[index] = replaceItem;
					this.gridViewRef.deleteRow(sectionIndex, index, animationType);
					this.gridViewRef
						.insertRows([replaceItem], sectionIndex, index, animationType)
						.finally(resolve);
				}
			}));

			return Promise.all(results);
		}

		/**
		 * @private
		 * @param {GridViewItem[]} items
		 * @return {Promise}
		 */
		removeItems(items)
		{
			if (items.length === 0)
			{
				return Promise.resolve();
			}

			const sectionIndex = 0;
			const animationType = 'automatic';

			const results = items.map((item) => new Promise((resolve) => {
				const index = this.state.items.findIndex((el) => el.id === item.id);
				this.state.items.splice(index, 1);
				this.useGridView()
					.then((gridView) => {
						gridView.deleteRow(sectionIndex, index, animationType, resolve);
					})
					.catch(resolve);
			}));

			return Promise.all(results);
		}

		/**
		 * @private
		 * @param {GridViewItem[]} items
		 * @return {Promise}
		 */
		addItems(items)
		{
			if (items.length === 0)
			{
				return Promise.resolve();
			}

			return new Promise((resolve) => {
				items.forEach((item) => this.state.items.push(item));
				void this.useGridView()
					.then((gridView) => gridView.appendRows(items))
					.finally(resolve);
			});
		}

		/**
		 * @private
		 * @param {GridViewItem[]} items
		 * @return {Promise}
		 */
		updateItems(items)
		{
			if (items.length === 0)
			{
				return Promise.resolve();
			}

			return new Promise((resolve) => {
				items.forEach((item) => {
					const index = this.state.items.findIndex((el) => el.id === item.id);
					if (index > -1)
					{
						this.state.items[index] = item;
					}
				});
				void this.useGridView()
					.then((gridView) => gridView.updateRows(items))
					.finally(resolve);
			});
		}

		/**
		 * @public
		 * @param {number} itemIndex
		 * @return {Promise}
		 */
		deleteRow(itemIndex)
		{
			const sectionIndex = 0;
			const animationType = 'automatic';

			return new Promise((resolve, reject) => {
				this.useGridView()
					.then((gridView) => {
						gridView.deleteRow(sectionIndex, itemIndex, animationType, () => {
							this.state.items.splice(itemIndex, 1);
							resolve();
						});
					})
					.catch(reject);
			});
		}

		/**
		 * @public
		 */
		scrollToBottom()
		{
			const sectionIndex = 0;
			const itemIndex = this.state.items.length - 1;
			const animate = true;

			this.useGridView()
				.then((gridView) => gridView.scrollTo(sectionIndex, itemIndex, animate))
				.catch(console.error);
		}

		/**
		 * @private
		 * @param {ContainsId} source
		 * @return {GridViewItem}
		 */
		prepareItem(source)
		{
			return {
				type: 'default',
				key: source.id,
				...source,
			};
		}

		/**
		 * @private
		 * @return {Promise}
		 */
		useGridView()
		{
			return new Promise((resolve, reject) => {
				return this.gridViewRef ? resolve(this.gridViewRef) : reject();
			});
		}

		render()
		{
			return OptimizedGridView({
				ref: (ref) => {
					this.gridViewRef = ref;
				},
				style: {
					flex: 1,
					paddingTop: 12,
					backgroundColor: AppTheme.colors.bgContentPrimary,
				},
				params: { orientation: 'vertical', rows: this.props.rowsCount },
				data: [{ items: this.state.items }],
				renderItem: this.props.renderItem,
			});
		}
	}

	module.exports = { GridViewAdapter };
});
