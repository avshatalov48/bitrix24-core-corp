/**
 * @module layout/ui/fields/datetime/theme/air-compact
 */
jn.define('layout/ui/fields/datetime/theme/air-compact', (require, exports, module) => {
	const { DateTimeFieldClass } = require('layout/ui/fields/datetime');
	const { AirCompactThemeField } = require('layout/ui/fields/base/theme/air-compact');

	/** @type {function(object): object} */
	const DateTimeField = AirCompactThemeField(DateTimeFieldClass);

	module.exports = {
		AirCompactThemeField,
		DateTimeField,
	};
});
