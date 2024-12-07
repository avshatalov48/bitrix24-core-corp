/**
 * @module layout/ui/fields/crm-element/theme/air
 */
jn.define('layout/ui/fields/crm-element/theme/air', (require, exports, module) => {
	const { CrmElementFieldClass } = require('layout/ui/fields/crm-element');
	const { withTheme } = require('layout/ui/fields/theme');
	const { FieldWrapper } = require('layout/ui/fields/theme/air/elements/field-wrapper');
	const { Content } = require('layout/ui/fields/entity-selector/theme/air');
	const { Entity } = require('layout/ui/fields/crm-element/theme/air/src/entity');

	/**
	 * @param  {CrmElementField} field - instance of the CrmElementFieldClass.
	 * @return {function} - functional component
	 */
	const AirTheme = ({ field }) => FieldWrapper(
		{ field },
		Content(Entity)({ field }),
	);

	/** @type {function(object): object} */
	const CrmElementField = withTheme(CrmElementFieldClass, AirTheme);

	module.exports = {
		CrmElementField,
	};
});
