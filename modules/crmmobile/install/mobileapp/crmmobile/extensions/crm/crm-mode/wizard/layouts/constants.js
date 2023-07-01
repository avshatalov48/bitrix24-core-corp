/**
 * @module crm/crm-mode/wizard/layouts/constants
 */
jn.define('crm/crm-mode/wizard/layouts/constants', (require, exports, module) => {
	const { TypeId } = require('crm/type');
	const { transparent } = require('utils/color');
	const EXTENSION_PATH = `${currentDomain}/bitrix/mobileapp/crmmobile/extensions/crm/crm-mode/wizard/layouts/images/`;

	const MODES = {
		simple: 'SIMPLE',
		classic: 'CLASSIC',
	};

	const ENTITY_COLORS = {
		[TypeId.Lead]: {
			backgroundColor: transparent('#55d0e0', 0.18),
			color: '#07b4aa',
		},
		[TypeId.Deal]: {
			backgroundColor: transparent('#a77bde', 0.16),
			color: '#a572e6',
		},
		[TypeId.Company]: {
			backgroundColor: '#fff1d6',
			color: '#c48300',
		},
		[TypeId.Contact]: {
			backgroundColor: '#f1fbd0',
			color: '#7fa800',
		},
	};

	module.exports = { EXTENSION_PATH, MODES, ENTITY_COLORS };
});
