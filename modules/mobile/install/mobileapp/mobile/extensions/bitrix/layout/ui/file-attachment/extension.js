(() => {
	const { clip } = jn.require('assets/common');
	const { transparent } = jn.require('utils/color');

	/**
	 * @class UI.FileAttachment
	 */
	class FileAttachment extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.state = {
				attachments: props.attachments
			};

			this.layoutWidget = props.layoutWidget;
			this.serverName = props.serverName;
		}

		onChangeAttachments(attachments)
		{
			this.setState({
				attachments
			}, () => {
				if (this.state.attachments.length === 0)
				{
					this.layoutWidget.close();
				}
			})
		}

		renderFile(file, index)
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

			return UI.File({
				id: file.id,
				url: file.url,
				imageUri: uri,
				type: file.type,
				name: file.name,
				isLoading: file.isUploading || false,
				hasError: file.hasError || false,
				attachmentCloseIcon: this.props.attachmentCloseIcon,
				attachmentFileIconFolder: this.props.attachmentFileIconFolder,
				onDeleteAttachmentItem: (
					this.props.onDeleteAttachmentItem && (() => this.props.onDeleteAttachmentItem(index))
				),
				styles: this.props.styles,
				files: this.state.attachments,
				showName: this.props.showName,
			});
		}

		render()
		{
			const {attachments} = this.state;

			return View(
				{
					style: {
						backgroundColor: '#eef2f4',
					}
				},
				View(
					{
						style: {
							backgroundColor: '#ffffff',
							flex: 2,
							flexDirection: 'column',
							borderRadius: 12,
						},
						safeArea: {
							bottom: true,
						},
					},
					ScrollView(
						{
							style: {
								flex: 1,
								flexDirection: 'column',
								flexShrink: 2,
							},
						},
						View(
							{
								style: {
									padding: 20,
									flexShrink: 2,
								},
							},
							View(
								{
									style: {
										flexDirection: 'row',
										flexWrap: 'wrap',
										justifyContent: 'flex-start',
										alignItems: 'flex-start',
									},
								},
								...attachments.map((file, index) => this.renderFile(file, index)),
							),
						),
					),
					this.renderAddButton(),
				)
			);
		}

		renderAddButton()
		{
			if (!this.showAddButton)
			{
				return null;
			}

			return Shadow(
				{
					style: {
						borderTopLeftRadius: 16,
						borderTopRightRadius: 16,
					},
					radius: 5,
					color: transparent('#000000', 0.14),
					offset: {
						x: 0,
						y: -3,
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
						onClick: () => {
							if (this.props.onAddButtonClick)
							{
								this.props.onAddButtonClick();
							}
						},
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
						style: {
							color: '#828B95',
							fontSize: 18,
						},
						text: BX.message('UI_FILE_ATTACHMENT_BUTTON_ADD'),
					}),
				),
			);
		}

		get showAddButton()
		{
			return BX.prop.getBoolean(this.props, 'showAddButton', false);
		}
	}

	this.UI = this.UI || {};
	this.UI.FileAttachment = FileAttachment;
})();