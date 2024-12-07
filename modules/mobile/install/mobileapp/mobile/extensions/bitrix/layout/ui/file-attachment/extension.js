/**
 * @module layout/ui/file-attachment
 */
jn.define('layout/ui/file-attachment', (require, exports, module) => {
	const { AppTheme } = require('apptheme/extended');
	const { clip } = require('assets/common');
	const { throttle } = require('utils/function');
	const { Loc } = require('loc');
	const { GridViewAdapter } = require('layout/ui/file-attachment/grid-view-adapter');

	/**
	 * @class FileAttachment
	 */
	class FileAttachment extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				attachments: props.attachments,
			};

			this.layoutWidget = props.layoutWidget;
			this.serverName = props.serverName;

			/** @type {GridViewAdapter|null} */
			this.gridViewAdapter = null;

			this.throttledOnAddButtonClick = throttle(this.onAddButtonClick, 500, this);

			this.layoutWidget.on('onViewHidden', () => this.handleViewHidden());
		}

		componentWillReceiveProps(props)
		{
			this.state.attachments = props.attachments;
		}

		handleViewHidden()
		{
			this.props.onViewHidden?.();
		}

		onChangeAttachments(attachments)
		{
			this.setState({ attachments }, () => {
				if (this.state.attachments.length === 0)
				{
					this.layoutWidget.close();
				}
			});
		}

		render()
		{
			return View(
				{
					style: {
						backgroundColor: AppTheme.colors.bgContentPrimary,
						flex: 1,
					},
					safeArea: { bottom: true },
				},
				this.renderFilesGrid(),
				this.renderEmptyState(),
				this.renderAddButton(),
			);
		}

		renderFilesGrid()
		{
			const { attachments } = this.state;
			if (attachments.length === 0)
			{
				return null;
			}

			const rowsCount = 4;
			const itemWidth = document.device.screen.width / rowsCount;

			return new GridViewAdapter({
				rowsCount,
				items: attachments,
				ref: (ref) => {
					this.gridViewAdapter = ref;
				},
				renderItem: (file) => View(
					{
						style: {
							width: itemWidth,
							flex: 1,
							alignItems: 'center',
						},
					},
					this.renderFile(file),
				),
			});
		}

		renderEmptyState()
		{
			const { attachments } = this.state;
			if (attachments.length > 0)
			{
				return null;
			}

			return View(
				{
					style: {
						flex: 1,
						alignItems: 'center',
						justifyContent: 'center',
					},
				},
				Text({
					text: Loc.getMessage('UI_FILE_ATTACHMENT_NO_FILES'),
					style: {
						color: AppTheme.colors.base3,
						fontSize: 18,
					},
				}),
			);
		}

		renderAddButton()
		{
			if (!this.showAddButton)
			{
				return null;
			}

			const text = this.props.addButtonText || Loc.getMessage('UI_FILE_ATTACHMENT_BUTTON_ADD');

			return Shadow(
				{
					style: {
						borderTopLeftRadius: 12,
						borderTopRightRadius: 12,
					},
					radius: 1,
					color: AppTheme.colors.shadowPrimary,
					offset: {
						x: 0,
						y: -5,
					},
					inset: {
						left: 5,
						right: 5,
						top: 0,
						bottom: 5,
					},
				},
				View(
					{
						style: {
							flexDirection: 'row',
							backgroundColor: AppTheme.colors.bgContentPrimary,
							paddingVertical: 12.5,
							justifyContent: 'center',
							alignItems: 'center',
						},
						onClick: this.throttledOnAddButtonClick,
					},
					View(
						{
							style: {
								width: 24,
								height: 24,
								borderRadius: 12,
								justifyContent: 'center',
								alignItems: 'center',
								marginRight: 6,
							},
						},
						Image({
							style: {
								width: 17,
								height: 19,
							},
							svg: {
								content: clip,
							},
						}),
					),
					Text({
						text,
						style: {
							color: AppTheme.colors.base3,
							fontSize: 18,
						},
					}),
				),
			);
		}

		renderFile(file)
		{
			let uri = '';
			if (file.previewUrl)
			{
				uri = file.previewUrl;
			}
			else if (file.dataAttributes && file.dataAttributes.IMAGE)
			{
				uri = this.serverName + file.dataAttributes.IMAGE;
			}
			else if (file.url)
			{
				uri = file.url;
			}

			const onDeleteFile = () => this.onDeleteFile(file.id);
			const onDeleteAttachmentItem = this.props.onDeleteAttachmentItem ? onDeleteFile : null;

			return UI.File({
				onDeleteAttachmentItem,
				id: file.id,
				url: file.url,
				imageUri: uri,
				type: file.type,
				name: file.name,
				isLoading: file.isUploading || false,
				hasError: file.hasError || false,
				attachmentCloseIcon: this.props.attachmentCloseIcon,
				attachmentFileIconFolder: this.props.attachmentFileIconFolder,
				styles: this.props.styles,
				files: this.state.attachments,
				showName: this.props.showName ?? true,
				textLines: 2,
				onFilePreviewMenuClick: this.props.onFilePreviewMenuClick,
			});
		}

		onAddButtonClick()
		{
			if (this.props.onAddButtonClick)
			{
				this.props.onAddButtonClick();
			}
		}

		onDeleteFile(id)
		{
			const index = this.state.attachments.findIndex((item) => item.id === id);

			this.useGridViewAdapter()
				.then((adapter) => adapter.deleteRow(index))
				.catch(console.error)
				.finally(() => {
					if (this.props.onDeleteAttachmentItem)
					{
						this.props.onDeleteAttachmentItem(index);
					}
				});
		}

		/**
		 * @return {Promise<GridViewAdapter>}
		 */
		useGridViewAdapter()
		{
			return new Promise((resolve, reject) => {
				return this.gridViewAdapter ? resolve(this.gridViewAdapter) : reject();
			});
		}

		/**
		 * @public
		 */
		scrollToBottom()
		{
			this.useGridViewAdapter().then((adapter) => adapter.scrollToBottom()).catch(console.error);
		}

		get showAddButton()
		{
			return BX.prop.getBoolean(this.props, 'showAddButton', false);
		}
	}

	module.exports = { FileAttachment };
});
