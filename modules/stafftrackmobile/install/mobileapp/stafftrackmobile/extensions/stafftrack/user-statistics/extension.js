/**
 * @module stafftrack/user-statistics
 */
jn.define('stafftrack/user-statistics', (require, exports, module) => {
	const { Type } = require('type');
	const { Loc } = require('loc');
	const { BottomSheet } = require('bottom-sheet');
	const { debounce } = require('utils/function');
	const { Color, Indent } = require('tokens');
	const { showToast } = require('toast');
	const { outline: { send, cross } } = require('assets/icons');
	const { Haptics } = require('haptics');

	const { Box } = require('ui-system/layout/box');
	const { Area } = require('ui-system/layout/area');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Button } = require('ui-system/form/buttons/button');
	const { H4 } = require('ui-system/typography/heading');

	const { ShiftManager } = require('stafftrack/data-managers/shift-manager');
	const { UserLinkStatisticsAjax } = require('stafftrack/ajax');
	const { DateHelper } = require('stafftrack/date-helper');
	const { Calendar } = require('stafftrack/user-statistics/calendar');
	const { CalendarHeader } = require('stafftrack/user-statistics/calendar-header');
	const { MonthSelector } = require('stafftrack/user-statistics/month-selector');
	const { ShiftView } = require('stafftrack/shift-view');
	const { Analytics, StatsOpenEnum } = require('stafftrack/analytics');

	const { PureComponent } = require('layout/pure-component');

	class UserStatistics extends PureComponent
	{
		/**
		 * @param props {{user: User, monthCode: string, myCheckins: boolean}}
		 */
		constructor(props)
		{
			super(props);

			this.refs = {
				layoutWidget: this.props.layoutWidget || PageManager,
				calendarHeaderRef: null,
				calendarRef: null,
			};

			this.todayMonthCode = DateHelper.getMonthCode(new Date());
			this.monthCode = this.props.monthCode ?? this.todayMonthCode;

			/**
			 * @type {ShiftModel[]}
			 */
			this.shifts = [];
			this.isLoading = true;

			this.onMonthSwitched = this.onMonthSwitched.bind(this);
			this.onDateSelected = debounce(this.onDateSelected, 50, this);
			this.onDataUpdated = this.onDataUpdated.bind(this);
			this.showDialogSelector = this.showDialogSelector.bind(this);

			void this.update(this.monthCode);

			if (props.myCheckins === false)
			{
				Analytics.sendStatisticsOpen(StatsOpenEnum.COLLEAGUE);
			}
			else
			{
				Analytics.sendStatisticsOpen(StatsOpenEnum.PERSONAL);
			}
		}

		componentDidMount()
		{
			ShiftManager.on('updated', this.onDataUpdated);
		}

		componentWillUnmount()
		{
			ShiftManager.off('updated', this.onDataUpdated);
		}

		onDataUpdated()
		{
			void this.update(this.monthCode);
		}

		/**
		 * @return {User}
		 */
		get user()
		{
			return this.props.user;
		}

		update(monthCode)
		{
			this.setMonthCode(monthCode);

			if (ShiftManager.hasUserShiftsForMonth(this.user.id, monthCode))
			{
				const shifts = ShiftManager.getCachedUserShiftsForMonth(this.user.id, monthCode);
				this.setShifts(shifts);
				this.setLoading(false);

				return;
			}

			return this.load(monthCode);
		}

		async load(monthCode)
		{
			this.loaderStart = null;

			clearTimeout(this.loaderTimeout);
			this.loaderTimeout = setTimeout(() => {
				if (this.requestSent)
				{
					this.loaderStart = Date.now();
					this.setLoading(true);
				}
			}, 300);

			this.requestSent = true;
			const shifts = await ShiftManager.getUserShiftsForMonth(this.user.id, monthCode);
			this.requestSent = false;

			let timeout = 0;
			if (this.loaderStart)
			{
				const timePassed = Date.now() - this.loaderStart;
				timeout = Math.max(0, 300 - timePassed);
			}

			if (timeout > 0)
			{
				setTimeout(() => {
					if (this.monthCode === monthCode)
					{
						this.setShifts(shifts);
						this.setLoading(false);
					}
				}, timeout);
			}
			else if (this.monthCode === monthCode)
			{
				this.setShifts(shifts);
				this.setLoading(false);
			}
		}

		setLoading(isLoading)
		{
			this.isLoading = isLoading;
			this.refs.calendarHeaderRef?.setLoading(isLoading);
		}

		setMonthCode(monthCode)
		{
			this.monthCode = monthCode;
			this.refs.monthSelectorRef?.setMonthCode(monthCode);
		}

		setShifts(shifts)
		{
			this.shifts = shifts;
			this.refs.calendarHeaderRef?.setShiftCount(shifts.filter((shift) => shift.isWorkingStatus()).length);
			this.refs.calendarRef?.setShifts(shifts);
		}

		show(parentLayout = PageManager)
		{
			void new BottomSheet({ component: this })
				.setParentWidget(parentLayout)
				.disableContentSwipe()
				.setMediumPositionPercent(65)
				.setBackgroundColor(Color.bgSecondary.toHex())
				.open()
				.then((widget) => {
					this.refs.layoutWidget = widget;
				})
			;
		}

		render()
		{
			return Box(
				{
					backgroundColor: Color.bgSecondary,
				},
				this.renderHeader(),
				this.renderCalendar(),
				this.renderShareButton(),
			);
		}

		renderHeader()
		{
			return Area(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
					},
					isFirst: true,
				},
				IconView({
					icon: Icon.ARROW_TO_THE_LEFT,
					size: 24,
					color: Color.base4,
					onClick: () => this.refs.layoutWidget.close(),
				}),
				H4({
					numberOfLines: 1,
					ellipsize: 'end',
					text: this.props.myCheckins === false
						? Loc.getMessage('M_STAFFTRACK_USER_STATISTICS_USER_CHECKINS')
						: Loc.getMessage('M_STAFFTRACK_USER_STATISTICS_MY_CHECKINS'),
					style: {
						flex: 1,
						marginLeft: Indent.XL.toNumber(),
					},
				}),
				new MonthSelector({
					monthCode: this.monthCode,
					onPick: (monthCode) => {
						this.refs.calendarRef.setMonth(monthCode);

						void this.update(monthCode);
					},
					ref: (ref) => {
						this.refs.monthSelectorRef = ref;
					},
				}),
			);
		}

		renderCalendar()
		{
			return Area(
				{
					isFirst: true,
				},
				new CalendarHeader({
					user: this.user,
					shiftCount: this.shifts.filter((shift) => shift.isWorkingStatus()).length,
					isLoading: this.isLoading,
					ref: (ref) => {
						this.refs.calendarHeaderRef = ref;
					},
				}),
				new Calendar({
					shifts: this.shifts,
					onMonthSwitched: this.onMonthSwitched,
					onDateSelected: this.onDateSelected,
					ref: (ref) => {
						this.refs.calendarRef = ref;
					},
				}),
			);
		}

		onMonthSwitched(timestamp)
		{
			const date = new Date(timestamp * 1000);
			const monthCode = DateHelper.getMonthCode(date);
			void this.update(monthCode);
		}

		async onDateSelected(timestamp)
		{
			const date = new Date(timestamp * 1000);
			const monthCode = DateHelper.getMonthCode(date);
			await this.update(monthCode);

			const dateCode = DateHelper.getDayCode(date);
			const currentShift = this.shifts.find((shift) => {
				const shiftDateCode = DateHelper.getDayCode(shift.getShiftDate());

				return shiftDateCode === dateCode;
			});

			this.closeToast();
			if (currentShift)
			{
				this.openShiftView(currentShift);
			}
			else
			{
				this.showToastNoCheckIn();
			}
		}

		openShiftView(shift)
		{
			const { user } = this.props;

			const shiftView = new ShiftView({
				shift,
				user,
			});

			shiftView.show(this.refs.layoutWidget);

			Haptics.impactLight();
		}

		showToastNoCheckIn()
		{
			this.previousToast = showToast(
				{
					message: Loc.getMessage('M_STAFFTRACK_USER_STATISTICS_NO_CHECKIN'),
					svg: {
						content: cross(),
					},
					backgroundColor: Color.bgContentInapp.toHex(),
				},
				this.layoutWidget,
			);

			Haptics.notifyWarning();
		}

		closeToast()
		{
			this.previousToast?.close();
		}

		renderShareButton()
		{
			if (this.props.myCheckins === false)
			{
				return null;
			}

			return Area(
				{
					isFirst: true,
				},
				Button({
					leftIcon: Icon.SHARE,
					text: Loc.getMessage('M_STAFFTRACK_USER_STATISTICS_SHARE'),
					color: Color.baseWhiteFixed,
					backgroundColor: Color.accentMainPrimary,
					stretched: true,
					testId: 'stafftrack-user-statistics-share',
					onClick: this.showDialogSelector,
				}),
			);
		}

		async showDialogSelector()
		{
			const { DialogSelector } = await requireLazy('im:messenger/api/dialog-selector');

			const selector = new DialogSelector();
			selector.show({
				title: Loc.getMessage('M_STAFFTRACK_USER_STATISTICS_CHOOSE_CHAT'),
				layout: this.refs.layoutWidget,
			})
				.then((result) => this.sendLink(result))
				.catch((error) => console.error(error));
		}

		async sendLink(dialogData)
		{
			const { dialogId, name } = dialogData;
			if (Type.isNil(dialogId) || Type.isNil(name))
			{
				return;
			}

			const userStatisticsUrl = currentDomain + this.getUserStatisticsUrl();

			const result = await UserLinkStatisticsAjax.send(dialogId, userStatisticsUrl);
			if (result.status === 'success')
			{
				this.showToastChecksInSent();
			}
		}

		getUserStatisticsUrl()
		{
			return `/check-in/statistics/${this.user.id}/${this.user.hash}/?month=${this.monthCode}`;
		}

		showToastChecksInSent()
		{
			showToast(
				{
					message: Loc.getMessage('M_STAFFTRACK_USER_STATISTICS_CHECKINS_SENT_V2'),
					svg: {
						content: send(),
					},
					backgroundColor: Color.bgContentInapp.toHex(),
				},
				this.layoutWidget,
			);

			Haptics.notifySuccess();
		}
	}

	module.exports = { UserStatistics };
});
