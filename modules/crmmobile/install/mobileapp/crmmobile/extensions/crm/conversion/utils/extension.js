/**
 * @module crm/conversion/utils
 */
jn.define('crm/conversion/utils', (require, exports, module) => {
	const { prepareConversionFields } = require('crm/conversion/utils/prepare-fields');
	const { prepareConversionConfig } = require('crm/conversion/utils/prepare-config');

	module.exports = { prepareConversionFields, prepareConversionConfig };
});
