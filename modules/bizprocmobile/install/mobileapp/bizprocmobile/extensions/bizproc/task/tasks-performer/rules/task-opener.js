/**
 * @module bizproc/task/tasks-performer/rules/task-opener
 */
jn.define('bizproc/task/tasks-performer/rules/task-opener', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { EventEmitter } = require('event-emitter');
	const { Type } = require('type');
	const { showToast, Position } = require('toast');

	const { TaskErrorCode } = require('bizproc/task/task-constants');
	const { TaskDetails } = require('bizproc/task/details');

	class TaskOpener
	{
		/**
		 * @param props

		 * @param {{}} props.parentLayout
		 * @param {string} props.widgetTitle

		 * @param {string} props.uid
		 * @param {number} props.taskId

		 * @param {Function} props.generateExitButton

		 */
		constructor(props)
		{
			this.parentLayout = props.parentLayout;
			this.widgetParams = {
				modal: true,
				titleParams: {
					text: props.widgetTitle,
					type: 'dialog',
				},
				backgroundColor: AppTheme.colors.bgSecondary,
				backdrop: {
					onlyMediumPosition: false,
					mediumPositionPercent: 90,
					navigationBarColor: AppTheme.colors.bgSecondary,
					swipeAllowed: true,
					swipeContentAllowed: true,
					horizontalSwipeAllowed: false,
				},
			};

			this.uid = `${props.uid}-task-${props.taskId}`;
			this.customEventEmitter = EventEmitter.createWithUid(this.uid);

			this.componentParams = {
				uid: this.uid,
				parentLayout: this.parentLayout,

				taskId: props.taskId,
				workflowId: null,
				targetUserId: null,

				readOnlyTimeline: true,
				showNotifications: false,
			};

			this.layout = null;

			this.result = {
				doTaskRequest: null,
				delegateRequest: null,
				finishRule: false,
				taskNotFound: false,
			};

			this.exitButton = null;
			if (Type.isFunction(props.generateExitButton))
			{
				this.exitButton = props.generateExitButton(() => {
					this.result.finishRule = true;
					if (this.layout)
					{
						this.layout.close();
					}
				});
			}

			this.subscribeOnEvents();
		}

		async open()
		{
			let isResolved = false;

			return new Promise((resolve) => {
				this.parentLayout.openWidget(
					'layout',
					{
						...this.widgetParams,
						onReady: (layout) => {
							this.layout = layout;
							this.layout.setListener((eventName) => {
								if (isResolved || (eventName !== 'onViewWillHidden' && eventName !== 'onViewRemoved'))
								{
									return;
								}

								isResolved = true;
								this.unsubscribeOnEvents();
								resolve(this.result);
							});

							this.layout.showComponent(
								new TaskDetails({
									...this.componentParams,
									layout: this.layout,
								}),
							);
						},
					},
				);
			});
		}

		subscribeOnEvents()
		{
			this.eventCallbacks = {
				'TaskDetails:onLoadFailed': ({ errors }) => {
					if (Array.isArray(errors) && errors.length > 0)
					{
						const firstError = errors[0];
						if (TaskErrorCode.isTaskNotFoundErrorCode(firstError.code))
						{
							this.result.taskNotFound = true;
						}
					}
				},
				'TaskDetails:OnTaskCompleteFailed': ({ errors }) => {
					if (Array.isArray(errors) && errors.length > 0)
					{
						const firstError = errors[0];
						if (TaskErrorCode.isTaskNotFoundErrorCode(firstError.code))
						{
							showToast({
								message: firstError.message,
								position: Position.TOP,
							});

							this.result.taskNotFound = true;
						}
					}
				},
				'TaskDetails:onLoadSuccess': () => {
					if (this.layout && this.layout.setRightButtons && this.exitButton)
					{
						this.layout.setRightButtons([this.exitButton]);
					}
				},
				'TaskDetails:onTaskDelegated': ({ request }) => {
					this.result.delegateRequest = request;
				},
				'TaskDetails:OnTaskCompleted': ({ request }) => {
					this.result.doTaskRequest = request.taskRequest;
				},
			};

			Object.entries(this.eventCallbacks).forEach(([eventName, callback]) => {
				this.customEventEmitter.on(eventName, callback);
			});
		}

		unsubscribeOnEvents()
		{
			Object.entries(this.eventCallbacks).forEach(([event, callback]) => {
				this.customEventEmitter.off(event, callback);
			});
		}
	}

	module.exports = { TaskOpener };
});
