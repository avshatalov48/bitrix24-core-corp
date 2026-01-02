/**
 * @module animation/components/counter
 */
jn.define('animation/components/counter', (require, exports, module) => {
	const { TextBase } = require('ui-system/typography/text-base');
	const { Color, Typography } = require('tokens');
	const { Animated } = require('animation/animated');
	const { PureComponent } = require('layout/pure-component');

	const DURATION = 0.2;
	const TYPE_IN = 'easeInQuad';
	const TYPE_OUT = 'easeOutQuad';

	/**
	 * @class AnimatedCounter
	 */
	class AnimatedCounter extends PureComponent
	{
		/**
		 * @param {{
		 *     testId: string,
		 *     value: number,
		 *     typography?: Typography,
		 *     color?: Color,
		 * }} props
		 */
		constructor(props)
		{
			super(props);

			const value = Number(props.value);

			this.state = {
				currentValue: value,
				targetValue: value,
			};
			this.memorizedValue = value;
			this.isUpdating = false;

			this.#resetPositions();
			this.#resetOpacities();
			this.#resetWidth();
		}

		componentWillReceiveProps(nextProps)
		{
			this.memorizedValue = Number(nextProps.value);

			if (this.isUpdating || !this.shouldUpdate)
			{
				return;
			}

			this.state.targetValue = this.memorizedValue;

			this.#resetPositions();
			this.#resetOpacities();
			this.#startAnimation();
		}

		/**
		 * @public
		 * @param {number} value
		 */
		updateToValue(value)
		{
			this.memorizedValue = Number(value);

			if (this.isUpdating || !this.shouldUpdate)
			{
				return;
			}

			this.#updateToMemorizedValue();
		}

		#updateToMemorizedValue()
		{
			const length = Math.max(this.currentValueRealLength, String(this.memorizedValue).length);

			this.#resetPositions(length, this.state.currentValue < this.memorizedValue);
			this.#resetOpacities(length);
			this.setState({ targetValue: this.memorizedValue }, () => this.#startAnimation());
		}

		/**
		 * @return {string}
		 */
		get testId()
		{
			return this.props.testId;
		}

		/**
		 * @return {Typography}
		 */
		get typography()
		{
			return this.props.typography;
		}

		/**
		 * @return {Color}
		 */
		get color()
		{
			return this.props.color;
		}

		get digitWidth()
		{
			return this.typography.getDigitWidth();
		}

		get digitHeight()
		{
			return this.typography.getDigitHeight();
		}

		get shouldUpdate()
		{
			return this.state.currentValue !== this.memorizedValue;
		}

		get currentValue()
		{
			return String(this.state.currentValue).padStart(
				Math.max(this.currentValueRealLength, this.targetValueRealLength),
				' ',
			);
		}

		get targetValue()
		{
			return String(this.state.targetValue).padStart(
				Math.max(this.currentValueRealLength, this.targetValueRealLength),
				' ',
			);
		}

		get currentValueRealLength()
		{
			return String(this.state.currentValue).length;
		}

		get targetValueRealLength()
		{
			return String(this.state.targetValue).length;
		}

		get isNumberIncreasing()
		{
			return Number(this.currentValue) < Number(this.targetValue);
		}

		/**
		 * @param {number} length
		 * @param {boolean} isNumberIncreasing
		 */
		#resetPositions(length = this.currentValue.length, isNumberIncreasing = this.isNumberIncreasing)
		{
			this.currentValuePositions = Array.from(
				{ length },
				() => Animated.newCalculatedValue(0),
			);
			this.targetValuePositions = Array.from(
				{ length },
				() => Animated.newCalculatedValue(isNumberIncreasing ? -this.digitHeight : this.digitHeight),
			);
		}

		/**
		 * @param {number} length
		 */
		#resetOpacities(length = this.currentValue.length)
		{
			this.currentValueOpacity = Array.from({ length }, () => Animated.newCalculatedValue(1));
			this.targetValueOpacity = Array.from({ length }, () => Animated.newCalculatedValue(0));
		}

		#resetWidth()
		{
			this.width = Animated.newCalculatedValue(this.currentValueRealLength * this.digitWidth);
		}

		#startAnimation()
		{
			this.isUpdating = true;

			const onAnimationEnd = () => {
				if (this.shouldUpdate)
				{
					this.#updateToMemorizedValue();
				}
				else
				{
					this.isUpdating = false;
				}
			};

			const animations = this.#prepareAnimations();
			if (animations.length > 0)
			{
				Animated.stagger(animations, { delay: 0.05 }).start(() => {
					this.setState({ currentValue: Number(this.targetValue) }, () => onAnimationEnd());
				});
			}
			else
			{
				onAnimationEnd();
			}
		}

		/**
		 * @return {Array<JNCalculatedAnimation>}
		 */
		#prepareAnimations()
		{
			let animations = [];

			const indexes = Array.from(
				{ length: this.currentValue.length },
				(_, index) => (this.isNumberIncreasing ? this.currentValue.length - 1 - index : index),
			);
			for (const i of indexes)
			{
				if (this.currentValue[i] === this.targetValue[i])
				{
					continue;
				}

				animations = this.#addCurrentValueAnimations(animations, i);
				animations = this.#addTargetValueAnimations(animations, i);
			}

			if (this.targetValueRealLength !== this.currentValueRealLength)
			{
				animations = this.#addWidthAnimation(animations);
			}

			return animations;
		}

		#addCurrentValueAnimations(animations, digitIndex)
		{
			return [
				...animations,
				this.#createAnimation(this.currentValueOpacity[digitIndex], 0, TYPE_OUT),
				this.#createAnimation(
					this.currentValuePositions[digitIndex],
					(
						this.currentValuePositions[digitIndex].getValue()
						+ (this.isNumberIncreasing ? this.digitHeight : -this.digitHeight)
					),
					TYPE_OUT,
				),
			];
		}

		#addTargetValueAnimations(animations, digitIndex)
		{
			return [
				...animations,
				this.#createAnimation(
					this.targetValuePositions[digitIndex],
					(
						this.targetValuePositions[digitIndex].getValue()
						+ (this.isNumberIncreasing ? this.digitHeight : -this.digitHeight)
					),
					TYPE_IN,
				),
				this.#createAnimation(this.targetValueOpacity[digitIndex], 1, TYPE_IN),
			];
		}

		#addWidthAnimation(animations)
		{
			const widthAnimation = this.#createAnimation(
				this.width,
				this.targetValueRealLength * this.digitWidth,
				TYPE_IN,
			);

			if (this.targetValueRealLength > this.currentValueRealLength)
			{
				return [
					widthAnimation,
					...animations,
				];
			}

			return [
				...animations,
				widthAnimation,
			];
		}

		/**
		 * @param {CalculatedValue} animatedValue
		 * @param {number} toValue
		 * @param {AnimationType} type
		 * @param {number} duration
		 * @return {JNCalculatedAnimation}
		 */
		#createAnimation(animatedValue, toValue, type, duration = DURATION)
		{
			return Animated.timing(animatedValue, { toValue, duration, type });
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
						width: this.width,
						justifyContent: 'flex-end',
						alignItems: 'center',
					},
					testId: `${this.testId}-${this.state.currentValue}`,
				},
				...Array.from({ length: this.currentValue.length }).map((_, index) => (
					View(
						{
							style: {
								width: this.digitWidth,
								height: this.digitHeight,
								justifyContent: 'center',
								alignItems: 'center',
							},
						},
						TextBase({
							style: {
								position: 'absolute',
								top: this.currentValuePositions[index],
								opacity: this.currentValueOpacity[index],
							},
							nativeElement: Text,
							header: false,
							typography: this.typography,
							color: this.color,
							text: this.currentValue[index],
						}),
						TextBase({
							style: {
								position: 'absolute',
								top: this.targetValuePositions[index],
								opacity: this.targetValueOpacity[index],
							},
							nativeElement: Text,
							header: false,
							typography: this.typography,
							color: this.color,
							text: this.targetValue[index],
						}),
					)
				)),
			);
		}
	}

	AnimatedCounter.defaultProps = {
		typography: Typography.text4,
		color: Color.base1,
	};

	AnimatedCounter.propTypes = {
		testId: PropTypes.string.isRequired,
		value: PropTypes.number.isRequired,
		typography: PropTypes.instanceOf(Typography),
		color: PropTypes.instanceOf(Color),
	};

	module.exports = { AnimatedCounter };
});
