/**
 * @module elements-stack
 */
jn.define('elements-stack', (require, exports, module) => {
	const { Type } = require('type');
	const { Color, Indent, Component } = require('tokens');
	const { mergeImmutable } = require('utils/object');
	const { PropTypes } = require('utils/validation');
	const { Text } = require('ui-system/typography/text');
	const { ElementsStackDirection } = require('elements-stack/src/direction-enum');

	const isAndroid = Application.getPlatform() === 'android';

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
	 * @param {string} props.testId
	 * @param {string} [props.direction]
	 * @param {Indent} [props.offset]
	 * @param {Indent} [props.indent]
	 * @param {Indent} [props.externalIndent]
	 * @param {Component} [props.radius]
	 * @param {Color} [props.textColor]
	 * @param {number} [props.textSize]
	 * @param {number} [props.maxElements]
	 * @param {Color} [props.backgroundColor]
	 * @param {boolean} [props.showRest]
	 * @param {Function} [props.restView]
	 * @param {Array<View>} restChildren
	 * @return View
	 */
	function ElementsStack(props = {}, ...restChildren)
	{
		PropTypes.validate(ElementsStack.propTypes, props, 'ElementsStack');

		const {
			textColor,
			textSize = 4,
			showRest = true,
			direction,
			offset = Indent.S,
			indent,
			externalIndent,
			maxElements = 999,
			radius = Component.elementAccentCorner,
			backgroundColor = Color.bgContentPrimary,
			restView,
			...restProps
		} = props;

		const elements = toArray(restChildren);

		if (elements.length === 0)
		{
			return null;
		}

		const isRight = ElementsStackDirection.resolve(direction, ElementsStackDirection.LEFT).isRight();
		const indentWidth = indent instanceof Indent ? indent.toNumber() : indent;
		const offsetWidth = offset instanceof Indent ? offset.toNumber() : offset;
		const calcOffset = offsetWidth > 0 ? offsetWidth + indentWidth : 0;
		const borderRadius = radius?.toNumber() || 0;

		const countElements = elements.length;
		const isShowRestText = showRest && countElements > maxElements;
		const countVisibleElements = Math.min(countElements, maxElements) + (isShowRestText ? 1 : 0);

		const getDirectionStyle = ({ index }) => {
			const isFirst = index === 0;
			const isLast = index + 1 === countVisibleElements;

			const directionStyle = {
				borderWidth: indentWidth,
				borderColor: backgroundColor.toHex(),
				borderRadius,
			};

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

			if (isFirst && externalIndent)
			{
				directionStyle.marginLeft = -(externalIndent / 2);

				if (isAndroid)
				{
					directionStyle.marginLeft += 1;
				}
			}

			return directionStyle;
		};

		const elementWrapper = (element, index) => View(
			{
				clickable: false,
				style: getDirectionStyle({ index }),
			},
			element,
		);

		const getRenderElements = () => elements.map((element, index) => {
			if (index + 1 > maxElements)
			{
				return null;
			}

			return elementWrapper(element, index);
		}).filter(Boolean);

		const minRestTextMargin = 4;
		const marginLeft = isRight ? minRestTextMargin : calcOffset + minRestTextMargin;

		const renderRestElement = () => {
			if (!isShowRestText)
			{
				return null;
			}

			const countRestElements = countElements - maxElements;

			return Type.isFunction(restView)
				? elementWrapper(restView(countRestElements), maxElements)
				: Text({
					size: textSize,
					text: `+${countRestElements}`,
					style: {
						color: Color.resolve(textColor, Color.base1).toHex(),
						marginLeft,
					},
				});
		};

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
			renderRestElement(),
		);
	}

	ElementsStack.defaultProps = {
		textSize: 4,
		showRest: true,
		maxElements: 999,
	};

	ElementsStack.propTypes = {
		testId: PropTypes.string.isRequired,
		children: PropTypes.oneOfType([
			PropTypes.object,
			PropTypes.arrayOf(PropTypes.object),
		]),
		direction: PropTypes.instanceOf(ElementsStackDirection),
		offset: PropTypes.oneOfType([PropTypes.number, PropTypes.instanceOf(Indent)]),
		indent: PropTypes.oneOfType([PropTypes.number, PropTypes.instanceOf(Indent)]),
		externalIndent: PropTypes.oneOfType([PropTypes.number, PropTypes.instanceOf(Indent)]),
		textColor: PropTypes.instanceOf(Color),
		radius: PropTypes.object,
		textSize: PropTypes.number,
		maxElements: PropTypes.number,
		showRest: PropTypes.bool,
		restView: PropTypes.func,
	};

	module.exports = {
		ElementsStack,
		ElementsStackDirection,
		Indent,
	};
});
