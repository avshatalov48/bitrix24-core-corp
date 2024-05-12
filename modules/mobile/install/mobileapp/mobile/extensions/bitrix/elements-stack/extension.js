/**
 * @module elements-stack
 */
jn.define('elements-stack', (require, exports, module) => {
	const { Corner, Color, Indent, IndentTypes } = require('tokens');
	const { mergeImmutable } = require('utils/object');
	const { PropTypes } = require('utils/validation');

	const Directions = {
		left: 'left',
		right: 'right',
	};

	const toArray = (value) => {
		if (!value)
		{
			return [];
		}

		return Array.isArray(value) ? value : [value];
	};

	/**
	 * @function ElementsStack
	 * @param {Object} props
	 * @param {string} [props.direction]
	 * @param {number} [props.offset]
	 * @param {number|string} [props.indent]
	 * @param {number} [props.radius]
	 * @param {string} [props.fontColor]
	 * @param {number} [props.fontSize]
	 * @param {number} [props.maxElements]
	 * @param {string} [props.backgroundColor]
	 * @param {boolean} [props.showRest]
	 * @param {Array<View>} restChildren
	 * @return View
	 */
	const ElementsStack = (props = {}, ...restChildren) => {
		const {
			fontColor,
			fontSize = 14,
			showRest = true,
			direction = Directions.left,
			offset = 6,
			indent = IndentTypes.XS2,
			maxElements = 999,
			radius = Corner.circle,
			backgroundColor = Color.bgContentPrimary,
			...restProps
		} = props;

		const elements = toArray(restChildren);

		if (elements.length === 0)
		{
			return null;
		}

		const isRight = direction.toLowerCase() === Directions.right;
		const indentWidth = parseInt(indent, 10) || Indent[indent];
		const calcOffset = offset > 0 ? Number(offset) + indentWidth : 0;

		const getDirectionStyle = ({ index }) => {
			const isFirst = index === 0;
			const isLast = index === elements.length - 1;
			const directionStyle = {};

			if (isRight)
			{
				directionStyle.marginLeft = isFirst ? 0 : -calcOffset;
				directionStyle.zIndex = -index;
			}
			else
			{
				directionStyle.marginRight = isLast ? 0 : -calcOffset;
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
							borderWidth: indentWidth,
							borderColor: backgroundColor,
							borderRadius: radius,
						},
					},
					element,
				),
			);
		}).filter(Boolean);

		const minRestTextMargin = 4;
		const isShowRestText = showRest && elements.length > maxElements;

		const paddingRight = isRight ? 0 : (isShowRestText ? 0 : offset);
		const marginLeft = isRight ? minRestTextMargin : calcOffset + minRestTextMargin;

		const restElementsCountText = Text({
			text: `+${elements.length - maxElements}`,
			style: {
				fontSize,
				color: fontColor,
				marginLeft,
			},
		});

		const mainProps = mergeImmutable(
			restProps,
			{
				style: {
					alignItems: 'center',
					flexDirection: 'row',
					paddingRight,
				},
			},
		);

		return View(
			mainProps,
			...getRenderElements(),
			isShowRestText && restElementsCountText,
		);
	};

	ElementsStack.defaultProps = {
		fontSize: 14,
		showRest: true,
		direction: Directions.left,
		offset: 6,
		indent: 2,
		maxElements: 999,
		radius: Corner.circle,
		backgroundColor: Color.bgContentPrimary,
	};

	ElementsStack.propTypes = {
		children: PropTypes.oneOfType([
			PropTypes.object,
			PropTypes.arrayOf(PropTypes.object),
		]),
		direction: PropTypes.oneOf([Directions.right, Directions.left]),
		offset: PropTypes.number,
		indent: PropTypes.number,
		radius: PropTypes.number,
		fontColor: PropTypes.string,
		fontSize: PropTypes.number,
		maxElements: PropTypes.number,
		showRest: PropTypes.bool,
	};

	module.exports = { ElementsStack, Directions, DirectionType: 'directions' };
});
