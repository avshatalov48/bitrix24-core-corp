/**
 * @module ui-system/typography/text-base
 */
jn.define('ui-system/typography/text-base', (require, exports, module) => {
	const { PropTypes } = require('utils/validation');
	const { mergeImmutable } = require('utils/object');
	const { Typography, Color } = require('tokens');

	/**
	 * @typedef {Object} TextBaseProps
	 * @property {number | string} [size]
	 * @property {boolean} [accent]
	 * @property {boolean} [header]
	 * @property {TextInput | Text | TextField} [nativeElement]
	 * @property {Typography} [typography]
	 *
	 * @param {TextBase} props
	 * @return TextBase
	 */
	const TextBase = (props) => {
		const { nativeElement, size = 4, accent, header, typography, color, ...restProps } = props;
		const typographyToken = Typography.resolve(
			Typography.getToken({ token: typography, accent }),
			Typography.getTokenBySize({ size, header, accent }),
		);

		const typographyStyle = typographyToken.getStyle();
		const style = Color.has(color) ? {
			color: color.toHex(),
			...typographyToken.getStyle(),
		} : typographyStyle;

		return nativeElement(mergeImmutable({ style }, restProps));
	};

	TextBase.defaultProps = {
		size: 4,
		accent: false,
		header: false,
		typography: null,
	};

	TextBase.propTypes = {
		typography: PropTypes.object,
		size: PropTypes.oneOfType([
			PropTypes.number,
			PropTypes.string,
		]),
		accent: PropTypes.bool,
		header: PropTypes.bool,
	};

	module.exports = { TextBase };
});
