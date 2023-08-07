/**
 * @module tasks/layout/task/fields/status
 */
jn.define('tasks/layout/task/fields/status', (require, exports, module) => {
	const { Loc } = require('loc');
	const { ActionMenu } = require('tasks/layout/task/actionMenu');
	const { ActionButton } = require('tasks/layout/task/fields/status/actionButton');
	const { BaseField } = require('layout/ui/fields/base');

	class Status extends LayoutComponent
	{
		static getStatusItems()
		{
			const locPrefix = 'TASKSMOBILE_LAYOUT_TASK_FIELDS_STATUS';

			return {
				[Task.statusList.pending]: {
					title: Loc.getMessage(`${locPrefix}_PENDING`),
					backgroundColor: '#55d0e0',
				},
				[Task.statusList.inprogress]: {
					title: Loc.getMessage(`${locPrefix}_IN_PROGRESS`),
					backgroundColor: '#9dcf00',
				},
				[Task.statusList.waitCtrl]: {
					title: Loc.getMessage(`${locPrefix}_SUPPOSEDLY_COMPLETED`),
					backgroundColor: '#ffa900',
				},
				[Task.statusList.completed]: {
					title: Loc.getMessage(`${locPrefix}_COMPLETED`),
					backgroundColor: '#a8adb4',
				},
				[Task.statusList.deferred]: {
					title: Loc.getMessage(`${locPrefix}_DEFERRED`),
					backgroundColor: '#a8adb4',
				},
			};
		}

		constructor(props)
		{
			super(props);

			this.state = {
				readOnly: props.readOnly,
				status: props.status,
				isTimerExisting: props.isTimerExisting,
				isTimerRunning: props.isTimerRunning,
				timeElapsed: props.timeElapsed,
				timeEstimate: props.timeEstimate,
			};
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				readOnly: props.readOnly,
				status: props.status,
				isTimerExisting: props.isTimerExisting,
				isTimerRunning: props.isTimerRunning,
				timeElapsed: props.timeElapsed,
				timeEstimate: props.timeEstimate,
			};
		}

		updateState(newState)
		{
			this.setState({
				readOnly: newState.readOnly,
				status: newState.status,
				isTimerExisting: newState.isTimerExisting,
				isTimerRunning: newState.isTimerRunning,
				timeElapsed: newState.timeElapsed,
				timeEstimate: newState.timeEstimate,
			});
		}

		render()
		{
			if (!this.actionMenu)
			{
				this.actionMenu = new ActionMenu({
					layoutWidget: this.props.parentWidget,
					task: this.props.task,
					possibleActions: [
						ActionMenu.action.startTimer,
						ActionMenu.action.pauseTimer,
						ActionMenu.action.start,
						ActionMenu.action.pause,
						ActionMenu.action.complete,
						ActionMenu.action.renew,
						ActionMenu.action.approve,
						ActionMenu.action.disapprove,
					],
				});
			}

			return View(
				{
					style: (this.props.style || {}),
				},
				new StatusField({
					readOnly: this.state.readOnly,
					showEditIcon: !this.state.readOnly,
					title: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_STATUS'),
					titlePosition: 'left',
					canFocusTitle: false,
					value: this.state.status,
					config: {
						deepMergeStyles: this.props.deepMergeStyles,
						statusItem: Status.getStatusItems()[this.state.status],
						task: this.props.task,
						isTimerExisting: this.state.isTimerExisting,
						isTimerRunning: this.state.isTimerRunning,
						timeElapsed: Number(this.state.timeElapsed),
						timeEstimate: Number(this.state.timeEstimate),
						balloonArrowDownUri: `${this.props.pathToImages}/tasksmobile-layout-task-balloon-arrow-down.png`,
					},
					testId: 'status',
					onContentClick: () => {
						if (!this.state.readOnly)
						{
							this.actionMenu.show();
						}
					},
				}),
			);
		}
	}

	class StatusField extends BaseField
	{
		static getImageUrl(imageUrl)
		{
			let result = imageUrl;

			if (result.indexOf(currentDomain) !== 0)
			{
				result = result.replace(`${currentDomain}`, '');
				result = (result.indexOf('http') === 0 ? result : `${currentDomain}${result}`);
			}

			return encodeURI(result);
		}

		renderContent()
		{
			const config = this.getConfig();

			return View(
				{
					style: {
						flex: 1,
						flexDirection: 'row',
						justifyContent: 'space-between',
					},
				},
				View(
					{
						style: this.styles.status,
					},
					Text({
						style: this.styles.value,
						text: config.statusItem.title,
						ellipsize: 'end',
						numberOfLines: 1,
					}),
					(!this.isReadOnly() && Image({
						style: {
							width: 14,
							height: 14,
							alignSelf: 'center',
							marginLeft: 2,
						},
						uri: StatusField.getImageUrl(this.getConfig().balloonArrowDownUri),
					})),
				),
				new ActionButton({
					isReadOnly: this.isReadOnly(),
					status: this.getValue(),
					task: config.task,
					isTimerExisting: config.isTimerExisting,
					isTimerRunning: config.isTimerRunning,
					timeElapsed: config.timeElapsed,
					timeEstimate: config.timeEstimate,
				}),
			);
		}

		getDefaultStyles()
		{
			const styles = super.getDefaultStyles();
			const config = this.getConfig();

			return {
				...styles,
				status: {
					flex: (config.isTimerExisting ? 1 : undefined),
					flexDirection: 'row',
					height: 22,
					borderRadius: 15,
					paddingLeft: 9,
					paddingRight: (this.isReadOnly() ? 9 : 5),
					paddingVertical: 2,
					backgroundColor: config.statusItem.backgroundColor,
					marginRight: 6,
				},
				value: {
					...styles.value,
					flex: (config.isTimerExisting ? 1 : undefined),
					fontSize: 12,
					fontColor: '#ffffff',
					fontWeight: '600',
					color: '#ffffff',
				},
			};
		}
	}

	module.exports = { Status };
});
