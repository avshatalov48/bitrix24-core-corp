/**
 * @module files/background-manager/unattached-files-storage
 */
jn.define('files/background-manager/unattached-files-storage', (require, exports, module) => {
	const { debounce } = require('utils/function');
	const { Logger, LogType } = require('utils/logger');
	const { Uuid } = require('utils/uuid');

	const logger = new Logger([LogType.INFO]);
	const UNATTACHED_FILES_STORAGE_KEY = 'unattachedFilesStorage';

	/**
	 * @class UnattachedFilesStorage
	 */
	class UnattachedFilesStorage
	{
		constructor()
		{
			this.uuid = Uuid.getV4();
			this.cache = this.#loadDataFromStorage();
			this.debouncedSave = debounce(this.#saveData.bind(this), 100);
			BX.addCustomEvent('UnattachedFilesStorage::onSaveCacheFilesData', this.#syncCache.bind(this));
		}

		/**
		 * @return {object}
		 */
		#loadDataFromStorage()
		{
			return Application.storage.getObject(UNATTACHED_FILES_STORAGE_KEY);
		}

		#saveData(cache)
		{
			Application.storage.setObject(UNATTACHED_FILES_STORAGE_KEY, cache);
			BX.postComponentEvent('UnattachedFilesStorage::onSaveCacheFilesData', [cache, this.uuid]);
		}

		#syncCache(cache, uuid)
		{
			if (uuid !== this.uuid)
			{
				this.cache = cache;
			}
		}

		/**
		 * @public
		 */
		clear()
		{
			this.#saveData({});
		}

		/**
		 * @public
		 * @param {string} entityId
		 * @return {array}
		 */
		getByEntityId(entityId)
		{
			return Array.isArray(this.cache[entityId]) ? [...this.cache[entityId]] : [];
		}

		/**
		 * @public
		 * @param {string} entityId
		 * @param {string} fileId
		 * @param {object} fileData
		 */
		addByEntityId(entityId, fileId, fileData)
		{
			const entityFiles = this.getByEntityId(entityId);
			const fileIndex = this.#getFileIndexByFileId(entityFiles, fileId);
			// if file already exists, do nothing (if file was not uploaded, uploader will try to upload it again)
			if (fileIndex !== -1)
			{
				return;
			}

			this.cache[entityId] = [...entityFiles, { fileId, ...fileData }];
			this.debouncedSave(this.cache);
		}

		/**
		 * @public
		 * @param {string} entityId
		 * @param {string} fileId
		 */
		removeFileByEntityIdAndFileId(entityId, fileId)
		{
			const entityFiles = this.getByEntityId(entityId);
			const newEntityFiles = entityFiles.filter((file) => file?.params?.id && file?.params?.id !== fileId);

			if (newEntityFiles.length === entityFiles.length)
			{
				logger.error(`Error: fileId (${fileId}) not found in unattachedFilesStorage.removeFileByEntityIdAndFileId`);

				return;
			}

			if (newEntityFiles.length === 0)
			{
				delete this.cache[entityId];
			}
			else
			{
				this.cache[entityId] = newEntityFiles;
			}

			this.debouncedSave(this.cache);
		}

		/**
		 * @public
		 * @param {string} entityId
		 * @param {string} fileId
		 */
		setErrorParamsByEntityId(entityId, fileId)
		{
			const entityFiles = this.getByEntityId(entityId);
			const fileIndex = this.#getFileIndexByFileId(entityFiles, fileId);
			if (fileIndex === -1)
			{
				logger.error(`Error: fileId (${fileId}) not found in unattachedFilesStorage`);

				return;
			}

			this.cache[entityId] = entityFiles.map((file, index) => {
				if (index === fileIndex)
				{
					return {
						...file,
						params: {
							...file.params,
							isUploading: false,
							hasError: true,
						},
					};
				}

				return file;
			});
			this.debouncedSave(this.cache);
		}

		/**
		 * @param {array} entityFiles
		 * @param {string} fileId
		 * @return {number} // -1 if not found
		 */
		#getFileIndexByFileId(entityFiles, fileId)
		{
			return entityFiles.findIndex((file) => file.fileId === fileId);
		}

		/**
		 * @public
		 * @return {object}
		 */
		getCache()
		{
			return this.cache;
		}

		/**
		 * @public
		 * @return {boolean}
		 */
		isEmpty()
		{
			return Object.keys(this.cache).length === 0;
		}

		/**
		 * @param {string} entityId
		 * @param {string} fileId
		 * @return {?object}
		 */
		getFileByEntityIdAndFileId(entityId, fileId)
		{
			const entityFiles = this.getByEntityId(entityId);
			const file = entityFiles.find((entityFile) => entityFile.fileId === fileId) || null;
			if (!file)
			{
				logger.error(`Error: fileId (${fileId}) not found in unattachedFilesStorage`);

				return null;
			}

			return file;
		}

		/**
		 * @param {string} entityId
		 * @param {array} filesIds
		 */
		setErrorShownToFiles(entityId, filesIds)
		{
			const entityFiles = this.getByEntityId(entityId);
			if (entityFiles.length === 0)
			{
				return;
			}

			this.cache[entityId] = entityFiles.map((file) => {
				if (filesIds.includes(file.fileId))
				{
					return { ...file, params: { ...file.params, errorShown: true } };
				}

				return file;
			});
			this.debouncedSave(this.cache);
		}

		/**
		 * @param {string} entityId
		 * @param {string} fileId
		 * @param {object} fileDto
		 */
		setObjectIdToFile(entityId, fileId, fileDto)
		{
			const entityFiles = this.getByEntityId(entityId);
			const fileIndex = this.#getFileIndexByFileId(entityFiles, fileId);
			if (fileIndex === -1)
			{
				logger.error(`Error: fileId (${fileId}) not found in unattachedFilesStorage.setObjectIdToFile`);

				return;
			}

			entityFiles[fileIndex].params = fileDto;

			this.cache[entityId] = entityFiles;
			this.debouncedSave(this.cache);
		}

		/**
		 * @param {string} entityId
		 * @param {string} fileId
		 * @param {string} key
		 * @param value
		 */
		setFileParamByEntityIdAndFileId(entityId, fileId, key, value)
		{
			const entityFiles = this.getByEntityId(entityId);
			const fileIndex = this.#getFileIndexByFileId(entityFiles, fileId);
			if (fileIndex === -1)
			{
				logger.error(`Error: fileId (${fileId}) not found in unattachedFilesStorage.setFileParamByEntityIdAndFileId`);

				return;
			}

			entityFiles[fileIndex][key] = value;
			this.cache[entityId] = entityFiles;
			this.debouncedSave(this.cache);
		}
	}

	module.exports = {
		UnattachedFilesStorage: new UnattachedFilesStorage(),
	};
});
