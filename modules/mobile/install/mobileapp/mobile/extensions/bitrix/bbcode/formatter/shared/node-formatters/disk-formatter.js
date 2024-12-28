/** @module bbcode/formatter/shared/node-formatters/disk-formatter */
jn.define('bbcode/formatter/shared/node-formatters/disk-formatter', (require, exports, module) => {
	const { DefaultBBCodeScheme } = require('bbcode/model');
	const { NodeFormatter } = require('bbcode/formatter');
	const { Type } = require('type');
	const { Loc } = require('loc');

	const scheme = new DefaultBBCodeScheme();

	class DiskNodeFormatter extends NodeFormatter
	{
		constructor(options = {})
		{
			super({
				name: 'disk',
				validate: ({ node, data }) => {
					if (Type.isArrayFilled(data.files))
					{
						const sourceFileId = node.getAttribute('id');
						const fileId = sourceFileId.replace(/^n/, '');
						const idPropName = sourceFileId.startsWith('n') ? 'objectId' : 'id';

						return data.files.some((file) => {
							return String(file[idPropName]) === String(fileId);
						});
					}

					return false;
				},
				convert({ node, data }) {
					const sourceFileId = node.getAttribute('id');
					const fileId = sourceFileId.replace(/^n/, '');
					const idPropName = sourceFileId.startsWith('n') ? 'objectId' : 'id';

					const file = data.files.find((fileOptions) => {
						return String(fileOptions[idPropName]) === String(fileId);
					});

					if (options.renderType === 'text')
					{
						return scheme.createText(
							Loc.getMessage(
								'BBCODE_PLAIN_TEXT_FORMATTER_DISK_TEXT',
								{ '{{fileName}}': file.name },
							),
						);
					}

					if (options.renderType === 'placeholder')
					{
						return scheme.createText(
							Loc.getMessage('BBCODE_PLAIN_TEXT_FORMATTER_DISK_PLACEHOLDER'),
						);
					}

					if (
						options.renderType === 'file'
						|| options.renderType === 'link'
					)
					{
						if (
							options.renderType === 'file'
							&& file.type.startsWith('image')
						)
						{
							const { width, height } = DiskNodeFormatter.resizeImageToFit({
								imageWidth: file.width,
								imageHeight: file.height,
								maxWidth: 300,
								maxHeight: 180,
							});

							return scheme.createElement({
								name: 'img',
								attributes: {
									width,
									height,
								},
								children: [
									scheme.createText(`${currentDomain}${file.url}`),
								],
							});
						}

						return scheme.createElement({
							name: 'url',
							value: file.url,
							children: [
								scheme.createText(file.name),
							],
						});
					}

					return null;
				},
			});
		}

		static resizeImageToFit({ imageWidth, imageHeight, maxWidth, maxHeight })
		{
			if (imageWidth < maxWidth && imageHeight < maxHeight)
			{
				return {
					width: imageWidth,
					height: imageHeight,
				};
			}

			const aspectRatio = imageWidth / imageHeight;
			const maxAspectRatio = maxWidth / maxHeight;

			if (aspectRatio > maxAspectRatio)
			{
				return {
					width: maxWidth,
					height: (maxWidth / aspectRatio),
				};
			}

			return {
				width: maxHeight * aspectRatio,
				height: maxHeight,
			};
		}

		static getAbsolutePath(url)
		{
			if (url && !url.startsWith('file://') && !url.startsWith('http://') && !url.startsWith('https://'))
			{
				return currentDomain + url;
			}

			return url;
		}

		static openFileViewer({ path, name, type = 'file' })
		{
			const filePath = DiskNodeFormatter.getAbsolutePath(path);

			if (type === 'image')
			{
				viewer.openImage(filePath, name);
			}
			else if (type === 'video')
			{
				viewer.openVideo(filePath, name);
			}
			else
			{
				viewer.openDocument(filePath, name);
			}
		}

		onClick({ url })
		{
			const formatter = this.getFormatter();
			if (formatter)
			{
				const data = formatter.getData();
				if (data)
				{
					const file = data.files.find((currentFile) => {
						return DiskNodeFormatter.getAbsolutePath(currentFile.url) === DiskNodeFormatter.getAbsolutePath(url);
					});

					if (file)
					{
						DiskNodeFormatter.openFileViewer({
							path: file.url,
							name: file.name,
							type: file.fileType,
						});

						return true;
					}
				}
			}

			return false;
		}
	}

	module.exports = {
		DiskNodeFormatter,
	};
});
