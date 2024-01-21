/**
 * @module crm/crm-mode/wizard/layouts/src/constants
 */
jn.define('crm/crm-mode/wizard/layouts/src/constants', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { TypeId } = require('crm/type');
	const { transparent } = require('utils/color');
	const EXTENSION_PATH = `${currentDomain}/bitrix/mobileapp/crmmobile/extensions/crm/crm-mode/wizard/layouts/images/`;

	const MODES = {
		simple: 'SIMPLE',
		classic: 'CLASSIC',
	};

	const ENTITY_COLORS = {
		[TypeId.Lead]: {
			backgroundColor: transparent(AppTheme.colors.accentExtraAqua, 0.18),
			color: AppTheme.colors.accentExtraAqua,
		},
		[TypeId.Deal]: {
			backgroundColor: transparent(AppTheme.colors.accentExtraPurple, 0.16),
			color: AppTheme.colors.accentExtraPurple,
		},
		[TypeId.Company]: {
			backgroundColor: AppTheme.colors.accentSoftOrange2,
			color: AppTheme.colors.accentMainWarning,
		},
		[TypeId.Contact]: {
			backgroundColor: AppTheme.colors.accentSoftGreen2,
			color: AppTheme.colors.accentMainSuccess,
		},
	};

	module.exports = { EXTENSION_PATH, MODES, ENTITY_COLORS };
});
