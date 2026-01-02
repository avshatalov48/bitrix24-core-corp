/**
 * @module layout/ui/elements-stack-steps/stack
 */

jn.define('layout/ui/elements-stack-steps/stack', (require, exports, module) => {
	const { Indent } = require('tokens');
	const { ElementsStack, ElementsStackDirection } = require('elements-stack');

	/**
	 * @function Stack
	 * @param {Object} props
	 * @param {ElementsStackDirection} [props.direction]
	 * @param {Indent} [props.marginTop]
	 * @param {Indent} [props.marginBottom]
	 * @param {Indent} [props.offset]
	 * @param {string} [props.testId]
	 * @param {View} [props.status]
	 * @param {'top' | 'bottom'} [props.statusVerticalPosition]
	 * @param {'left' | 'right'} [props.statusHorizontalPosition]
	 * @param {Array<View>} children
	 * @return View
	 */
	function Stack(props = {}, ...children)
	{
		const elements = Array.isArray(children) ? children : [];
		if (elements.length === 0)
		{
			return null;
		}

		const {
			direction = ElementsStackDirection.LEFT,
			offset = Indent.XL3,
			marginTop = Indent.XS2.toNumber(),
			marginBottom = 0,
			status = null,
			statusVerticalPosition,
			statusHorizontalPosition,
			testId = 'elements-stack-steps-stack',
		} = props;

		const statusTop = statusVerticalPosition ? statusVerticalPosition === 'top' : true;
		const statusRight = statusHorizontalPosition ? statusHorizontalPosition === 'right' : true;

		return View(
			{
				style: {
					maxWidth: '100%',
					marginTop,
					marginBottom,
					paddingVertical: Indent.XS2.toNumber(),
					paddingHorizontal: Indent.XS2.toNumber(),
				},
			},
			ElementsStack(
				{
					direction,
					offset,
					indent: null,
					testId,
				},
				...elements,
			),
			View(
				{
					style: {
						position: 'absolute',
						right: statusRight ? 0 : null,
						top: statusTop ? 0 : null,
						left: statusRight ? null : 0,
						bottom: statusTop ? null : 0,
					},
				},
				status,
			),
		);
	}

	module.exports = { Stack };
});
