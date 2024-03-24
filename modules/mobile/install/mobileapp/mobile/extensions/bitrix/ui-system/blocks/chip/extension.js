/**
 * @module ui-system/blocks/chip
 */
jn.define('ui-system/blocks/chip', (require, exports, module) => {
	const { Indent, IndentTypes, Color, Corner } = require('tokens');
	const { merge } = require('utils/object');
	const { last } = require('utils/array');

	const getIndent = (indent) => Indent[indent.toUpperCase()] || IndentTypes.M;

	/**
	 * @function Chip
	 * @params {object} props
	 * @params {boolean} [props.ellipse]
	 * @params {boolean} [props.disabled]
	 * @params {string} [props.indent]
	 * @params {number} [props.height]
	 * @params {string} [props.borderColor]
	 * @params {string} [props.children]
	 * @return View
	 */
	const Chip = (props) => {
		const {
			ellipse = false,
			disabled = false,
			indent = IndentTypes.M,
			height = 30,
			borderColor = Color.bgSeparatorPrimary,
			children = [],
			...restProps
		} = props;

		const chipStyle = {
			alignItems: 'center',
			justifyContent: 'center',
			flexDirection: 'row',
			borderWidth: 1,
			height,
			borderColor,
			borderRadius: ellipse ? Corner.circle : Corner.M,
			opacity: disabled ? 0.5 : 1,
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
		ellipse: PropTypes.bool,
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
	};

	module.exports = { Chip };
});
