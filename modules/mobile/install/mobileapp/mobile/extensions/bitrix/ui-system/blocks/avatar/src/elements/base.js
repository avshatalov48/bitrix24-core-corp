/**
 * @module ui-system/blocks/avatar/src/elements/base
 */
jn.define('ui-system/blocks/avatar/src/elements/base', (require, exports, module) => {
	const { Type } = require('type');
	const { Component, Corner, Color } = require('tokens');
	const { withCurrentDomain } = require('utils/url');
	const { SafeImage } = require('layout/ui/safe-image');
	const { PureComponent } = require('layout/pure-component');
	const { UserLetters } = require('layout/ui/user/empty-avatar');
	const { AvatarAccentGradient } = require('ui-system/blocks/avatar/src/enums/accent-gradient-enum');
	const { AvatarShape } = require('ui-system/blocks/avatar/src/enums/shape-enum');
	const { reduxConnect } = require('ui-system/blocks/avatar/src/wrappers/redux');

	/**
	 * 	@typedef AvatarViewProps
	 * 	@property {string} testId
	 * 	@property {string | number} id
	 * 	@property {number} [size=32]
	 * 	@property {number} [offset]
	 * 	@property {string} [name]
	 * 	@property {string} [uri]
	 * 	@property {string} [emptyAvatar]
	 * 	@property {boolean} [shape=AvatarShape.CIRCLE]
	 * 	@property {boolean} [accent=false]
	 * 	@property {AvatarAccentGradient} [accentGradient]
	 * 	@property {Array} [accentGradientColors]
	 * 	@property {boolean} [useLetterImage]
	 * 	@property {boolean} [withRedux=false]
	 * 	@property {Color} [outlineColor=Color.bgSecondary]
	 * 	@property {Color} [backgroundColor=Color.bgSecondary]
	 * 	@property {Object} [style={}]
	 * 	@property {Function} [onClick]
	 *
	 * @class Avatar
	 */
	class AvatarView extends PureComponent
	{
		render()
		{
			if (this.shouldRenderOffset())
			{
				return this.renderOffsetWrapper(this.renderImageContainer());
			}

			return this.renderImageContainer();
		}

		renderImageContainer()
		{
			const { style = {} } = this.props;
			const size = this.getSize();

			return View(
				{
					testId: this.getTestId(),
					style: {
						...style,
						width: size,
						height: size,
						borderRadius: this.getBorderRadius(),
					},
					onClick: this.handleOnClick,
				},
				this.isAccent()
					? this.renderAccentImage()
					: this.renderImage({
						style: this.getImageStyle(),
					}),
			);
		}

		renderAccentImage()
		{
			const imageSize = this.getImageSize();
			const center = this.getSize() / 2 - imageSize / 2;
			const thickness = (this.getSize() - imageSize) / 2;

			return View(
				{
					clickable: false,
					style: {
						flex: 1,
						position: 'relative',
					},
				},
				Image({
					style: {
						flex: 1,
					},
					svg: {
						content: this.getAccentSvg(thickness),
					},
				}),
				this.renderAccentBackground({ thickness, imageSize }),
				this.renderImage({
					style: {
						borderRadius: this.getBorderRadius(),
						width: imageSize,
						height: imageSize,
					},
					wrapperStyle: {
						position: 'absolute',
						top: center,
						left: center,
						borderRadius: this.getBorderRadius(),
					},
				}),
			);
		}

		renderAccentBackground({ thickness, imageSize })
		{
			const size = imageSize + thickness;
			const center = (size - imageSize) / 2;

			return View({
				style: {
					position: 'absolute',
					top: center,
					left: center,
					width: size,
					height: size,
					borderRadius: this.getBorderRadius(),
					backgroundColor: this.getOutlineColor(),
				},
			});
		}

		renderImage(params)
		{
			const icon = this.getIcon();

			if (icon)
			{
				const { wrapperStyle, style } = params;

				return View(
					{
						style: {
							flex: 1,
							backgroundColor: this.getBackgroundColor(),
							borderRadius: this.getBorderRadius(),
							...wrapperStyle,
						},
					},
					View(
						{
							style: {
								alignItems: 'center',
								justifyContent: 'center',
								...style,
							},
						},
						icon,
					),
				);
			}

			return SafeImage({
				withShimmer: true,
				clickable: false,
				uri: this.getUri(),
				renderPlaceholder: this.renderPlaceholder(),
				...params,
			});
		}

		renderPlaceholder()
		{
			const { useLetterImage } = this.props;
			const userLetters = this.renderUserLetters();

			if (useLetterImage && Type.isStringFilled(this.getUserName()) && userLetters)
			{
				return userLetters;
			}

			return this.renderEmptyAvatar();
		}

		renderEmptyAvatar()
		{
			return Image({
				style: this.getImageStyle(),
				svg: {
					uri: this.getEmptyAvatar(),
				},
			});
		}

		renderOffsetWrapper(avatar)
		{
			const size = this.getSize();
			const offset = this.getOffset();

			return View(
				{
					style: {
						alignItems: 'center',
						justifyContent: 'center',
						width: size + offset,
						height: size + offset,
						backgroundColor: this.getOutlineColor(),
						borderRadius: this.getBorderRadius(),
					},
				},
				avatar,
			);
		}

		renderUserLetters()
		{
			return UserLetters({
				clickable: false,
				id: this.getUserId(),
				name: this.getUserName(),
				size: this.getImageSize(),
				style: this.getImageStyle(),
			});
		}

		handleOnClick = () => {
			const { onClick } = this.props;

			if (onClick)
			{
				onClick({ id: this.getUserId() });
			}
		};

		getEmptyAvatar()
		{
			const { emptyAvatar } = this.props;

			return emptyAvatar;
		}

		getUserId()
		{
			const { id } = this.props;

			return Number(id) || 0;
		}

		getUserName()
		{
			const { name } = this.props;

			return name;
		}

		getTestId()
		{
			const { testId } = this.props;

			return testId;
		}

		getImageSize()
		{
			return this.isAccent() ? this.getSize() / 1.2 : this.getSize();
		}

		getSize()
		{
			const { size } = this.props;

			return size;
		}

		getImageStyle()
		{
			const size = this.getImageSize();

			return {
				width: size,
				height: size,
				borderRadius: this.getBorderRadius(),
			};
		}

		getUri()
		{
			const { uri } = this.props;

			return uri ? encodeURI(withCurrentDomain(uri)) : null;
		}

		isAccent()
		{
			const { accent } = this.props;

			return Boolean(accent);
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

		getBorderRadius()
		{
			return AvatarView.resolveBorderRadius(this.getShape().isCircle(), this.getSize());
		}

		getOutlineColor()
		{
			const { outlineColor } = this.props;

			return Color.resolve(outlineColor, Color.bgSecondary).toHex();
		}

		getBackgroundColor()
		{
			const { backgroundColor } = this.props;

			return Color.resolve(backgroundColor, Color.bgSecondary).toHex();
		}

		getAccentSvg()
		{
			const size = this.getSize();
			const radius = this.getBorderRadius();
			const pathData = this.calculateAccentPath(size, radius);

			return `
				<svg xmlns="http://www.w3.org/2000/svg" width="${size}" height="${size}" viewBox="0 0 ${size} ${size}" fill="none">
				    <path fill-rule="evenodd" clip-rule="evenodd" d="${pathData}" fill="url(#paint0_linear)"/>
				    <defs>
				        <linearGradient id="paint0_linear" x1="8.47059" y1="10" x2="51.4509" y2="58.2313" gradientUnits="userSpaceOnUse">
							${this.generateAccentLinearGradient()}
						</linearGradient>
				    </defs>
				</svg>
			`;
		}

		getIcon()
		{
			const { icon } = this.props;

			return icon;
		}

		getOffset()
		{
			const { offset } = this.props;

			return offset;
		}

		generateAccentLinearGradient()
		{
			const { accentGradientColors, accentGradient } = this.props;

			const colors = Array.isArray(accentGradientColors)
				? accentGradientColors
				: AvatarAccentGradient.resolve(accentGradient, AvatarAccentGradient.GREEN).getValue();

			return colors.map((color, index) => {
				const offset = index > 0 ? `offset="${index / (colors.length - 1)}"` : '';

				return `<stop ${offset} stop-color="${color}"/>`;
			}).join('\n');
		}

		calculateAccentPath(size, radius)
		{
			const adjustedRadius = Math.min(radius, size / 2);

			return `
				M${adjustedRadius},0
				H${size - adjustedRadius}
				A${adjustedRadius},${adjustedRadius} 0 0 1 ${size},${adjustedRadius}
				V${size - adjustedRadius}
				A${adjustedRadius},${adjustedRadius} 0 0 1 ${size - adjustedRadius},${size}
				H${adjustedRadius}
				A${adjustedRadius},${adjustedRadius} 0 0 1 0,${size - adjustedRadius}
				V${adjustedRadius}
				A${adjustedRadius},${adjustedRadius} 0 0 1 ${adjustedRadius},0
				Z
			`;
		}

		shouldRenderOffset()
		{
			return this.getOffset() > 0;
		}

		/**
		 * @returns {AvatarShape}
		 */
		getShape()
		{
			const { shape } = this.props;

			return AvatarShape.resolve(shape, AvatarShape.CIRCLE);
		}
	}

	AvatarView.defaultProps = {
		size: 32,
		icon: null,
		accent: false,
		offset: null,
		withRedux: false,
		emptyAvatar: 'person.svg',
		useLetterImage: true,
	};

	AvatarView.propTypes = {
		testId: PropTypes.string.isRequired,
		offset: PropTypes.number,
		id: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
		size: PropTypes.number,
		name: PropTypes.string,
		emptyAvatar: PropTypes.string,
		uri: PropTypes.string,
		shape: PropTypes.instanceOf(AvatarShape),
		accent: PropTypes.bool,
		icon: PropTypes.object,
		accentGradient: PropTypes.instanceOf(AvatarAccentGradient),
		backgroundColor: PropTypes.instanceOf(Color),
		outlineColor: PropTypes.instanceOf(Color),
		accentGradientColors: PropTypes.arrayOf(PropTypes.string),
		withRedux: PropTypes.bool,
		useLetterImage: PropTypes.bool,
		style: PropTypes.object,
		onClick: PropTypes.func,
	};

	module.exports = {
		/**
		 * @param {AvatarViewProps} props
		 */
		AvatarView: (props = {}) => {
			if (props.withRedux)
			{
				return reduxConnect(AvatarView)(props);
			}

			return new AvatarView(props);
		},
		AvatarViewClass: AvatarView,
		AvatarAccentGradient,
		AvatarShape,
	};
});
