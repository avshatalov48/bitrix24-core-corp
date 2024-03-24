/**
 * @module elements-stack
 */
jn.define('elements-stack', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { Corner } = require('tokens');
	const { mergeImmutable } = require('utils/object');
	const { PropTypes } = require('utils/validation');

	const LEFT = 'left';
	const RIGHT = 'right';

	/**
	 * @function ElementsStack
	 * @param {Object} props
	 * @param {Array<View>} [props.children]
	 * @param {string} [props.direction]
	 * @param {number} [props.offset]
	 * @param {number} [props.indent]
	 * @param {number} [props.radius]
	 * @param {string} [props.fontColor]
	 * @param {number} [props.fontSize]
	 * @param {number} [props.maxElements]
	 * @param {boolean} [props.showRest]
	 * @return View
	 */
	const ElementsStack = (props = {}) => {
		const {
			fontColor,
			fontSize = 14,
			showRest = true,
			children,
			direction = LEFT,
			offset = 6,
			indent = 2,
			maxElements = 999,
			radius = Corner.circle,
			backgroundColor = AppTheme.colors.bgContentPrimary,
			...restProps
		} = props;

		const elements = Array.isArray(children) ? children : [children];

		if (elements.length === 0)
		{
			return null;
		}

		const borderWidth = offset === 0 ? 0 : Number(indent);

		const getDirectionStyle = ({ element, index }) => {
			const isFirst = index === 0;
			const isLast = index === elements.length - 1;

			const directionStyle = {};

			if (direction.toLowerCase() === RIGHT)
			{
				directionStyle.marginLeft = isFirst ? 0 : -Number(offset);
				directionStyle.zIndex = -index;
			}
			else
			{
				directionStyle.marginRight = isLast ? 0 : -Number(offset);
				directionStyle.zIndex = index;
			}

			return directionStyle;
		};

		const getRenderElements = () => elements.map((element, index) => {
			if (index + 1 > maxElements)
			{
				return null;
			}

			return View(
				{
					style: getDirectionStyle({ element, index }),
				},
				View(
					{
						style: {
							borderWidth,
							borderColor: backgroundColor,
							borderRadius: radius,
						},
					},
					element,
				),
			);
		}).filter(Boolean);

		let restElementsCountText = null;

		if (showRest && elements.length > Number(maxElements))
		{
			restElementsCountText = Text({
				text: `+${elements.length - maxElements}`,
				style: {
					fontSize,
					fontColor,
					marginLeft: 4 - indent,
				},
			});
		}

		const mainProps = mergeImmutable(
			restProps,
			{
				style: {
					alignItems: 'center',
					flexDirection: 'row',
				},
			},
		);

		return View(
			mainProps,
			...getRenderElements(),
			restElementsCountText,
		);
	};

	ElementsStack.propTypes = {
		children: PropTypes.oneOfType([
			PropTypes.object,
			PropTypes.arrayOf(PropTypes.object),
		]),
		direction: PropTypes.oneOf([RIGHT, LEFT]),
		offset: PropTypes.number,
		indent: PropTypes.number,
		radius: PropTypes.number,
		fontColor: PropTypes.string,
		fontSize: PropTypes.number,
		maxElements: PropTypes.number,
		showRest: PropTypes.bool,
	};

	module.exports = { ElementsStack };
});
