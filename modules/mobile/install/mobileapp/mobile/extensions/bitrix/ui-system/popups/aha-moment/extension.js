/**
 * @module ui-system/popups/aha-moment
 */
jn.define('ui-system/popups/aha-moment', (require, exports, module) => {
	const { Color, Indent, Component } = require('tokens');
	const { H4 } = require('ui-system/typography/heading');
	const { Text4 } = require('ui-system/typography/text');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { isEqual } = require('utils/object');
	const { transition, chain } = require('animation');
	const { PropTypes } = require('utils/validation');
	const { Button, ButtonSize, ButtonDesign } = require('ui-system/form/buttons/button');
	const { AhaMomentDirection } = require('ui-system/popups/aha-moment/src/direction-enum');

	const FIXED_WIDTH = 339;
	const FIXED_IMAGE_WIDTH = 98;
	const CLOSE_SIZE = 24;

	/**
	 * @class AhaMoment
	 * @param {object} props
	 * @return AhaMoment
	 */
	class AhaMoment extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.ref = null;
			this.direction = this.getDirection();
			this.svgSize = this.direction.getSvgSize();
			this.state = {
				popupRect: {},
			};
		}

		static isShown = false;

		/**
		 * @public
		 * @param {object} props
		 * @param {number} props.testId
		 * @param {object} props.targetRef
		 * @param {object} [props.spotlightParams]
		 * @param {string} [props.title]
		 * @param {string} [props.description]
		 * @param {boolean} [props.closeButton=true]
		 * @param {function} [props.onClose]
		 * @param {function} [props.onClick]
		 * @param {object} [props.image]
		 * @param {number} [props.image.size=78]
		 */
		static show(props)
		{
			const { targetRef, spotlightParams = {}, ...restProps } = props;

			if (!targetRef)
			{
				return;
			}

			const spotlight = dialogs.createSpotlight();
			const targetParams = spotlight.setTarget(targetRef, {
				useHighlight: false,
				type: 'rectangle',
			});
			const component = new AhaMoment({ ...restProps, spotlightRef: spotlight, targetParams });
			spotlight.setHandler(component.#eventHandler);
			spotlight.setComponent(component, {
				showPointer: false,
				pointerMargin: 2,
				...spotlightParams,
			});

			if (!AhaMoment.isShown)
			{
				AhaMoment.isShown = true;
				spotlight.show();
			}
		}

		/**
		 * @param {SpotlightHandlersType} eventName
		 */
		#eventHandler = (eventName) => {
			this[eventName]?.();
		};

		onHide()
		{
			AhaMoment.isShown = false;
		}

		closeSpotlight()
		{
			const { spotlightRef } = this.props;

			if (spotlightRef)
			{
				spotlightRef.hide();
				this.onHide();
			}
		}

		handleOnClose = () => {
			const { spotlightRef, onClose } = this.props;

			if (spotlightRef)
			{
				this.closeSpotlight();

				if (onClose)
				{
					onClose();
				}
			}
		};

		handleOnClick = () => {
			const { spotlightRef, onClick } = this.props;

			if (spotlightRef)
			{
				this.closeSpotlight();

				if (onClick)
				{
					onClick();
				}
			}
		};

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

			const { buttonText, testId } = this.props;

			return Button({
				testId: testId ? `${testId}__actionButton` : null,
				text: buttonText,
				stretched: true,
				size: ButtonSize.S,
				design: ButtonDesign.OUTLINE,
				color: Color.baseWhiteFixed,
				borderColor: Color.baseWhiteFixed,
				onClick: this.handleOnClick,
				style: {
					marginVertical: Indent.XS.toNumber(),
				},
			});
		}

		#renderEar()
		{
			return Image({
				ref: (ref) => {
					this.earRef = ref;
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
			const { closeButton, testId } = this.props;

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
					onClick: this.handleOnClose,
				})
				: null;
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

		getDirection()
		{
			const { position = 'top' } = this.getTargetParams();

			return AhaMomentDirection.resolve(
				AhaMomentDirection.getEnum(position.toUpperCase()),
				AhaMomentDirection.TOP,
			);
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
						this.ref = ref;
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
				const animate = chain(
					transition(this.earRef, {
						duration: 10,
						opacity: 1,
					}),
					transition(this.ref, {
						duration: 10,
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
	}

	AhaMoment.defaultProps = {
		title: null,
		description: null,
		buttonText: null,
		closeButton: true,
		onClick: null,
		onClose: null,
		image: null,
	};

	AhaMoment.propTypes = {
		testId: PropTypes.string.isRequired,
		targetRef: PropTypes.object.isRequired,
		title: PropTypes.string,
		description: PropTypes.string,
		buttonText: PropTypes.string,
		image: PropTypes.object,
		closeButton: PropTypes.bool,
		onClick: PropTypes.func,
		onClose: PropTypes.func,
	};

	module.exports = {
		AhaMoment,
	};
});
