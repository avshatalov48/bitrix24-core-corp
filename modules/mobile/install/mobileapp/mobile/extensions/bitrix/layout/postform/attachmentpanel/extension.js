(function(){

	this.AttachmentPanel = ({
		attachments,
		onDeleteAttachmentItem,
		serverName,
		postFormData,
	}) => {

		const horizontalScrollVersion = 37;

		const view = View(
			{
				style: {
					paddingLeft: 16,
					paddingRight: 16,
					flexDirection: 'row',
				},
			},
			...attachments.map((item, index) => {
				let uri = '';
				if (item.previewUrl)
				{
					uri = item.previewUrl;
				}
				else if(item.dataAttributes && item.dataAttributes.IMAGE)
				{
					uri = serverName + item.dataAttributes.IMAGE;
				}

				return Utils.drawFile({
					url: (!!item.url ? item.url : null),
					imageUri: uri,
					name: (!!item.name ? item.name : ''),
					type: (!!item.type ? item.type : null),
					attachmentCloseIcon: currentDomain + postFormData.attachmentCloseIcon,
					attachmentFileIconFolder: currentDomain + postFormData.attachmentFileIconFolder,
					onDeleteAttachmentItem: () => { onDeleteAttachmentItem(index); },
				});
			})
		);

		const rootView = (
			Application.getApiVersion() >= horizontalScrollVersion
				? ScrollView(
					{
						horizontal: true,
						showsHorizontalScrollIndicator: true,
						style: {
							height: 75,
						},
					},
					view
				)
				: view
		);

		return (
			(attachments && attachments.length) && rootView
		);
	};


})();