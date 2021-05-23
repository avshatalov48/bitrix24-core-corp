(() =>
{
	this.AttachmentComponent = class AttachmentComponent extends LayoutComponent {

		constructor(props) {
			super(props);

			const {
				attachments
			} = props;

			this.state = {
				attachments: attachments
			};

			this.onDeleteAttachmentItem = props.onDeleteAttachmentItem;
			this.postFormData = props.postFormData;
			this.serverName =  props.serverName;
		}

		render() {

			const { attachments } = this.state;

			const
				onDeleteAttachmentItem = this.onDeleteAttachmentItem,
				postFormData = this.postFormData,
				serverName = this.serverName;

			return ScrollView(
				{
					style: {
						backgroundColor: '#ffffff'
					}
				},
				View({
					},
					View(
						{
							style: {
								padding: 16,
								flexDirection: 'row',
								flexWrap: 'wrap',
							}
						},
						...attachments.map((item, index) =>
						{
							let uri = '';
							if (item.previewUrl)
							{
								uri = item.previewUrl;
							} else if (item.dataAttributes && item.dataAttributes.IMAGE)
							{
								uri = serverName + item.dataAttributes.IMAGE;
							}

							return Utils.drawFile({
								url: (!!item.url ? item.url : null),
								imageUri: uri,
								type: (!!item.type ? item.type : null),
								name: (!!item.name ? item.name : ''),
								attachmentCloseIcon: currentDomain + postFormData.attachmentCloseIcon,
								attachmentFileIconFolder: currentDomain + postFormData.attachmentFileIconFolder,
								onDeleteAttachmentItem: () => { onDeleteAttachmentItem(index); }
							});
						})
					)
				)
			)
		}
	}
})();