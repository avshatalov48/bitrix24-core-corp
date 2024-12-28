/**
 * @module avatar-picker
 */
jn.define('avatar-picker', (require, exports, module) => {
	// eslint-disable-next-line no-undef
	include('media');

	const { getFile } = require('files/entry');
	const { FileConverter } = require('files/converter');
	const { Type } = require('type');

	class AvatarPicker
	{
		constructor()
		{
			this.#init();
		}

		#init = () => {
			this.originalImage = null;
			this.previewUrl = null;
			this.base64 = null;

			return Promise.resolve();
		};

		/**
		 * @public
		 * @returns {Promise}
		 */
		open = () => {
			return this.#init()
				.then(this.#pickImage)
				.then(this.#editImage)
				.then(this.#resizeImage)
				.then(this.#prepareBase64)
				.then(this.#export);
		};

		#pickImage = () => new Promise((resolve) => {
			dialogs.showImagePicker(
				{
					settings: {
						resize: {
							targetWidth: -1,
							targetHeight: -1,
							sourceType: 1,
							encodingType: 0,
							mediaType: 0,
							allowsEdit: true,
							saveToPhotoAlbum: true,
							cameraDirection: 0,
						},
						editingMediaFiles: false,
						maxAttachedFilesCount: 1,
						attachButton: {
							items: [
								{ id: 'mediateka' },
								{ id: 'camera' },
							],
						},
					},
				},
				resolve,
				() => {},
			);
		});

		/**
		 * @param {object[]} images
		 * @returns {Promise}
		 */
		#editImage = (images = []) => {
			if (!Type.isArrayFilled(images))
			{
				return Promise.resolve();
			}

			this.originalImage = images[0];

			// eslint-disable-next-line no-undef
			return media.showImageEditor(this.originalImage.url);
		};

		/**
		 * @param {string|undefined} path
		 * @return {Promise}
		 */
		#resizeImage = (path) => {
			if (!path)
			{
				return Promise.resolve();
			}

			const converter = new FileConverter();
			const resizeOptions = {
				url: path,
				width: 1000,
				height: 1000,
			};

			return converter.resize('AvatarPickerResize', resizeOptions)
				.then((resizedPath) => {
					this.previewUrl = resizedPath;

					return getFile(resizedPath);
				});
		};

		/**
		 * @param {object|undefined} file
		 * @return {Promise}
		 */
		#prepareBase64 = (file) => {
			if (!file)
			{
				return Promise.resolve();
			}

			// eslint-disable-next-line no-param-reassign
			file.readMode = 'readAsDataURL';

			return file.readNext()
				.then(({ content }) => {
					if (content)
					{
						this.base64 = content.slice(content.indexOf('base64,') + 7);
					}
				});
		};

		/**
		 * @return {Promise}
		 */
		#export = () => {
			if (this.previewUrl && this.base64)
			{
				return Promise.resolve({
					originalImage: this.originalImage,
					previewUrl: this.previewUrl,
					base64: this.base64,
				});
			}

			return Promise.resolve(null);
		};
	}

	module.exports = { AvatarPicker };
});
