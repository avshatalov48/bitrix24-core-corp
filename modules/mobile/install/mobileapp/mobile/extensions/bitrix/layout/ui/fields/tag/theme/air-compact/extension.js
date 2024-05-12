/**
 * @module layout/ui/fields/tag/theme/air-compact
 */
jn.define('layout/ui/fields/tag/theme/air-compact', (require, exports, module) => {
	const { TagFieldClass } = require('layout/ui/fields/tag');
	const { AirCompactThemeField } = require('layout/ui/fields/base/theme/air-compact');

	/** @type {function(object): object} */
	const TagField = AirCompactThemeField(TagFieldClass);

	module.exports = {
		TagField,
	};
});
