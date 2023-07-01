(() => {
	const require = ext => jn.require(ext);

	const { clip } = require('assets/common');
	const { transparent } = require('utils/color');
	const { throttle } = require('utils/function');
	const { Loc } = require('loc');

	/**
	 * @class UI.FileAttachment
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
			this.gridViewRef = null;

			this.throttledOnAddButtonClick = throttle(this.onAddButtonClick, 500, this);
		}

		componentWillReceiveProps(props)
		{
			this.state.attachments = props.attachments;
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
			const { attachments } = this.state;

			const items = attachments.map((item) => ({
				type: 'default',
				key: item.id,
				...item,
			}));

			const rowsCount = 4;

			return View(
				{
					style: {
						backgroundColor: '#ffffff',
						flex: 1,
					},
					safeArea: { bottom: true }
				},
				GridView({
					style: {
						flex: 1,
						paddingTop: 12,
					},
					ref: (ref) => this.gridViewRef = ref,
					params: { orientation: 'vertical', rows: rowsCount },
					data: [{ items }],
					renderItem: (file) => View(
						{
							style: {
								width: document.device.screen.width / rowsCount,
								flex: 1,
								alignItems: 'center',
							}
						},
						this.renderFile(file),
					),
				}),
				this.renderAddButton(),
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
				showName: this.props.showName,
			});
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
					radius: 5,
					color: transparent('#000000', 0.06),
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
							backgroundColor: '#fff',
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
								backgroundColor: '#E1F3F9',
								borderRadius: 12,
								justifyContent: 'center',
								alignItems: 'center',
								marginRight: 6,
							},
						},
						Image({
							style: {
								width: 17,
								height: 17,
							},
							svg: {
								content: clip,
							},
						}),
					),
					Text({
						text,
						style: {
							color: '#828B95',
							fontSize: 18,
						},
					}),
				),
			);
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
			const index = this.state.attachments.findIndex(item => item.id === id);

			this.withGridView()
				.then(gridView => new Promise(resolve => {
					gridView.deleteRow(0, index, 'automatic', resolve);
				}))
				.finally(() => {
					if (this.props.onDeleteAttachmentItem)
					{
						this.props.onDeleteAttachmentItem(index);
					}
				});
		}

		/**
		 * @return {Promise}
		 */
		withGridView()
		{
			return new Promise((resolve, reject) => {
				if (this.gridViewRef)
				{
					resolve(this.gridViewRef);
				}
				else
				{
					reject();
				}
			});
		}

		/**
		 * @public
		 */
		scrollToBottom()
		{
			const section = 0;
			const index = this.state.attachments.length - 1;
			const animate = true;

			this.withGridView().then(gridView => gridView.scrollTo(section, index, animate));
		}

		get showAddButton()
		{
			return BX.prop.getBoolean(this.props, 'showAddButton', false);
		}
	}

	this.UI = this.UI || {};
	this.UI.FileAttachment = FileAttachment;
})();