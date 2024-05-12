/**
 * @module ui-system/blocks/chip
 */
jn.define('ui-system/blocks/chip', (require, exports, module) => {
	const { PropTypes } = require('utils/validation');
	const { Indent, IndentTypes, Color, Corner } = require('tokens');
	const { merge } = require('utils/object');
	const { last } = require('utils/array');

	const getIndent = (indent) => Indent[indent.toUpperCase()] || IndentTypes.M;

	/**
	 * @function Chip
	 * @params {object} props
	 * @params {boolean} [props.rounded]
	 * @params {boolean} [props.disabled]
	 * @params {string} [props.indent]
	 * @params {number} [props.height]
	 * @params {string} [props.borderColor]
	 * @params {string} [props.children]
	 * @return View
	 */
	const Chip = (props) => {
		const {
			rounded = false,
			disabled = false,
			indent = IndentTypes.M,
			height = 30,
			borderColor,
			backgroundColor,
			children = [],
			...restProps
		} = props;

		const chipStyle = {
			alignItems: 'center',
			justifyContent: 'center',
			flexDirection: 'row',
			borderWidth: 1,
			height,
			borderColor: disabled ? Color.bgSeparatorPrimary : borderColor || Color.base4,
			borderRadius: rounded ? Corner.circle : Corner.M,
			backgroundColor,
		};

		if (typeof indent === 'object')
		{
			chipStyle.paddingLeft = getIndent(indent.left);
			chipStyle.paddingRight = getIndent(indent.right);
		}
		// eslint-disable-next-line valid-typeof
		else if (!indent && children.length > 1 && last(children)?.name === 'Image')
		{
			chipStyle.paddingLeft = getIndent(indent);
			chipStyle.paddingRight = getIndent(IndentTypes.XS);
		}
		else
		{
			chipStyle.paddingHorizontal = getIndent(indent);
		}

		const chipProps = merge({ style: chipStyle }, restProps);

		return View(
			chipProps,
			...Array.isArray(children) ? children : [children],
		);
	};

	Chip.propTypes = {
		rounded: PropTypes.bool,
		disabled: PropTypes.bool,
		indent: PropTypes.oneOfType([
			PropTypes.string,
			PropTypes.shape({
				left: PropTypes.string,
				right: PropTypes.string,
			}),
		]),
		height: PropTypes.number,
		borderColor: PropTypes.string,
		backgroundColor: PropTypes.string,
		children: PropTypes.object,
	};

	Chip.defaultProps = {
		rounded: false,
		indent: IndentTypes.M,
		height: 30,
		disabled: false,
		backgroundColor: null,
		borderColor: Color.base4,
	};

	module.exports = { Chip };
});
