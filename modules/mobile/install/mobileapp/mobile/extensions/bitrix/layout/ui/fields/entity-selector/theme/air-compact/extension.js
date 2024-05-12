/**
 * @module layout/ui/fields/entity-selector/theme/air-compact
 */
jn.define('layout/ui/fields/entity-selector/theme/air-compact', (require, exports, module) => {
	const { EntitySelectorFieldClass } = require('layout/ui/fields/entity-selector');
	const { AirCompactThemeField } = require('layout/ui/fields/base/theme/air-compact');

	/** @type {function(object): object} */
	const EntitySelectorField = AirCompactThemeField(EntitySelectorFieldClass);

	module.exports = {
		EntitySelectorField,
	};
});
