/**
 * @module stafftrack/check-in/statistics-menu
 */
jn.define('stafftrack/check-in/statistics-menu', (require, exports, module) => {
	const { Type } = require('type');
	const { Loc } = require('loc');
	const { BaseMenu, baseSectionType } = require('stafftrack/base-menu');

	/**
	 * @class StatisticsMenu
	 */
	class StatisticsMenu extends BaseMenu
	{
		getItems()
		{
			const result = [];

			result.push(this.getUserStatisticsItem());
			const departmentStatistics = this.getDepartmentStatisticsItem();
			if (departmentStatistics !== null)
			{
				result.push(departmentStatistics);
			}

			return result;
		}

		getUserStatisticsItem()
		{
			return {
				id: itemTypes.userStatistics,
				sectionCode: baseSectionType,
				testId: 'stafftrack-user-statistics-menu',
				title: Loc.getMessage('M_STAFFTRACK_CHECK_IN_MY_CHECKINS'),
			};
		}

		getDepartmentStatisticsItem()
		{
			const { departments } = this.props.user;

			if (Type.isArrayFilled(departments))
			{
				return {
					id: itemTypes.departmentStatistics,
					sectionCode: baseSectionType,
					testId: 'stafftrack-department-statistics-menu',
					title: Loc.getMessage('M_STAFFTRACK_CHECK_IN_DEPARTMENT_CHECKINS'),
					showTopSeparator: true,
				};
			}

			return null;
		}

		onItemSelected(item)
		{
			switch (item.id)
			{
				case itemTypes.userStatistics:
					this.onUserStatisticsSelect();
					break;
				case itemTypes.departmentStatistics:
					this.onDepartmentStatisticsSelect();
					break;
				default:
					break;
			}
		}

		async onUserStatisticsSelect()
		{
			const { UserStatistics } = await requireLazy('stafftrack:user-statistics');
			const { user } = this.props;

			const userStatistics = new UserStatistics({ user });

			return userStatistics.show(this.layoutWidget);
		}

		async onDepartmentStatisticsSelect()
		{
			const { DepartmentStatistics } = await requireLazy('stafftrack:department-statistics');
			const { departments } = this.props.user;

			const departmentStatistics = new DepartmentStatistics({ departments });

			return departmentStatistics.show(this.layoutWidget);
		}
	}

	const itemTypes = {
		userStatistics: 'userStatistics',
		departmentStatistics: 'departmentStatistics',
	};

	module.exports = { StatisticsMenu };
});
