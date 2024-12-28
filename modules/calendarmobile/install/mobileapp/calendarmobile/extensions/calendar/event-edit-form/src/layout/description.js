/**
 * @module calendar/event-edit-form/layout/description
 */
jn.define('calendar/event-edit-form/layout/description', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { Loc } = require('loc');
	const { TextEditor } = require('text-editor');
	const { Card } = require('ui-system/layout/card');
	const { FileField } = require('layout/ui/fields/file/theme/air');

	const { State, observeState } = require('calendar/event-edit-form/state');

	/**
	 * @class Description
	 */
	class Description extends LayoutComponent
	{
		render()
		{
			return Card(
				{
					testId: 'calendar-event-edit-form-description',
					border: true,
					onClick: this.openTextEditor,
					excludePaddingSide: {
						bottom: this.hasFiles(),
					},
				},
				this.renderText(),
				this.renderFiles(),
			);
		}

		renderText()
		{
			return BBCodeText({
				testId: 'calendar-event-edit-form-description-input',
				value: this.getTextToShow(),
				ellipsize: 'end',
				numberOfLines: 5,
				style: {
					lineSpacing: 1.2,
					fontSize: 15,
					fontWeight: '400',
					color: this.props.description.length > 0 ? Color.base1.toHex() : Color.base5.toHex(),
				},
			});
		}

		renderFiles()
		{
			return View(
				{
					style: {
						display: this.hasFiles() ? 'flex' : 'none',
						paddingTop: this.hasFiles() ? Indent.XL2.toNumber() : 0,
					},
				},
				FileField({
					testId: 'calendar-event-edit-form-description-files',
					value: [...this.props.existingFiles, ...this.props.uploadedFiles],
					config: {
						...this.getFileFieldConfig(),
						parentWidget: this.props.layout,
					},
					multiple: true,
					showTitle: false,
					showAddButton: false,
					onChange: this.#onChangeDescription,
				}),
			);
		}

		#onChangeDescription = (value) => this.onChange(this.props.description, value);

		openTextEditor = () => {
			void TextEditor.edit({
				title: Loc.getMessage('M_CALENDAR_EVENT_EDIT_DESCRIPTION_TITLE'),
				textInput: {
					placeholder: Loc.getMessage('M_CALENDAR_EVENT_EDIT_DESCRIPTION_PLACEHOLDER'),
				},
				fileField: {
					config: this.getFileFieldConfig(),
					value: [...this.props.existingFiles, ...this.props.uploadedFiles],
				},
				value: this.props.description,
				parentWidget: this.props.layout,
				allowFiles: true,
				allowInsertToText: false,
				closeOnSave: true,
				onSave: ({ bbcode, files }) => this.onChange(bbcode, files),
			});
		};

		onChange(description, files)
		{
			const uploadedFiles = [];
			const existingFiles = [];

			files.forEach((file) => {
				if (file.isUploading || file.token)
				{
					uploadedFiles.push(file);
				}
				else if (file.id && !file.hasError)
				{
					existingFiles.push(file);
				}
			});

			State.setDescriptionParams({
				description,
				existingFiles,
				uploadedFiles,
			});
		}

		getFileFieldConfig()
		{
			return {
				controller: {
					endpoint: 'calendar.FileUploader.EventController',
					options: {
						eventId: State.id.toString().startsWith('tmp') ? 0 : State.id,
					},
				},
				disk: {
					isDiskModuleInstalled: true,
					isWebDavModuleInstalled: true,
					fileAttachPath: `/mobile/?mobile_action=disk_folder_list&type=user&path=%2F&entityId=${env.userId}`,
				},
			};
		}

		hasFiles()
		{
			return this.props.existingFiles.length > 0 || this.props.uploadedFiles.length > 0;
		}

		getTextToShow()
		{
			if (this.props.description.length === 0)
			{
				return Loc.getMessage('M_CALENDAR_EVENT_EDIT_DESCRIPTION_PLACEHOLDER');
			}

			return this.props.description;
		}
	}

	const mapStateToProps = (state) => ({
		description: state.description,
		existingFiles: state.existingFiles,
		uploadedFiles: state.uploadedFiles,
	});

	module.exports = { Description: observeState(Description, mapStateToProps) };
});
