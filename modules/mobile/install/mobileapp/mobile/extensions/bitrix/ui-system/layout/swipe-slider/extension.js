/**
 * @module ui-system/layout/swipe-slider
 */
jn.define('ui-system/layout/swipe-slider', (require, exports, module) => {
	const { Color } = require('tokens');
	const { createTestIdGenerator } = require('utils/test');
	const { SliderNavigationMode } = require('ui-system/layout/swipe-slider/src/enum');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Indent } = require('tokens');
	const { Type } = require('type');

	const PAGE_INDICATOR_HEIGHT = 38;
	/**
	 * @typedef {Object} SwipeSliderProps
	 * @property {Array<LayoutComponent>} children
	 * @property {number} contentHeight
	 * @property {number[]} pageHeights
	 * @property {number} [width]
	 * @property {string} testId
	 * @property {SliderNavigationMode} [navigationMode=SliderNavigationMode.BUTTON]

	 * @class SwipeSlider
	 */
	class SwipeSlider extends LayoutComponent
	{
		constructor(props) {
			super(props);

			this.scrollOffset = Animated.newCalculatedValue2D(0, 0);
			this.scrollOffsetX = this.scrollOffset.getValue1();

			this.getTestId = createTestIdGenerator({
				context: this,
			});
			this.sliderRef = null;
			this.#initState(props);
		}

		componentWillReceiveProps(props)
		{
			this.#initState(props);
		}

		#initState(props)
		{
			this.state = {
				pageIndex: props.pageIndex ?? 0,
			};
			const calculatedPageHeight = this.#getCalculatedPageHeight(props);
			this.height = Animated.newCalculatedValue(calculatedPageHeight);
		}

		#changeHeightAnimated(height, duration = 0.1)
		{
			return new Promise((resolve) => {
				const config = {
					toValue: height,
					duration,
					type: 'easeInQuad',
				};
				Animated.timing(this.height, config).start(() => resolve());
			});
		}

		render()
		{
			const {
				children = [],
				navigationMode = SliderNavigationMode.BUTTON,
				style = {},
			} = this.props;
			const containerStyle = {
				...style,
				height: this.height,
			};

			return View(
				{
					testId: this.getTestId('swipe-slider'),
					style: containerStyle,
				},
				Slider(
					{
						ref: this.#bindSliderRef,
						style: {
							flex: 1,
						},
						onScrollCalculated: {
							contentOffset: this.scrollOffset,
						},
						swipeEnabled: navigationMode === SliderNavigationMode.SWIPE,
						onPageChange: this.#onPageChange,
					},
					...children,
				),
				navigationMode === SliderNavigationMode.SWIPE && this.#renderPageIndicator(),
				navigationMode === SliderNavigationMode.BUTTON && this.#renderNavigationButtons(),
			);
		}

		#getCalculatedPageHeight = (props) => {
			const { pageHeights = [], contentHeight, pageIndex = 0 } = props;
			if (Type.isArrayFilled(pageHeights))
			{
				return pageHeights[pageIndex] + PAGE_INDICATOR_HEIGHT || 0;
			}

			return contentHeight + PAGE_INDICATOR_HEIGHT;
		};

		#onPageChange = async (pageIndex) => {
			await this.#changeHeightAnimated(this.#getCalculatedPageHeight({
				...this.props,
				pageIndex,
			}));
			this.setState({
				pageIndex,
			});
		};

		#bindSliderRef = (ref) => {
			this.sliderRef = ref;
		};

		#renderNavigationButtons()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						justifyContent: 'center',
					},
				},
				this.#renderNavigationButton(true),
				this.#renderNavigationButton(false),
			);
		}

		#renderNavigationButton(isLeft = true) {
			const icon = isLeft ? Icon.CHEVRON_TO_THE_LEFT : Icon.CHEVRON_TO_THE_RIGHT;
			const testId = isLeft ? this.getTestId('left-button') : this.getTestId('right-button');

			return IconView({
				testId,
				icon,
				size: 28,
				color: this.#getButtonColor(isLeft),
				style: {
					paddingHorizontal: Indent.S.toNumber(),
					paddingTop: Indent.L.toNumber(),
					paddingBottom: Indent.XS.toNumber(),
					marginRight: isLeft ? Indent.XL.toNumber() : 0,
				},
				onClick: this.#onButtonClick.bind(this, isLeft),
			});
		}

		#getButtonColor(isLeft)
		{
			const isDisabled = isLeft ? this.#isFirstPage() : this.#isLastPage();

			return isDisabled ? Color.base5 : Color.accentMainPrimaryalt;
		}

		#isLastPage = () => {
			return this.state.pageIndex === (this.props.children.length - 1);
		};

		#isFirstPage = () => {
			return this.state.pageIndex === 0;
		};

		#onButtonClick = (isLeft) => {
			if (isLeft)
			{
				this.sliderRef?.prevSlide();

				return;
			}

			this.sliderRef?.nextSlide();
		};

		#renderPageIndicator() {
			const renderDot = (index) => {
				const { width } = this.props;

				const sliderWidth = width ?? device.screen.width;
				const widthOffset = index * sliderWidth;

				return View(
					{
						style: {
							width: this.scrollOffsetX.interpolate({
								inputRange: [
									widthOffset - sliderWidth - 1,
									widthOffset - sliderWidth,
									widthOffset,
									widthOffset + sliderWidth,
									widthOffset + sliderWidth + 1,
								],
								outputRange: [6, 6, 20, 6, 6],
							}),
							height: 6,
							marginLeft: 4,
							borderRadius: 3,
							backgroundColor: Color.accentSoftBlue1.toHex(),
						},
					},
				);
			};

			const renderDots = () => {
				const length = this.props.children.length;
				const dots = Array.from({ length }).fill(1);

				return dots.map((dot, index) => renderDot(index));
			};

			return View(
				{
					style: {
						flexDirection: 'row',
						alignItems: 'center',
						justifyContent: 'center',
						paddingVertical: 16,
					},
				},
				...renderDots(),
			);
		}
	}

	module.exports = {
		/**
		 * @param {SwipeSliderProps} props
		 * @returns {SwipeSlider}
		 */
		SwipeSlider: (props) => new SwipeSlider(props),
		SliderNavigationMode,
	};
});
