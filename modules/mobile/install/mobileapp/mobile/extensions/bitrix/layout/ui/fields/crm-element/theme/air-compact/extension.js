/**
 * @module layout/ui/fields/crm-element/theme/air-compact
 */
jn.define('layout/ui/fields/crm-element/theme/air-compact', (require, exports, module) => {
	const { CrmElementFieldClass } = require('layout/ui/fields/crm-element');
	const { AirCompactThemeField } = require('layout/ui/fields/base/theme/air-compact');

	const CrmElementField = AirCompactThemeField(CrmElementFieldClass);

	module.exports = {
		CrmElementField,
	};
});
