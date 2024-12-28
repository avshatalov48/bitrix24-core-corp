/**
 * @module disk/uploader/src/preview-overlay
 */
jn.define('disk/uploader/src/preview-overlay', (require, exports, module) => {
	const { Color } = require('tokens');

	const DiskFileRowPreviewOverlay = (props, ...children) => View(
		{
			testId: props.testId,
			style: {
				width: 40,
				height: 40,
				position: 'absolute',
				justifyContent: 'center',
				alignItems: 'center',
			},
			onClick: props.onClick,
		},
		View(
			{
				style: {
					width: 40,
					height: 40,
					backgroundColor: Color.bgContentPrimary.toHex(),
					opacity: 0.7,
					position: 'absolute',
				},
			},
		),
		View(
			{
				style: {
					width: 24,
					height: 24,
					borderRadius: 24,
					backgroundColor: props.backgroundColor,
					justifyContent: 'center',
					alignItems: 'center',
				},
			},
			...children,
		),
	);

	module.exports = { DiskFileRowPreviewOverlay };
});
