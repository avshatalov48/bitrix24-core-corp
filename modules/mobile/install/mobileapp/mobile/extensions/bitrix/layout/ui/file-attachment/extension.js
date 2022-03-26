(() => {
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

			return UI.File({
				id: file.id,
				url: file.url,
				imageUri: uri,
				type: file.type,
				name: file.name,
				attachmentCloseIcon: this.props.attachmentCloseIcon,
				attachmentFileIconFolder: this.props.attachmentFileIconFolder,
				onDeleteAttachmentItem: (
					this.props.onDeleteAttachmentItem && (() => this.props.onDeleteAttachmentItem(index))
				),
				styles: this.props.styles,
				files: this.state.attachments
			});
		}

		render()
		{
			const {attachments} = this.state;

			return ScrollView(
				{
					style: {
						backgroundColor: '#ffffff'
					}
				},
				View({},
					View(
						{
							style: {
								padding: 16,
								flexDirection: 'row',
								flexWrap: 'wrap'
							}
						},
						...attachments.map((file, index) => this.renderFile(file, index))
					)
				)
			)
		}
	}

	this.UI = this.UI || {};
	this.UI.FileAttachment = FileAttachment;
})();