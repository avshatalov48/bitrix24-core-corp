/**
 * @module layout/ui/elements-stack-steps/block/avatar-stub
 */
jn.define('layout/ui/elements-stack-steps/block/avatar-stub', (require, exports, module) => {
	const { Color, Component } = require('tokens');
	const { Avatar: BaseAvatar } = require('ui-system/blocks/avatar');
	const { ELEMENT_SIZE, ELEMENT_BORDER } = require('layout/ui/elements-stack-steps/constants');

	/**
	 * @function AvatarStub
	 * @param {Object} props
	 * @param {number} [props.size]
	 * @param {string} [props.testId]
	 * @return View
	 */
	function AvatarStub(props = {})
	{
		const {
			size = ELEMENT_SIZE,
			testId = 'elements-stack-steps-avatar-stub',
		} = props;

		const borderWidth = ELEMENT_BORDER.toNumber() / 2;

		return View(
			{
				style: {
					alignItems: 'center',
					justifyContent: 'center',
					width: size + ELEMENT_BORDER.toNumber(),
					height: size + ELEMENT_BORDER.toNumber(),
					borderWidth,
					borderStyle: 'dash',
					borderDashSegmentLength: 3,
					borderDashGapLength: 3,
					borderRadius: Component.elementAccentCorner.toNumber(),
					borderColor: Color.accentMainPrimary.toHex(0.36),
					backgroundColor: Color.bgContentPrimary.toHex(),
				},
			},
			BaseAvatar({
				id: 0,
				size,
				testId,
				style: { opacity: 0.36 },
			}),
		);
	}

	module.exports = { AvatarStub };
});
