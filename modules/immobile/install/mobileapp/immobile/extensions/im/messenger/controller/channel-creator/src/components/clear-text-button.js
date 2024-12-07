/**
 * @module im/messenger/controller/channel-creator/components/clear-text-button
 */
jn.define('im/messenger/controller/channel-creator/components/clear-text-button', (require, exports, module) => {
	const { Theme } = require('im/lib/theme');
	const { cross } = require('im/messenger/assets/common');
	/**
	 * @param {ClearTextButtonProps} props
	 */
	function clearTextButton(props)
	{
		return View(
			{
				clickable: true,
				onClick: () => {
					props.onClick();
				},
			},
			Image(
				{
					style: {
						height: 24,
						width: 24,
						opacity: props.isVisible ? 1 : 0,
					},
					resizeMode: 'contain',
					svg: {
						content: cross({ color: Theme.colors.base4, strokeWight: 0 }),
					},
				},
			),
		);
	}

	module.exports = { clearTextButton };
});
