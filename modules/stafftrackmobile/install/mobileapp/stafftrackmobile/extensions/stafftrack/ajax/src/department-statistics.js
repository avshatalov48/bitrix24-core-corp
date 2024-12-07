/**
 * @module stafftrack/ajax/department-statistics
 */
jn.define('stafftrack/ajax/department-statistics', (require, exports, module) => {
	const { BaseAjax } = require('stafftrack/ajax/base');

	const DepartmentStatisticsActions = {
		GET: 'get',
		GET_FOR_MONTH: 'getForMonth',
	};

	class DepartmentStatisticsAjax extends BaseAjax
	{
		/**
		 * @returns {string}
		 */
		getEndpoint()
		{
			return 'stafftrack.DepartmentStatistics';
		}

		/**
		 * @param id {number}
		 * @param date { string }
		 * @returns {Promise<Object, void>}
		 */
		get(id, date)
		{
			return this.fetch(DepartmentStatisticsActions.GET, { id, date });
		}

		/**
		 * @param id {number}
		 * @param monthCode {string}
		 * @returns {Promise<Object, void>}
		 */
		getForMonth(id, monthCode)
		{
			return this.fetch(DepartmentStatisticsActions.GET_FOR_MONTH, { id, monthCode });
		}
	}

	module.exports = { DepartmentStatisticsAjax: new DepartmentStatisticsAjax() };
});
