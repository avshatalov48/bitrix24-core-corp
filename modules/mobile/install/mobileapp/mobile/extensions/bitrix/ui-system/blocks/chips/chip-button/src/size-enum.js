/**
 * @module ui-system/blocks/chips/chip-button/src/size-enum
 */
jn.define('ui-system/blocks/chips/chip-button/src/size-enum', (require, exports, module) => {
	const { Indent, Corner } = require('tokens');
	const { BaseEnum } = require('utils/enums/base');
	const { Text4, Text5 } = require('ui-system/typography/text');

	/**
	 * @class ChipButtonSize
	 * @template TChipButtonSize
	 * @extends {BaseEnum<ChipButtonSize>}
	 */
	class ChipButtonSize extends BaseEnum
	{
		static NORMAL = new ChipButtonSize('NORMAL', {
			text: Text4,
			radius: Corner.M,
			height: 32,
			icon: Indent.M,
			avatar: {
				size: 20,
			},
			indent: {
				left: {
					text: Indent.XL.toNumber(),
					icon: Indent.S.toNumber(),
					avatar: Indent.M.toNumber(),
				},
				right: {
					text: Indent.XL.toNumber(),
					dropdown: Indent.XS.toNumber(),
					icon: Indent.S.toNumber(),
				},
			},
		});

		static SMALL = new ChipButtonSize('SMALL', {
			text: Text5,
			radius: Corner.S,
			height: 24,
			icon: Indent.S,
			avatar: {
				size: 20,
			},
			indent: {
				left: {
					text: Indent.L.toNumber(),
					icon: Indent.XS.toNumber(),
					avatar: Indent.S.toNumber(),
				},
				right: {
					text: Indent.L.toNumber(),
					dropdown: Indent.XS.toNumber(),
					icon: Indent.XS.toNumber(),
				},
			},
		});

		getIndent(direction = 'right', type = 'text')
		{
			return this.getValue()?.indent?.[direction]?.[type];
		}

		getTypography()
		{
			return this.getValue().text;
		}

		getHeight()
		{
			return this.getValue().height;
		}

		getRadius()
		{
			return this.getValue().radius;
		}

		getAvatar()
		{
			return this.getValue().avatar;
		}

		getIconIndents()
		{
			return this.getValue().icon;
		}
	}

	module.exports = { ChipButtonSize };
});
