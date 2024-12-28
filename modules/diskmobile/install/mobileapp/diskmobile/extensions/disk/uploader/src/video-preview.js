/**
 * @module disk/uploader/src/video-preview
 */
jn.define('disk/uploader/src/video-preview', (require, exports, module) => {
	const { Filesystem, Reader } = require('native/filesystem');

	const createVideoPreviewMiddleware = async (task) => {
		if (isVideo(task) && task.previewUrl)
		{
			const content = await getFileContent(task.previewUrl);
			const previewName = `preview_${task.name}.jpg`;
			const boundary = `DiskUploaderFormBoundary${task.taskId}`;

			return {
				headers: {
					'Content-Type': `multipart/form-data; boundary=${boundary}`,
				},
				data: `--${boundary}\r\n`
					+ `Content-Disposition: form-data; name="previewFile"; filename="${previewName}"\r\n`
					+ `Content-Type: image/jpeg\r\n\r\n${content}\r\n\r\n`
					+ `--${boundary}--`,
			};
		}

		return {};
	};

	const isVideo = (task) => {
		const type = (task.type ? String(task.type) : '').toLowerCase();

		return type === 'mp4' || type === 'mov';
	};

	const getFileContent = async (localUrl) => {
		const file = await Filesystem.getFile(localUrl);

		return new Promise((resolve, reject) => {
			const reader = new Reader();
			reader.on('loadEnd', (event) => {
				const previewFile = event.result;
				resolve(previewFile);
			});

			reader.on('error', () => {
				reject(new Error('DiskUploader.prepareVideoPreview: file read error'));
			});

			reader.readAsBinaryString(file);
		});
	};

	module.exports = { createVideoPreviewMiddleware };
});
