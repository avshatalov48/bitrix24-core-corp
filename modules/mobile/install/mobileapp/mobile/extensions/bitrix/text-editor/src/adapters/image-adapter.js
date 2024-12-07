/**
 * @module text-editor/adapters/image-adapter
 */
jn.define('text-editor/adapters/image-adapter', (require, exports, module) => {
	const { BaseAdapter } = require('text-editor/adapters/base-adapter');
	const { Type } = require('type');

	/**
	 * @param options {{
	 *     node: ElementNode,
	 * }}
	 */
	class ImageAdapter extends BaseAdapter
	{
		/**
		 * @param imageWidth {number}
		 * @param imageHeight {number}
		 * @param maxWidth {number}
		 * @param maxHeight {number}
		 * @return {{width, height}|{width: number, height}|{width, height: number}}
		 */
		static resizeImageToFit({ imageWidth, imageHeight, maxWidth, maxHeight })
		{
			if (imageWidth < maxWidth && imageHeight < maxHeight)
			{
				return {
					width: Math.floor(imageWidth),
					height: Math.floor(imageHeight),
				};
			}

			const aspectRatio = imageWidth / imageHeight;
			const maxAspectRatio = maxWidth / maxHeight;

			if (aspectRatio > maxAspectRatio)
			{
				return {
					width: Math.floor(maxWidth),
					height: Math.floor(maxWidth / aspectRatio),
				};
			}

			return {
				width: Math.floor(maxHeight * aspectRatio),
				height: Math.floor(maxHeight),
			};
		}

		/**
		 * Gets preview element
		 * @returns {BBCodeElementNode}
		 */
		getPreview()
		{
			if (!this.previewSync)
			{
				const sourceNode = this.getSource();

				if (sourceNode.getName() === 'img')
				{
					const sourceImageWidth = parseInt(sourceNode.getAttribute('width'), 10);
					const sourceImageHeight = parseInt(sourceNode.getAttribute('height'), 10);

					if (Type.isNumber(sourceImageWidth) && Type.isNumber(sourceImageHeight))
					{
						const { width, height } = ImageAdapter.resizeImageToFit({
							imageWidth: sourceImageWidth,
							imageHeight: sourceImageHeight,
							maxWidth: 300,
							maxHeight: 180,
						});

						const previewNode = sourceNode.clone({ deep: true });
						previewNode.setAttributes({
							width,
							height,
						});

						this.previewSync = previewNode;
					}
				}
			}

			return this.previewSync;
		}
	}

	module.exports = {
		ImageAdapter,
	};
});
