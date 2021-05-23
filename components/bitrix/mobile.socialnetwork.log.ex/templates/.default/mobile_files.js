;
(function(){
	"use strict";

	/** *********
	 * Utils
	 *********** */

	/**
	 *
	 * @param {String} path
	 * @param {Function} action
	 * @returns {Promise}
	 */
	var resolveLocalFileSystemURL = function resolveLocalFileSystemURL(path, action) {
		return new Promise(function (resolve, reject) {
			window.resolveLocalFileSystemURL(path, function (entry) {
				return action(entry, resolve, reject);
			}, function (err) {
				return reject(err);
			});
		});
	};

	BX.FileUtils = {
		getFileEntry: function getFileEntry(filePath) {
			if (filePath.indexOf("file://") < 0) {
				filePath = "file://" + filePath;
			}
			return resolveLocalFileSystemURL(filePath, function (entry, resolve, reject) {
				if (entry.isFile) {
					resolve(entry);
				} else {
					reject(new FileError(100));
				}
			});
		},
		getFile: function getFile(filePath) {
			return new Promise(function (resolve, reject) {
				return BX.FileUtils.getFileEntry(filePath).then(function (entry) {
					return entry.file(function (file) {
						return resolve(file);
					});
				}).catch(function (e) {
					return reject(e);
				});
			});
		},
		readFile: function readFile(file, readMode) {
			return new Promise(function (resolve, reject) {

				if (file instanceof File) {
					var reader = new FileReader();
					var mode = readMode ? readMode : "readAsText";
					reader.onloadend = function (_) {
						return resolve(reader.result);
					};
					reader.onerror = function (e) {
						return reject({ "Error reading": reader });
					};
					reader[mode](file);
				} else {
					reject(new BX.FileError(102, "Parameter 'file' is not instance of 'File'"));
				}
			});
		},
		readFileEntry: function readFileEntry(fileEntry, readMode) {
			return new Promise(function (resolve, reject) {
				if (fileEntry instanceof FileEntry) {
					fileEntry.file(function (file) {
						BX.FileUtils.readFile(file, readMode).then(function (result) {
							return resolve(result);
						}).catch(function (e) {
							return reject(e);
						});
					});
				} else {
					reject(new BX.FileError(102, "Parameter 'file' is not instance of 'File'"));
				}
			});
		},
		readFileByPath: function readFileByPath(url, readMode) {
			return new Promise(function (finalResolve, finalReject) {
				BX.FileUtils.getFileEntry(url).then(function (fileEntry) {
					return BX.FileUtils.readFileEntry(fileEntry, readMode).then(function (result) {
						return finalResolve(result);
					}).catch(function (e) {
						return finalReject(e);
					});
				}).catch(function (e) {
					return finalReject(e);
				});
			});
		},
		readDir: function readDir(path) {
			return resolveLocalFileSystemURL(path, function (fileSystem, resolve, reject) {
				fileSystem.createReader().readEntries(function (entries) {
					return resolve(entries);
				}, function (err) {
					return reject(err);
				});
			});
		},
		fileForReading: function fileForReading(path) {
			return new Promise(function (resolve, reject) {
				BX.FileUtils.getFile(path).then(function (file) {
					var fileEntry = new BX.File(file);
					fileEntry.originalPath = path;
					resolve(fileEntry);
				}).catch(function (e) {
					return reject(e);
				});
			});
		}
	};
})();