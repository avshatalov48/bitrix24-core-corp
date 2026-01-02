/**
 * @module tasks/layout/task/view-new/ui/views-count
 */
jn.define('tasks/layout/task/view-new/ui/views-count', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { Text6 } = require('ui-system/typography/text');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { ViewerListManager } = require('layout/ui/viewer-list');
	const { selectByTaskIdOrGuid } = require('tasks/statemanager/redux/slices/tasks');
	const { Moment } = require('utils/date');
	const { connect } = require('statemanager/redux/connect');
	const { FriendlyDate } = require('layout/ui/friendly-date');
	const { FormatterTypes } = require('layout/ui/friendly-date/formatter-factory');

	const EntityType = {
		TASK: 'TASK',
	};

	class ViewsCount extends LayoutComponent
	{
		get #taskId()
		{
			return this.props.taskId;
		}

		get #count()
		{
			return this.props.count || null;
		}

		renderDate(data)
		{
			if (!data)
			{
				return null;
			}

			const moment = Moment.createFromTimestamp(data.viewTimestamp);

			return new FriendlyDate({
				moment,
				showTime: true,
				useTimeAgo: true,
				futureAllowed: false,
				formatType: FormatterTypes.HUMAN_DATE,
				style: {
					color: Color.base3.toHex(),
				},
			});
		}

		openViewerList()
		{
			ViewerListManager.open({
				testId: 'viewer-list',
				entityId: this.#taskId,
				entityType: EntityType.TASK,
				renderCustomDescription: this.renderDate,
			});
		}

		render()
		{
			return View(
				{
					testId: `${this.#taskId}_ViewsCountContainer`,
					style: Styles.counter,
					onClick: () => this.openViewerList(),
				},
				IconView({
					size: 20,
					icon: Icon.OBSERVER,
					color: Color.base5,
				}),
				Text6({
					testId: `${this.#taskId}_ViewsCountValue`,
					text: Number(this.#count) > 1 ? String(this.#count) : '1',
					style: Styles.counterText,
				}),
			);
		}
	}

	const Styles = {
		counter: {
			flexDirection: 'row',
			alignItems: 'center',
			marginLeft: Indent.M.toNumber(),
		},
		counterText: {
			color: Color.base5.toHex(),
			marginLeft: Indent.XS.toNumber(),
		},
	};

	const mapStateToProps = (state, { taskId }) => {
		const task = selectByTaskIdOrGuid(state, taskId);

		return {
			count: task?.viewsCount || 1,
		};
	};

	module.exports = {
		ViewsCount: connect(mapStateToProps)(ViewsCount),
	};
});
