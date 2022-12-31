/**
 * @module layout/ui/fields/file/file-preview
 */
jn.define('layout/ui/fields/file/file-preview', (require, exports, module) => {

	/**
	 * @function filePreview
	 */
	const filePreview = (file, index, files, onDeleteFile, showName) => {
		return View(
			{
				style: {
					marginRight: 3,
				},
			},
			UI.File({
				id: file.id,
				url: file.url,
				imageUri: file.previewUrl || file.url,
				type: file.type,
				name: file.name,
				isLoading: file.isUploading || false,
				hasError: file.hasError || false,
				onDeleteAttachmentItem: onDeleteFile && (() => onDeleteFile(index)),
				styles: {
					deleteButtonWrapper: {
						width: 18,
						height: 18,
					},
				},
				files,
				showName,
			}),
		);
	};

	module.exports = { filePreview };

});
