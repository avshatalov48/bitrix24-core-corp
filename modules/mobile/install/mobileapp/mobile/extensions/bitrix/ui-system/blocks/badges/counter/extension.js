/**
 * @module ui-system/blocks/badges/counter
 */
jn.define('ui-system/blocks/badges/counter', (require, exports, module) => {
	const { PropTypes } = require('utils/validation');
	const { Color } = require('tokens');

	/**
	 * @function Counter
	 */
	const Counter = (props) => {
		const {
			number = '',
			backgroundColor = Color.accentMainPrimary,
		} = props;

		return View(
			{
				style: {
					backgroundColor,
				},
			},
			Text(
				{
					text: number,
				},
			),
		);
	};

	Counter.defaultProps = {
		number: 0,
		backgroundColor: Color.accentMainPrimary,
	};

	Counter.propTypes = {
		number: PropTypes.number,
		backgroundColor: PropTypes.string,
	};

	module.exports = { Counter };
});
