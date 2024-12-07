/**
 * @module stafftrack/department-statistics/today-statistics
 */
jn.define('stafftrack/department-statistics/today-statistics', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Type } = require('type');
	const { Color, Indent } = require('tokens');
	const { Haptics } = require('haptics');

	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { H3 } = require('ui-system/typography/heading');
	const { Text3 } = require('ui-system/typography/text');

	const { ShiftManager } = require('stafftrack/data-managers/shift-manager');
	const { DateHelper } = require('stafftrack/date-helper');
	const { TableStatisticsView } = require('stafftrack/department-statistics/table-statistics-view');
	const { TableUser } = require('stafftrack/department-statistics/table-user');
	const { ProgressBar } = require('stafftrack/department-statistics/progress-bar');
	const { ShiftView } = require('stafftrack/shift-view');
	const { todayStatisticsDaySumIcon, todayStatisticsEmptyStateIcon } = require('stafftrack/ui');

	const { PureComponent } = require('layout/pure-component');

	class TodayStatistics extends PureComponent
	{
		/**
		 * @param props {{departmentId: number}}
		 */
		constructor(props)
		{
			super(props);

			this.state = {
				users: [],
				statistics: [],
			};

			this.load = this.load.bind(this);

			this.renderStatisticsUser = this.renderStatisticsUser.bind(this);
			this.renderStatisticsValue = this.renderStatisticsValue.bind(this);
			this.onItemClickHandler = this.onItemClickHandler.bind(this);

			void this.load();
		}

		componentDidMount()
		{
			ShiftManager.on('updated', this.load);
		}

		componentWillUnmount()
		{
			ShiftManager.off('updated', this.load);
		}

		get departmentId()
		{
			return this.props.departmentId;
		}

		setDepartmentId(departmentId)
		{
			this.props.departmentId = departmentId;

			void this.load();
		}

		async load()
		{
			const { users, shifts } = await ShiftManager.getDepartmentStatistics(this.departmentId);

			const statistics = shifts
				.filter((shift) => shift.isWorkingStatus())
				.sort((a, b) => b.getDateCreate().getTime() - a.getDateCreate().getTime())
				.map((shift) => ({
					user: users.find((user) => user.id === shift.getUserId()),
					shift,
					time: DateHelper.formatTime(shift.getDateCreate()),
				}))
			;

			this.setState({ users, statistics });
		}

		render()
		{
			const isEmpty = this.state.statistics.length <= 0;

			return View(
				{
					style: {
						flex: 1,
					},
				},
				isEmpty && this.renderEmptyState(),
				!isEmpty && this.renderDaySum(),
				!isEmpty && this.renderTableStatistics(),
			);
		}

		renderEmptyState()
		{
			return View(
				{
					style: {
						flex: 1,
						alignItems: 'center',
						justifyContent: 'center',
						marginBottom: '20%',
					},
				},
				Image(
					{
						svg: {
							content: todayStatisticsEmptyStateIcon,
						},
						style: {
							width: 213,
							height: 117,
						},
					},
				),
				H3({
					text: Loc.getMessage('M_STAFFTRACK_DEPARTMENT_STATISTICS_EMPTY_STATE_TITLE'),
					color: Color.base1,
				}),
				Text3({
					text: Loc.getMessage('M_STAFFTRACK_DEPARTMENT_STATISTICS_EMPTY_STATE_DESCRIPTION'),
					color: Color.base2,
					style: {
						textAlign: 'center',
					},
				}),
			);
		}

		renderDaySum()
		{
			const { users, statistics } = this.state;

			return View(
				{
					style: {
						flexDirection: 'row',
						paddingVertical: Indent.XL4.toNumber(),
					},
				},
				Image(
					{
						svg: {
							content: todayStatisticsDaySumIcon,
						},
						style: {
							width: 100,
							height: 100,
							marginRight: Indent.XL4.toNumber(),
						},
					},
				),
				View(
					{
						style: {
							flex: 1,
						},
					},
					H3({
						testId: 'stafftrack-today-statistics-count',
						text: Loc.getMessagePlural(
							'M_STAFFTRACK_DEPARTMENT_STATISTICS_N_EMPLOYEES',
							statistics.length,
							{
								'#NUM#': statistics.length,
							},
						),
						color: Color.base0,
					}),
					Text3({
						text: Loc.getMessage('M_STAFFTRACK_DEPARTMENT_STATISTICS_CHECK_INS_ON_WORK'),
						color: Color.base2,
						style: {
							marginTop: Indent.S.toNumber(),
						},
					}),
					Text3({
						testId: 'stafftrack-today-statistics-date',
						text: Loc.getMessage('M_STAFFTRACK_DEPARTMENT_STATISTICS_N_EMPLOYEES_CHECKED_TODAY_DATE', {
							'#DATE#': DateHelper.formatDayMonth(new Date()),
						}),
						color: Color.base2,
						style: {
							marginBottom: Indent.XL3.toNumber(),
						},
					}),
					new ProgressBar({
						percent: statistics.length / users.length * 100,
					}),
				),
			);
		}

		renderTableStatistics()
		{
			return new TableStatisticsView({
				items: this.state.statistics,
				left: {
					title: Loc.getMessage('M_STAFFTRACK_DEPARTMENT_STATISTICS_EMPLOYEES'),
					render: this.renderStatisticsUser,
				},
				right: {
					title: Loc.getMessage('M_STAFFTRACK_DEPARTMENT_STATISTICS_CHECK_IN_TIME'),
					render: this.renderStatisticsValue,
				},
				onItemClick: this.onItemClickHandler,
			});
		}

		onItemClickHandler(item)
		{
			this.openShiftView(item.user, item.shift);
		}

		openShiftView(user, shift)
		{
			new ShiftView({
				user,
				shift,
			}).show(this.props.layoutWidget);

			Haptics.impactLight();
		}

		renderStatisticsUser(item)
		{
			return TableUser(item.user);
		}

		renderStatisticsValue(item)
		{
			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				Type.isStringFilled(item.shift.getAddress()) && IconView({
					size: 24,
					icon: Icon.LOCATION,
					color: Color.accentMainPrimary,
				}),
				Text3({
					text: item.time,
					color: Color.accentMainPrimary,
				}),
			);
		}
	}

	module.exports = { TodayStatistics };
});
