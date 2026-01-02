/**
 * @module animation/animated
 */
jn.define('animation/animated', (require, exports, module) => {
	/**
	 * @class Animate
	 */
	class Animate
	{
		/**
		 * @param {number} value
		 * @return {CalculatedValue}
		 */
		static newCalculatedValue(value)
		{
			// eslint-disable-next-line no-undef
			return Animated.newCalculatedValue(value);
		}

		/**
		 * @param {number} value1
		 * @param {number} value2
		 * @return {CalculatedValue2D}
		 */
		static newCalculatedValue2D(value1, value2)
		{
			// eslint-disable-next-line no-undef
			return Animated.newCalculatedValue2D(value1, value2);
		}

		/**
		 * @param {CalculatedValue} value
		 * @param {TimingConfig} config
		 * @return {JNCalculatedAnimation}
		 */
		static timing(value, config)
		{
			// eslint-disable-next-line no-undef
			return Animated.timing(value, config);
		}

		/**
		 * @param {Array<JNCalculatedAnimation>} animations
		 * @param {StaggerConfig} config
		 * @return {JNCalculatedAnimation}
		 */
		static stagger(animations, config)
		{
			// eslint-disable-next-line no-undef
			return Animated.stagger(animations, config);
		}
	}

	module.exports = {
		Animated: Animate,
	};
});
