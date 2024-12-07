/**
 * @module stafftrack/department-statistics/month-statistics
 */
jn.define('stafftrack/department-statistics/month-statistics', (require, exports, module) => {
	const { Loc } = require('loc');
	const { Color, Indent } = require('tokens');
	const { Text3 } = require('ui-system/typography/text');

	const { ShiftManager } = require('stafftrack/data-managers/shift-manager');
	const { DateHelper } = require('stafftrack/date-helper');
	const { TableStatisticsView } = require('stafftrack/department-statistics/table-statistics-view');
	const { TableUser } = require('stafftrack/department-statistics/table-user');
	const { MonthSelector } = require('stafftrack/department-statistics/month-selector');
	const { TableStatistics } = require('stafftrack/department-statistics/skeleton');

	const { PureComponent } = require('layout/pure-component');

	class MonthStatistics extends PureComponent
	{
		/**
		 * @param props {{departmentId: number}}
		 */
		constructor(props)
		{
			super(props);

			this.todayMonthCode = DateHelper.getMonthCode(new Date());

			this.state = {
				loading: false,
				monthCode: this.todayMonthCode,
				statistics: [],
			};

			this.onMonthPickedHandler = this.onMonthPickedHandler.bind(this);
			this.renderStatisticsUser = this.renderStatisticsUser.bind(this);
			this.renderStatisticsValue = this.renderStatisticsValue.bind(this);
			this.load = this.load.bind(this);

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
			if (!ShiftManager.hasDepartmentMonthStatistics(this.departmentId, this.state.monthCode))
			{
				this.setState({ loading: true });
			}

			const { users } = await ShiftManager.getDepartmentStatistics(this.departmentId);
			const monthStatistics = await ShiftManager.getDepartmentMonthStatistics(
				this.departmentId,
				this.state.monthCode,
			);

			const statistics = monthStatistics
				.sort((a, b) => b.checkinCount - a.checkinCount)
				.map((it) => ({
					...users.find((user) => user.id === it.userId),
					checkinCount: it.checkinCount,
				}))
			;

			this.setState({ statistics, loading: false });
		}

		render()
		{
			const { statistics, loading } = this.state;

			return View(
				{
					style: {
						flex: 1,
					},
				},
				this.renderMonthPicker(),
				loading && TableStatistics(9),
				!loading && this.renderTableStatistics(statistics),
			);
		}

		renderMonthPicker()
		{
			return View(
				{
					style: {
						marginTop: Indent.XL4.toNumber(),
						marginBottom: Indent.M.toNumber(),
					},
				},
				new MonthSelector({
					onPick: this.onMonthPickedHandler,
				}),
			);
		}

		onMonthPickedHandler(monthCode)
		{
			this.setState({ monthCode }, this.load);
		}

		renderTableStatistics(statistics)
		{
			return new TableStatisticsView({
				items: statistics,
				left: {
					title: Loc.getMessage('M_STAFFTRACK_DEPARTMENT_STATISTICS_EMPLOYEES'),
					render: this.renderStatisticsUser,
				},
				right: {
					title: Loc.getMessage('M_STAFFTRACK_DEPARTMENT_STATISTICS_CHECK_INS'),
					render: this.renderStatisticsValue,
				},
			});
		}

		renderStatisticsUser(user)
		{
			return TableUser(user);
		}

		renderStatisticsValue(user)
		{
			return Text3({
				testId: `stafftrack-department-month-statistics-user-${user.id}`,
				text: user.checkinCount.toString(),
				color: Color.base3,
			});
		}
	}

	module.exports = { MonthStatistics };
});
