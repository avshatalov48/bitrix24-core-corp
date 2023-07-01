/**
 * @module tasks/layout/task/fields/status/actionButton
 */
jn.define('tasks/layout/task/fields/status/actionButton', (require, exports, module) => {
	const {EventEmitter} = require('event-emitter');

	class ActionButton extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				isTimerRunning: props.isTimerRunning,
				timeElapsed: Number(props.timeElapsed),
			};
			this.eventEmitter = EventEmitter.createWithUid(props.task.id);
		}

		componentWillReceiveProps(props)
		{
			this.state = {
				isTimerRunning: props.isTimerRunning,
				timeElapsed: Number(props.timeElapsed),
			};
		}

		componentWillUnmount()
		{
			if (this.timerId)
			{
				clearTimeout(this.timerId);
			}
		}

		render()
		{
			const actionButtonOptions = this.getActionButtonOptions()[this.props.status];

			if (this.props.isTimerExisting)
			{
				if (this.state.isTimerRunning && !this.timerId)
				{
					this.timerId = setInterval(
						() => {
							this.setState({timeElapsed: this.state.timeElapsed + 1});
							this.eventEmitter.emit(
								'actionButton:timeElapsedChanged',
								[this.state.timeElapsed]
							);
						},
						1000
					);
				}
				if (!this.state.isTimerRunning && this.timerId)
				{
					clearTimeout(this.timerId);
					this.timerId = null;
				}

				return View(
					{
						style: {
							flexDirection: 'row',
							alignSelf: 'center',
							justifyContent: 'center',
							height: 22,
							borderRadius: 15,
							paddingHorizontal: 5,
							paddingVertical: 2,
							backgroundColor: '#0d333333',
						},
						testId: 'statusTimerActionButton',
						onClick: (this.props.isReadOnly ? () => {} : actionButtonOptions.onClick),
					},
					(!this.props.isReadOnly && Image({
						style: {
							width: 12,
							height: 12,
							marginRight: 2,
							alignSelf: 'center',
						},
						svg: {
							content: actionButtonOptions.icon,
						},
					})),
					this.renderTimer(),
				);
			}

			if (this.timerId)
			{
				clearTimeout(this.timerId);
				this.timerId = null;
			}

			if (this.props.isReadOnly)
			{
				return View();
			}

			return Image({
				style: {
					width: 24,
					height: 24,
				},
				svg: {
					content: actionButtonOptions.icon,
				},
				onClick: actionButtonOptions.onClick,
			});
		}

		renderTimer()
		{
			const timeElapsed = this.state.timeElapsed;
			const elapsedHours = String(Math.floor(timeElapsed / 3600)).padStart(2, '0');
			const elapsedMinutes = String(Math.floor((timeElapsed - elapsedHours * 3600) / 60)).padStart(2, '0');

			const timeEstimate = this.props.timeEstimate;
			const estimateHours = String(Math.floor(timeEstimate / 3600)).padStart(2, '0');
			const estimateMinutes = String(Math.floor((timeEstimate - estimateHours * 3600) / 60)).padStart(2, '0');

			return Text({
				style: {
					flex: null,
					fontSize: 10,
					fontColor: '#696c70',
					fontWeight: '600',
					color: (timeEstimate && timeElapsed > timeEstimate ? '#ff5752' : '#696c70'),
				},
				text: `${elapsedHours}:${elapsedMinutes} ${(timeEstimate ? `/ ${estimateHours}:${estimateMinutes}` : '')}`,
			});
		}

		getActionButtonOptions()
		{
			const timerHandler = () => {
				if (this.state.isTimerRunning)
				{
					this.setState({isTimerRunning: false});

					const actions = this.props.task.exportActions();
					this.props.task.updateActions({
						canStart: true,
						canPause: false,
						canRenew: false,
					});
					this.props.task.pauseTimer().then(
						() => this.setState({isTimerRunning: this.props.task.isTimerRunningForCurrentUser}),
						() => {
							this.props.task.updateActions(actions);
							this.setState({isTimerRunning: this.props.task.isTimerRunningForCurrentUser});
						}
					);
				}
				else
				{
					this.setState({isTimerRunning: true});

					const actions = this.props.task.exportActions();
					this.props.task.updateActions({
						canStart: false,
						canPause: true,
						canRenew: false,
					});
					this.props.task.updateData({status: Task.statusList.inprogress});
					this.props.task.startTimer().then(
						() => this.setState({isTimerRunning: this.props.task.isTimerRunningForCurrentUser}),
						() => {
							this.props.task.updateActions(actions);
							this.setState({isTimerRunning: this.props.task.isTimerRunningForCurrentUser});
						}
					);
					this.eventEmitter.emit('tasks.task.actionMenu:start');
				}
			};
			const renewHandler = () => {
				this.props.task.updateActions({
					canStart: true,
					canPause: false,
					canRenew: false,
				});
				void this.props.task.renew();
				this.eventEmitter.emit('tasks.task.actionMenu:renew');
			};

			return {
				[Task.statusList.pending]: {
					icon: (
						this.props.isTimerExisting && this.state.isTimerRunning
							? '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M10.5 5H7V19H10.5V5Z" fill="#2FC6F6"/><path d="M18 5H14.5V19H18V5Z" fill="#2FC6F6"/></svg>'
							: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M17.6778 11.872L8.14943 5.13493C7.92478 4.97385 7.63206 4.95562 7.39033 5.08763C7.1486 5.21965 6.9981 5.47994 7.00004 5.76265V19.2362C6.99719 19.5193 7.14755 19.7803 7.38964 19.9125C7.63173 20.0447 7.92496 20.026 8.14943 19.8639L17.6778 13.1268C17.8793 12.986 18 12.7509 18 12.4994C18 12.2479 17.8793 12.0128 17.6778 11.872Z" fill="#9DCF00"/></svg>'
					),
					onClick: () => {
						if (this.props.isTimerExisting)
						{
							timerHandler();
						}
						else
						{
							this.props.task.updateActions({
								canStart: false,
								canPause: true,
								canRenew: false,
							});
							void this.props.task.start();
							this.eventEmitter.emit('tasks.task.actionMenu:start');
						}
					},
				},
				[Task.statusList.inprogress]: {
					icon: (
						this.props.isTimerExisting && !this.state.isTimerRunning
							? '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M17.6778 11.872L8.14943 5.13493C7.92478 4.97385 7.63206 4.95562 7.39033 5.08763C7.1486 5.21965 6.9981 5.47994 7.00004 5.76265V19.2362C6.99719 19.5193 7.14755 19.7803 7.38964 19.9125C7.63173 20.0447 7.92496 20.026 8.14943 19.8639L17.6778 13.1268C17.8793 12.986 18 12.7509 18 12.4994C18 12.2479 17.8793 12.0128 17.6778 11.872Z" fill="#9DCF00"/></svg>'
							: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M10.5 5H7V19H10.5V5Z" fill="#2FC6F6"/><path d="M18 5H14.5V19H18V5Z" fill="#2FC6F6"/></svg>'
					),
					onClick: () => {
						if (this.props.isTimerExisting)
						{
							timerHandler();
						}
						else
						{
							this.props.task.updateActions({
								canStart: true,
								canPause: false,
								canRenew: false,
							});
							void this.props.task.pause();
							this.eventEmitter.emit('tasks.task.actionMenu:pause');
						}
					},
				},
				[Task.statusList.waitCtrl]: {
					icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.70462 6.61184C3.72867 9.58779 3.72867 14.4128 6.70462 17.3887C9.68056 20.3647 14.5055 20.3647 17.4815 17.3887C17.5802 17.29 17.6756 17.1892 17.7678 17.0866L16.1118 15.4308C16.0223 15.5354 15.9281 15.6373 15.8291 15.7363C13.7657 17.7997 10.4204 17.7997 8.357 15.7363C6.29364 13.673 6.29364 10.3276 8.357 8.26423C10.4204 6.20086 13.7657 6.20086 15.8291 8.26423L15.9121 8.3501L13.6972 10.5659H19.527V4.7361L17.5658 6.69746L17.4815 6.61184C14.5055 3.6359 9.68056 3.6359 6.70462 6.61184Z" fill="#2FC6F6"/></svg>',
					onClick: renewHandler,
				},
				[Task.statusList.completed]: {
					icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.70462 6.61184C3.72867 9.58779 3.72867 14.4128 6.70462 17.3887C9.68056 20.3647 14.5055 20.3647 17.4815 17.3887C17.5802 17.29 17.6756 17.1892 17.7678 17.0866L16.1118 15.4308C16.0223 15.5354 15.9281 15.6373 15.8291 15.7363C13.7657 17.7997 10.4204 17.7997 8.357 15.7363C6.29364 13.673 6.29364 10.3276 8.357 8.26423C10.4204 6.20086 13.7657 6.20086 15.8291 8.26423L15.9121 8.3501L13.6972 10.5659H19.527V4.7361L17.5658 6.69746L17.4815 6.61184C14.5055 3.6359 9.68056 3.6359 6.70462 6.61184Z" fill="#2FC6F6"/></svg>',
					onClick: renewHandler,
				},
				[Task.statusList.deferred]: {
					icon: '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.70462 6.61184C3.72867 9.58779 3.72867 14.4128 6.70462 17.3887C9.68056 20.3647 14.5055 20.3647 17.4815 17.3887C17.5802 17.29 17.6756 17.1892 17.7678 17.0866L16.1118 15.4308C16.0223 15.5354 15.9281 15.6373 15.8291 15.7363C13.7657 17.7997 10.4204 17.7997 8.357 15.7363C6.29364 13.673 6.29364 10.3276 8.357 8.26423C10.4204 6.20086 13.7657 6.20086 15.8291 8.26423L15.9121 8.3501L13.6972 10.5659H19.527V4.7361L17.5658 6.69746L17.4815 6.61184C14.5055 3.6359 9.68056 3.6359 6.70462 6.61184Z" fill="#2FC6F6"/></svg>',
					onClick: renewHandler,
				},
			};
		}
	}

	module.exports = {ActionButton};
});