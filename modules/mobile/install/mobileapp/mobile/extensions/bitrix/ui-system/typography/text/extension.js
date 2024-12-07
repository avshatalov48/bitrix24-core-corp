/**
 * @module ui-system/typography/text
 */
jn.define('ui-system/typography/text', (require, exports, module) => {
	const { TextBase } = require('ui-system/typography/text-base');
	const { Typography } = require('tokens');

	const DefaultTextSize = 4;

	/**
	 * @typedef {Object & TextProps} TypographyTextProps
	 * @property {string} [header]
	 * @property {boolean} [accent]
	 * @property {Function} [color]
	 */

	/**
	 * @typedef {TypographyTextProps} TypographyBodyTextProps
	 * @property {number} [size]
	 */

	/**
	 * @param {TypographyBodyTextProps} props
	 */
	const BodyText = (props) => TextBase({
		nativeElement: Text,
		header: false,
		...props,
	});

	/**
	 * @param {TypographyBodyTextProps} props
	 */
	const CustomText = (props = {}) => {
		const { size, ...restProps } = props;
		const textSize = Typography.isValidTextSize(size) ? size : DefaultTextSize;

		return TextBase({
			nativeElement: Text,
			size: textSize,
			...restProps,
		});
	};

	module.exports = {
		Text: CustomText,
		/**
		 * @param {TypographyTextProps} props
		 */
		Text1: (props) => BodyText({ ...props, size: 1 }),
		/**
		 * @param {TypographyTextProps} props
		 */
		Text2: (props) => BodyText({ ...props, size: 2 }),
		/**
		 * @param {TypographyTextProps} props
		 */
		Text3: (props) => BodyText({ ...props, size: 3 }),
		/**
		 * @param {TypographyTextProps} props
		 */
		Text4: (props) => BodyText({ ...props, size: 4 }),
		/**
		 * @param {TypographyTextProps} props
		 */
		Text5: (props) => BodyText({ ...props, size: 5 }),
		/**
		 * @param {TypographyTextProps} props
		 */
		Text6: (props) => BodyText({ ...props, size: 6 }),
		/**
		 * @param {TypographyTextProps} props
		 */
		Text7: (props) => BodyText({ ...props, size: 7 }),
		/**
		 * @param {TypographyTextProps} props
		 */
		Capital: (props) => BodyText({ ...props, typography: Typography.textCapital }),
	};
});
