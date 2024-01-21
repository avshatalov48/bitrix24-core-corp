(() => {
	const require = (ext) => jn.require(ext);
	const AppTheme = require('apptheme');

	this.AttachmentComponent = class AttachmentComponent extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			const {
				attachments,
				onDeleteAttachmentItem,
				postFormData,
				serverName,
			} = props;

			this.state = {
				attachments,
			};

			this.onDeleteAttachmentItem = onDeleteAttachmentItem;
			this.postFormData = postFormData;
			this.serverName = serverName;
		}

		render()
		{
			const { attachments } = this.state;

			const
				onDeleteAttachmentItem = this.onDeleteAttachmentItem;
			const postFormData = this.postFormData;
			const serverName = this.serverName;

			return ScrollView(
				{
					style: {
						backgroundColor: AppTheme.colors.bgContentPrimary,
					},
				},
				View(
					{},
					View(
						{
							style: {
								padding: 16,
								flexDirection: 'row',
								flexWrap: 'wrap',
							},
						},
						...attachments.map((item, index) => {
							let uri = '';
							if (item.previewUrl)
							{
								uri = item.previewUrl;
							}
							else if (item.dataAttributes && item.dataAttributes.IMAGE)
							{
								uri = serverName + item.dataAttributes.IMAGE;
							}

							return Utils.drawFile({
								url: item.url || null,
								imageUri: uri,
								type: item.type || null,
								name: item.name || '',
								attachmentCloseIcon: currentDomain + postFormData.attachmentCloseIcon,
								attachmentFileIconFolder: currentDomain + postFormData.attachmentFileIconFolder,
								onDeleteAttachmentItem: () => {
									onDeleteAttachmentItem(index);
								},
							});
						}),
					),
				),
			);
		}
	};
})();
