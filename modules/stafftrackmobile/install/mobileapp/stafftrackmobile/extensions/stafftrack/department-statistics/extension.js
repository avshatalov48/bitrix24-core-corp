/**
 * @module stafftrack/department-statistics
 */
jn.define('stafftrack/department-statistics', (require, exports, module) => {
	const { Loc } = require('loc');
	const { BottomSheet } = require('bottom-sheet');
	const { Color, Indent } = require('tokens');
	const { Area } = require('ui-system/layout/area');
	const { Box } = require('ui-system/layout/box');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { H4 } = require('ui-system/typography/heading');
	const { StageSelector, CardDesign } = require('ui-system/blocks/stage-selector');

	const { ShiftManager } = require('stafftrack/data-managers/shift-manager');
	const { OptionManager, OptionEnum } = require('stafftrack/data-managers/option-manager');
	const { Skeleton } = require('stafftrack/department-statistics/skeleton');
	const { SegmentButton } = require('stafftrack/department-statistics/segment-button');
	const { TodayStatistics } = require('stafftrack/department-statistics/today-statistics');
	const { MonthStatistics } = require('stafftrack/department-statistics/month-statistics');
	const { Analytics, StatsOpenEnum } = require('stafftrack/analytics');

	const { PureComponent } = require('layout/pure-component');

	const STATISTICS_FOR_TODAY = 'today';
	const STATISTICS_FOR_MONTH = 'month';

	class DepartmentStatistics extends PureComponent
	{
		/**
		 * @param props {{departments: Department[], selectedDepartmentId: number}}
		 */
		constructor({ departments })
		{
			super({ departments });

			this.refs = {
				layoutWidget: null,
				todayStatistics: null,
				monthStatistics: null,
			};

			const selectedDepartmentId = Number(OptionManager.getOption(OptionEnum.SELECTED_DEPARTMENT_ID));
			this.selectedDepartment = this.getDepartmentById(selectedDepartmentId) ?? departments[0];

			this.state = {
				rangeMode: STATISTICS_FOR_TODAY,
				isLoading: true,
				departmentId: this.selectedDepartment.id,
			};

			this.onRangeModeSelectedHandler = this.onRangeModeSelectedHandler.bind(this);

			void this.load();

			Analytics.sendStatisticsOpen(StatsOpenEnum.DEPARTMENT);
		}

		get departments()
		{
			return this.props.departments;
		}

		get departmentId()
		{
			return this.selectedDepartment.id;
		}

		get departmentName()
		{
			return this.selectedDepartment.name;
		}

		componentWillUnmount()
		{
			this.saveSelectedDepartment();
		}

		saveSelectedDepartment()
		{
			if (this.departmentId !== this.props.selectedDepartmentId)
			{
				OptionManager.saveSelectedDepartmentId(this.departmentId);
			}
		}

		async load()
		{
			if (!ShiftManager.hasDepartmentStatistics(this.departmentId))
			{
				this.setState({ isLoading: true });
			}

			this.refs.todayStatistics?.setDepartmentId(this.departmentId);
			this.refs.monthStatistics?.setDepartmentId(this.departmentId);

			await ShiftManager.getDepartmentStatistics(this.departmentId);

			this.setState({ isLoading: false, departmentId: this.departmentId });
		}

		show(parentLayout = PageManager)
		{
			void new BottomSheet({ component: this })
				.setParentWidget(parentLayout)
				.setBackgroundColor(Color.bgSecondary.toHex())
				.disableContentSwipe()
				.setMediumPositionPercent(70)
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
				this.state.isLoading && Skeleton(),
				this.renderHeader(),
				this.renderDepartmentSelector(),
				this.renderContent(),
			);
		}

		renderHeader()
		{
			const shouldDisplay = !this.state.isLoading;

			return Area(
				{
					isFirst: true,
					style: {
						display: shouldDisplay ? 'flex' : 'none',
						flexDirection: 'row',
						alignItems: 'center',
					},
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
					text: this.departments.length > 1
						? Loc.getMessage('M_STAFFTRACK_DEPARTMENT_STATISTICS_DEPARTMENT_CHECKINS')
						: Loc.getMessage('M_STAFFTRACK_DEPARTMENT_STATISTICS_DEPARTMENT_NAME_CHECKINS', {
							'#NAME#': this.departmentName,
						}),
					style: {
						flex: 1,
						marginLeft: Indent.XL.toNumber(),
					},
				}),
			);
		}

		renderDepartmentSelector()
		{
			const shouldDisplay = (!this.state.isLoading && this.departments.length > 1);

			return Area(
				{
					isFirst: true,
					style: {
						display: shouldDisplay ? 'flex' : 'none',
					},
				},
				StageSelector({
					testId: 'stafftrack-department-selector',
					cardDesign: CardDesign.SECONDARY,
					cardBorder: true,
					leftIcon: Icon.DEPARTMENT,
					title: this.departmentName,
					onClick: () => {
						this.onDepartmentSelectorClick();
					},
				}),
			);
		}

		renderContent()
		{
			const shouldDisplay = !this.state.isLoading;

			return Area(
				{
					isFirst: true,
					style: {
						display: shouldDisplay ? 'flex' : 'none',
						flex: 1,
					},
				},
				this.renderStatisticsRangeMode(),
				this.renderTodayStatistics(),
				this.renderMonthStatistics(),
			);
		}

		renderStatisticsRangeMode()
		{
			return new SegmentButton({
				segments: [
					{
						id: STATISTICS_FOR_TODAY,
						title: Loc.getMessage('M_STAFFTRACK_DEPARTMENT_STATISTICS_FOR_TODAY'),
						selected: this.state.rangeMode === STATISTICS_FOR_TODAY,
					},
					{
						id: STATISTICS_FOR_MONTH,
						title: Loc.getMessage('M_STAFFTRACK_DEPARTMENT_STATISTICS_FOR_MONTH'),
						selected: this.state.rangeMode === STATISTICS_FOR_MONTH,
					},
				],
				onSegmentSelected: this.onRangeModeSelectedHandler,
			});
		}

		onRangeModeSelectedHandler(rangeMode)
		{
			this.setState({ rangeMode });
		}

		renderTodayStatistics()
		{
			return View(
				{
					style: {
						display: this.state.rangeMode === STATISTICS_FOR_TODAY ? 'flex' : 'none',
						flex: 1,
					},
				},
				new TodayStatistics({
					departmentId: this.departmentId,
					layoutWidget: this.refs.layoutWidget,
					ref: (ref) => {
						this.refs.todayStatistics = ref;
					},
				}),
			);
		}

		renderMonthStatistics()
		{
			return View(
				{
					style: {
						display: this.state.rangeMode === STATISTICS_FOR_MONTH ? 'flex' : 'none',
						flex: 1,
					},
				},
				new MonthStatistics({
					departmentId: this.departmentId,
					ref: (ref) => {
						this.refs.monthStatistics = ref;
					},
				}),
			);
		}

		onDepartmentSelectorClick()
		{
			// eslint-disable-next-line promise/catch-or-return
			this.refs.layoutWidget
				.openWidget('selector', {
					backdrop: {},
					title: Loc.getMessage('M_STAFFTRACK_DEPARTMENT_STATISTICS_SELECT_DEPARTMENT'),
				})
				.then((selector) => {
					selector.setSearchEnabled(false);
					selector.allowMultipleSelection(false);
					selector.setItems(this.prepareDepartmentItems());
					selector.setListener((eventName, data) => {
						if (eventName === 'onSelectedChanged')
						{
							const departmentId = parseInt(data.items[0].id, 10);
							this.selectDepartment(departmentId);
							selector.close();
						}
					});
				})
			;
		}

		prepareDepartmentItems()
		{
			return this.departments.map((department) => ({
				id: department.id,
				title: department.name,
				useLetterImage: false,
				sectionCode: 'departments',
			}));
		}

		selectDepartment(departmentId)
		{
			if (this.selectedDepartment.id === departmentId)
			{
				return;
			}

			this.selectedDepartment = this.getDepartmentById(departmentId);

			void this.load();
		}

		getDepartmentById(departmentId)
		{
			return this.departments.find((department) => department.id === departmentId);
		}
	}

	module.exports = { DepartmentStatistics };
});
