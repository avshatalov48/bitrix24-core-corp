/**
 * @module ui-system/blocks/chip
 */
jn.define('ui-system/blocks/chip', (require, exports, module) => {
	const { PropTypes } = require('utils/validation');
	const { Indent, Color, Corner, Component } = require('tokens');
	const { merge } = require('utils/object');
	const { last } = require('utils/array');

	const getIndent = (indent) => Indent.resolve(indent).toNumber();

	/**
	 * @function Chip
	 * @params {object} props
	 * @params {boolean} [props.rounded]
	 * @params {boolean} [props.disabled]
	 * @params {Indent} [props.indent]
	 * @params {number} [props.height]
	 * @params {string} [props.borderColor]
	 * @params {string} [props.children]
	 * @return View
	 */
	const Chip = (props) => {
		const {
			rounded = false,
			disabled = false,
			indent = Indent.M,
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
			borderColor: disabled
				? Color.bgSeparatorPrimary.toHex()
				: Color.resolve(borderColor, Color.base4).toHex(),
			borderRadius: rounded ? Component.elementAccentCorner.toNumber() : Corner.M.toNumber(),
			backgroundColor: backgroundColor && Color.resolve(backgroundColor),
		};

		if (indent && indent.left && indent.right)
		{
			chipStyle.paddingLeft = getIndent(indent.left);
			chipStyle.paddingRight = getIndent(indent.right);
		}
		// eslint-disable-next-line valid-typeof
		else if (!indent && children.length > 1 && last(children)?.name === 'Image')
		{
			chipStyle.paddingLeft = getIndent(indent);
			chipStyle.paddingRight = getIndent(Indent.XS);
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
		indent: Indent.M,
		height: 30,
		disabled: false,
		backgroundColor: null,
		borderColor: Color.base4,
	};

	module.exports = { Chip };
});
