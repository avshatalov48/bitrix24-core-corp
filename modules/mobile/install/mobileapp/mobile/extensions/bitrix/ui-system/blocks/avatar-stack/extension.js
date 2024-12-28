/**
 * @module ui-system/blocks/avatar-stack
 */
jn.define('ui-system/blocks/avatar-stack', (require, exports, module) => {
	const { Type } = require('type');
	const { inRange } = require('utils/number');
	const { Color, Indent } = require('tokens');
	const { isEmpty } = require('utils/object');
	const { Text } = require('ui-system/typography/text');
	const { PureComponent } = require('layout/pure-component');
	const { ElementsStack, ElementsStackDirection } = require('elements-stack');
	const { Avatar, AvatarClass, AvatarShape, AvatarEntityType } = require('ui-system/blocks/avatar');

	const OUTLINE_INDENT = {
		MIN: 2,
		MAX: 4,
	};

	/**
	 * 	@typedef EntityParams
	 * 	@property {string} testId
	 * 	@property {string|number} id
	 * 	@property {string} [uri]
	 * 	@property {string} [name]
	 *
	 * 	@typedef AvatarStackProps
	 * 	@property {string} testId
	 * 	@property {Array<number | EntityParams>} entities
	 * 	@property {number} [visibleEntityCount=5]
	 * 	@property {boolean} [useLetterImage=true]
	 * 	@property {boolean} [withRedux=true]
	 * 	@property {Color} [backgroundColor=Color.bgSecondary]
	 * 	@property {Object} [style={}]
	 * 	@property {Function} [onClick]
	 * 	@property {Function} [restView]
	 * 	@property {ElementsStackDirection} [direction]
	 *
	 * @class AvatarStack
	 */
	class AvatarStack extends PureComponent
	{
		render()
		{
			const { style } = this.props;

			return ElementsStack(
				{
					testId: this.getTestId(),
					style,
					clickable: false,
					radius: null,
					indent: null,
					externalIndent: this.getOutline(),
					restView: this.renderRestElement,
					...this.getElementsStackParams(),
				},
				...this.renderEntities(),
			);
		}

		renderRestElement = (restCount) => {
			const { restView } = this.props;

			if (restView)
			{
				return restView(restCount);
			}

			const size = this.getSize();
			const baseStyle = {
				alignItems: 'center',
				justifyContent: 'center',
				borderRadius: AvatarClass.resolveBorderRadius(this.isCircle(), size),
			};
			const restText = restCount > 99 ? '99+' : `+${restCount}`;
			const fontSize = size >= 22 || restText.length < 3 ? (size / 2) : (size / 2) - 1;

			return View(
				{
					style: {
						width: size + this.getOutline(),
						height: size + this.getOutline(),
						backgroundColor: this.getBackgroundColor(),
						...baseStyle,
					},
				},
				View(
					{
						style: {
							width: size,
							height: size,
							backgroundColor: Color.bgContentTertiary.toHex(),
							...baseStyle,
						},
					},
					Text({
						text: restText,
						color: Color.base4,
						style: {
							fontSize,
						},
					}),
				),
			);
		};

		renderEntities()
		{
			return this.getEntities()
				.map((params) => this.renderAvatar(this.prepareEntityParam(params)))
				.filter(Boolean);
		}

		prepareEntityParam(params)
		{
			let entityParams = {};

			if (Type.isNumber(Number(params)))
			{
				entityParams.id = Number(params);
			}
			else if (!isEmpty(params))
			{
				entityParams = {
					...entityParams,
					...params,
				};
			}

			if (Type.isBoolean(params.accent))
			{
				entityParams.accent = params.accent;
			}

			return entityParams;
		}

		renderAvatar(params)
		{
			const { elementType } = this.props;

			return Avatar({
				elementType,
				onClick: this.handleOnClick,
				testId: this.getTestId(),
				outline: this.getOutline(),
				...params,
				...this.getAvatarProps(),
			});
		}

		getAvatarProps()
		{
			const { useLetterImage, withRedux } = this.props;

			return {
				withRedux,
				useLetterImage,
				size: this.getSize(),
				shape: this.getShape(),
				style: this.getAvatarStyle(),
			};
		}

		getAvatarStyle()
		{
			const { backgroundColor } = this.props;

			return {
				backgroundColor: Color.resolve(backgroundColor, Color.bgSecondary).toHex(),
			};
		}

		getEntities()
		{
			const { entities } = this.props;

			return entities;
		}

		getTestId()
		{
			const { testId } = this.props;

			return testId;
		}

		getSize()
		{
			const { size } = this.props;

			return size;
		}

		getBackgroundColor()
		{
			const { backgroundColor } = this.props;

			return Color.resolve(backgroundColor, Color.bgSecondary).toHex();
		}

		/**
		 * @returns {AvatarShape}
		 */
		getShape()
		{
			const { shape } = this.props;

			return AvatarShape.resolve(shape, AvatarShape.CIRCLE);
		}

		getElementsStackParams()
		{
			const { visibleEntityCount, direction } = this.props;

			return {
				direction,
				maxElements: visibleEntityCount,
				offset: this.getOffset(),
			};
		}

		getOffset()
		{
			const { offset } = this.props;

			return offset || 5;
		}

		getOutline()
		{
			const { outline } = this.props;

			if (Indent.has(outline))
			{
				return Indent.resolve(outline, Indent.XS).toNumber();
			}

			if (this.getSize() <= 24)
			{
				return OUTLINE_INDENT.MIN;
			}

			const outlineDependingSize = this.getSize() * 0.04;

			if (inRange(outlineDependingSize, OUTLINE_INDENT.MIN, OUTLINE_INDENT.MAX))
			{
				return outlineDependingSize;
			}

			return OUTLINE_INDENT.MAX;
		}

		isCircle()
		{
			return this.getShape().isCircle();
		}

		handleOnClick = (entity) => {
			const { onClick } = this.props;

			if (onClick)
			{
				onClick(entity);
			}
		};
	}

	AvatarStack.defaultProps = {
		size: 32,
		visibleEntityCount: 5,
		entities: [],
		withRedux: true,
		style: {},
		restView: null,
		useLetterImage: true,
	};

	AvatarStack.propTypes = {
		size: PropTypes.number,
		testId: PropTypes.string.isRequired,
		visibleEntityCount: PropTypes.number,
		shape: PropTypes.instanceOf(AvatarShape),
		withRedux: PropTypes.bool,
		onClick: PropTypes.func,
		useLetterImage: PropTypes.bool,
		entities: PropTypes.arrayOf(
			PropTypes.oneOfType([
				PropTypes.number,
				PropTypes.string,
				PropTypes.shape(AvatarClass.propTypes),
			]),
		).isRequired,
		style: PropTypes.object,
		offset: PropTypes.instanceOf(Indent),
		outline: PropTypes.instanceOf(Indent),
		direction: PropTypes.instanceOf(ElementsStackDirection),
		restView: PropTypes.func,
	};

	module.exports = {
		/**
		 * @param {AvatarStackProps} props
		 */
		AvatarStack: (props) => new AvatarStack(props),
		AvatarShape,
		AvatarEntityType,
		AvatarStackDirection: ElementsStackDirection,
	};
});
