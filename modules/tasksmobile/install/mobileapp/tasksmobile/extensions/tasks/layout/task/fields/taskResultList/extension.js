/**
 * @module tasks/layout/task/fields/taskResultList
 */
jn.define('tasks/layout/task/fields/taskResultList', (require, exports, module) => {
	const {TaskResult} = require('tasks/layout/task/fields/taskResultList/taskResult');
	const {Loc} = require('loc');

	class TaskResultList extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				showAll: false,
				resultList: props.resultList,
			};
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				showAll: this.state.showAll,
				resultList: props.resultList,
			};
		}

		updateState(newState)
		{
			this.setState({
				resultList: newState.resultList,
			});
		}

		render()
		{
			if (!this.state.resultList || !this.state.resultList.length)
			{
				return View({style: {display: 'none'}});
			}

			const isMore = (this.state.resultList.length > 1);
			const taskId = this.props.taskId;
			const parentWidget = this.props.parentWidget;

			return View(
				{
					style: {
						backgroundColor: '#eef2f4',
						paddingBottom: (isMore ? 18 : 12),
					},
				},
				new TaskResult({
					taskResult: this.state.resultList[0],
					taskId,
					parentWidget,
					isFirst: true,
				}),
				...(
					this.state.showAll
						? this.state.resultList.map((taskResult, index) => {
							return (index === 0 ? null : new TaskResult({taskResult, taskId, parentWidget}));
						})
						: []
				),
				(isMore && View(
					{
						style: {
							position: 'absolute',
							alignSelf: 'center',
							bottom: 6,
							backgroundColor: '#ffffff',
							borderWidth: 1,
							borderColor: '#1a6a737f',
							borderRadius: 20,
							paddingHorizontal: 12,
							paddingVertical: 4,
						},
						onClick: () => this.setState({showAll: !this.state.showAll}),
					},
					Text({
						style: {
							fontSize: 14,
							fontWeight: '500',
							color: '#99525c69',
						},
						text: (
							this.state.showAll
								? Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_TASK_RESULT_LIST_HIDE')
								: Loc.getMessage(
									'TASKSMOBILE_LAYOUT_TASK_FIELDS_TASK_RESULT_LIST_MORE',
									{'#NUMBER#': this.state.resultList.length - 1}
								)
						),
					}),
				)),
			);
		}
	}

	module.exports = {TaskResultList};
});