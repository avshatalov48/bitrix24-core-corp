/**
 * @module ui-system/popups/aha-moment/src/hint
 */
jn.define('ui-system/popups/aha-moment/src/hint', (require, exports, module) => {
	const { Color, Indent, Component } = require('tokens');
	const { H4 } = require('ui-system/typography/heading');
	const { Text4 } = require('ui-system/typography/text');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Button, ButtonSize, ButtonDesign } = require('ui-system/form/buttons/button');
	const { AhaMomentDirection } = require('ui-system/popups/aha-moment/src/direction-enum');
	const { isEqual } = require('utils/object');
	const { transition, chain } = require('animation');

	const FIXED_WIDTH = 339;
	const FIXED_IMAGE_WIDTH = 98;
	const CLOSE_SIZE = 24;

	class Hint extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
			this.direction = this.getDirection();
			this.svgSize = this.direction.getSvgSize();
			this.state = {
				popupRect: {},
			};

			this.ref = null;
			this.earRef = null;
			this.state = {
				popupRect: {},
			};
		}

		getBackgroundColor()
		{
			return Color.bgContentInapp.toHex();
		}

		/**
		 * @returns {{position: 'top' | 'bottom', x: number, y: number, width: number, height: number}}
		 */
		getTargetParams()
		{
			const { targetParams = {} } = this.props;

			return targetParams;
		}

		shouldShowActionButton()
		{
			const { buttonText } = this.props;

			return Boolean(buttonText);
		}

		shouldRenderDescription()
		{
			const { description } = this.props;

			return Boolean(description);
		}

		getEarPosition()
		{
			const { popupRect } = this.state;
			const { x: popupX = 0 } = popupRect;
			const { x: targetX = 0, width: targetWidth } = this.getTargetParams();
			const horizontalValue = Math.round(targetX - popupX + targetWidth / 2 - this.svgSize.width / 2);
			const position = this.direction.getPosition();

			return {
				left: horizontalValue,
				[position]: 1,
			};
		}

		getDirection()
		{
			const { position = 'top' } = this.getTargetParams();

			return AhaMomentDirection.resolve(
				AhaMomentDirection.getEnum(position.toUpperCase()),
				AhaMomentDirection.TOP,
			);
		}

		render()
		{
			const { testId } = this.props;

			return this.#renderWrapper(
				View(
					{
						testId,
						style: {
							position: 'relative',
							padding: Indent.XL.toNumber(),
							borderRadius: Component.popupCorner.toNumber(),
							width: FIXED_WIDTH,
							backgroundColor: this.getBackgroundColor(),
						},
					},
					View(
						{
							style: {
								flexDirection: 'row',
							},
						},
						this.#renderImage(),
						this.#renderBody(),
					),
					this.#renderCloseButton(),
				),
			);
		}

		#renderBody()
		{
			return View(
				{
					style: {
						flex: 1,
						alignItems: 'flex-start',
						paddingLeft: Indent.XL.toNumber(),
					},
				},
				this.#renderHeader(),
				this.#renderDescription(),
				this.#renderButtons(),
			);
		}

		#renderButtons()
		{
			if (!this.shouldShowActionButton())
			{
				return null;
			}

			const { buttonText, testId, handleOnClick } = this.props;

			return Button({
				testId: testId ? `${testId}__actionButton` : null,
				text: buttonText,
				stretched: true,
				size: ButtonSize.S,
				design: ButtonDesign.OUTLINE,
				color: Color.baseWhiteFixed,
				borderColor: Color.baseWhiteFixed,
				onClick: handleOnClick,
				style: {
					marginVertical: Indent.XS.toNumber(),
				},
			});
		}

		#renderDescription()
		{
			if (!this.shouldRenderDescription())
			{
				return null;
			}

			const { description } = this.props;

			return Text4({
				text: description,
				color: Color.baseWhiteFixed,
				style: {
					marginVertical: Indent.S.toNumber(),
				},
			});
		}

		#renderHeader()
		{
			const { title } = this.props;

			if (!title)
			{
				return null;
			}

			return H4({
				text: title,
				color: Color.baseWhiteFixed,
			});
		}

		#renderImage()
		{
			const { image } = this.props;

			if (!image)
			{
				return null;
			}

			return View({
				style: {
					alignItems: 'center',
					justifyContent: 'center',
					width: FIXED_IMAGE_WIDTH,
					padding: Indent.L.toNumber(),
					borderRadius: Component.elementMCorner.toNumber(),
					backgroundColor: Color.baseWhiteFixed.toHex(0.12),
				},
			}, image);
		}

		#renderWrapper(content)
		{
			const isTop = this.direction.isTop();
			const style = {
				opacity: 0,
				paddingTop: isTop ? this.svgSize.height : 0,
				paddingBottom: isTop ? 0 : this.svgSize.height,
			};

			return View(
				{
					ref: (ref) => {
						if (ref)
						{
							this.ref = ref;
						}
					},
					onLayout: this.handleOnLayout,
					style,
				},
				content,
				this.#renderEar(),
			);
		}

		handleOnLayout = () => {
			const popupRect = this.ref.getAbsolutePosition();

			if (popupRect && !isEqual(popupRect, this.state.popupRect))
			{
				const duration = this.props.fadeInDuration ?? 10;

				const animate = chain(
					transition(this.earRef, {
						duration,
						opacity: 1,
					}),
					transition(this.ref, {
						duration,
						opacity: 1,
					}),
				);

				this.setState(
					{ popupRect },
					() => {
						animate();
					},
				);
			}
		};

		#renderEar()
		{
			return Image({
				ref: (ref) => {
					if (ref)
					{
						this.earRef = ref;
					}
				},
				style: {
					position: 'absolute',
					opacity: 0,
					...this.svgSize,
					...this.getEarPosition(),
				},
				resizeMode: 'contain',
				tintColor: this.getBackgroundColor(),
				svg: {
					content: this.direction.getSvg(this.getBackgroundColor()),
				},
			});
		}

		#renderCloseButton()
		{
			const { closeButton, testId, handleOnClose } = this.props;

			return closeButton
				? IconView({
					testId: testId ? `${testId}_close` : null,
					icon: Icon.CROSS,
					color: Color.baseWhiteFixed,
					opacity: 0.3,
					size: CLOSE_SIZE,
					style: {
						position: 'absolute',
						right: Indent.S.toNumber(),
						top: Indent.S.toNumber(),
					},
					onClick: handleOnClose,
				})
				: null;
		}
	}

	module.exports = {
		Hint: (props) => new Hint(props),
	};
});
