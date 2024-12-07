/**
 * @module ui-system/blocks/avatar-stack
 */
jn.define('ui-system/blocks/avatar-stack', (require, exports, module) => {
	const { Type } = require('type');
	const { Color, Indent } = require('tokens');
	const { isEmpty } = require('utils/object');
	const { Text } = require('ui-system/typography/text');
	const { ElementsStack, ElementsStackDirection } = require('elements-stack');
	const { Avatar, AvatarClass, AvatarShape, AvatarEntityType } = require('ui-system/blocks/avatar');

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
	 * 	@property {Array} [entityTypeIds=[]]
	 * 	@property {boolean} [useLetterImage=true]
	 * 	@property {boolean} [withRedux=false]
	 * 	@property {Color} [backgroundColor=Color.bgSecondary]
	 * 	@property {Object} [style={}]
	 * 	@property {Function} [onClick]
	 * 	@property {ElementsStackDirection} [direction]
	 *
	 * @class AvatarStack
	 */
	class AvatarStack extends LayoutComponent
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
					restView: this.renderRestElement,
					...this.getElementsStackParams(),
				},
				...this.renderEntities(),
			);
		}

		renderRestElement = (restCount) => {
			const size = this.getSize();
			const baseStyle = {
				alignItems: 'center',
				justifyContent: 'center',
				borderRadius: AvatarClass.resolveBorderRadius(this.isCircle(), size),
			};

			return View(
				{
					style: {
						width: size + this.getAvatarOffset(),
						height: size + this.getAvatarOffset(),
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
						text: `+${restCount}`,
						color: Color.base4,
						style: {
							fontSize: size / 2,
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
			let entityParams = {
				accent: params.accent,
			};

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

			return entityParams;
		}

		renderAvatar(params)
		{
			return Avatar({
				onClick: this.handleOnClick,
				testId: this.getTestId(),
				offset: this.getAvatarOffset(),
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

		getAvatarOffset()
		{
			return 4;
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

			if (Indent.has(offset))
			{
				return Indent.resolve(offset, Indent.XS).toNumber();
			}

			const offsetDependingSize = (this.getSize() * 0.3) - this.getAvatarOffset();

			return offsetDependingSize >= this.getAvatarOffset() ? offsetDependingSize : this.getAvatarOffset();
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
		withRedux: false,
		style: {},
		indent: null,
		useLetterImage: true,
	};

	AvatarStack.propTypes = {
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
		direction: PropTypes.instanceOf(ElementsStackDirection),
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
