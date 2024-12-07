/**
 * @module stafftrack/ajax
 */
jn.define('stafftrack/ajax', (require, exports, module) => {
	const { ShiftAjax } = require('stafftrack/ajax/shift');
	const { DepartmentStatisticsAjax } = require('stafftrack/ajax/department-statistics');
	const { UserLinkStatisticsAjax } = require('stafftrack/ajax/user-link-statistics');
	const { OptionAjax } = require('stafftrack/ajax/option');
	const { FeatureAjax } = require('stafftrack/ajax/feature');

	module.exports = {
		ShiftAjax,
		DepartmentStatisticsAjax,
		UserLinkStatisticsAjax,
		OptionAjax,
		FeatureAjax,
	};
});
