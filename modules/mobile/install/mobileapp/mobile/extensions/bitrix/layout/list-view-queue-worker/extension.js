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
			this.listLiveRef = null;
		}

		/**
		 * @return ListView
		 */
		getListViewRef()
		{
			return this.listLiveRef;
		}

		/**
		 * @param {ListView} ref
		 */
		setListViewRef(ref)
		{
			this.listLiveRef = ref;
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

			return this.add({
				name: 'insertRows',
				task: () => this.listLiveRef.insertRows(preparedItems, sectionIndex, elementIndex, animation),
			}).run();
		}

		/**
		 * @param {ListViewRow[]} items
		 * @param {ListViewAnimate} animation
		 * @return {Promise<ListViewQueueWorker>}
		 */
		// eslint-disable-next-line default-param-last
		updateRows(items = [], animation = 'automatic')
		{
			const preparedItems = [...this.prepareAddArray(items)];

			return this.add({
				name: 'updateRows',
				task: () => this.listLiveRef.updateRows(preparedItems, animation),
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
					this.listLiveRef.deleteRowsByKeys(preparedKeys, animation, resolve);
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
		 * @return {void}
		 */
		run()
		{
			if (!this.inProgress && this.queueList.length > 0)
			{
				this.inProgress = true;
				const executablePromise = this.queueList.shift();

				executablePromise.task().then(() => {
					this.inProgress = false;

					return this.run();
				}).catch(() => {
					this.inProgress = false;
					throw new Error(`Error in queue execution ${executablePromise.name}`);
				});
			}

			return Promise.resolve();
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
	}

	module.exports = { ListViewQueueWorker };
});
