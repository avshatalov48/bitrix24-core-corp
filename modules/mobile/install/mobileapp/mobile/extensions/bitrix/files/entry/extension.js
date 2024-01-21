/**
 * @module files/entry
 */
jn.define('files/entry', (require, exports, module) => {
	const { Filesystem, Reader } = require('native/filesystem');

	const FileError = function(code, mess) {
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
			this.readMode = 'readAsBinaryString';
		}

		static toBXUrl(path)
		{
			return `bx${path}`;
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
			let extension = '';
			if (this.file.extension)
			{
				return this.file.name;
			}

			extension = (this.file.localURL.match(/\.(\w+)$/gi) || []).pop();
			if (extension)
			{
				name = name.replaceAll(/\.(\w+)$/gi, extension);
			}

			return name;
		}

		getExtension()
		{
			let extension = '';
			if (this.file.extension)
			{
				return this.file.extension;
			}

			extension = (this.file.localURL.match(/\.(\w+)$/gi) || []).pop();
			if (extension)
			{
				return extension;
			}
		}

		readNext()
		{
			return new Promise((resolve, reject) => {
				if (this.isEOF())
				{
					reject(new FileError(101));
				}
				else
				{
					const nextOffset = this.readOffset + this.chunk;
					const file = this.file.slice(this.readOffset, nextOffset);
					const mode = (this.readMode) ? this.readMode : 'readAsText';

					if (file instanceof File)
					{
						const reader = new FileReader();
						reader.onloadend = (_) => {
							const content = reader.result;
							this.readOffset = nextOffset;
							resolve({ content, start: file.start, end: file.end });
						};
						reader.onerror = (e) => reject({ 'Error reading': reader });
						reader[mode](file);

						return;
					}

					if (typeof Reader !== 'undefined')
					{
						const reader = new Reader();
						reader.on('load', (event) => {
							const content = event.result;
							this.readOffset = nextOffset;
							resolve({ content, start: file.start, end: file.end });
						});
						reader.on('error', () => {
							reject({ 'Error reading': reader });
						});
						reader[mode](file);

						return;
					}

					reject(new FileError(102, "Parameter 'file' is not instance of 'File'"));
				}
			});
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
		if (!path.includes('file://'))
		{
			path = `file://${path}`;
		}

		return new Promise((resolve, reject) => {
			const fileHandler = (file) => {
				const fileEntry = new FileEntry(file);
				fileEntry.originalPath = path;
				resolve(fileEntry);
			};

			if (typeof Filesystem === 'object')
			{
				Filesystem.getFile(path)
					.then(fileHandler)
					.catch((e) => reject(new FileError(100)));

				return;
			}

			window.resolveLocalFileSystemURL(path, (entry) => {
				if (entry.isFile)
				{
					entry.file(fileHandler);
				}
				else
				{
					reject(new FileError(100));
				}
			}, (err) => reject(err));
		});
	}

	module.exports = { getFile };
});
