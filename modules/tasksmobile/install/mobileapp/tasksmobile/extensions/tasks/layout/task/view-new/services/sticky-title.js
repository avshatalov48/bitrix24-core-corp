/**
 * @module tasks/layout/task/view-new/services/sticky-title
 */
jn.define('tasks/layout/task/view-new/services/sticky-title', (require, exports, module) => {
	const store = require('statemanager/redux/store');
	const { selectByTaskIdOrGuid } = require('tasks/statemanager/redux/slices/tasks');

	class StickyTitle
	{
		constructor({ taskId, layout, defaultTitle })
		{
			this.taskId = taskId;
			this.layout = layout;
			this.defaultTitle = defaultTitle;
			this.currentTitle = defaultTitle;

			const task = selectByTaskIdOrGuid(store.getState(), this.taskId);
			this.taskName = task?.name || this.defaultTitle;
			this.scrollOffset = this.#calcScrollOffset();

			if (!this.#isSubscribed())
			{
				this.#subscribe();
			}
		}

		#calcScrollOffset()
		{
			const minOffset = 96;
			const maxOffset = 170;
			const symbolsInLine = 36;
			const linesCount = Math.round(this.taskName.length / symbolsInLine);
			const lineHeight = 24;
			const approxOffset = Math.max((linesCount * lineHeight), minOffset);

			return Math.min(approxOffset, maxOffset);
		}

		#subscribe()
		{
			this.cancelSubscription = store.subscribe(() => {
				const task = selectByTaskIdOrGuid(store.getState(), this.taskId);
				this.taskName = task?.name || this.defaultTitle;
				this.scrollOffset = this.#calcScrollOffset();
			});
		}

		/**
		 * @return {boolean}
		 */
		#isSubscribed()
		{
			return Boolean(this.cancelSubscription);
		}

		/**
		 * @public
		 */
		unsubscribe()
		{
			this.cancelSubscription?.();
			this.cancelSubscription = null;
		}

		/**
		 * @public
		 * @param {{ y: number }} contentOffset
		 */
		onScroll({ contentOffset })
		{
			const text = contentOffset.y > this.scrollOffset ? this.taskName : this.defaultTitle;
			const type = text === this.defaultTitle ? 'entity' : 'common';

			if (text !== this.currentTitle)
			{
				this.currentTitle = text;
				this.layout.setTitle({ text, type });
			}
		}
	}

	module.exports = { StickyTitle };
});
