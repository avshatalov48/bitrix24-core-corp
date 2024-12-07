/**
 * @module call/calls-card/card-content/elements/overlay
 */
jn.define('call/calls-card/card-content/elements/overlay', (require, exports, module) => {
	const Overlay = ({imagePath = ''}) => {
		const pathToExtension = '/bitrix/mobileapp/callmobile/extensions/call/calls-card/card-content/';
		const defaultClintIcon = `${currentDomain}${pathToExtension}images/client-background.png`;
		const overlayPath = `${currentDomain}${pathToExtension}images/gradient.png`;

		return View(
			{
				style: {
					position: 'absolute',
					top: 0,
					left: 0,
					right: 0,
					bottom: -2,
				},
			},
			View(
				{
					style: {
						position: 'absolute',
						top: 0,
						left: 0,
						right: 0,
						bottom: 0,
						backgroundResizeMode: 'cover',
						backgroundPosition: 'center',
						backgroundImage: imagePath ? imagePath : defaultClintIcon,
						backgroundBlurRadius: 5,
					},
				},
			),
			View(
				{
					style: {
						position: 'absolute',
						top: 0,
						left: 0,
						right: 0,
						bottom: -2,
						backgroundImage: overlayPath,
						backgroundResizeMode: 'cover',
					},
				},
			),
		);
	}

	module.exports = { Overlay };
});