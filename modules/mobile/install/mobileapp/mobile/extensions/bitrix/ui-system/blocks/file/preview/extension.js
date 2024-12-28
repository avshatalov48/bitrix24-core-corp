/**
 * @module ui-system/blocks/file/preview
 */
jn.define('ui-system/blocks/file/preview', (require, exports, module) => {
	const { IconView } = require('ui-system/blocks/icon');
	const { DiskIcon, FileType } = require('assets/icons/src/disk');
	const { Color, Component } = require('tokens');
	const { withCurrentDomain } = require('utils/url');
	const { ShimmedSafeImage } = require('layout/ui/safe-image');
	const { transparent } = require('utils/color');

	/**
	 * @typedef {Object} FilePreviewProps
	 * @property {string} previewUrl
	 * @property {number} size
	 */
	function FilePreview(props)
	{
		const size = props.size ?? 40;
		const isVideo = props.type === FileType.VIDEO;

		return View(
			{
				style: {
					position: 'relative',
					width: size,
					height: size,
					testId: props.testId,
				},
			},
			ShimmedSafeImage({
				style: {
					width: size,
					height: size,
					borderRadius: Component.elementSCorner.toNumber(),
					borderColor: transparent(Color.base0.toHex(), 0.12),
					borderWidth: 1,
				},
				wrapperStyle: {
					position: 'absolute',
					top: 0,
					left: 0,
				},
				resizeMode: 'cover',
				uri: withCurrentDomain(props.previewUrl),
			}),
			isVideo && View(
				{
					style: {
						bottom: 3,
						right: 3,
						position: 'absolute',
					},
				},
				IconView({
					size: 8,
					color: null,
					icon: DiskIcon.PLAY_FILLED,
				}),
			),
		);
	}

	module.exports = { FilePreview };
});