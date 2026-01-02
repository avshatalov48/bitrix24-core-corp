/**
 * @module ui-system/blocks/avatar/src/elements/avatar-view
 */
jn.define('ui-system/blocks/avatar/src/elements/avatar-view', (require, exports, module) => {
	const { Icon } = require('assets/icons');
	const { isEmpty } = require('utils/object');
	const { isFunction } = require('utils/object');
	const { withCurrentDomain } = require('utils/url');
	const { Component, Corner, Color } = require('tokens');
	const { makeLibraryImagePath } = require('asset-manager');
	const { PureComponent } = require('layout/pure-component');
	const { getFirstLetters } = require('layout/ui/user/empty-avatar');
	const { AvatarShape } = require('ui-system/blocks/avatar/src/enums/shape');
	const { AvatarAccentGradient } = require('ui-system/blocks/avatar/src/enums/accent-gradient');
	const { getBackgroundColorStyles: getLettresBackgroundColor } = require('layout/ui/user/empty-avatar');
	const { AvatarNativePlaceholderType } = require('ui-system/blocks/avatar/src/enums/native-placeholder-type');

	/**
	 * 	@typedef AvatarViewProps
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
	 * 	@property {boolean} [withRedux=true]
	 * 	@property {Color} [backgroundColor=Color.bgSecondary]
	 * 	@property {Object} [style={}]
	 * 	@property {Function} [onClick]
	 *
	 * @class AvatarView
	 */
	class AvatarView extends PureComponent
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

		render()
		{
			if (this.shouldRenderOutline())
			{
				return this.renderOutlineWrapper(this.renderAvatar());
			}

			return this.renderAvatar();
		}

		renderAvatar()
		{
			return Avatar(this.getAvatarProps());
		}

		getAvatarProps()
		{
			const avatarProps = {
				ref: this.handleForwardRef,
				onUriLoadFailure: () => {
					console.error('Avatar image loading failed');
				},
				...this.getAvatarNativeProps(),
			};

			if (this.hasOnClick())
			{
				avatarProps.onClick = this.handleOnClick;
			}

			return avatarProps;
		}

		getAvatarNativeProps()
		{
			return {
				testId: this.getTestId(),
				title: this.getUserName(),
				uri: this.getUri(),
				type: this.getShape().getValue(),
				radius: this.getBorderRadius(),
				placeholder: this.getPlaceholder(),
				backBorderWidth: this.getBackBorderWidth(),
				style: this.getContainerStyle(),
				hideOutline: !this.isAccent(),
				...this.getAccent(),
			};
		}

		getAccent()
		{
			const accent = {};

			if (!this.isAccent())
			{
				return accent;
			}

			accent.accentType = this.getAccentType();

			return accent;
		}

		getPlaceholder()
		{
			const placeholderType = this.getPlaceholderType();
			const placeholderParams = {
				type: placeholderType.getValue(),
				...this.getPlaceholderBackgroundColorParams(),
			};

			if (placeholderType.isSvg())
			{
				placeholderParams.svg = this.getPlaceholderSvgParams();
			}

			return placeholderParams;
		}

		getPlaceholderSvgParams()
		{
			const icon = this.getIcon();
			let placeholderSvgParams = {};

			if (icon)
			{
				placeholderSvgParams = this.getIconParams();
			}
			else
			{
				placeholderSvgParams = this.getEmptyAvatar();
			}

			return placeholderSvgParams;
		}

		getIconParams()
		{
			const icon = this.getIcon();

			if (isEmpty(icon.props))
			{
				return null;
			}

			const { color, icon: iconEnum, size } = icon.props;

			if (!Icon.hasIcon(iconEnum))
			{
				return null;
			}

			const tintColor = Color.resolve(color, Color.base0);

			return {
				size,
				tintColor: tintColor.toHex(),
				named: iconEnum.getIconName(),
			};
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
			return AvatarView.resolveBorderRadius(this.getShape().isCircle(), this.getSize());
		}

		getSize()
		{
			const { size } = this.props;

			return size;
		}

		handleOnClick = () => {
			const { onClick } = this.props;

			onClick?.({ id: this.getUserId() });
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

			const accentGradients = Array.isArray(accentGradientColors)
				? accentGradientColors
				: this.getAvatarAccentGradient().getValue();

			const start = accentGradients[0];
			const middle = accentGradients[1] || start;
			const end = accentGradients[2] || middle;

			return { start, middle, end };
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

			const svgParams = {
				uri: emptyAvatar === 'string'
					? emptyAvatar
					: makeLibraryImagePath(emptyAvatar?.uri, 'empty-avatar'),
			};

			if (emptyAvatar?.named)
			{
				svgParams.named = emptyAvatar.named.getNamed();
			}

			return svgParams;
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

		getAccentType()
		{
			return this.getAvatarAccentGradient().getName().toLowerCase();
		}

		hasOnClick()
		{
			const { onClick } = this.props;

			return isFunction(onClick);
		}
	}

	module.exports = {
		AvatarView,
	};
});
