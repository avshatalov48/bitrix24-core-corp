/**
 * @module tasks/layout/fields/result
 */
jn.define('tasks/layout/fields/result', (require, exports, module) => {
	const { TaskResultList } = require('tasks/layout/fields/result/list');
	const { TaskResultView } = require('tasks/layout/fields/result/view');
	const { TextEditor } = require('text-editor');
	const { Loc } = require('loc');
	const { Haptics } = require('haptics');
	const { showToast, Position } = require('toast');
	const { Icon } = require('assets/icons');
	const { Color } = require('tokens');
	const { BottomSheet } = require('bottom-sheet');

	const store = require('statemanager/redux/store');
	const { dispatch } = store;
	const { create, update, remove } = require('tasks/statemanager/redux/slices/tasks-results');

	/**
	 * @class TaskResultField
	 */
	class TaskResultField extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.fieldContainerRef = null;
		}

		render()
		{
			if (this.props.ThemeComponent)
			{
				return this.props.ThemeComponent(this);
			}

			return null;
		}

		/**
		 * @public
		 * @param {PageManager} [parentWidget = this.parentWidget]
		 */
		createNewResult(parentWidget = this.parentWidget)
		{
			void TextEditor.edit({
				parentWidget,
				textInput: {
					placeholder: Loc.getMessage('TASKS_FIELDS_RESULT_ADD_WIDGET_PLACEHOLDER'),
				},
				title: Loc.getMessage('TASKS_FIELDS_RESULT_ADD_WIDGET_TITLE'),
				fileField: {
					config: {
						controller: {
							endpoint: 'tasks.FileUploader.TaskResultController',
							options: {
								taskId: this.taskId,
								commentId: 0,
							},
						},
						disk: {
							isDiskModuleInstalled: true,
							isWebDavModuleInstalled: true,
							fileAttachPath: `/mobile/?mobile_action=disk_folder_list&type=user&path=%2F&entityId=${env.userId}`,
						},
					},
					value: [],
				},
				closeOnSave: true,
				onSave: ({ bbcode, files }) => {
					return new Promise((resolve, reject) => {
						if (bbcode.length === 0)
						{
							this.#showEmptyEditorError();
							reject();

							return;
						}

						const { existingFiles, uploadedFiles } = this.#processFiles(files);

						dispatch(
							create({
								taskId: this.taskId,
								commentData: {
									POST_MESSAGE: bbcode,
									EXISTING_FILES: existingFiles,
									UPLOADED_FILES: uploadedFiles,
								},
							}),
						)
							.then(() => {
								Haptics.notifySuccess();
								resolve();
							})
							.catch(console.error)
						;
					});
				},
			});
		}

		/**
		 * @public
		 * @param {number} resultId
		 * @param {boolean} [isFocused = false]
		 */
		openResult(resultId, isFocused = false)
		{
			TaskResultView.open({
				isFocused,
				resultId,
				taskId: this.taskId,
				parentWidget: this.parentWidget,
				onSave: (result, text, files) => {
					return new Promise((resolve, reject) => {
						if (text.length === 0)
						{
							this.#showEmptyEditorError();
							reject();

							return;
						}

						const { existingFiles, uploadedFiles } = this.#processFiles(files);

						dispatch(
							update({
								taskId: this.taskId,
								commentId: result.commentId,
								commentData: {
									POST_MESSAGE: text,
									EXISTING_FILES: existingFiles,
									UPLOADED_FILES: uploadedFiles,
								},
							}),
						)
							.then(() => {
								Haptics.notifySuccess();
								resolve();
							})
							.catch(console.error)
						;
					});
				},
				onRemove: (commentId) => this.removeResult(commentId),
			});
		}

		/**
		 * @public
		 * @param {number} commentId
		 */
		removeResult(commentId)
		{
			dispatch(
				remove({
					commentId,
					taskId: this.taskId,
				}),
			)
				.then(() => Haptics.notifySuccess())
				.catch(console.error)
			;
		}

		/**
		 * @public
		 */
		openResultList()
		{
			void new BottomSheet({
				titleParams: {
					text: Loc.getMessage('TASKS_FIELDS_RESULT_LIST_WIDGET_TITLE'),
					type: 'dialog',
				},
				component: (layout) => {
					return TaskResultList({
						taskId: this.taskId,
						parentWidget: layout,
						onResultClick: (resultId) => this.openResult(resultId),
						onCreateClick: () => this.createNewResult(layout),
					});
				},
			})
				.setParentWidget(this.parentWidget || PageManager)
				.setBackgroundColor(Color.bgSecondary.toHex())
				.setNavigationBarColor(Color.bgSecondary.toHex())
				.alwaysOnTop()
				.open()
			;
		}

		#processFiles(files)
		{
			const loadedFiles = files.filter((file) => !file.isUploading);
			const existingFiles = loadedFiles.filter((file) => file.id && !file.token).map((file) => file.id);
			const uploadedFiles = Object.fromEntries(
				loadedFiles.filter((file) => file.token).map((file) => [file.id, file.token]),
			);

			return { existingFiles, uploadedFiles };
		}

		#showEmptyEditorError()
		{
			showToast({
				position: Position.TOP,
				message: Loc.getMessage('TASKS_FIELDS_RESULT_EMPTY_ERROR'),
				iconName: Icon.INFO_CIRCLE.getIconName(),
			});
		}

		/**
		 * @public
		 * @returns {number|string}
		 */
		get taskId()
		{
			return this.props.taskId;
		}

		/**
		 * @public
		 * @returns {string}
		 */
		get testId()
		{
			return this.props.testId;
		}

		/**
		 * @public
		 * @returns {PageManager}
		 */
		get parentWidget()
		{
			return this.props.config.parentWidget;
		}

		/**
		 * @public
		 * @returns {number}
		 */
		getResultsCount()
		{
			return this.props.resultsCount;
		}

		/**
		 * @public
		 * @returns {boolean}
		 */
		isEmpty()
		{
			return (this.getResultsCount() === 0);
		}

		/**
		 * @public
		 * @returns {boolean}
		 */
		isReadOnly()
		{
			return this.props.readOnly;
		}

		/**
		 * @public
		 * @returns {boolean}
		 */
		validate()
		{
			return true;
		}

		/**
		 * @public
		 * @returns {boolean}
		 */
		isValid()
		{
			return true;
		}

		/**
		 * @public
		 * @returns {boolean}
		 */
		isRequired()
		{
			return false;
		}

		/**
		 * @public
		 * @returns {string}
		 */
		getId()
		{
			return 'result';
		}

		/**
		 * @public
		 * @returns {boolean}
		 */
		hasUploadingFiles()
		{
			return false;
		}

		/**
		 * @public
		 * @param ref
		 */
		bindContainerRef(ref)
		{
			this.fieldContainerRef = ref;
		}
	}

	module.exports = { TaskResultField };
});
