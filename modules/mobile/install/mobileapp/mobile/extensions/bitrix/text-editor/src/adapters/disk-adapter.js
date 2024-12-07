/**
 * @module text-editor/adapters/disk-adapter
 */
jn.define('text-editor/adapters/disk-adapter', (require, exports, module) => {
	const { BaseAdapter } = require('text-editor/adapters/base-adapter');
	const { ImageAdapter } = require('text-editor/adapters/image-adapter');
	const { scheme } = require('text-editor/internal/scheme');
	const { Type } = require('type');
	const { getMimeType } = require('utils/file');

	/**
	 * @param options {{
	 *     node: ElementNode,
	 *     fileOptions: {
	 *     		id: number,
	 *     		url: string,
	 *     		width: number,
	 *     		height: number,
	 *     		isImage: boolean,
	 *     },
	 * }}
	 */
	class DiskAdapter extends BaseAdapter
	{
		/**
		 * Gets preview element
		 * @returns {ElementNode}
		 */
		getPreview()
		{
			if (!this.previewSync)
			{
				const { fileOptions: file } = this.getOptions();
				const mimeType = getMimeType(file.type, file.name);
				if (
					mimeType.startsWith?.('image')
					&& Type.isNumber(file?.width)
					&& Type.isNumber(file?.height)
				)
				{
					const { width, height } = ImageAdapter.resizeImageToFit({
						imageWidth: file.width,
						imageHeight: file.height,
						maxWidth: 300,
						maxHeight: 180,
					});

					this.previewSync = scheme.createElement({
						name: 'img',
						attributes: {
							width,
							height,
						},
						children: [
							scheme.createText({
								content: DiskAdapter.normalizePath(file.url),
							}),
						],
					});
				}
				else
				{
					this.previewSync = scheme.createElement({
						name: 'url',
						value: DiskAdapter.normalizePath(file.url),
						children: [
							scheme.createText({
								content: file.name,
							}),
						],
					});
				}
			}

			return this.previewSync;
		}

		static normalizePath(sourcePath)
		{
			if (Type.isStringFilled(sourcePath))
			{
				if (sourcePath.startsWith('file://'))
				{
					return sourcePath;
				}

				if (sourcePath.startsWith('/'))
				{
					return `${currentDomain}${sourcePath}`;
				}
			}

			return sourcePath;
		}
	}

	module.exports = {
		DiskAdapter,
	};
});
