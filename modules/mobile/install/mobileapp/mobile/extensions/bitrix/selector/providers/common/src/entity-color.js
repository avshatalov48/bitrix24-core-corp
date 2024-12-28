/**
 * @module selector/providers/common/src/entity-color
 */
jn.define('selector/providers/common/src/entity-color', (require, exports, module) => {
	const { Color } = require('tokens');

	const getEntityColor = (code, group = 'background') => {
		const Colors = {
			background: {
				user: Color.accentSoftBlue1,
				userExtranet: Color.accentMainWarning,
				userAll: Color.accentSoftGreen1,
				'meta-user': Color.accentSoftGreen1,
				group: Color.accentSoftBlue1,
				project: Color.accentSoftBlue1,
				'project-tag': Color.base5,
				'task-tag': Color.base5,
				groupExtranet: Color.accentMainWarning,
				department: Color.bgSeparatorSecondary,
				section: Color.accentSoftRed2,
				product: Color.bgContentPrimary,
				contractor: Color.accentExtraPurple,
				store: Color.accentExtraPurple,
				lead: Color.accentExtraAqua,
				deal: Color.accentExtraPurple,
				contact: Color.accentMainSuccess,
				company: Color.accentMainWarning,
				quote: Color.accentExtraAqua,
				smart_invoice: Color.accentMainLinks,
				order: Color.accentExtraBrown,
				dynamic: Color.accentMainPrimary,
				default: Color.accentSoftRed2,
			},
			subtitle: {
				userExtranet: Color.accentMainWarning,
				groupExtranet: Color.accentMainWarning,
			},
			title: {
				userExtranet: Color.accentMainWarning,
				groupExtranet: Color.accentMainWarning,
			},
			tag: {
				groupExtranet: Color.accentSoftOrange1,
			},
		};

		return (Colors?.[group]?.[code] || Colors?.[group]?.default || Color.base7).toHex();
	};

	module.exports = { getEntityColor };
});
