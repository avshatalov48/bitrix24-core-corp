/**
 * @module layout/ui/loaders/spinner
 */
jn.define('layout/ui/loaders/spinner', (require, exports, module) => {
	const { mergeImmutable } = require('utils/object');
	const { SpinnerDesign } = require('layout/ui/loaders/spinner/src/design-enum');

	/**
	 * @function SpinnerLoader
	 * @param {Object} props
	 * @param {number} [props.size]
	 * @param {SpinnerDesign} [props.design]
	 * @return {LottieView}
	 */
	function SpinnerLoader(props = {})
	{
		PropTypes.validate(SpinnerLoader.propTypes, props, 'SpinnerLoader');

		const {
			size = 24,
			design,
			...restProps
		} = props;

		if (!LottieView)
		{
			return null;
		}

		const spinnerDesign = SpinnerDesign.resolve(design, SpinnerDesign.BLUE);

		return LottieView(
			mergeImmutable(
				{
					style: {
						width: size,
						height: size,
					},
					data: {
						content: spinnerDesign.getAnimate(),
					},
					params: {
						loopMode: 'loop',
					},
					autoPlay: true,
				},
				restProps,
			),
		);
	}

	SpinnerLoader.defaultProps = {
		size: 24,
	};

	SpinnerLoader.propTypes = {
		size: PropTypes.number,
		design: PropTypes.instanceOf(SpinnerDesign),
	};

	module.exports = { SpinnerLoader, SpinnerDesign };
});
