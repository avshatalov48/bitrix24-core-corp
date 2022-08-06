jn.define("files/entry", function (require, exports, module) {
	let {Filesystem, Reader} = jn.require("native/filesystem")

	const FileError = function (code, mess)
	{
		this.code = code;
		this.mess = mess;
	};

	class FileEntry
	{
		constructor(nativeFile)
		{
			this.readOffset = 0;
			this.file = nativeFile;
			this.chunk = nativeFile.size;
			this.readMode = "readAsBinaryString";
		}

		static toBXUrl(path)
		{
			return "bx" + path;
		}

		getChunkSize()
		{
			return this.chunk && this.chunk < this.file.size
				? Math.round(this.chunk)
				: this.file.size;
		}

		getSize()
		{
			return this.file.size;
		}

		getType()
		{
			return this.file.type;
		}

		getMimeType()
		{
			return this.file.type;
		}

		getName()
		{
			let name = this.file.name;
			let extension = "";
			if (this.file.extension)
			{
				return this.file.name
			}
			else
			{
				extension = (this.file.localURL.match(/\.(\w+)$/gi) || []).pop();
				if (extension)
				{
					name = name.replace(/\.(\w+)$/gi, extension);
				}
			}

			return name;
		}

		getExtension() {
			let extension = "";
			if (this.file.extension)
			{
				return this.file.extension;
			}
			else
			{
				extension = (this.file.localURL.match(/\.(\w+)$/gi) || []).pop();
				if (extension)
				{
					return extension;
				}
			}
		}

		readNext()
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
					let file = this.file.slice(this.readOffset, nextOffset);
					let mode = (this.readMode) ? this.readMode : "readAsText";

					if (file instanceof File)
					{
						let reader = new FileReader();
						reader.onloadend = _ => {
							let content = reader.result;
							this.readOffset = nextOffset;
							resolve({content, start: file.start, end: file.end});
						};
						reader.onerror = e => reject({"Error reading": reader});
						reader[mode](file);
						return;
					}
					else
					{
						if (typeof Reader !== "undefined")
						{
							let reader = new Reader();
							reader.on("load", event => {
								let content = event.result;
								this.readOffset = nextOffset;
								resolve({content, start: file.start, end: file.end});
							})
							reader.on("error", () => {
								reject({"Error reading": reader})
							});
							reader[mode](file);
							return;
						}

					}

					reject(new FileError(102, "Parameter 'file' is not instance of 'File'"));
				}

			})
		}

		isEOF()
		{
			return (this.readOffset >= this.file.size);
		}

		reset()
		{
			this.readOffset = 0;
		}
	}

	/**
	 * @return {Promise}
	 */
	function getFile(path)
	{
		if (path.indexOf("file://") < 0)
		{
			path = "file://" + path;
		}

		return new Promise((resolve, reject) => {
				let fileHandler = file => {
					let fileEntry = new FileEntry(file);
					fileEntry.originalPath = path;
					resolve(fileEntry);
				}

				if (typeof Filesystem == "object")
				{
					Filesystem.getFile(path)
						.then(fileHandler)
						.catch(e => reject(new FileError(100)))

					return;
				}

				window.resolveLocalFileSystemURL(path, entry => {
					if (entry.isFile)
					{
						entry.file(fileHandler)
					}
					else
					{
						reject(new FileError(100))
					}

				}, err => reject(err));
			}
		)
	}

	module.exports = { getFile }
});