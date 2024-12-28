/**
 * @module assets/icons/src/base
 */
jn.define('assets/icons/src/base', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	/**
	 * @class BaseIcon
	 * @template TBaseIcon
	 * @extends {BaseEnum<BaseIcon>}
	 */
	class BaseIcon extends BaseEnum
	{
		getIconName()
		{
			return this.getValue().name;
		}

		getPath()
		{
			return this.getValue().path;
		}

		/**
		 * @public
		 * @return string
		 */
		getSvg()
		{
			return this.getValue().content;
		}

		static hasIcon(iconEnum)
		{
			return iconEnum instanceof BaseIcon;
		}
	}

	module.exports = {
		BaseIcon,
	};
});
