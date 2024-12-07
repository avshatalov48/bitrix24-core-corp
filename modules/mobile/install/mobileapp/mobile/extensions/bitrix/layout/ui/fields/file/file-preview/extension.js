/**
 * @module layout/ui/fields/file/file-preview
 */
jn.define('layout/ui/fields/file/file-preview', (require, exports, module) => {
	/**
	 * @function filePreview
	 */
	const filePreview = (
		file,
		index,
		files,
		onDeleteFile,
		showName,
		textLines = 2,
		leftIndent = 3,
		onFilePreviewMenuClick = null,
	) => {
		return View(
			{
				style: {
					marginRight: leftIndent,
				},
			},
			UI.File({
				id: file.id,
				url: file.url,
				imageUri: file.previewUrl || file.url,
				type: file.type,
				name: file.name,
				isLoading: file.isUploading || false,
				isShimmed: file.isShimmed || false,
				hasError: file.hasError || false,
				onDeleteAttachmentItem: onDeleteFile && (() => onDeleteFile(index)),
				styles: {
					deleteButtonWrapper: {
						width: 18,
						height: 18,
					},
					menuButtonWrapper: {
						width: 18,
						height: 18,
					},
				},
				files,
				showName,
				textLines,
				onFilePreviewMenuClick,
			}),
		);
	};

	module.exports = { filePreview };
});
