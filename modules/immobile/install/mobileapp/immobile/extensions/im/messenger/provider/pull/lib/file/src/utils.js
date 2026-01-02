/**
 * @module im/messenger/provider/pull/lib/file/utils
 */
jn.define('im/messenger/provider/pull/lib/file/utils', (require, exports, module) => {
	const { serviceLocator } = require('im/messenger/lib/di/service-locator');
	const { Logger } = require('im/messenger/lib/logger');
	const { Type } = require('type');
	const { FileType, TranscriptStatus, TranscriptResponseStatus } = require('im/messenger/const');
	const { downloadImages } = require('asset-manager');

	/**
	 * @class FileUtils
	 */
	class FileUtils
	{
		/**
		 * @protected
		 * @return {MessengerCoreStore|null}
		 */
		get #store()
		{
			return serviceLocator.get('core')?.getStore();
		}

		/**
		 * @param {object} params
		 * @return {Promise<Awaited<unknown>[]>|Promise<void>}
		 */
		async setFiles(params)
		{
			if (!params.files)
			{
				return Promise.resolve();
			}

			const promises = [];
			const tempFileId = params.message?.templateFileId;
			const templateFileIdExists = this.#store.getters['filesModel/isInCollection']({
				fileId: tempFileId,
			});
			const files = Type.isArray(params.files) ? params.files : Object.values(params.files);
			files.forEach((file) => {
				const fileId = file?.id;
				const idFileIdExists = this.#store.getters['filesModel/isInCollection']({
					fileId,
				});

				if (templateFileIdExists)
				{
					const updateFileWithIdPromise = this.#store.dispatch('filesModel/updateWithId', {
						id: tempFileId,
						fields: file,
					});

					promises.push(updateFileWithIdPromise);
				}
				else if (idFileIdExists)
				{
					const updateFileWithIdPromise = this.#store.dispatch('filesModel/updateWithId', {
						id: fileId,
						fields: file,
					});

					promises.push(updateFileWithIdPromise);
				}
				else
				{
					const setFilePromise = this.#store.dispatch('filesModel/set', file);
					promises.push(setFilePromise);
				}

				this.preloadToNativeCache(file);
			});

			return Promise.all(promises);
		}

		/**
		 * @param {FilesModelState} file
		 * @void
		 */
		preloadToNativeCache(file)
		{
			if (file.type === FileType.image)
			{
				downloadImages([file.urlShow, file.urlPreview])
					.then(() => {
						Logger.log(`${this.constructor.name}.preloadToNativeCache: images downloaded successfully`);
					})
					.catch((error) => {
						Logger.error(`${this.constructor.name}.preloadToNativeCache: error downloading images`, error);
					});

				return;
			}

			if (file.type === FileType.video)
			{
				downloadImages([file.urlPreview])
					.then(() => {
						Logger.log(`${this.constructor.name}.preloadToNativeCache: video image preview downloaded successfully`);
					})
					.catch((error) => {
						Logger.error(`${this.constructor.name}.preloadToNativeCache: error downloading video preview image`, error);
					});
			}
		}

		/**
		 * @param {FileId} fileId
		 * @param {string} transcriptText
		 */
		async setTranscript({ fileId, transcriptText, status })
		{
			if (Type.isUndefined(fileId) || Type.isUndefined(transcriptText))
			{
				Logger.error(`${this.constructor.name}.setTranscript: fileId or transcriptText not defined`);

				return;
			}

			const transcriptModel = this.#store.getters['filesModel/transcriptModel/getById'](fileId);

			if (Type.isNull(transcriptModel))
			{
				Logger.log(`${this.constructor.name}.setTranscript: transcriptModel not defined`);

				return;
			}

			if (status === TranscriptResponseStatus.error)
			{
				await this.#store.dispatch('filesModel/transcriptModel/set', {
					...transcriptModel,
					status: TranscriptStatus.error,
				});

				return;
			}

			await this.#store.dispatch('filesModel/transcriptModel/set', {
				...transcriptModel,
				text: transcriptText,
				status: TranscriptStatus.expanded,
			});
		}
	}

	module.exports = { FileUtils: new FileUtils() };
});
