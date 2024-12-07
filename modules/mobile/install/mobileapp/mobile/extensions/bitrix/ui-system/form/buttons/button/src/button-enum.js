/**
 * @module ui-system/form/buttons/button/src/button-enum
 */
jn.define('ui-system/form/buttons/button/src/button-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');
	const { Corner, Indent } = require('tokens');

	/**
	 * @class ButtonSize
	 * @template TButtonSize
	 * @extends {BaseEnum<ButtonSize>}
	 */
	class ButtonSize extends BaseEnum
	{
		/**
		 * @public
		 * @return number
		 */
		getInternalIndents()
		{
			const { indent } = this.getValue();

			return indent.toNumber();
		}

		getText()
		{
			const { text } = this.getValue();

			return text;
		}

		/**
		 * @public
		 * @return {Indent}
		 */
		getTextIndent()
		{
			const { indent } = this.getText();

			return indent;
		}

		/**
		 * @public
		 * @return number
		 */
		getCorner()
		{
			const { corner } = this.getValue();

			return corner.toNumber();
		}

		/**
		 * @public
		 * @return number
		 */
		getSize()
		{
			const { size } = this.getValue();

			return size;
		}

		getTypography()
		{
			const { typography } = this.getText();

			return typography;
		}
	}

	ButtonSize.XL = new ButtonSize(
		'XL',
		{
			size: 48,
			text: {
				typography: {
					size: 2,
					accent: true,
				},
			},
			indent: Indent.XL4,
			corner: Corner.M,
		},
	);
	ButtonSize.L = new ButtonSize(
		'L',
		{
			size: 42,
			text: {
				typography: {
					size: 3,
					accent: true,
				},
				indent: Indent.XS,
			},
			indent: Indent.XL3,
			corner: Corner.M,
		},
	);
	ButtonSize.M = new ButtonSize(
		'M',
		{
			size: 36,
			text: {
				typography: {
					size: 4,
					accent: true,
				},
				indent: Indent.XS2,
			},
			indent: Indent.XL2,
			corner: Corner.M,
		},
	);
	ButtonSize.S = new ButtonSize(
		'S',
		{
			size: 28,
			text: {
				typography: {
					size: 4,
					accent: true,
				},
				indent: Indent.XS2,
			},
			indent: Indent.L,
			corner: Corner.M,
		},
	);
	ButtonSize.XS = new ButtonSize(
		'XS',
		{
			size: 22,
			text: {
				typography: {
					size: 5,
					accent: true,
				},
				indent: Indent.XS2,
			},
			indent: Indent.M,
			corner: Corner.S,
		},
	);

	module.exports = { ButtonSize };
});
