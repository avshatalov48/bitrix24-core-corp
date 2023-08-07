(() => {
	/**
	 * @class TaskUploadFilesStorage
	 */
	class TaskUploadFilesStorage
	{
		constructor()
		{
			this.name = TaskUploaderStorageConstants.STORAGE_NAME;
			this.files = null;
		}

		getFiles()
		{
			if (this.files === null)
			{
				this.files = Application.storage.getObject(this.name);
			}

			return this.files;
		}

		getArrayFiles()
		{
			return Object.values(this.getFiles());
		}

		setFiles(files)
		{
			Application.storage.setObject(this.name, files);
			this.files = null;
		}

		addFiles(files)
		{
			files.forEach((file) => Application.storage.updateObject(this.name, { [file.id]: file }));
			this.files = null;
		}

		removeFiles(filesIds)
		{
			const files = this.getFiles();

			filesIds.forEach((id) => delete files[id]);
			this.setFiles(files);
		}

		clear()
		{
			this.setFiles({});
		}
	}

	jnexport([TaskUploadFilesStorage, 'TaskUploadFilesStorage']);
})();
