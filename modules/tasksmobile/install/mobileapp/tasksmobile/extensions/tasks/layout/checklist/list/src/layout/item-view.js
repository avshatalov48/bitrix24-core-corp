/**
 * @module tasks/layout/checklist/list/src/layout/item-view
 */
jn.define('tasks/layout/checklist/list/src/layout/item-view', (require, exports, module) => {
	const { Color } = require('tokens');

	const BORDER_COLOR = Color.bgSeparatorSecondary.toHex();

	/**
	 * @function ChecklistItemView
	 * @param {Object} [props]
	 * @param {View[]} [props.children]
	 * @param {Boolean} [props.divider]
	 * @param {Number} [props.dividerShift]
	 * @param {...*} props.restProps
	 * @return View
	 */
	const ChecklistItemView = (props = {}) => {
		const { children, style, divider = false, dividerShift = 0, ...restProps } = props;

		return View(
			{
				style: {
					flexDirection: 'column',
					paddingHorizontal: 18,
					...style,
				},
				...restProps,
			},
			View(
				{
					style: {
						paddingVertical: 14,
					},
				},
				...(Array.isArray(children) ? children : [children]),
			),
			divider && View({
				style: {
					backgroundColor: BORDER_COLOR,
					width: '100%',
					height: 1,
					marginLeft: dividerShift,
				},
			}),
		);
	};

	module.exports = { ChecklistItemView };
});
