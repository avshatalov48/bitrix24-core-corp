/**
 * @module ui-system/blocks/avatar/src/elements/layout
 */
jn.define('ui-system/blocks/avatar/src/elements/layout', (require, exports, module) => {
	const { isEmpty } = require('utils/object');
	const { SafeImage } = require('layout/ui/safe-image');
	const { UserLetters } = require('layout/ui/user/empty-avatar');
	const { AvatarAccentGradient } = require('ui-system/blocks/avatar/src/enums/accent-gradient-enum');
	const { AvatarShape } = require('ui-system/blocks/avatar/src/enums/shape-enum');
	const { AvatarBase } = require('ui-system/blocks/avatar/src/elements/base');

	/**
	 * @typedef {AvatarBaseProps} AvatarViewProps
	 *
	 * @class Avatar
	 */
	class AvatarView extends AvatarBase
	{
		render()
		{
			if (this.shouldRenderOutline())
			{
				return this.renderOutlineWrapper(this.renderImageContainer());
			}

			return this.renderImageContainer();
		}

		renderImageContainer()
		{
			return View(
				{
					ref: this.handleForwardRef,
					testId: this.getTestId(),
					style: this.getContainerStyle(),
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
						content: this.getAccentSvg(),
					},
				}),
				this.renderAccentBackground(),
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

		renderAccentBackground()
		{
			const imageSize = this.getImageSize();
			const thickness = (this.getSize() - this.getImageSize()) / 2;
			const size = imageSize + thickness + this.getBackBorderWidth();
			const center = (size - imageSize) / 2 - this.getBackBorderWidth();

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
							backgroundColor: this.getOutlineColor(),
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
				resizeMode: 'cover',
				uri: this.getUri(),
				renderPlaceholder: this.renderPlaceholder(),
				...params,
			});
		}

		renderPlaceholder()
		{
			if (this.isUseLetterImage())
			{
				return this.renderUserLetters();
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

		renderUserLetters()
		{
			let style = this.getImageStyle();
			const placeholderBackgroundColor = this.getPlaceholderBackgroundColorParams();

			if (!isEmpty(placeholderBackgroundColor))
			{
				const { backgroundColor, backgroundColorGradient = null } = placeholderBackgroundColor;
				style = { ...style, backgroundColor, backgroundColorGradient };
			}

			return UserLetters({
				style,
				clickable: false,
				id: this.getUserId(),
				name: this.getUserName(),
				size: this.getImageSize(),
			});
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

		getImageSize()
		{
			const backBorderWidth = this.getBackBorderWidth();

			return (this.isAccent() ? this.getSize() / 1.2 : this.getSize()) - backBorderWidth;
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

		generateAccentLinearGradient()
		{
			const colors = this.getAccentColorGradient();

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
	}

	module.exports = {
		/**
		 * @param {AvatarViewProps} props
		 */
		AvatarView: (props) => new AvatarView(props),
		AvatarViewClass: AvatarView,
		AvatarAccentGradient,
		AvatarShape,
	};
});
