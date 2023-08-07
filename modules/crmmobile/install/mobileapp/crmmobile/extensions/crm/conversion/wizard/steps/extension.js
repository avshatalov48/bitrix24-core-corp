/**
 * @module crm/conversion/wizard/steps
 */
jn.define('crm/conversion/wizard/steps', (require, exports, module) => {
	const { ConversionWizardEntitiesStep, ENTITIES } = require('crm/conversion/wizard/steps/entities');
	const { ConversionWizardFieldsStep, FIELDS } = require('crm/conversion/wizard/steps/fields');
	const wizardSteps = [ConversionWizardEntitiesStep, ConversionWizardFieldsStep];

	module.exports = {
		FIELDS,
		ENTITIES,
		wizardSteps,
		ConversionWizardFieldsStep,
		ConversionWizardEntitiesStep,
	};
});
