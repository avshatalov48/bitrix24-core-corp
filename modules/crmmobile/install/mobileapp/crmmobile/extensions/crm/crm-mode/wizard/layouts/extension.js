/**
 * @module crm/crm-mode/wizard/layouts
 */
jn.define('crm/crm-mode/wizard/layouts', (require, exports, module) => {
	const { ConversionLayout } = require('crm/crm-mode/wizard/layouts/conversion/layout');
	const { ModeLayout } = require('crm/crm-mode/wizard/layouts/mode/layout');
	const { MODES } = require('crm/crm-mode/wizard/layouts/constants');

	module.exports = { ConversionLayout, ModeLayout, MODES };
});
