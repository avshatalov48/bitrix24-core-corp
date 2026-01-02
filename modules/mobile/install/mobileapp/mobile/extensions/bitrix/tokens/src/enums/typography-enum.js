/**
 * @module tokens/src/enums/typography-enum
 */
jn.define('tokens/src/enums/typography-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

	const isAndroid = Application.getPlatform() !== 'ios';

	/**
	 * @class TypographyEnum
	 * @extends {BaseEnum<TypographyEnum>}
	 */
	class TypographyEnum extends BaseEnum
	{
		/**
		 * @public
		 * @param {Boolean} accent
		 * @param {Typography} token
		 * @return {Typography}
		 */
		static getToken({ token, accent })
		{
			if (!this.has(token))
			{
				return token;
			}

			let tokenName = token.getName();
			if (accent)
			{
				tokenName = tokenName.endsWith('Accent') ? tokenName : `${tokenName}Accent`;
			}

			return this.getEnum(tokenName);
		}

		/**
		 * @public
		 * @param {String | Number} size
		 * @param {Boolean} header
		 * @param {Boolean} accent
		 * @return {Typography}
		 */
		static getTokenBySize({ size, header = false, accent = false })
		{
			const token = header && TypographyEnum.isValidHeadingSize(size) ? `h${size}` : `text${size}`;
			const tokenName = accent && !token.includes('Accent') ? `${token}Accent` : token;

			return this.getEnum(tokenName);
		}

		/**
		 * @param {string} tokenName
		 * @return {Typography}
		 */
		static getEnum(tokenName)
		{
			const typographyToken = super.getEnum(tokenName);

			/**
			 * Should be removed after a full migration to AppTheme
			 */
			if (!typographyToken?.value)
			{
				const preparedTokenName = tokenName.startsWith('text')
					? tokenName.replace('text', 'body')
					: tokenName;

				return super.getEnum(preparedTokenName) || super.getEnum(`${preparedTokenName}Style`);
			}

			return typographyToken;
		}

		/**
		 * @param {number} size
		 * @returns {boolean}
		 */
		static isValidTextSize(size)
		{
			return size >= 1 && size <= 7;
		}

		/**
		 * @param {number} size
		 * @returns {boolean}
		 */
		static isValidHeadingSize(size)
		{
			return size >= 1 && size <= 5;
		}

		/**
		 * @return {{fontSize: number, fontWeight: string, letterSpacing: number}}
		 */
		getStyle()
		{
			const { fontSize } = this.getValue();

			const style = { fontSize };

			const letterSpacing = this.getLetterSpacing();

			if (letterSpacing)
			{
				style.letterSpacing = letterSpacing;
			}

			const fontWeight = this.getFontWeight();

			if (fontWeight)
			{
				style.fontWeight = fontWeight;
			}

			return style;
		}

		getFontWeight()
		{
			const { fontWeight } = this.getValue();

			if (fontWeight > 0)
			{
				return String(fontWeight);
			}

			const styleWeightMap = {
				Regular: '400',
				Medium: '500',
				'Semi Bold': '600',
			};

			return styleWeightMap[fontWeight] || null;
		}

		getLetterSpacing()
		{
			const { letterSpacing } = this.getValue();

			return letterSpacing > 0 ? letterSpacing : null;
		}

		getDigitWidth()
		{
			const width = {
				h1: 17,
				h2: 14,
				h3: 13,
				h4: 12,
				h5: 10,

				h1Accent: 17,
				h2Accent: 14,
				h3Accent: 13,
				h4Accent: 12,
				h5Accent: 11,

				text1: 13,
				text2: 11,
				text3: 11,
				text4: 10,
				text5: 9,
				text6: 8,
				text7: 7,
				textCapital: 7,

				text1Accent: 13,
				text2Accent: 12,
				text3Accent: 11,
				text4Accent: 10,
				text5Accent: 9,
				text6Accent: 9,
				text7Accent: 7,
				textCapitalAccent: 7,
			};
			const name = this.getName().replace('body', 'text').replace('Style', '');

			if (!width[name])
			{
				console.error('TypographyEnum: getDigitWidth: Invalid token name', name);

				return 0;
			}

			let androidValueShift = -1;
			if (width[name] > 14)
			{
				androidValueShift = -3;
			}
			else if (width[name] > 11)
			{
				androidValueShift = -2;
			}

			return width[name] + (isAndroid ? androidValueShift : 0);
		}

		getDigitHeight()
		{
			const digitHeight = {
				h1: 33,
				h2: 27,
				h3: 24,
				h4: 22,
				h5: 20,

				h1Accent: 33,
				h2Accent: 27,
				h3Accent: 24,
				h4Accent: 22,
				h5Accent: 20,

				text1: 24,
				text2: 22,
				text3: 21,
				text4: 20,
				text5: 18,
				text6: 16,
				text7: 14,
				textCapital: 14,

				text1Accent: 24,
				text2Accent: 22,
				text3Accent: 21,
				text4Accent: 20,
				text5Accent: 18,
				text6Accent: 16,
				text7Accent: 14,
				textCapitalAccent: 14,
			};
			const name = this.getName().replace('body', 'text').replace('Style', '');

			if (!digitHeight[name])
			{
				console.error('TypographyEnum: getDigitHeight: Invalid token name', name);

				return 0;
			}

			return digitHeight[name] + (isAndroid ? 1 : 0);
		}
	}

	module.exports = { TypographyEnum };
});
