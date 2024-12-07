/**
 * @module ui-system/blocks/chips/chip
 */
jn.define('ui-system/blocks/chips/chip', (require, exports, module) => {
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
	 * @params {Indent} [props.indent=Indent.M]
	 * @params {string} [props.borderColor=Color.bgPrimary]
	 * @params {string} [props.children]
	 * @return Chip
	 */
	const Chip = (props) => {
		PropTypes.validate(Chip.propTypes, props, 'Chip');

		const {
			rounded = false,
			disabled = false,
			indent = Indent.M,
			borderColor,
			backgroundColor = Color.bgPrimary,
			children = [],
			...restProps
		} = props;

		const chipStyle = {
			alignItems: 'center',
			justifyContent: 'center',
			flexDirection: 'row',
			borderWidth: 1,
			height: Component.itbChipHeight.toNumber(),
			borderColor: disabled
				? Color.bgSeparatorPrimary.toHex()
				: Color.resolve(borderColor, Color.base4).toHex(),
			borderRadius: rounded ? Component.elementAccentCorner.toNumber() : Corner.M.toNumber(),
			backgroundColor: Color.resolve(backgroundColor, Color.bgPrimary).withPressed(),
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

	Chip.defaultProps = {
		height: 30,
		rounded: false,
		disabled: false,
		backgroundColor: null,
	};

	Chip.propTypes = {
		rounded: PropTypes.bool,
		disabled: PropTypes.bool,
		indent: PropTypes.oneOfType([
			PropTypes.object,
			PropTypes.shape({
				left: PropTypes.string,
				right: PropTypes.string,
			}),
		]),
		height: PropTypes.number,
		borderColor: PropTypes.object,
		backgroundColor: PropTypes.object,
		children: PropTypes.array,
	};

	module.exports = { Chip };
});
