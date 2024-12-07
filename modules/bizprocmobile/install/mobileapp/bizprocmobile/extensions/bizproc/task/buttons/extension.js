/**
 * @module bizproc/task/buttons
 */
jn.define('bizproc/task/buttons', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Alert } = require('alert');
	const { EventEmitter } = require('event-emitter');
	const { Haptics } = require('haptics');
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { isFunction, isObjectLike } = require('utils/object');
	const { useCallback } = require('utils/function');
	const { PureComponent } = require('layout/pure-component');

	const { TaskUserStatus } = require('bizproc/task/task-constants');

	const { ButtonsWrapper } = require('bizproc/task/buttons/buttons-wrapper');
	const { Button } = require('bizproc/task/buttons/button');
	const { AcceptButton } = require('bizproc/task/buttons/accept-button');
	const { DeclineButton } = require('bizproc/task/buttons/decline-button');
	const { StartButton } = require('bizproc/task/buttons/start-button');
	const { DetailButton } = require('bizproc/task/buttons/detail-button');
	const { DelegateButton } = require('bizproc/task/buttons/delegate-button');

	class TaskButtons extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.testId = Type.isStringFilled(props.testId) ? props.testId : 'MBP_TASK_BUTTONS';

			this.uid = props.uid || 'bizproc';
			if (this.shouldUseEvents)
			{
				this.customEventEmitter = EventEmitter.createWithUid(this.uid);
			}

			this.onTaskTouch = this.onTaskTouch.bind(this);
			this.onStartClick = this.onStartClick.bind(this);
		}

		get shouldUseEvents()
		{
			return BX.prop.getBoolean(this.props, 'shouldUseEvents', true);
		}

		componentDidMount()
		{
			if (this.shouldUseEvents)
			{
				this.customEventEmitter.on('Task:onTouch', this.onTaskTouch);
			}
		}

		componentWillUnmount()
		{
			if (this.shouldUseEvents)
			{
				this.customEventEmitter.off('Task:onTouch', this.onTaskTouch);
			}
		}

		onTaskTouch({ task })
		{
			if (task.id === this.task.id && this.props.isInline)
			{
				this.setIsDoing(true);
			}
		}

		onStartClick()
		{
			void requireLazy('bizproc:task/details').then(({ TaskDetails }) => {
				void TaskDetails.open(
					this.props.layout,
					{
						taskId: this.task.id,
						title: this.props.title,
					},
				);
			});
		}

		get task()
		{
			return this.props.task || {};
		}

		render()
		{
			return ButtonsWrapper(
				{
					ref: (ref) => {
						this.elementRef = ref;
					},
				},
				this.renderIsDoing(),
				...this.getButtons(),
			);
		}

		getButtons()
		{
			const task = this.task;
			let buttons = [];

			if (this.state.isDoing)
			{
				return buttons;
			}

			if (this.props.isInline && !task.isInline)
			{
				buttons.push(this.renderStartButton(
					Loc.getMessage('BPMOBILE_TASK_BUTTONS_DETAILS'),
					this.onStartClick,
				));
			}
			else if (task.buttons && task.buttons.length > 0)
			{
				buttons = task.buttons.map((button) => {
					if (TaskUserStatus.isDecline(button.TARGET_USER_STATUS))
					{
						return this.renderDeclineButton(
							button.TEXT,
							useCallback(() => {
								Haptics.notifySuccess();
								this.onTaskButtonAction(task, button);
							}),
						);
					}

					return this.renderAcceptButton(
						button.TEXT,
						useCallback(() => {
							Haptics.impactMedium();
							this.onTaskButtonAction(task, button);
						}),
					);
				});
			}

			if (buttons.length < 2 && this.props.isInline)
			{
				buttons.push(this.renderDetailButton(Loc.getMessage('BPMOBILE_TASK_BUTTONS_DETAILS_LINK')));
			}

			return buttons;
		}

		renderIsDoing()
		{
			if (this.state.isDoing !== true)
			{
				return null;
			}

			return View(
				{},
				Text({
					text: Loc.getMessage('BPMOBILE_TASK_BUTTONS_STATUS_LABEL'),
					style: styles.statusTitle,
				}),
				Text({
					testId: `${this.testId}_STATUS_TEXT`,
					text: Loc.getMessage('BPMOBILE_TASK_BUTTONS_STATUS_IS_DOING'),
					style: styles.statusText,
					numberOfLines: 1,
					ellipsize: 'end',
				}),
			);
		}

		renderStartButton(text, onClick)
		{
			return new StartButton({
				style: this.alignButton(buttonStyles.start),
				testId: `${this.testId}_BUTTON_START`,
				text,
				onClick,
			});
		}

		renderAcceptButton(text, onClick)
		{
			return new AcceptButton({
				style: this.alignButton(buttonStyles.accept),
				testId: `${this.testId}_BUTTON_ACCEPT`,
				text,
				onClick,
			});
		}

		renderDeclineButton(text, onClick)
		{
			return new DeclineButton({
				style: this.alignButton(buttonStyles.decline),
				testId: `${this.testId}_BUTTON_DECLINE`,
				text,
				onClick,
			});
		}

		alignButton(style)
		{
			const alone = !this.props.isInline && this.task.buttons.length === 1;

			const alignment = {
				width: alone ? '100%' : '50%',
				maxWidth: alone ? '100%' : '50%',
				fontSize: this.props.isInline ? 15 : 14,
			};
			if (alone)
			{
				alignment.marginRight = 0;
			}

			return {
				...style,
				...alignment,
			};
		}

		renderDetailButton(text)
		{
			return new DetailButton({
				layout: this.props.layout,
				text,
				testId: `${this.testId}_BUTTON_DETAILS`,
				taskId: this.task.id,
				title: this.props.title,
			});
		}

		onTaskButtonAction(task, button)
		{
			const props = {
				taskId: task.id,
				taskRequest: { INLINE_USER_STATUS: button.TARGET_USER_STATUS },
			};

			const onBeforeActionResult = (
				isFunction(this.props.onBeforeAction)
					? this.props.onBeforeAction(task, button)
					: null
			);

			const promise = (
				isFunction(onBeforeActionResult?.then)
					? onBeforeActionResult
					: Promise.resolve(onBeforeActionResult)
			);
			promise
				.then((fields) => {
					if (!Type.isNil(fields))
					{
						props.taskRequest.fields = fields;
					}

					this.doTask(props);
				})
				.catch(() => {})
			;
		}

		doTask(props)
		{
			if (this.shouldUseEvents)
			{
				this.customEventEmitter.emit(
					'Task:onTouch',
					{ task: this.task, isInline: this.props.isInline },
				);
			}

			const defaultCallback = () => BX.ajax.runAction('bizprocmobile.Task.do', { data: props });
			const callback = BX.prop.getFunction(
				this.props,
				'onTaskButtonClick',
				defaultCallback,
			);

			const result = callback(props, defaultCallback);

			const onSuccess = (response) => {
				if (isFunction(this.props.onComplete))
				{
					this.props.onComplete(response.data, props);
				}
			};

			const onErrors = ({ errors }) => {
				if (isFunction(this.props.onFail))
				{
					this.props.onFail(errors);
				}
				else
				{
					Alert.alert(errors.pop().message);
				}
			};

			if (isObjectLike(result))
			{
				if (isFunction(result.then))
				{
					result.then(onSuccess).catch(onErrors);
				}
				else if (result.data)
				{
					onSuccess(result.data);
				}
				else if (result.errors)
				{
					onErrors(result.errors);
				}
			}
		}

		setIsDoing(flag)
		{
			if (flag === this.state.isDoing)
			{
				return;
			}

			if (!this.elementRef)
			{
				return;
			}

			const duration = 300;
			const opacity = 0;

			this.elementRef.animate({ duration, opacity }, () => {
				this.setState({ isDoing: flag }, () => {
					this.elementRef.animate({ duration, opacity: 1 }, () => {
						this.state.isDoing = !flag;
					});
				});
			});
		}
	}

	const buttonStyles = {
		accept: {
			marginLeft: 0,
			marginRight: 6,
		},
		decline: {
			marginLeft: 6,
			marginRight: 0,
		},
		start: {
			marginLeft: 0,
			marginRight: 6,
		},
	};

	const styles = {
		statusTitle: {
			fontSize: 11,
			height: 14,
			fontWeight: '400',
			color: AppTheme.colors.base4,
		},
		statusText: {
			fontSize: 14,
			height: 17,
			fontWeight: '400',
			color: AppTheme.colors.base1,
		},
	};

	module.exports = {
		TaskButtons,
		Button,
		ButtonsWrapper,
		DetailButton,
		AcceptButton,
		DeclineButton,
		StartButton,
		DelegateButton,
	};
});
