/**
 * @module collab/create/src/settings-banner
 */
jn.define('collab/create/src/settings-banner', (require, exports, module) => {
	const { Text3, Text5 } = require('ui-system/typography/text');
	const { Area } = require('ui-system/layout/area');
	const { Color } = require('tokens');
	const { Loc } = require('loc');

	const CollabSettingsBanner = ({ onClick }) => {
		return Area(
			{
				onClick,
				divider: true,
			},
			Text3({
				text: Loc.getMessage('M_COLLAB_CREATE_SETTINGS_TITLE'),
				color: Color.base4,
				style: {
					marginBottom: 2,
				},
			}),
			Text5({
				text: Loc.getMessage('M_COLLAB_CREATE_SETTINGS_DESCRIPTION'),
				color: Color.base4,
			}),
		);
	};

	module.exports = { CollabSettingsBanner };
});
