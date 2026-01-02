/**
 * @module im/messenger/controller/sidebar-v2/tabs/audio/src/ui/audio-icon
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/audio/src/ui/audio-icon', (require, exports, module) => {

	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { Color } = require('tokens');
	const { IconLoader } = require('im/messenger/controller/sidebar-v2/tabs/audio/src/ui/icon-loader');
	const { PlayerStage } = require('im/messenger/controller/sidebar-v2/tabs/audio/src/const');

	/**
	 * @param {PlayerStage} stage
	 * @param {number} size=40
	 * @returns {View}
	 * @constructor
	 */
	function AudioIcon(stage, size = 40)
	{
		return View(
			{
				style: {
					borderRadius: 999,
					backgroundColor: Color.accentMainPrimaryalt.toHex(),
					height: size,
					width: size,
					alignItems: 'center',
					justifyContent: 'center',
					alignContent: 'center',
				},
			},
			renderIcon(stage, size),
		);
	}

	/**
	 * @param {PlayerStage} stage
	 * @param {number} size
	 * @returns {IconView|View}
	 */
	function renderIcon(stage, size)
	{
		if (stage === PlayerStage.LOADING)
		{
			return IconLoader(size);
		}

		return IconView({
			icon: (stage === PlayerStage.PLAYING) ? Icon.PAUSE : Icon.PLAY,
			size: size / 2 + 4,
			color: Color.baseWhiteFixed,
		});
	}

	module.exports = {
		AudioIcon,
		PlayerStage,
	};
});
