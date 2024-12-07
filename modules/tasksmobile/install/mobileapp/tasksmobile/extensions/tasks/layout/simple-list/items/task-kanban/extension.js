/**
 * @module tasks/layout/simple-list/items/task-kanban
 */
jn.define('tasks/layout/simple-list/items/task-kanban', (require, exports, module) => {
	const { Base } = require('layout/ui/simple-list/items/base');
	const { mergeImmutable } = require('utils/object');
	const { withPressed } = require('utils/color');
	const { TaskContentView } = require('tasks/layout/simple-list/items/task-kanban/src/task-kanban-content');

	class TaskKanban extends Base
	{
		constructor(props)
		{
			super(props);

			this.onContextMenuClick = this.onContextMenuClick.bind(this);
		}

		getStyles()
		{
			return mergeImmutable(super.getStyles(), {
				wrapper: {
					paddingBottom: 12,
					backgroundColor: this.colors.bgPrimary,
				},
				item: {
					position: 'relative',
					backgroundColor: withPressed(this.colors.bgContentPrimary),
					borderRadius: 12,
				},
			});
		}

		renderItemContent()
		{
			const {
				onChangeItemStage,
				view,
				projectId,
				ownerId,
			} = this.params;

			return TaskContentView({
				onChangeItemStage,
				view,
				projectId,
				ownerId,
				id: this.props.item.id,
				testId: this.props.testId,
				menuViewRef: this.props.menuViewRef,
				itemLayoutOptions: this.props.itemLayoutOptions,
				onContextMenuClick: this.onContextMenuClick,
				layout: this.props.layout,
			});
		}

		onContextMenuClick()
		{
			const { item } = this.props;

			this.onItemLongClick(item.id, item.data, this.params);
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

	module.exports = { TaskKanban };
});
