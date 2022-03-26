(() => {
	const MAX_PHOTO_WIDTH = 2048;
	const MAX_PHOTO_HEIGHT = 2048;

	/**
	 * @class EntityEditorFileField
	 */
	class EntityEditorFileField extends EntityEditorField
	{
		prepareConfig()
		{
			return {
				...super.prepareConfig(),
				// ToDo reloadEntityListFromProps: this.editor && this.editor.settings.loadFromModel,
				fileInfo: this.prepareFileInfo()
			};
		}

		prepareFileInfo()
		{
			const fileInfoField = this.schemeElement.getDataParam('fileInfoField', null);
			if (fileInfoField)
			{
				return this.model.getField(fileInfoField, {});
			}

			return [];
		}

		getValuesToSave()
		{
			return {
				[this.getName()]: new Promise((resolve) => {
					let files = CommonUtils.objectClone(this.getValue());

					if (!Array.isArray(files))
					{
						files = []
					}

					const resultFiles = files.filter((file) => !(typeof file === 'object' && file !== null));

					const promises = files
						.filter((file) => typeof file === 'object' && file !== null)
						.map((newFile) => {
							return this.resizeIfFileIsImage(newFile).then((path) => {
								return BX.FileUtils.fileForReading(path)
									.then((file) => {
										file.readMode = BX.FileConst.READ_MODE.DATA_URL;

										return file
											.readNext()
											.then((fileData) => {
												if (fileData.content)
												{
													const fileInfo = file.file;
													const content = fileData.content;

													return {
														name: (fileInfo && fileInfo.name ? fileInfo.name : photo.name),
														type: newFile.type,
														content: content.substr(content.indexOf("base64,") + 7)
													};
												}

												return null;
											})
											.catch(e => console.error(e));
									})
							})
						})
					;

					Promise
						.all(promises)
						.then((newFiles) => {
							resolve({
								[this.getName()]: [
									...resultFiles,
									...newFiles
								]
							});
						})
					;
				})
			};
		}

		resizeIfFileIsImage(file)
		{
			if (file.type.indexOf('image/') === 0)
			{
				return FileProcessing.resize(
					'EntityEditorFileField_' + Math.random().toString(),
					{
						url: file.url,
						width: MAX_PHOTO_WIDTH,
						height: MAX_PHOTO_HEIGHT
					}
				);
			}

			return Promise.resolve(file.url);
		}
	}

	jnexport(EntityEditorFileField)
})();
