(() => {
	/**
	 * @class TaskUploadFilesStorage
	 */
	class TaskUploadFilesStorage
	{
		constructor()
		{
			this.name = TaskUploaderStorageConstants.STORAGE_NAME;
		}

		getFiles()
		{
			return Application.storage.getObject(this.name);
		}

		getArrayFiles()
		{
			return Object.values(this.getFiles());
		}

		setFiles(files)
		{
			Application.storage.setObject(this.name, files);
		}

		addFiles(files)
		{
			files.forEach(file => Application.storage.updateObject(this.name, {[file.id]: file}));
		}

		removeFiles(filesIds)
		{
			const files = this.getFiles();

			filesIds.forEach(id => delete files[id]);
			this.setFiles(files);
		}

		clear()
		{
			this.setFiles({});
		}
	}

	jnexport([TaskUploadFilesStorage, 'TaskUploadFilesStorage']);
})();