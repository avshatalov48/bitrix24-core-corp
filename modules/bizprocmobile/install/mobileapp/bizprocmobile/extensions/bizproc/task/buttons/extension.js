/**
 * @module bizproc/task/buttons
 */
jn.define('bizproc/task/buttons', (require, exports, module) => {
	const { Alert } = require('alert');
	const AppTheme = require('apptheme');
	const { Loc } = require('loc');
	const { PureComponent } = require('layout/pure-component');
	const { isFunction } = require('utils/object');
	const { Type } = require('type');
	const { EventEmitter } = require('event-emitter');

	class TaskButtons extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.customEventEmitter = EventEmitter.createWithUid('bizproc');
			this.onTaskTouch = this.onTaskTouch.bind(this);
		}

		componentDidMount()
		{
			this.customEventEmitter.on('Task:onTouch', this.onTaskTouch);
		}

		componentWillUnmount()
		{
			this.customEventEmitter.off('Task:onTouch', this.onTaskTouch);
		}

		onTaskTouch({ task })
		{
			if (task.id === this.task.id && this.props.isInline)
			{
				this.setIsDoing(true);
			}
		}

		get task()
		{
			return this.props.task || {};
		}

		render()
		{
			return View(
				{
					style: {
						width: '100%',
						flexDirection: 'row',
						flexWrap: 'no-wrap',
						height: 36,
					},
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
					() => {
						void requireLazy('bizproc:task/details').then(({ TaskDetails }) => {
							void TaskDetails.open(
								this.props.layout,
								{
									taskId: task.id,
									title: this.props.title,
								},
							);
						});
					},
				));
			}
			else if (task.buttons && task.buttons.length > 0)
			{
				buttons = task.buttons.map((button) => {
					const isDecline = (
						button.TARGET_USER_STATUS === 2
						|| button.TARGET_USER_STATUS === 4
					);

					if (isDecline)
					{
						return this.renderDeclineButton(
							button.TEXT,
							() => this.onTaskButtonAction(task, button),
						);
					}

					return this.renderAcceptButton(
						button.TEXT,
						() => this.onTaskButtonAction(task, button),
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
			return this.renderButton('start', text, onClick);
		}

		renderAcceptButton(text, onClick)
		{
			return this.renderButton('accept', text, onClick);
		}

		renderDeclineButton(text, onClick)
		{
			return this.renderButton('decline', text, onClick);
		}

		renderButton(type, text, onClick)
		{
			const alone = !this.props.isInline && this.task.buttons.length === 1;

			return View(
				{
					style: {
						flexGrow: 1,
						flexShrink: 1,
						flexDirection: 'row',
						marginLeft: buttonStyles[type].marginLeft,
						marginRight: alone ? 0 : buttonStyles[type].marginRight,
						justifyContent: 'center',
						height: 36,
						borderRadius: 100,
						borderWidth: 1,
						borderColor: buttonStyles[type].borderColor,
						padding: 8,
						paddingHorizontal: 16,
						width: alone ? '100%' : '50%',
						maxWidth: alone ? '100%' : '50%',
					},
					testId: `${this.testId}_BUTTON_${type.toUpperCase()}`,
					onClick,
				},
				icons[type] && Image({
					style: {
						width: 28,
						height: 28,
						alignSelf: 'center',
					},
					svg: {
						content: icons[type],
					},
				}),
				Text({
					style: {
						fontWeight: '500',
						fontSize: this.props.isInline ? 15 : 14,
						color: buttonStyles[type].textColor,
					},
					text,
					ellipsize: 'end',
					numberOfLines: 1,
				}),
			);
		}

		renderDetailButton(text)
		{
			return View(
				{
					style: {
						flexGrow: 1,
						flexDirection: 'row',
						justifyContent: 'center',
						height: 36,
						padding: 8,
					},
					testId: `${this.testId}_BUTTON_DETAILS`,
					onClick: () => {
						void requireLazy('bizproc:task/details').then(({ TaskDetails }) => {
							void TaskDetails.open(
								this.props.layout,
								{
									taskId: this.task.id,
									title: this.props.title,
								},
							);
						});
					},
				},
				Text({
					style: {
						fontWeight: '500',
						fontSize: 14,
						ellipsize: 'end',
						numberOfLines: 1,
						color: AppTheme.colors.base3,
					},
					text,
				}),
			);
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
			this.customEventEmitter.emit(
				'Task:onTouch',
				{ task: this.task },
			);

			BX.ajax.runAction('bizprocmobile.Task.do', {
				data: props,
			}).then((response) => {
				if (isFunction(this.props.onComplete))
				{
					this.props.onComplete(response.data);
				}
			}).catch(({ errors }) => {
				Alert.alert(errors.pop().message);
				if (isFunction(this.props.onFail))
				{
					this.props.onFail();
				}
			});
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

	const icons = {
		accept: (() => {
			const fill = AppTheme.colors.accentMainSuccess;

			return `
				<svg xmlns="http://www.w3.org/2000/svg" width="23" height="22" viewBox="0 0 23 22" fill="none">
					<path fill-rule="evenodd" clip-rule="evenodd" d="M5.33044 12.102L9.88012 16.7472L18.1638 8.28963L16.5714 6.66382L9.88012 13.4955L6.92283 10.4762L5.33044 12.102Z" fill="${fill}"/>
				</svg>
			`;
		})(),
		decline: (() => {
			const fill = AppTheme.colors.base2;

			return `
				<svg xmlns="http://www.w3.org/2000/svg" width="23" height="22" viewBox="0 0 23 22" fill="none">
					<path fill-rule="evenodd" clip-rule="evenodd" d="M15.6939 17.1677L17.6673 15.1943L13.7205 11.2475L17.6673 7.30077L15.6939 5.32739L11.7472 9.27415L7.8004 5.32739L5.82703 7.30077L9.77378 11.2475L5.82703 15.1943L7.8004 17.1677L11.7472 13.2209L15.6939 17.1677Z" fill="${fill}"/>
				</svg>
			`;
		})(),
	};

	const buttonStyles = {
		accept: {
			borderColor: AppTheme.colors.accentMainSuccess,
			textColor: AppTheme.colors.accentMainSuccess,
			marginLeft: 0,
			marginRight: 6,
		},
		decline: {
			borderColor: AppTheme.colors.base5,
			textColor: AppTheme.colors.base2,
			marginLeft: 6,
			marginRight: 0,
		},
		start: {
			borderColor: AppTheme.colors.accentMainPrimary,
			textColor: AppTheme.colors.accentMainPrimary,
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

	module.exports = { TaskButtons };
});
