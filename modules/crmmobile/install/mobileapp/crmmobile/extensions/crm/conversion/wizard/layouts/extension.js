/**
 * @module crm/conversion/wizard/layouts
 */
jn.define('crm/conversion/wizard/layouts', (require, exports, module) => {
	const { ConversionWizardFieldsLayout } = require('crm/conversion/wizard/layouts/fields');
	const { ConversionWizardEntitiesLayout } = require('crm/conversion/wizard/layouts/entities');

	module.exports = { ConversionWizardEntitiesLayout, ConversionWizardFieldsLayout };
});
