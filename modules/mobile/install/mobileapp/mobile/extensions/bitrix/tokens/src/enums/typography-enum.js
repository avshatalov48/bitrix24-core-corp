/**
 * @module tokens/src/enums/typography-enum
 */
jn.define('tokens/src/enums/typography-enum', (require, exports, module) => {
	const { BaseEnum } = require('utils/enums/base');

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
	}

	module.exports = { TypographyEnum };
});
