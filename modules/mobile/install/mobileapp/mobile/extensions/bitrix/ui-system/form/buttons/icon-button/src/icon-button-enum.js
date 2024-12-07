/**
 * @module ui-system/form/buttons/icon-button/src/icon-button-enum
 */
jn.define('ui-system/form/buttons/icon-button/src/icon-button-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');
	const { Indent } = require('tokens');
	const { ButtonSize } = require('ui-system/form/buttons/button');

	/**
	 * @class IconButtonSize
	 */
	class IconButtonSize extends BaseEnum
	{
		/**
		 *
		 * @param {ButtonSize} buttonSize
		 * @param {ButtonSize} defaultSize
		 * @return {IconButtonSize}
		 */
		static getIconButton(buttonSize, defaultSize)
		{
			const size = ButtonSize.resolve(buttonSize, defaultSize);

			return IconButtonSize.getEnum(size.getName());
		}

		/**
		 * @public
		 * @return {number}
		 */
		getIconSize()
		{
			const { iconSize } = this.getValue();

			return iconSize;
		}

		/**
		 * @public
		 * @return number
		 */
		getInternalIndents()
		{
			const { indent } = this.getValue();

			return indent.toNumber();
		}

		/**
		 * @public
		 * @return number
		 */
		getSquaredIndents()
		{
			const { squaredIndent } = this.getValue();

			return squaredIndent.toNumber();
		}
	}

	IconButtonSize.XL = new IconButtonSize(
		'XL',
		{
			iconSize: 28,
			indent: Indent.XL,
			squaredIndent: Indent.L,
		},
	);
	IconButtonSize.L = new IconButtonSize(
		'L',
		{
			iconSize: 28,
			indent: Indent.L,
			squaredIndent: Indent.M,
		},
	);
	IconButtonSize.M = new IconButtonSize(
		'M',
		{
			iconSize: 24,
			indent: Indent.L,
			squaredIndent: Indent.S,
		},
	);
	IconButtonSize.S = new IconButtonSize(
		'S',
		{
			iconSize: 24,
			indent: Indent.S,
			squaredIndent: Indent.XS2,
		},
	);
	IconButtonSize.XS = new IconButtonSize(
		'XS',
		{
			iconSize: 20,
			indent: Indent.XS,
			squaredIndent: Indent.XS2,
		},
	);

	Object.freeze(IconButtonSize);

	module.exports = { IconButtonSize };
});
