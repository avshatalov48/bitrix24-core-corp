/**
 * @module tasks/layout/simple-list/items/task-redux
 */
jn.define('tasks/layout/simple-list/items/task-redux', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Base } = require('layout/ui/simple-list/items/base');
	const { mergeImmutable } = require('utils/object');
	const { withPressed } = require('utils/color');
	const { TaskContentView } = require('tasks/layout/simple-list/items/task-redux/task-content');

	class Task extends Base
	{
		constructor(props)
		{
			super(props);

			this.styles = mergeImmutable(this.styles, styles);
		}

		renderItemContent()
		{
			return TaskContentView({
				id: this.props.item.id,
				testId: this.props.testId,
				itemLayoutOptions: this.props.itemLayoutOptions,
				showBorder: this.props.item.showBorder,
				isLastPinned: this.props.item.isLastPinned,
			});
		}

		blink(callback = null, showUpdated = true)
		{
			if (typeof callback === 'function')
			{
				callback();
			}
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

	const styles = {
		wrapper: {
			paddingBottom: 0,
			backgroundColor: AppTheme.colors.bgContentPrimary,
		},
		item: {
			position: 'relative',
			backgroundColor: withPressed(AppTheme.colors.bgContentPrimary),
		},
	};

	module.exports = { Task };
});
