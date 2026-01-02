/**
 * @module more-menu/block/header/worktime
 */
jn.define('more-menu/block/header/worktime', (require, exports, module) => {
	const { Indent, Color } = require('tokens');
	const { Loc } = require('loc');
	const { inAppUrl } = require('in-app-url');
	const { formatHHMMSS, toMs, parseHmsToSec, parseDateToSec } = require('utils/time');

	const { Text3, Text4 } = require('ui-system/typography/text');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Button, ButtonSize, ButtonDesign } = require('ui-system/form/buttons/button');

	const { debounce } = require('utils/function');
	const { createTestIdGenerator } = require('utils/test');
	const { PropTypes } = require('utils/validation');
	const { isEmpty } = require('utils/object');

	const STATUS = {
		OPENED: 'OPENED',
		CLOSED: 'CLOSED',
		PAUSED: 'PAUSED',
		EXPIRED: 'EXPIRED',
	};

	const DEBOUNCE_DELAY = 300;
	const STATUS_POLL_INTERVAL = 3 * 60 * 1000; // 3 minutes

	/**
	 * @class WorkTime
	 */
	class WorkTime extends LayoutComponent
	{
		/**
		 * @param {object} props
		 * @param {boolean} props.showBorder
		 * @param {string} props.testId
		 *
		 * @param {object} props.workTime
		 * @param {object} props.workTime.INFO
		 * @param {boolean} props.workTime.INFO.ACTIVE
		 * @param {string} props.workTime.INFO.CURRENT_STATUS
		 * @param {number} props.workTime.INFO.DATE_START
		 * @param {number} props.workTime.INFO.DATE_FINISH
		 * @param {number|string} props.workTime.INFO.DURATION
		 * @param {boolean} props.workTime.INFO.PAUSED
		 * @param {number} props.workTime.INFO.RECOMMENDED_CLOSE_TIMESTAMP
		 * @param {number|string} props.workTime.INFO.TIME_FINISH
		 * @param {number|string} props.workTime.INFO.TIME_LEAKS
		 * @param {number|string} props.workTime.INFO.TIME_START
		 * @param {object} props.workTime.LAST_PAUSE
		 * @param {number} props.workTime.LAST_PAUSE.DATE_START
		 * @param {number} props.workTime.LAST_PAUSE.DATE_FINISH
		 * @param {string} props.workTime.STATE
		 */
		constructor(props)
		{
			super(props);
			const workTime = props?.workTime || {};

			const mapped = this.mapWorkTimeToState(workTime, {});

			this.state = {
				...mapped,
			};

			this.getTestId = createTestIdGenerator({
				prefix: props.testId,
			});

			this.debouncedSubscribeToPullEvent = debounce(this.subscribeToPullEvent, DEBOUNCE_DELAY, this);
			this.openWorkTime = this.openWorkTime.bind(this);
			this.getStatus = this.getStatus.bind(this);
		}

		componentDidMount()
		{
			this.updateLiveTimer();
			BX.addCustomEvent('onAppActive', this.getStatus);

			BX.addCustomEvent('onPullEvent-timeman', this.debouncedSubscribeToPullEvent);

			this.statusPoller = setInterval(this.getStatus, STATUS_POLL_INTERVAL);
		}

		componentWillReceiveProps(props)
		{
			const workTime = props?.workTime;

			if (!isEmpty(workTime))
			{
				const mapped = this.mapWorkTimeToState(workTime, this.state);

				this.state = {
					...mapped,
				};
			}
		}

		componentDidUpdate(prevProps, prevState)
		{
			if (prevState.status !== this.state.status)
			{
				this.updateLiveTimer();
			}
		}

		componentWillUnmount()
		{
			BX.removeCustomEvent('onPullEvent-timeman', this.debouncedSubscribeToPullEvent);
			BX.removeCustomEvent('onAppActive', this.getStatus);

			if (this.liveTimer)
			{
				clearInterval(this.liveTimer);
			}

			if (this.statusPoller)
			{
				clearInterval(this.statusPoller);
				this.statusPoller = null;
			}
		}

		mapWorkTimeToState(raw = {}, prevState = {})
		{
			const info = (raw && !isEmpty(raw.INFO)) ? raw.INFO : {};

			const active = (info.ACTIVE || prevState.active);
			const status = (raw.STATE || info.CURRENT_STATUS);

			const dateFinishMs = toMs(info.DATE_FINISH);
			const dateStart = toMs(info.DATE_START);
			const durationMs = toMs(info.DURATION);
			const recommendedCloseTime = toMs(info.RECOMMENDED_CLOSE_TIMESTAMP);

			const timeFinishMs = toMs(info.TIME_FINISH);
			const timeLeaksMs = toMs(info.TIME_LEAKS);

			const timeFinishDefault = toMs(raw.TIME_FINISH_DEFAULT);
			const canOpen = raw.CAN_OPEN === undefined ? prevState.canOpen : raw.CAN_OPEN;

			const pauseStartSec = raw.LAST_PAUSE?.DATE_START;
			const pauseFinishSec = raw.LAST_PAUSE?.DATE_FINISH;

			const pauseStartMs = toMs(pauseStartSec);
			const pauseFinishMs = toMs(pauseFinishSec);

			const isPaused = (status === STATUS.PAUSED);
			const wasPaused = (prevState.status === STATUS.PAUSED);

			let breakStart = null;

			if (isPaused)
			{
				if (dateFinishMs !== null)
				{
					breakStart = dateFinishMs;
				}
				else if (pauseStartMs !== null && pauseStartMs !== undefined)
				{
					breakStart = pauseStartMs;
				}
				else if (wasPaused && prevState.breakStart)
				{
					breakStart = prevState.breakStart;
				}
				else
				{
					breakStart = Date.now();
				}
			}

			const initialLeaksMs = isPaused
				? (Number.isFinite(timeLeaksMs) ? timeLeaksMs : 0)
				: 0;

			return {
				active,
				status,
				dateFinishMs,
				dateStart,
				timeFinishMs,
				durationMs,
				timeLeaksMs,
				recommendedCloseTime,
				timeFinishDefault,
				canOpen,
				// expose computed pause info in state
				breakStart,
				initialLeaksMs,
				lastPauseStartMs: pauseStartMs ?? null,
				lastPauseFinishMs: pauseFinishMs ?? null,
				now: Date.now(),
			};
		}

		updateLiveTimer()
		{
			const shouldRun = (this.isOpened() || this.isPaused());

			if (shouldRun && !this.liveTimer)
			{
				this.liveTimer = setInterval(() => this.setState({ now: Date.now() }), 1000);
			}
			else if (!shouldRun && this.liveTimer)
			{
				clearInterval(this.liveTimer);
				this.liveTimer = null;
			}
		}

		subscribeToPullEvent(command, params, extra)
		{
			if (params?.info)
			{
				const data = params?.info;
				const payload = {
					STATE: data?.state,
					INFO: {
						DATE_START: data?.dateStart,
						DATE_FINISH: data.dateFinish,
						TIME_LEAKS: data?.timeLeaks,
					},
					LAST_PAUSE: data?.lastPause,
				};

				this.updateWorkTimeState(payload);
			}
			else
			{
				this.updateWorkTimeState(params);
			}
		}

		updateWorkTimeState(workTime = {})
		{
			const mapped = this.mapWorkTimeToState(workTime, this.state);

			this.setState({
				...mapped,
			});
		}

		render()
		{
			const {
				showBorder,
			} = this.props;

			return View(
				{
					style: {
						padding: Indent.XL2.toNumber(),
						paddingBottom: 0,
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
							paddingBottom: Indent.XL2.toNumber(),
							borderBottomWidth: showBorder && 1,
							borderBottomColor: Color.bgSeparatorSecondary.toHex(),
						},
					},
					View(
						{
							style: {
								flex: 2,
								marginRight: Indent.L.toNumber(),
								flexShrink: 2,
							},
							onClick: this.openWorkTime,
						},
						this.renderHeader(),
						this.renderTimer(),
					),
					this.renderActions(),
				),
			);
		}

		renderHeader()
		{
			return View(
				{
					testId: this.getTestId('header'),
					style: {
						flexDirection: 'row',
						marginBottom: Indent.S.toNumber(),
						alignItems: 'center',
					},
				},
				Text3({
					testId: this.getTestId('status'),
					text: this.getStatusText(),
					color: Color.base1,
					numberOfLines: 1,
					ellipsize: 'end',
				}),
				IconView({
					testId: this.getTestId('chevron'),
					size: 20,
					icon: Icon.CHEVRON_TO_THE_RIGHT,
					color: Color.base1,
					resizeMode: 'cover',
					style: {
						marginTop: 2,
						width: 10,
						height: 16,
						marginLeft: Indent.XS.toNumber(),
					},
				}),
			);
		}

		getStatusText()
		{
			if (this.isExpired())
			{
				return Loc.getMessage('MOBILE_MORE_MENU_WORKTIME_TITLE_EXPIRED');
			}

			if (this.isOpened())
			{
				return Loc.getMessage('MOBILE_MORE_MENU_WORKTIME_TITLE_OPENED');
			}

			if (this.isPaused())
			{
				return Loc.getMessage('MOBILE_MORE_MENU_WORKTIME_TITLE_PAUSED');
			}

			if (this.isCompleted())
			{
				return Loc.getMessage('MOBILE_MORE_MENU_WORKTIME_TITLE_CLOSED');
			}

			return Loc.getMessage('MOBILE_MORE_MENU_WORKTIME_TITLE_START');
		}

		renderTimer()
		{
			if (!this.state.status || (this.isStart()))
			{
				return Text4({
					testId: this.getTestId('timer-placeholder'),
					text: Loc.getMessage('MOBILE_MORE_MENU_WORKTIME_TIME_PLACEHOLDER'),
					color: Color.base3,
					numberOfLines: 1,
					ellipsize: 'end',
					style: {
						flexShrink: 2,
					},
				});
			}

			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				View(
					{
						style: {
							width: 14,
							height: 16,
							justifyContent: 'center',
							alignItems: 'center',
							overflow: 'hidden',
							marginTop: 1,
							marginRight: Indent.S.toNumber(),
						},
					},
					IconView({
						testId: this.getTestId('timer-icon'),
						size: 20,
						icon: this.getTimerIcon(),
						color: this.getDisplayTimeColor(),
						resizeMode: 'cover',
					}),
				),
				Text4({
					testId: this.getTestId('timer'),
					text: this.getDisplayTime(),
					color: this.getDisplayTimeColor(),
					numberOfLines: 1,
					ellipsize: 'end',
				}),
			);
		}

		getDisplayTimeColor()
		{
			if (this.isOpened() || this.isPaused())
			{
				return Color.accentMainPrimary;
			}

			if (this.isExpired())
			{
				return Color.accentExtraOrange;
			}

			return Color.base3;
		}

		getDisplayTime()
		{
			const {
				status,
				now,
				dateStart,
				timeLeaksMs,
				breakStart,
				initialLeaksMs,
				durationMs,
				recommendedCloseTime,
				timeFinishDefault,
				timeFinishMs,
			} = this.state;

			switch (status)
			{
				case STATUS.OPENED:
				{
					if (!Number.isFinite(dateStart))
					{
						console.warn('[WorkTime] getDisplayTime: invalid dateStart', dateStart);

						return '00:00:00';
					}

					const leaks = Number.isFinite(timeLeaksMs) ? timeLeaksMs : 0;
					const elapsedMs = now - dateStart - leaks;

					return formatHHMMSS(Math.max(elapsedMs, 0));
				}

				case STATUS.PAUSED:
				{
					if (breakStart === null)
					{
						console.warn('[WorkTime] getDisplayTime: breakStart is null in PAUSED state');
					}

					const currentMs = breakStart ? now - breakStart : 0;
					const totalLeaks = (initialLeaksMs || 0) + currentMs;

					return formatHHMMSS(Math.max(totalLeaks, 0));
				}

				case STATUS.CLOSED:
				{
					let resultMs = Number.isFinite(durationMs) ? durationMs : null;
					if (resultMs === null && Number.isFinite(dateStart) && Number.isFinite(timeFinishMs))
					{
						const leaks = Number.isFinite(timeLeaksMs) ? timeLeaksMs : 0;
						resultMs = Math.max(timeFinishMs - dateStart - leaks, 0);
					}

					return resultMs === null ? '00:00:00' : formatHHMMSS(resultMs);
				}

				case STATUS.EXPIRED:
				{
					const baseMs = (recommendedCloseTime ?? timeFinishDefault);
					if (baseMs)
					{
						const d = new Date(baseMs);
						const pad = (num) => String(num).padStart(2, '0');

						return `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
					}

					return '00:00:00';
				}

				default:
				{
					return null;
				}
			}
		}

		getTimerIcon()
		{
			if (this.isPaused())
			{
				return Icon.PAUSE;
			}

			if (this.isExpired())
			{
				return Icon.ALERT;
			}

			if (this.isCompleted())
			{
				return Icon.POWER;
			}

			return Icon.TIMER;
		}

		isOpened()
		{
			return this.state.status === STATUS.OPENED;
		}

		isClosed()
		{
			return this.state.status === STATUS.CLOSED;
		}

		isPaused()
		{
			return this.state.status === STATUS.PAUSED;
		}

		isExpired()
		{
			return this.state.status === STATUS.EXPIRED;
		}

		isCompleted()
		{
			return this.state.status === STATUS.CLOSED
			&& (this.state.canOpen === 'REOPEN' || !this.state.canOpen);
		}

		isStart()
		{
			return this.state.status === STATUS.CLOSED
			&& (this.state.canOpen === 'OPEN');
		}

		renderActions()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
					},
				},
				this.isOpened() && this.renderPauseButton(),
				this.renderStartButton(),
			);
		}

		renderPauseButton()
		{
			return Button({
				testId: this.getTestId('pause-button'),
				leftIcon: Icon.PAUSE,
				size: ButtonSize.M,
				design: ButtonDesign.OUTLINE,
				onClick: () => {
					this.pauseWorkTime();
				},
				style: {
					marginRight: Indent.M.toNumber(),
				},
				backgroundColor: Color.bgContentPrimary,
			});
		}

		renderStartButton()
		{
			if (this.isExpired())
			{
				return Button({
					testId: this.getTestId('stop-button'),
					leftIcon: Icon.POWER,
					text: Loc.getMessage('MOBILE_MORE_MENU_WORKTIME_BUTTON_STOP'),
					size: ButtonSize.M,
					design: ButtonDesign.FILLED,
					onClick: () => {
						this.openWorkTime();
					},
				});
			}

			if (this.isCompleted())
			{
				return Button({
					testId: this.getTestId('reopen-button'),
					leftIcon: Icon.REFRESH,
					text: Loc.getMessage('MOBILE_MORE_MENU_WORKTIME_BUTTON_REOPEN'),
					size: ButtonSize.M,
					design: ButtonDesign.FILLED,
					onClick: () => {
						this.reopenWorkTime();
					},
				});
			}

			if (this.isStart())
			{
				return Button({
					testId: this.getTestId('start-button'),
					leftIcon: Icon.PLAY,
					text: Loc.getMessage('MOBILE_MORE_MENU_WORKTIME_BUTTON_START'),
					size: ButtonSize.M,
					design: ButtonDesign.FILLED,
					onClick: () => {
						this.reopenWorkTime();
					},
					disabled: !this.state.canOpen,
				});
			}

			if (this.isPaused())
			{
				return Button({
					testId: this.getTestId('resume-button'),
					leftIcon: Icon.PLAY,
					text: Loc.getMessage('MOBILE_MORE_MENU_WORKTIME_BUTTON_RESUME'),
					size: ButtonSize.M,
					design: ButtonDesign.FILLED,
					onClick: () => {
						this.reopenWorkTime();
					},
				});
			}

			return Button({
				testId: this.getTestId('stop-button'),
				leftIcon: Icon.POWER,
				text: Loc.getMessage('MOBILE_MORE_MENU_WORKTIME_BUTTON_STOP'),
				size: ButtonSize.M,
				design: ButtonDesign.TINTED,
				onClick: this.openWorkTime,
			});
		}

		getStatus()
		{
			if (this.statusFetching)
			{
				return;
			}

			this.statusFetching = true;

			BX.rest.callMethod('timeman.status', {
				USER_ID: env.userId,
			})
				.then((response) => {
					this.statusFetching = false;
					const payload = this.transformTimeManResponseToPayload(response?.answer?.result);

					this.updateWorkTimeState({
						...payload,
					});
				})
				.catch((error) => {
					this.statusFetching = false;
					console.error(error);
				});
		}

		pauseWorkTime()
		{
			BX.rest.callMethod('timeman.pause', {
				USER_ID: env.userId,
			})
				.then((response) => {
					const payload = this.transformTimeManResponseToPayload(response?.answer?.result);

					this.updateWorkTimeState({
						...payload,
					});
				})
				.catch((error) => {
					console.error(error);
				});
		}

		reopenWorkTime()
		{
			BX.rest.callMethod('timeman.open', {
				USER_ID: env.userId,
			})
				.then((response) => {
					const payload = this.transformTimeManResponseToPayload(response?.answer?.result);

					this.updateWorkTimeState({
						...payload,
					});
				})
				.catch((error) => {
					console.error(error);
				});
		}

		transformTimeManResponseToPayload(data)
		{
			if (isEmpty(data))
			{
				return {};
			}

			const durationSec = (data.DURATION === '00:00:00') ? undefined : parseHmsToSec(data.DURATION);

			const payload = {
				STATE: data.STATUS,
				INFO: {
					DATE_START: parseDateToSec(data.TIME_START),
					DATE_FINISH: parseDateToSec(data.TIME_FINISH),
					DURATION: durationSec,
					TIME_LEAKS: parseHmsToSec(data.TIME_LEAKS),
					ACTIVE: data.ACTIVE,
					CURRENT_STATUS: data.STATUS,
					RECOMMENDED_CLOSE_TIMESTAMP: parseDateToSec(data.TIME_FINISH_DEFAULT),
				},
				TIME_FINISH_DEFAULT: parseDateToSec(data.TIME_FINISH_DEFAULT),
			};

			if (payload.TIME_FINISH_DEFAULT === null || payload.TIME_FINISH_DEFAULT === undefined)
			{
				delete payload.TIME_FINISH_DEFAULT;
			}

			return payload;
		}

		openWorkTime()
		{
			inAppUrl.open('/timeman/work.time', {
				title: Loc.getMessage('MOBILE_MORE_MENU_WORKTIME_TIME_PLACEHOLDER'),
			});
		}
	}

	WorkTime.propTypes = {
		showBorder: PropTypes.bool,
		testId: PropTypes.string.isRequired,
		workTime: PropTypes.object,
	};

	module.exports = {
		WorkTime,
	};
});
