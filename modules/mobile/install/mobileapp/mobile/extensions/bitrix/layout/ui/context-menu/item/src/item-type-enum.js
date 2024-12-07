/**
 * @module layout/ui/context-menu/item/src/item-type-enum
 */
jn.define('layout/ui/context-menu/item/src/item-type-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');
	const { Icon } = require('assets/icons');

	/**
	 * @class IconAfterType
	 * @template TIconAfterType
	 * @extends {BaseEnum<IconAfterType>}
	 */
	class ItemType extends BaseEnum
	{
		static ITEM = new ItemType('ITEM', {
			name: 'item',
		});

		static BUTTON = new ItemType('BUTTON', {
			name: 'button',
		});

		static LAYOUT = new ItemType('LAYOUT', {
			name: 'layout',
		});

		static CANCEL = new ItemType('CANCEL', {
			name: 'cancel',
			icon: Icon.CROSS,
		});

		static DELETE = new ItemType('DELETE', {
			name: 'delete',
			icon: Icon.TRASHCAN,
		});

		isLayout()
		{
			return this.equal(ItemType.LAYOUT);
		}

		getTypeName()
		{
			return this.getValue().name;
		}

		getIcon()
		{
			return this.getValue().icon;
		}
	}

	module.exports = {
		ItemType,
	};
});
