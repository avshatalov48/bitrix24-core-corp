/**
 * @module layout/ui/file/selector
 */
jn.define('layout/ui/file/selector', (require, exports, module) => {

	const { Loc } = require('loc');
	const { FileField } = require('layout/ui/fields/file');
	const { WidgetHeaderButton } = require('layout/ui/widget-header-button');

	/**
	 * @class FileSelector
	 */
	class FileSelector extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			if (!props.layout)
			{
				throw new Error('File selector: layout property is required');
			}

			this.layout = props.layout;

			this.state = {
				files: props.files || [],
			};

			/** @type {FileField|null} */
			this.fileFieldRef = null;

			/** @type {UI.FileAttachment|null} */
			this.fileListRef = null;

			this.layout.enableNavigationBarBorder(false);
			this.refreshTitle();

			this.saveButton = new WidgetHeaderButton({
				widget: this.layout,
				text: Loc.getMessage('UI_FILE_SELECTOR_SAVE_BUTTON'),
				loadingText: Loc.getMessage('UI_FILE_SELECTOR_SAVE_BUTTON_LOADING'),
				disabled: () => !this.isSaveAllowed(),
				onClick: () => this.save(),
			});
		}

		refreshTitle()
		{
			const { title } = this.getProps();
			let text;

			if (typeof title === 'function')
			{
				text = title(this);
			}
			else
			{
				text = title || Loc.getMessage('UI_FILE_SELECTOR_DEFAULT_TITLE');
				text = text.replace(/#NUM#/gi, this.getFilesCount());
			}

			this.layout.setTitle({ text });
		}

		/**
		 * @return {FileSelectorProps}
		 */
		getProps()
		{
			return this.props;
		}

		/**
		 * @public
		 * @return {FileSelectorFile[]}
		 */
		getFiles()
		{
			return this.state.files;
		}

		/**
		 * @public
		 * @return {number}
		 */
		getFilesCount()
		{
			return this.getFiles().length;
		}

		componentDidMount()
		{
			if (this.getProps().focused)
			{
				this.focus();
			}
		}

		focus()
		{
			if (this.fileFieldRef)
			{
				this.fileFieldRef.setFocus();
			}
		}

		/**
		 * @public
		 * @param {FileSelectorProps} options
		 */
		static open(options)
		{
			const files = options.files || [];

			PageManager.openWidget('layout', {
				modal: true,
				backgroundColor: '#eef2f4',
				backdrop: {
					onlyMediumPosition: false,
					showOnTop: files.length > 12,
					mediumPositionHeight: 450,
					navigationBarColor: '#EEF2F4',
					swipeAllowed: true,
					swipeContentAllowed: false,
					horizontalSwipeAllowed: false,
				}}
			).then(widget => {
				widget.showComponent(new FileSelector({
					layout: widget,
					...options,
				}));
			});
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						flex: 1,
						backgroundColor: '#eef2f4',
					},
					resizableByKeyboard: true,
				},
				View(
					{
						style: {
							backgroundColor: '#ffffff',
							flexDirection: 'column',
							flex: 1,
							borderTopLeftRadius: 12,
							borderTopRightRadius: 12,
						},
					},
					this.renderFileField(),
					this.renderEmptyList(),
					this.renderFileList(),
				)
			);
		}

		renderFileField()
		{
			return View(
				{
					style: {
						display: 'none',
					},
				},
				FileField({
					ref: (ref) => {
						this.fileFieldRef = ref;
						this.setState({});
					},
					showTitle: false,
					showAddButton: true,
					multiple: true,
					value: this.state.files,
					config: {
						fileInfo: {},
						mediaType: 'file',
						parentWidget: this.layout,
						controller: this.getProps().controller,
					},
					readOnly: false,
					onChange: (files) => {
						files = Array.isArray(files) ? files : [];
						const prevFilesCount = this.state.files.length;

						this.setState({ files }, () => {
							this.refreshTitle();
							this.refreshSaveButton();
							if (prevFilesCount < files.length)
							{
								this.scrollToBottom();
							}
						});
					},
				}),
			);
		}

		renderFileList()
		{
			if (!this.fileFieldRef || !this.state.files.length)
			{
				return null;
			}

			const FILE_PREVIEW_MEASURE = 66;
			const MAX_RESIZABLE_SCREEN_WIDTH = 375;
			const imageSize = device.screen.width > MAX_RESIZABLE_SCREEN_WIDTH
				? FILE_PREVIEW_MEASURE
				: device.screen.width * FILE_PREVIEW_MEASURE / MAX_RESIZABLE_SCREEN_WIDTH;

			return new UI.FileAttachment({
				ref: (ref) => this.fileListRef = ref,
				attachments: this.fileFieldRef.getFilesInfo(this.fileFieldRef.getValue()),
				layoutWidget: this.layout,
				onDeleteAttachmentItem: (index) => this.onDeleteFile(index),
				styles: {
					wrapper: {
						marginBottom: 12,
						marginHorizontal: 3,
						paddingRight: 9,
					},
					imagePreview: {
						width: imageSize,
						height: imageSize,
					},
					imageOutline: (hasError) => ({
						width: imageSize,
						height: imageSize,
						position: 'absolute',
						top: 8,
						right: 9,
						borderColor: hasError ? '#ff5752' : '#333333',
						backgroundColor: hasError ? '#ff615c' : null,
						borderWidth: 1,
						opacity: hasError ? 0.5 : 0.08,
						borderRadius: 6,
					}),
					deleteButtonWrapper: {
						width: 18,
						height: 18,
						right: 0,
					},
				},
				showName: true,
				showAddButton: true,
				onAddButtonClick: () => this.onAddButtonClick(),
			});
		}

		renderEmptyList()
		{
			if (this.state.files.length)
			{
				return null;
			}

			return View(
				{
					style: {
						flex: 1,
						alignItems: 'center',
						justifyContent: 'center',
					}
				},
				Text({
					text: Loc.getMessage('UI_FILE_SELECTOR_EMPTY_LIST'),
					style: {
						color: '#828B95',
						fontSize: 18,
					}
				})
			);
		}

		onDeleteFile(index)
		{
			if (!this.fileFieldRef)
			{
				return;
			}

			this.fileFieldRef.onDeleteFile(index);
		}

		onAddButtonClick()
		{
			if (!this.fileFieldRef)
			{
				return;
			}

			this.fileFieldRef.openFilePicker();
		}

		isSaveAllowed()
		{
			if (this.hasUploadingFiles())
			{
				return false;
			}

			return this.getProps().required ? Boolean(this.state.files.length) : true;
		}

		hasUploadingFiles()
		{
			if (!this.fileFieldRef)
			{
				return false;
			}

			return this.fileFieldRef.hasUploadingFiles();
		}

		refreshSaveButton()
		{
			this.saveButton.refresh();
		}

		/**
		 * @return {Promise}
		 */
		save()
		{
			if (this.getProps().onSave)
			{
				const result = this.getProps().onSave(this);
				if (!(result instanceof Promise))
				{
					throw new Error("File selector: 'onSave' handler must return Promise");
				}

				return result.then(() => this.close());
			}

			return this.close();
		}

		/**
		 * @public
		 * @return {Promise}
		 */
		close()
		{
			return new Promise(resolve => {
				if (this.layout)
				{
					this.layout.close();
				}
				resolve();
			});
		}

		scrollToBottom()
		{
			if (this.fileListRef)
			{
				this.fileListRef.scrollToBottom();
			}
		}
	}

	module.exports = { FileSelector };

});