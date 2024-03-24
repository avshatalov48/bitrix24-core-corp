/**
 * @module tasks/layout/task/fields/status
 */
jn.define('tasks/layout/task/fields/status', (require, exports, module) => {
	const { Loc } = require('loc');
	const { chevronDown } = require('assets/common');
	const AppTheme = require('apptheme');
	const { BaseField } = require('layout/ui/fields/base');
	const { TaskStatus } = require('tasks/enum');
	const { ActionMenu } = require('tasks/layout/task/actionMenu');
	const { ActionButton } = require('tasks/layout/task/fields/status/actionButton');

	class Status extends LayoutComponent
	{
		static getStatusItems()
		{
			const locPrefix = 'TASKSMOBILE_LAYOUT_TASK_FIELDS_STATUS';

			return {
				[TaskStatus.PENDING]: {
					title: Loc.getMessage(`${locPrefix}_PENDING`),
					backgroundColor: AppTheme.colors.accentExtraAqua,
				},
				[TaskStatus.IN_PROGRESS]: {
					title: Loc.getMessage(`${locPrefix}_IN_PROGRESS`),
					backgroundColor: AppTheme.colors.accentMainSuccess,
				},
				[TaskStatus.SUPPOSEDLY_COMPLETED]: {
					title: Loc.getMessage(`${locPrefix}_SUPPOSEDLY_COMPLETED`),
					backgroundColor: AppTheme.colors.accentMainWarning,
				},
				[TaskStatus.COMPLETED]: {
					title: Loc.getMessage(`${locPrefix}_COMPLETED`),
					backgroundColor: AppTheme.colors.base4,
				},
				[TaskStatus.DEFERRED]: {
					title: Loc.getMessage(`${locPrefix}_DEFERRED`),
					backgroundColor: AppTheme.colors.base4,
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

			this.handleOnContentClick = this.handleOnContentClick.bind(this);
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

		handleOnContentClick()
		{
			const { readOnly } = this.state;

			if (!readOnly)
			{
				this.actionMenu.show();
			}
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
					},
					testId: 'status',
					onContentClick: this.handleOnContentClick,
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
				result = result.replace(currentDomain, '');
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
					!this.isReadOnly() && Image({
						style: {
							width: 14,
							height: 14,
							alignSelf: 'center',
							marginLeft: 2,
						},
						tintColor: AppTheme.colors.base8,
						svg: {
							content: chevronDown(AppTheme.colors.base8, { box: true }),
						},
					}),
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
					fontColor: AppTheme.colors.base8,
					fontWeight: '600',
					color: AppTheme.colors.base8,
				},
			};
		}
	}

	module.exports = { Status };
});
