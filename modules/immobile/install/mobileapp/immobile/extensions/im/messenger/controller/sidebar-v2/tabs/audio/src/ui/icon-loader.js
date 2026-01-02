/**
 * @module im/messenger/controller/sidebar-v2/tabs/audio/src/ui/icon-loader
 */
jn.define('im/messenger/controller/sidebar-v2/tabs/audio/src/ui/icon-loader', (require, exports, module) => {
	const { Color } = require('tokens');
	const { SpinnerLoader, SpinnerDesign } = require('layout/ui/loaders/spinner');
	const { IconView, Icon } = require('ui-system/blocks/icon');

	function IconLoader(size = 40)
	{
		return View(
			{
				testId: 'icon-loader',
				style: {
					width: size,
					height: size,
					position: 'relative',
					justifyContent: 'center',
					alignItems: 'center',
				},
			},
			View(
				{
					style: {
						width: size,
						height: size,
						backgroundColor: Color.accentMainPrimaryalt.toHex(),
						opacity: 0.7,
						position: 'absolute',
					},
				},
			),
			View(
				{
					style: {
						width: size,
						height: size,
						borderRadius: size / 2 + 4,
						backgroundColor: Color.accentMainPrimaryalt.toHex(),
						justifyContent: 'center',
						alignItems: 'center',
					},
				},
				IconView({
					testId: 'cancel-loading-icon',
					icon: Icon.CROSS,
					color: Color.baseWhiteFixed,
					size: size / 2,
				}),
				SpinnerLoader({
					size: size * 0.8,
					design: SpinnerDesign.WHITE,
					style: {
						position: 'absolute',
					},
				}),
			),
		);
	}

	module.exports = {
		IconLoader,
	};
});
