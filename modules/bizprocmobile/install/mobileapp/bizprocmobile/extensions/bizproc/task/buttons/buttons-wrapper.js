/**
 * @module bizproc/task/buttons/buttons-wrapper
 * */

jn.define('bizproc/task/buttons/buttons-wrapper', (require, exports, module) => {
	const { merge } = require('utils/object');

	function ButtonsWrapper(props = {}, ...buttons)
	{
		const defaultProps = {
			style: {
				width: '100%',
				flexDirection: 'row',
				flexWrap: 'no-wrap',
				height: 36,
			},
		};

		return View(
			merge(defaultProps, props),
			...buttons,
		);
	}

	module.exports = {
		ButtonsWrapper,
	};
});
