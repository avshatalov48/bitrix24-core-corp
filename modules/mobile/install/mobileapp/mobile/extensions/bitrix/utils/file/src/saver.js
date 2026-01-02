/**
 * @module utils/file/src/saver
 */
jn.define('utils/file/src/saver', (require, exports, module) => {
	const { Feature } = require('feature');
	const { withCurrentDomain } = require('utils/url');
	const { Filesystem, utils } = require('native/filesystem');

	const REJECTED = 'rejected';
	const FULFILLED = 'fulfilled';

	/**
	 * @class FileSaver
	 */
	class FileSaver
	{
		/**
		 * @public
		 * @param {saveToDeviceParams} params
		 * @returns {Promise<Array|Error>}
		 */
		save = async ({ files }) => {
			const fileToSave = this.#toArray(files);
			const paths = fileToSave.map((file) => file.url);
			const localPaths = await this.downloadFileToLocalCache(paths);

			const saveToLibrary = [];
			const saveToFile = [];

			localPaths.forEach((result, index) => {
				if (result.status === FULFILLED)
				{
					const file = files[index];

					if (file.toLibrary)
					{
						saveToLibrary.push(result.value);
					}
					else
					{
						saveToFile.push(result.value);
					}
				}
			});

			const savePromises = [
				saveToLibrary.length > 0 && (() => this.saveToMediaLibrary(saveToLibrary)),
				saveToFile.length > 0 && (() => this.saveToStorage(saveToFile)),
			].filter(Boolean);

			const results = await this.#parallelPromiseHandler(savePromises);
			const errors = this.#extractResults(results, REJECTED);

			if (errors.length > 0)
			{
				return Promise.reject(errors);
			}

			return this.#extractResults(results, FULFILLED);
		};

		/**
		 * @public
		 * @param {Array<String> | String} urls
		 * @returns {Promise}
		 */
		downloadFileToLocalCache = (urls) => {
			const paths = this.#toArray(urls);
			const downloadPromises = paths.map((url) => {
				return () => Filesystem.downloadFile(withCurrentDomain(url)).catch((error) => {
					console.error('FileSaver.#downloadFileToLocalCache.catch:', error);
					throw new Error('FileSaver.#downloadFileToLocalCache');
				});
			});

			if (Feature.isMultipleFilesDownloadSupported())
			{
				return this.#parallelPromiseHandler(downloadPromises);
			}

			return this.#sequentialPromiseHandler(downloadPromises);
		};

		/**
		 * @public
		 * @param {Array<String> | String} localPath
		 */
		saveToMediaLibrary = (localPath) => {
			return this.#saveToDevice({
				localPath,
				type: 'saveToMediaLibrary',
				saveFunction: (path) => utils.saveToLibrary(path),
			});
		};

		/**
		 * @public
		 * @param {Array<String> | String} localPath
		 */
		saveToStorage = (localPath) => {
			return this.#saveToDevice({
				localPath,
				type: 'saveToStorage',
				saveFunction: (path) => utils.saveFile(path),
			});
		};

		/**
		 * @private
		 * @param {Array<String> | String} localPath
		 * @param {Function} saveFunction
		 * @param {String} type
		 */
		#saveToDevice = async ({ localPath, saveFunction, type }) => {
			const paths = this.#toArray(localPath);
			if (Feature.isMultipleFilesDownloadSupported())
			{
				return saveFunction(paths).catch((error) => {
					console.error(`FileSaver.#${type}.catch:`, error);
					throw new Error(error);
				});
			}

			const savePromises = paths.map((path) => () => saveFunction(path));
			const results = await this.#sequentialPromiseHandler(savePromises);
			const errors = results.filter((result) => result.status === REJECTED);

			if (errors.length > 0)
			{
				return Promise.reject(errors);
			}

			return results;
		};

		/**
		 * @private
		 * @param {Array<Function>} promises
		 * @returns {Promise<Array>}
		 */
		#sequentialPromiseHandler(promises)
		{
			return promises.reduce((promise, fn) => {
				return promise.then((results) => fn()
					.then((result) => [...results, { status: FULFILLED, value: result }])
					.catch((error) => [...results, { status: REJECTED, reason: error }]));
			}, Promise.resolve([]));
		}

		/**
		 * @private
		 * @param {Array} results
		 * @param {String} status
		 * @returns {Array}
		 */
		#extractResults(results, status)
		{
			return results
				.flatMap((result) => {
					const resultResponse = status === FULFILLED
						? result?.value
						: result?.reason;

					return Array.isArray(resultResponse) ? resultResponse : [result];
				}).filter((result) => result.status === status);
		}

		#parallelPromiseHandler(promisesFn)
		{
			return Promise.allSettled(promisesFn.map((fn) => fn()));
		}

		/**
		 * @returns {*|*[]}
		 */
		#toArray(data)
		{
			return Array.isArray(data) ? data : [data];
		}
	}

	module.exports = {
		fileSaver: (props) => new FileSaver(props),
	};
});
