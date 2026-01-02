/**
 * @module ui-system/blocks/avatar/src/enums/empty-avatar
 */
jn.define('ui-system/blocks/avatar/src/enums/empty-avatar', (require, exports, module) => {
	const { AppTheme, Theme } = require('apptheme/extended');
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class EmptyAvatar
	 * @template TEmptyAvatar
	 * @extends {BaseEnum<EmptyAvatar>}
	 */
	class EmptyAvatar extends BaseEnum
	{
		static DEFAULT_USER = new EmptyAvatar('DEFAULT_USER', 'default_user');

		static COLLAB_USER = new EmptyAvatar('COLLAB_USER', 'collab_user');

		static EXTRANET_USER = new EmptyAvatar('EXTRANET_USER', 'extranet_user');

		getNamed()
		{
			const themeId = AppTheme.id || Theme.LIGHT.getValue();

			return `${this.getValue()}_${themeId}`;
		}
	}

	module.exports = { EmptyAvatar };
});
