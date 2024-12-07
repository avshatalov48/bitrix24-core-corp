/**
 * @module layout/list-view-queue-worker
 */
jn.define('layout/list-view-queue-worker', (require, exports, module) => {
	/**
	 * @class ListViewQueueWorker
	 */
	const DEFAULT_ANIMATION = 'automatic';

	class ListViewQueueWorker
	{
		constructor()
		{
			this.queueList = [];

			this.inProgress = false;
			this.listViewRef = null;
			this.result = new Set();
		}

		/**
		 * @return ListViewMethods
		 */
		getListViewRef()
		{
			return this.listViewRef;
		}

		/**
		 * @param {ListView} ref
		 */
		setListViewRef(ref)
		{
			this.listViewRef = ref;
		}

		/**
		 * @param {string} key
		 * @return {Object.<{section: number, index: number}>}
		 */
		getElementPosition(key)
		{
			return this.listViewRef.getElementPosition(key);
		}

		/**
		 * @param {number} section
		 * @param {index} index
		 * @param {boolean} animated
		 * @param {string} position
		 * @return {void}
		 */
		scrollTo(section, index, animated = false, position = 'middle')
		{
			return this.listViewRef.scrollTo(section, index, animated, position);
		}

		/**
		 * @param {boolean} animated
		 * @return {void}
		 */
		scrollToBegin(animated = false)
		{
			return this.listViewRef.scrollToBegin(animated);
		}

		/**
		 * @param {ListViewRow[]} items
		 * @param {number} sectionIndex
		 * @param {number} elementIndex
		 * @param {ListViewAnimate} animation
		 * @return {Promise<ListViewQueueWorker>}
		 */
		insertRows(items = [], sectionIndex = 0, elementIndex = 0, animation = DEFAULT_ANIMATION)
		{
			const preparedItems = this.prepareAddArray(items);
			if (preparedItems.length === 0)
			{
				return Promise.resolve();
			}

			return this.add({
				name: 'insertRows',
				task: () => this.listViewRef.insertRows(preparedItems, sectionIndex, elementIndex, animation),
			}).run();
		}

		/**
		 * @param {ListViewRow[]} items
		 * @param {ListViewAnimate} animation
		 * @return {Promise<ListViewQueueWorker>}
		 */
		appendRows(items = [], animation = DEFAULT_ANIMATION)
		{
			const preparedItems = this.prepareAddArray(items);
			if (preparedItems.length === 0)
			{
				return Promise.resolve();
			}

			return this.add({
				name: 'appendRows',
				task: () => this.listViewRef.appendRows(preparedItems, animation),
			}).run();
		}

		/**
		 * @param {ListViewRow[]} items
		 * @param {ListViewAnimate} animation
		 * @param {boolean} shouldRender
		 * @return {Promise<ListViewQueueWorker>}
		 */
		updateRows(items = [], animation = 'automatic', shouldRender = true)
		{
			const preparedItems = [...this.prepareAddArray(items)];

			if (preparedItems.length === 0)
			{
				return Promise.resolve();
			}

			return this.add({
				name: 'updateRows',
				task: () => this.listViewRef.updateRows(preparedItems, animation, shouldRender),
			}).run();
		}

		/**
		 * @param {String} key
		 * @param {Object} item
		 * @param {Boolean} animation
		 * @param {Boolean} shouldRender
		 * @return {Promise<ListViewQueueWorker>}
		 */
		updateRowByKey(key = null, item = null, animation = false, shouldRender = true)
		{
			if (!key || !item)
			{
				return Promise.reject();
			}

			return this.add({
				name: 'updateRowByKey',
				task: () => this.listViewRef.updateRowByKey(key, item, animation, shouldRender),
			}).run();
		}

		/**
		 * @public
		 * @param {string[]}keys
		 * @param {ListViewAnimate} animation
		 * @return {Promise<ListViewQueueWorker>}
		 */
		deleteRowsByKeys(keys = [], animation = DEFAULT_ANIMATION)
		{
			const preparedKeys = this.prepareAddArray(keys);

			return this.add({
				name: 'deleteRowsByKeys',
				task: () => new Promise((resolve) => {
					this.listViewRef.deleteRowsByKeys(preparedKeys, animation, () => {
						resolve(keys);
					});
				}),
			}).run();
		}

		/**
		 * @public
		 * @param {number} section
		 * @param {number} index
		 * @param {ListViewAnimate} animation
		 * @return {Promise<ListViewQueueWorker>}
		 */
		deleteRow(section = 0, index = 0, animation = DEFAULT_ANIMATION)
		{
			return this.add({
				name: 'deleteRow',
				task: () => new Promise((resolve) => {
					this.listViewRef.deleteRow(section, index, animation, resolve);
				}),
			}).run();
		}

		/**
		 * @public
		 * @param {Function} promisesFn
		 * @return {ListViewQueueWorker}
		 */
		add(promisesFn)
		{
			this.queueList.push(promisesFn);

			return this;
		}

		/**
		 * @public
		 * @return {Promise<void>}
		 */
		async run()
		{
			if (!this.inProgress && this.queueList.length > 0)
			{
				this.inProgress = true;
				const executableTask = this.queueList.shift();
				const promiseResult = await executableTask.task().catch(console.error);
				this.result.add({ name: executableTask.name, result: promiseResult });
				this.inProgress = false;

				return this.run();
			}

			return Promise.resolve(this.#getResult(true));
		}

		/**
		 * @private
		 * @param values
		 * @return {Function[]}
		 */
		prepareAddArray(values)
		{
			return Array.isArray(values) ? values : [values];
		}

		#clearResult()
		{
			this.result.clear();
		}

		/**
		 * @param {boolean} clear
		 * @returns {Object[]}
		 */
		#getResult(clear = false)
		{
			const result = [...this.result.values()];

			if (clear)
			{
				this.#clearResult();
			}

			return result;
		}
	}

	module.exports = { ListViewQueueWorker };
});
