/**
 * @module tasks/layout/simple-list/items/task-redux
 */
jn.define('tasks/layout/simple-list/items/task-redux', (require, exports, module) => {
	const { Base } = require('layout/ui/simple-list/items/base');
	const { mergeImmutable } = require('utils/object');
	const { withPressed } = require('utils/color');
	const { TaskContentView } = require('tasks/layout/simple-list/items/task-redux/task-content');

	class Task extends Base
	{
		taskContentRef = null;

		constructor(props)
		{
			super(props);
		}

		getStyles()
		{
			return mergeImmutable(super.getStyles(), {
				wrapper: {
					paddingBottom: 0,
					backgroundColor: this.colors.bgContentPrimary,
				},
				item: {
					position: 'relative',
					backgroundColor: withPressed(this.colors.bgContentPrimary),
				},
			});
		}

		renderItemContent()
		{
			const { testId, itemLayoutOptions, item } = this.props;

			return TaskContentView({
				forwardedRef: this.bindRef,
				testId,
				itemLayoutOptions,
				id: item.id,
				showBorder: item.showBorder,
				isLastPinned: item.isLastPinned,
			});
		}

		bindRef = (ref) => {
			this.taskContentRef = ref;
		};

		async blink(callback, showUpdated)
		{
			await this.taskContentRef?.blink();
		}

		/**
		 * @public
		 * @param callback
		 */
		setLoading(callback = null)
		{
			this.blink(callback);
		}

		/**
		 * @public
		 * @param callback
		 * @param blink
		 */
		dropLoading(callback = null, blink = true)
		{
			this.blink(callback);
		}
	}

	module.exports = { Task };
});
