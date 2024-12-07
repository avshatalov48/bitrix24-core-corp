/**
 * @module bizproc/task/details/buttons
 */
jn.define('bizproc/task/details/buttons', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Alert } = require('alert');
	const { EventEmitter } = require('event-emitter');
	const { Loc } = require('loc');
	const { NotifyManager } = require('notify-manager');
	const { showToast, Position } = require('toast');

	const { PureComponent } = require('layout/pure-component');

	const { TaskErrorCode } = require('bizproc/task/task-constants');
	const { TaskButtons, DelegateButton } = require('bizproc/task/buttons');

	class TaskDetailsButtons extends PureComponent
	{
		/**
		 * @param {{}} props
		 * @param {boolean} props.isMyTask
		 * @param {boolean} props.isTaskCompleted
		 * @param {boolean} props.canDelegate
		 * @param {{}} props.task
		 * @param {string} props.uid
		 * @param {() => {}} props.onDelegateButtonClick
		 * @param {() => {}} props.onTimelineButtonClick
		 * @param {number} props.allTaskCount
		 * @param {{}} props.layout
		 * @param {boolean} props.showNotifications
		 * @param {TaskDetails} props.detailsRef
		 */
		constructor(props)
		{
			super(props);

			// eslint-disable-next-line no-undef
			this.uid = props.uid || Random.getString();
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);
		}

		get layout()
		{
			return this.props.layout;
		}

		get task()
		{
			return this.props.task;
		}

		get isMyTask()
		{
			return this.props.isMyTask;
		}

		get canShowTaskButtons()
		{
			return (
				this.isMyTask
				&& !this.props.isTaskCompleted
				&& this.task.buttons
				&& this.task.buttons.length > 0
			);
		}

		get canShowDelegateButton()
		{
			return !this.props.isTaskCompleted && this.props.canDelegate;
		}

		get canShowTimelineButton()
		{
			return this.isMyTask;
		}

		render()
		{
			const hasBeforeSeparatorButtons = this.canShowTaskButtons || (!this.isMyTask && this.canShowDelegateButton);
			const hasAfterSeparatorButtons = this.canShowTimelineButton || (this.isMyTask && this.canShowDelegateButton);

			return ScrollView(
				{
					style: { height: 64 },
					horizontal: true,
				},
				View(
					{
						style: {
							paddingTop: 16,
							paddingBottom: 12,
							flexDirection: 'row',
							alignContent: 'center',
							alignItems: 'center',
							paddingHorizontal: 11,
						},
						testId: 'MBP_TASK_DETAILS_HORIZONTAL_BUTTONS',
					},
					this.canShowTaskButtons && this.renderTaskButtons(),
					!this.isMyTask && this.canShowDelegateButton && this.renderDelegateButton(),
					hasBeforeSeparatorButtons && hasAfterSeparatorButtons && this.renderSeparator(),
					this.canShowTimelineButton && this.renderTimelineButton(),
					this.isMyTask && this.canShowDelegateButton && this.renderDelegateButton(),
				),
			);
		}

		renderTaskButtons()
		{
			return View(
				{
					style: {
						maxWidth: (device.screen.width) * 0.69,
					},
				},
				new TaskButtons({
					uid: this.uid,
					testId: 'TASK_DETAILS_BUTTONS',
					task: this.task,
					onBeforeAction: this.onBeforeTaskButtonAction.bind(this),
					onComplete: this.onTaskCompleted.bind(this),
					onFail: this.onTaskCompleteFailed.bind(this),
				}),
			);
		}

		async onBeforeTaskButtonAction(task, button)
		{
			await NotifyManager.showLoadingIndicator();

			if (!this.props.detailsRef)
			{
				return Promise.resolve(null);
			}

			let hasErrors = false;
			const data = await this.props.detailsRef.getFieldValues(button).catch(() => {
				hasErrors = true;
			});

			if (!hasErrors)
			{
				return Promise.resolve(data);
			}

			NotifyManager.hideLoadingIndicator(false);

			return Promise.reject();
		}

		onTaskCompleted(responseData, taskCompletionParams)
		{
			NotifyManager.hideLoadingIndicatorWithoutFallback();
			// eslint-disable-next-line no-undef
			Notify.showIndicatorSuccess({ hideAfter: 300 });

			this.customEventEmitter.emit(
				'TaskDetails:OnTaskCompleted',
				[{ response: responseData, request: taskCompletionParams, task: this.task }],
			);

			setTimeout(
				() => {
					if (this.layout)
					{
						this.layout.close();
					}
				},
				280,
			);
		}

		onTaskCompleteFailed(errors)
		{
			NotifyManager.hideLoadingIndicator(false);

			this.customEventEmitter.emit(
				'TaskDetails:OnTaskCompleteFailed',
				[{ errors, task: this.task }],
			);

			if (!Array.isArray(errors) || errors.length <= 0)
			{
				return;
			}

			const firstError = errors[0];

			if (TaskErrorCode.isTaskNotFoundErrorCode(firstError.code))
			{
				if (this.props.showNotifications)
				{
					showToast({
						message: firstError.message,
						position: Position.TOP,
						code: 'bp-workflow-details-buttons-task-not-found',
					});
				}

				if (this.layout)
				{
					this.layout.close();
				}

				return;
			}

			Alert.alert(firstError.message);
		}

		renderDelegateButton()
		{
			return new DelegateButton({
				onClick: this.props.onDelegateButtonClick,
				style: {
					flexGrow: null,
				},
			});
		}

		renderSeparator()
		{
			return View(
				{
					style: {
						marginLeft: 12,
						width: 1,
						height: 19,
						backgroundColor: AppTheme.colors.base6,
					},
				},
			);
		}

		renderTimelineButton()
		{
			return View(
				{
					style: {
						paddingLeft: 12,
						height: 64,
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				View(
					{
						style: {
							justifyContent: 'center',
							height: 36,
							borderRadius: 8,
							borderWidth: 1,
							borderColor: AppTheme.colors.base5,
							padding: 8,
							paddingHorizontal: 10,
							marginRight: 12,
							maxWidth: 157,
						},
						onClick: this.props.onTimelineButtonClick,
					},
					Text({
						style: {
							fontWeight: '500',
							fontSize: 14,
							color: AppTheme.colors.base2,
						},
						text: Loc.getMessage('BPMOBILE_TASK_DETAILS_TIMELINE_MSGVER_1'),
					}),
				),
				this.renderTimelineCounter(),
			);
		}

		renderTimelineCounter()
		{
			let value = this.props.allTaskCount;

			if (!this.props.isTaskCompleted)
			{
				value -= 1;
			}

			if (value <= 0)
			{
				return null;
			}

			return Text(
				{
					style: {
						position: 'absolute',
						top: 10,
						left: 7,
						width: 18,
						height: 18,
						borderRadius: 9,
						backgroundColor: AppTheme.colors.accentMainAlert,
						textAlign: 'center',
						color: AppTheme.colors.baseWhiteFixed,
						fontSize: 12,
						fontWeight: '500',
					},
					text: String(value),
				},
			);
		}
	}

	module.exports = { TaskDetailsButtons };
});
