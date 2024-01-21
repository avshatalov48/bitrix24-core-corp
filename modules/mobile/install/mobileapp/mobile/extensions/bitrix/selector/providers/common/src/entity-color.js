/**
 * @module selector/providers/common/src/entity-color
 */
jn.define('selector/providers/common/src/entity-color', (require, exports, module) => {
	const AppTheme = require('apptheme');

	const Color = (code, group = 'background') => {
		const Colors = {
			background: {
				user: AppTheme.colors.accentSoftBlue1,
				userExtranet: AppTheme.colors.accentMainWarning,
				userAll: AppTheme.colors.accentSoftGreen1,
				'meta-user': AppTheme.colors.accentSoftGreen1,
				group: AppTheme.colors.accentSoftBlue1,
				project: AppTheme.colors.accentSoftBlue1,
				'project-tag': AppTheme.colors.base5,
				'task-tag': AppTheme.colors.base5,
				groupExtranet: AppTheme.colors.accentMainWarning,
				department: AppTheme.colors.bgSeparatorSecondary,
				section: AppTheme.colors.accentSoftRed2,
				product: AppTheme.colors.bgContentPrimary,
				contractor: AppTheme.colors.accentExtraPurple,
				store: AppTheme.colors.accentExtraPurple,
				lead: AppTheme.colors.accentExtraAqua,
				deal: AppTheme.colors.accentExtraPurple,
				contact: AppTheme.colors.accentMainSuccess,
				company: AppTheme.colors.accentMainWarning,
				quote: AppTheme.colors.accentExtraAqua,
				smart_invoice: AppTheme.colors.accentMainLinks,
				order: AppTheme.colors.accentExtraBrown,
				dynamic: AppTheme.colors.accentMainPrimary,
				default: AppTheme.colors.accentSoftRed2,
			},
			subtitle: {
				userExtranet: AppTheme.colors.accentMainWarning,
				groupExtranet: AppTheme.colors.accentMainWarning,
			},
			title: {
				userExtranet: AppTheme.colors.accentMainWarning,
				groupExtranet: AppTheme.colors.accentMainWarning,
			},
			tag: {
				groupExtranet: AppTheme.colors.accentSoftOrange1,
			},

		};

		return Colors?.[group]?.[code] || Colors?.[group]?.default || AppTheme.colors.base7;
	};

	module.exports = { Color };
});
