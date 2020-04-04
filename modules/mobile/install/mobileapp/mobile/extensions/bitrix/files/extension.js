(function ()
{
	include("MediaConverter");
	this.FileProcessing = {
		promiseList: {},
		resize: function (taskId, params)
		{
			return new Promise((resolve, reject) =>
			{
				this.promiseList[taskId] = (event, data) =>
				{
					if (event == "onSuccess")
					{
						if (data.path.indexOf("file://") == -1)
						{
							data.path = "file://" + data.path;
						}
						resolve(data.path);
					}
					else
					{
						reject();
					}
				};

				MediaConverter.resize(taskId, params);
			});
		},
		cancel: function ()
		{

		},
		init: function ()
		{
			if (window.MediaConverter)
			{
				MediaConverter.setListener((event, data) =>
				{
					if (this.promiseList[data.id])
					{
						this.promiseList[data.id](event, data);
						delete this.promiseList[data.id];
					}
				});
			}
		},
	};
	this.FileProcessing.init();

	/** *********
	 * Consts
	 *********** */
	BX.FileConst = {
		READ_MODE: {
			STRING: "readAsText",
			BIN_STRING: "readAsBinaryString",
			DATA_URL: "readAsDataURL"
		}
	};

	/** *********
	 * Events
	 *********** */

	BX.FileUploadEvents = {
		FILE_CREATED: "onfilecreated",
		FILE_CREATED_FAILED: "onerrorfilecreate",
		FILE_UPLOAD_PROGRESS: "onprogress",
		FILE_UPLOAD_START: "onloadstart",
		FILE_UPLOAD_FAILED: "onfileuploadfailed",
		FILE_READ_ERROR: "onfilereaderror",
		ALL_TASK_COMPLETED: "oncomplete",
		TASK_TOKEN_DEFINED: "ontasktokendefined",
		TASK_STARTED_FAILED: "onloadstartfailed",
		TASK_CREATED: "ontaskcreated",
		TASK_CANCELLED: "ontaskcancelled",
		TASK_NOT_FOUND: "ontasknotfound"
	};

	/**
	 * @readonly
	 * @typedef {string} Events
	 * @enum {Events}
	 */
	 this.TaskEventConsts = BX.FileUploadEvents;

	BX.FileError = function (code, mess)
	{
		this.code = code;
		this.mess = mess;
	};

	/**
	 *
	 * @param {String} path
	 * @param {Function} action
	 * @returns {Promise}
	 */
	let resolveLocalFileSystemURL = (path, action) =>
	{
		return new Promise((resolve, reject) =>
			{
				window.resolveLocalFileSystemURL(path, entry => action(entry, resolve, reject), err => reject(err))
			}
		)
	};

	/** *********
	 * Utils
	 *********** */

	BX.FileUtils = {
		getFileEntry: (filePath) =>
		{
			if (filePath.indexOf("file://") < 0)
			{
				filePath = "file://" + filePath;
			}
			return resolveLocalFileSystemURL(filePath, (entry, resolve, reject) =>
			{
				if (entry.isFile)
				{
					resolve(entry);
				}
				else
				{
					reject(new FileError(100))
				}

			});
		},
		getFile: (filePath) =>
		{
			return new Promise((resolve, reject) =>
				BX.FileUtils.getFileEntry(filePath)
					.then(entry => entry.file(file => resolve(file)))
					.catch(e => reject(e))
			)
		},
		readFile: (file, readMode) =>
		{
			return new Promise((resolve, reject) =>
			{

				if (file instanceof File)
				{
					let reader = new FileReader();
					let mode = (readMode)
						? readMode
						: "readAsText";
					reader.onloadend = _ => resolve(reader.result);
					reader.onerror = e => reject({"Error reading": reader});
					reader[mode](file);
				}
				else
				{
					reject(new BX.FileError(102, "Parameter 'file' is not instance of 'File'"));
				}

			})
		},
		readFileEntry: (fileEntry, readMode) =>
		{
			return new Promise((resolve, reject) =>
			{
				if (fileEntry instanceof FileEntry)
				{
					fileEntry.file(
						file =>
						{
							BX.FileUtils.readFile(file, readMode)
								.then(result => resolve(result))
								.catch(e => reject(e))
						}
					)
				}
				else
				{
					reject(new BX.FileError(102, "Parameter 'file' is not instance of 'File'"));
				}
			})
		},
		readFileByPath: (url, readMode) =>
		{
			return new Promise((finalResolve, finalReject) =>
			{
				BX.FileUtils.getFileEntry(url)
					.then(
						fileEntry => BX.FileUtils.readFileEntry(fileEntry, readMode)
							.then(result => finalResolve(result))
							.catch(e => finalReject(e))
					)
					.catch(e => finalReject(e))
				;
			});
		},
		readDir: (path) =>
		{
			return resolveLocalFileSystemURL(path, (fileSystem, resolve, reject) =>
			{
				fileSystem.createReader().readEntries(entries => resolve(entries), err => reject(err));
			});
		},
		fileForReading: (path) =>
		{
			return new Promise((resolve, reject) =>
			{
				BX.FileUtils.getFile(path)
					.then(file =>
					{
						let fileEntry = new BX.File(file);
						fileEntry.originalPath = path;
						resolve(fileEntry);
					})
					.catch(e => reject(e))
			})
		}
	};

	/** *********
	 * File
	 *********** */
	BX.File = function (file)
	{
		this.init(file);
	};
	BX.File.toBXUrl = (path) =>
	{
		return "bx" + path;
	};
	BX.File.prototype = {
		readOffset: 0,
		init: function (file)
		{
			this.file = file;
			this.readOffset = 0;
			this.chunk = file.size;
			this.readMode = "readAsBinaryString";
		},
		getChunkSize: function ()
		{
			return this.chunk && this.chunk < this.file.size
				? Math.round(this.chunk)
				: this.file.size;
		},
		getSize: function ()
		{
			return this.file.size;
		},
		getType: function ()
		{
			return this.file.type;
		},
		getMimeType: function ()
		{
			return this.file.mimeType;
		},
		getName: function ()
		{
			return this.file.name;
		},
		readNext: function ()
		{
			return new Promise((resolve, reject) =>
			{
				if (this.isEOF())
				{
					reject(new FileError(101))
				}
				else
				{
					let nextOffset = this.readOffset + this.chunk;
					let fileRange = this.file.slice(this.readOffset, nextOffset);
					BX.FileUtils.readFile(fileRange, this.readMode)
						.then(content =>
						{
							this.readOffset = nextOffset;
							resolve({content: content, start: fileRange.start, end: fileRange.end});
						})
						.catch(e => reject(e))
				}

			});
		},
		isEOF: function ()
		{
			return (this.readOffset >= this.file.size);
		},
		reset: function ()
		{
			this.readOffset = 0;
		}
	};
})();