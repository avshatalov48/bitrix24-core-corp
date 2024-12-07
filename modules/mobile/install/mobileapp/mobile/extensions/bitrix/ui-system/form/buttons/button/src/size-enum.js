/**
 * @module ui-system/form/buttons/button/src/size-enum
 */
jn.define('ui-system/form/buttons/button/src/size-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');
	const { Corner, Indent } = require('tokens');

	/**
	 * @class ButtonSize
	 * @template TButtonSize
	 * @extends {BaseEnum<ButtonSize>}
	 */
	class ButtonSize extends BaseEnum
	{
		static XL = new ButtonSize(
			'XL',
			{
				height: 48,
				text: {
					typography: {
						size: 2,
						accent: true,
					},
					indent: {
						base: Indent.XL4,
						icon: Indent.XS,
						badge: Indent.XS,
					},
				},
				border: {
					width: 1.4,
					radius: Corner.M,
				},
				icon: {
					size: 28,
					indent: {
						base: Indent.XL,
						squared: Indent.L,
						badge: Indent.XS,
					},
				},
				badge: {
					indent: Indent.XL4,
				},
			},
		);

		static L = new ButtonSize(
			'L',
			{
				height: 42,
				text: {
					typography: {
						size: 3,
						accent: true,
					},
					indent: {
						base: Indent.XL3,
						icon: Indent.XS,
						badge: Indent.XS,
					},
				},
				border: {
					width: 1.4,
					radius: Corner.M,
				},
				icon: {
					size: 28,
					indent: {
						base: Indent.M,
						squared: Indent.M,
						badge: Indent.XS,
					},
				},
				badge: {
					indent: Indent.XL3,
				},
			},
		);

		static M = new ButtonSize(
			'M',
			{
				height: 36,
				text: {
					typography: {
						size: 4,
						accent: true,
					},
					indent: {
						base: Indent.XL2,
						icon: Indent.XS2,
						badge: Indent.XS,
					},
				},
				border: {
					width: 1.4,
					radius: Corner.M,
				},
				icon: {
					size: 24,
					indent: {
						base: Indent.M,
						squared: Indent.S,
						badge: Indent.XS,

					},
				},
				badge: {
					indent: Indent.XL2,
				},
			},
		);

		static S = new ButtonSize(
			'S',
			{
				height: 28,
				text: {
					typography: {
						size: 4,
						accent: true,
					},
					indent: {
						base: Indent.L,
						icon: Indent.XS2,
						badge: Indent.XS,
					},
				},
				border: {
					width: 1,
					radius: Corner.M,
				},
				icon: {
					size: 24,
					indent: {
						base: Indent.S,
						squared: Indent.XS2,
						badge: Indent.XS,
					},
				},
				badge: {
					indent: Indent.L,
				},
			},
		);

		static XS = new ButtonSize(
			'XS',
			{
				height: 22,
				text: {
					typography: {
						size: 5,
						accent: true,
					},
					indent: {
						base: Indent.M,
						icon: Indent.XS2,
						badge: Indent.XS,
					},
				},
				border: {
					width: 1,
					radius: Corner.S,
				},
				icon: {
					size: 20,
					indent: {
						base: Indent.XS2,
						squared: Indent.XS2,
						badge: Indent.XS,
					},
				},
				badge: {
					indent: Indent.M,
				},
			},
		);

		getText()
		{
			const { text } = this.getValue();

			return text;
		}

		/**
		 * @public
		 * @return number
		 */
		getTextIndents(indentParams = {})
		{
			const { icon, badge } = indentParams;
			const { indent } = this.getText();

			if (icon)
			{
				return indent.icon.toNumber();
			}

			if (badge)
			{
				return indent.badge.toNumber();
			}

			return indent.base.toNumber();
		}

		/**
		 * @public
		 * @return object
		 */
		getBorder()
		{
			return this.getValue().border;
		}

		/**
		 * @public
		 * @return number
		 */
		getHeight()
		{
			const { height } = this.getValue();

			return height;
		}

		getTypography()
		{
			const { typography } = this.getText();

			return typography;
		}

		getIcon()
		{
			return this.getValue().icon;
		}

		getIconSize()
		{
			return this.getIcon().size;
		}

		getIconIndents({ badge, squared })
		{
			const { indent } = this.getIcon();

			if (squared)
			{
				return indent.squared.toNumber();
			}

			if (badge)
			{
				return indent.badge.toNumber();
			}

			return indent.base.toNumber();
		}

		getBadgeIndent()
		{
			return this.getValue().badge.indent.toNumber();
		}
	}

	module.exports = { ButtonSize };
});
