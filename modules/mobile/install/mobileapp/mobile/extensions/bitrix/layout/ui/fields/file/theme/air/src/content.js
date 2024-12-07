/**
 * @module layout/ui/fields/file/theme/air/src/content
 */
jn.define('layout/ui/fields/file/theme/air/src/content', (require, exports, module) => {
	const { Counter } = require('layout/ui/fields/theme/air/elements/counter');
	const { AddButton } = require('layout/ui/fields/theme/air/elements/add-button');
	const { filePreview } = require('layout/ui/fields/file/file-preview');
	const { Indent } = require('tokens');

	const MAX_ELEMENTS = 3;

	/**
	 * @param {FileField} field
	 * @returns {object}
	 * @constructor
	 */
	const Content = ({ field }) => {
		const files = field.getFilesInfo(field.getValue());
		const showCounter = files.length > MAX_ELEMENTS;

		const elementsToShow = files.slice(0, MAX_ELEMENTS);
		const elementsToHide = files.slice(MAX_ELEMENTS);

		const hasLoadingHiddenFiles = elementsToHide.some((file) => file.isUploading && file.uuid === field.uuid);
		const hasErrorsInHiddenFiles = !hasLoadingHiddenFiles && elementsToHide.some((file) => file.hasError);

		const addButtonText = field.getAddButtonText();
		const isEnableToEdit = field.getConfig().isEnabledToEdit;
		const canDeleteFilesInPreview = !field.isReadOnly() && isEnableToEdit;

		return View(
			{
				style: {
					flexDirection: 'column',
				},
			},
			View(
				{
					style: {
						flexDirection: 'row',
						flexWrap: 'wrap',
						justifyContent: 'flex-start',
						marginBottom: Indent.XL.toNumber(),
					},
				},
				...elementsToShow.map((file, index) => filePreview(
					file,
					index,
					files,
					(canDeleteFilesInPreview || file.token) && field.deleteFileHandler,
					true,
					2,
					Indent.XL2.toNumber(),
					field.onFilePreviewMenuClick,
				)),
				View(
					{
						onClick: () => field.onOpenAttachmentList(),
						style: {
							alignSelf: 'center',
						},
					},
					showCounter && Counter({
						count: files.length - MAX_ELEMENTS,
						testId: field.testId,
						isLoading: hasLoadingHiddenFiles,
						hasErrors: hasErrorsInHiddenFiles,
					}),
				),
			),
			addButtonText
			&& field.shouldShowAddButton()
			&& field.isMultiple()
			&& !field.isReadOnly()
			&& !field.isEmpty()
			&& AddButton({
				onClick: field.openFilePicker,
				text: addButtonText,
				testId: field.testId,
			}),
		);
	};

	module.exports = {
		Content,
	};
});
