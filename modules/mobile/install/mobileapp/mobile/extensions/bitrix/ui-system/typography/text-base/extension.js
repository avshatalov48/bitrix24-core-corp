/**
 * @module ui-system/typography/text-base
 */
jn.define('ui-system/typography/text-base', (require, exports, module) => {
	const { PropTypes } = require('utils/validation');
	const { mergeImmutable } = require('utils/object');
	const { Typography, Color } = require('tokens');

	/**
	 * @param {object} props
	 * @param {number | string} props.size
	 * @param {boolean} props.accent
	 * @param {boolean} props.header
	 * @param {TextInput | Text | TextField} props.nativeElement
	 * @param {Typography} props.typography
	 *
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
