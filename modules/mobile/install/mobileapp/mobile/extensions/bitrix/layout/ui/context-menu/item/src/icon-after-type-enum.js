/**
 * @module layout/ui/context-menu/item/src/icon-after-type-enum
 */
jn.define('layout/ui/context-menu/item/src/icon-after-type-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');
	const { Icon } = require('assets/icons');

	/**
	 * @class IconAfterType
	 * @template TIconAfterType
	 * @extends {BaseEnum<IconAfterType>}
	 */
	class IconAfterType extends BaseEnum
	{
		static WEB = new IconAfterType('WEB', Icon.GO_TO);

		static LOCK = new IconAfterType('LOCK', Icon.LOCK);

		getIcon()
		{
			return this.getValue();
		}
	}

	module.exports = {
		IconAfterType,
	};
});
