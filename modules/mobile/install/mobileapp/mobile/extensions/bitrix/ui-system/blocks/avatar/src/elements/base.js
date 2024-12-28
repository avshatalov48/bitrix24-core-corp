/**
 * @module ui-system/blocks/avatar/src/elements/base
 */
jn.define('ui-system/blocks/avatar/src/elements/base', (require, exports, module) => {
	const { withCurrentDomain } = require('utils/url');
	const { Component, Corner, Color } = require('tokens');
	const { PureComponent } = require('layout/pure-component');
	const { getFirstLetters } = require('layout/ui/user/empty-avatar');
	const { AvatarShape } = require('ui-system/blocks/avatar/src/enums/shape-enum');
	const { AvatarEntityType } = require('ui-system/blocks/avatar/src/enums/entity-type-enum');
	const { AvatarAccentGradient } = require('ui-system/blocks/avatar/src/enums/accent-gradient-enum');
	const { getBackgroundColorStyles: getLettresBackgroundColor } = require('layout/ui/user/empty-avatar');
	const { AvatarNativePlaceholderType } = require('ui-system/blocks/avatar/src/enums/native-placeholder-type-enum');

	/**
	 * 	@typedef AvatarBaseProps
	 * 	@property {string} testId
	 * 	@property {string | number} id
	 * 	@property {Object} [forwardRef]
	 * 	@property {number} [size=32]
	 * 	@property {number} [outline]
	 * 	@property {number} [backBorderWidth]
	 * 	@property {string} [name]
	 * 	@property {string} [uri]
	 * 	@property {string} [emptyAvatar]
	 * 	@property {AvatarEntityType} [entityType]
	 * 	@property {AvatarShape} [shape=AvatarShape.CIRCLE]
	 * 	@property {boolean} [accent=false]
	 * 	@property {AvatarAccentGradient} [accentGradient]
	 * 	@property {Array} [accentGradientColors]
	 * 	@property {boolean} [useLetterImage=true]
	 * 	@property {boolean} [withRedux=false]
	 * 	@property {Color} [backgroundColor=Color.bgSecondary]
	 * 	@property {Object} [style={}]
	 * 	@property {Function} [onClick]
	 *
	 * @class Avatar
	 */
	class AvatarBase extends PureComponent
	{
		constructor(props)
		{
			super(props);

			this.handleForwardRef = this.handleForwardRef.bind(this);
		}

		static resolveBorderRadius(rounded, size)
		{
			if (rounded)
			{
				return Component.elementAccentCorner.toNumber();
			}

			if (size <= 27)
			{
				return Corner.XS.toNumber();
			}

			if (size <= 47)
			{
				return Corner.S.toNumber();
			}

			if (size <= 83)
			{
				return Corner.M.toNumber();
			}

			return Corner.L.toNumber();
		}

		renderOutlineWrapper(avatar)
		{
			const size = this.getSize();
			const outline = this.getOutline();

			return View(
				{
					style: {
						alignItems: 'center',
						justifyContent: 'center',
						width: size + outline,
						height: size + outline,
						backgroundColor: this.getOutlineColor(),
						borderRadius: this.getBorderRadius(),
					},
				},
				avatar,
			);
		}

		getUserId()
		{
			const { id } = this.props;

			return Number(id) || 0;
		}

		getStyle()
		{
			const { style } = this.props;

			return style || {};
		}

		getContainerStyle()
		{
			const size = this.getSize();

			return {
				...this.getStyle(),
				width: size,
				height: size,
			};
		}

		/**
		 * @returns {AvatarShape}
		 */
		getShape()
		{
			const { shape } = this.props;

			return AvatarShape.resolve(shape, AvatarShape.CIRCLE);
		}

		getUserName()
		{
			const { name } = this.props;

			return name;
		}

		getTestId()
		{
			const { testId } = this.props;
			const testIds = ['avatar'];

			if (this.hasUri())
			{
				testIds.push('image');
			}
			else if (this.isUseLetterImage())
			{
				testIds.push(AvatarNativePlaceholderType.LETTERS.getValue(), this.getFirstLetters());
			}
			else
			{
				testIds.push(AvatarNativePlaceholderType.SVG.getValue());
			}

			testIds.push(testId);

			return testIds.filter(Boolean).join('-');
		}

		getUri()
		{
			const { uri } = this.props;

			return this.hasUri() ? encodeURI(withCurrentDomain(uri)) : null;
		}

		hasUri()
		{
			const { uri } = this.props;

			return Boolean(uri);
		}

		getBorderRadius()
		{
			return AvatarBase.resolveBorderRadius(this.getShape().isCircle(), this.getSize());
		}

		getSize()
		{
			const { size } = this.props;

			return size;
		}

		handleOnClick = () => {
			const { onClick } = this.props;

			if (onClick)
			{
				onClick({ id: this.getUserId() });
			}
		};

		getBackgroundColor()
		{
			const { backgroundColor } = this.props;

			if (Color.has(backgroundColor))
			{
				return backgroundColor.toHex();
			}

			return null;
		}

		getOutlineColor()
		{
			const { style } = this.props;

			return style?.backgroundColor || Color.bgSecondary.toHex();
		}

		getAccentColorGradient()
		{
			const { accentGradientColors } = this.props;

			return Array.isArray(accentGradientColors)
				? accentGradientColors
				: this.getAvatarAccentGradient().getValue();
		}

		/**
		 * @returns {AvatarAccentGradient}
		 */
		getAvatarAccentGradient()
		{
			const { accentGradient } = this.props;

			return AvatarAccentGradient.resolve(accentGradient, AvatarAccentGradient.GREEN);
		}

		getEmptyAvatar()
		{
			const { emptyAvatar } = this.getPlaceholderParams();

			return emptyAvatar;
		}

		isIOs()
		{
			return Application.getPlatform() === 'ios';
		}

		isAccent()
		{
			const { accent } = this.props;

			return Boolean(accent);
		}

		isUseLetterImage()
		{
			const { useLetterImage } = this.props;

			return Boolean(useLetterImage) && this.getFirstLetters();
		}

		getPlaceholderType()
		{
			if (this.isUseLetterImage())
			{
				return AvatarNativePlaceholderType.LETTERS;
			}

			return AvatarNativePlaceholderType.SVG;
		}

		getFirstLetters()
		{
			return getFirstLetters(this.getUserName());
		}

		getIcon()
		{
			const { icon } = this.props;

			return icon;
		}

		getOutline()
		{
			const { outline } = this.props;

			return outline;
		}

		getPlaceholderParams()
		{
			const { placeholder } = this.props;

			return placeholder;
		}

		getPlaceholderBackgroundColorParams()
		{
			const backgroundColor = this.getBackgroundColor();
			if (backgroundColor)
			{
				return {
					backgroundColor,
				};
			}

			const placeholderBackgroundColor = this.getPlaceholderBackgroundColor();
			if (placeholderBackgroundColor)
			{
				return {
					backgroundColor: placeholderBackgroundColor,
				};
			}

			if (this.getPlaceholderType().isLetters())
			{
				return getLettresBackgroundColor(this.getUserId());
			}

			return {};
		}

		getPlaceholderBackgroundColor()
		{
			const { backgroundColor } = this.getPlaceholderParams();

			if (Color.has(backgroundColor))
			{
				return backgroundColor.toHex();
			}

			return null;
		}

		shouldRenderOutline()
		{
			return this.getOutline() > 0;
		}

		handleForwardRef(ref)
		{
			const { forwardRef } = this.props;

			forwardRef?.(ref);
		}

		getBackBorderWidth()
		{
			const { backBorderWidth } = this.props;

			return backBorderWidth;
		}
	}

	AvatarBase.defaultProps = {
		size: 32,
		icon: null,
		outline: null,
		withRedux: false,
		useLetterImage: true,
		backBorderWidth: null,
	};

	AvatarBase.propTypes = {
		forwardRef: PropTypes.func,
		testId: PropTypes.string.isRequired,
		outline: PropTypes.number,
		id: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
		size: PropTypes.number,
		name: PropTypes.string,
		emptyAvatar: PropTypes.string,
		uri: PropTypes.string,
		shape: PropTypes.instanceOf(AvatarShape),
		entityType: PropTypes.instanceOf(AvatarEntityType),
		accent: PropTypes.bool,
		icon: PropTypes.object,
		accentGradient: PropTypes.instanceOf(AvatarAccentGradient),
		backgroundColor: PropTypes.instanceOf(Color),
		accentGradientColors: PropTypes.arrayOf(PropTypes.string),
		withRedux: PropTypes.bool,
		useLetterImage: PropTypes.bool,
		style: PropTypes.object,
		onClick: PropTypes.func,
	};

	module.exports = {
		AvatarBase,
	};
});
