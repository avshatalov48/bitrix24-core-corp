(() => {
	/**
	 * @function filePreview
	 */
	const filePreview = (file, index, files, isInitialValueEmpty, onDeleteFile) => {
		return View(
			{
				style: {
					marginRight: 5
				}
			},
			UI.File({
				id: file.id,
				url: file.url,
				imageUri: file.previewUrl || file.url,
				type: file.type,
				name: file.name,
				onDeleteAttachmentItem: isInitialValueEmpty && (() => onDeleteFile(index)),
				styles: {
					deleteButtonWrapper: {
						width: 16,
						height: 16
					}
				},
				files,
			})
		);
	}

	jnexport(filePreview)
})();