/**
 * @module layout/ui/elements-stack-steps/block/avatar
 */
jn.define('layout/ui/elements-stack-steps/block/avatar', (require, exports, module) => {
	const { Avatar: BaseAvatar } = require('ui-system/blocks/avatar');
	const { ELEMENT_SIZE, ELEMENT_BORDER } = require('layout/ui/elements-stack-steps/constants');

	/**
	 * @function Avatar
	 * @param {Object} props
	 * @param {number | string} props.id
	 * @param {string} [props.testId]
	 * @param {number} [props.size]
	 * @param {Indent} [props.outline]
	 * @param {Color} [props.borderColor]
	 * @param {Object} [props.style]
	 * @return View
	 */
	function Avatar(props = {})
	{
		const {
			id = 0,
			testId = 'elements-stack-steps-avatar',
			size = ELEMENT_SIZE,
			outline = ELEMENT_BORDER,
			borderColor = null,
			style = {},
		} = props;

		if (borderColor)
		{
			style.backgroundColor = borderColor.toHex();
		}

		return BaseAvatar({
			id,
			size,
			withRedux: true,
			outline: outline.toNumber(),
			style,
			testId,
		});
	}

	module.exports = { Avatar };
});
