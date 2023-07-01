/**
 * @module crm/crm-mode/wizard/steps
 */
jn.define('crm/crm-mode/wizard/steps', (require, exports, module) => {
	const { ConversionStep, CONVERSION } = require('crm/crm-mode/wizard/steps/conversion');
	const { ModeStep, MODE, MODES } = require('crm/crm-mode/wizard/steps/mode');
	const { ConversionFieldsStep, FIELDS } = require('crm/crm-mode/wizard/steps/fields');
	const wizardSteps = [ModeStep, ConversionStep, ConversionFieldsStep];

	module.exports = { wizardSteps, ModeStep, ConversionStep, ConversionFieldsStep, MODE, MODES, CONVERSION, FIELDS };
});
