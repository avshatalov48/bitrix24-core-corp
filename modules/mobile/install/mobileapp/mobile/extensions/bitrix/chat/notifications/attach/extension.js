(() => {

	var styles = {
		tableSeparator: {
			height: 1,
			marginBottom: 10,
		},
		attachAvatar: {
			width: 22,
			height: 22,
			marginRight: 10,
			borderRadius: 10
		},
		fileSize: {
			fontSize: 12,
			color: '#777',
			marginLeft: 6
		}
	}

	this.Attach = class Attach extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			this.currentDomain = currentDomain.replace('https', 'http');
		}

		renderAttach()
		{
			let blocks = [];

			if (!this.props.params.hasOwnProperty('ATTACH'))
			{
				return blocks;
			}

			Array.from(this.props.params.ATTACH).forEach(attachBlock => {
					Array.from(attachBlock.BLOCKS).forEach(attach => {
						//USER
						if (attach.USER)
						{
							Array.from(attach.USER).forEach(userNode => {
								blocks.push(
									View(
										{
											style: {
												flexDirection: 'row',
												marginBottom: 10,
											},
										},
										Image({
											style: styles.attachAvatar,
											uri: this.prepareUserAttachAvatar(userNode)
										}),
										Text({
											style: {
												fontWeight: 'bold',
												fontSize: 16,
												color: '#000000',
											},
											text: userNode.NAME,
											onLinkClick: () => {
												if (userNode.LINK)
												{
													PageManager.openPage({'url': userNode.LINK});
												}
											}
										}),
									)
								)
							});
						}
						//LINK
						if (attach.LINK)
						{
							Array.from(attach.LINK).forEach(linkNode => {
								const showDescription = linkNode.DESC && linkNode.DESC !== '';
								const showPreview = linkNode.PREVIEW && linkNode.PREVIEW !== '';
								blocks.push(
									View(
										{
											style: {
												flexDirection: 'column',
											},
											clickable: false
										},
										Text({
											style: {
												fontWeight: 'bold',
												color: '#1d54a2',
												marginBottom: 10,
											},
											text: linkNode.NAME,
											onLinkClick: () => {
												if (linkNode.LINK)
												{
													PageManager.openPage({'url': linkNode.LINK});
												}
											}
										}),
										showDescription && Text({
											text: linkNode.DESC,
										}),
										showPreview && Image({
											style: {
												width: 100, //todo ?
												height: 100, //todo ?
												borderColor: "#c6cdd3",
												borderRadius: 10,
												borderWidth: 1,

											},
											resizeMode: 'contain',
											uri: linkNode.PREVIEW
										}),
									)
								);
							});
						}
						//MESSAGE
						if (attach.MESSAGE)
						{
							blocks.push(
								Text({
									html: Utils.decodeBbCode({text: attach.MESSAGE}),
								})
							);
						}
						//FILES
						if (attach.FILE)
						{
							Array.from(attach.FILE).forEach(fileNode => {
								const fileSize = Math.floor(fileNode.SIZE / 1024 / 1024 * 100) / 100;
								blocks.push(
									View(
										{
											style: {
												flexDirection: 'row',

											},
										},
										Button({
											style: {
												fontWeight: 'bold',
												color: '#1d54a2',
											},
											text: fileNode.NAME,
											onClick: () => {
												PageManager.openPage({'url': fileNode.LINK});
											}
										}),
										Text({
											style: styles.fileSize,
											text: fileSize + ' ' + BX.message('MOBILE_EXT_NOTIFICATION_ATTACH_FILE_SIZE_MB')
										})
									)
								);
							});
						}
						//DELIMITER
						if (attach.DELIMITER)
						{
							const size = attach.DELIMITER.SIZE ? attach.DELIMITER.SIZE : 200;
							const color = attach.DELIMITER.COLOR ? attach.DELIMITER.COLOR : '#d4d4d5';

							blocks.push(
								View({
									style: Object.assign({width: size, backgroundColor:color}, styles.tableSeparator)
								})
							);
						}
						//IMAGE
						if (attach.IMAGE)
						{
							Array.from(attach.IMAGE).forEach(imageNode => {
								blocks.push(
									Image({
										style: {
											// width: imageNode.WIDTH ? imageNode.WIDTH : '100%',
											// height: imageNode.HEIGHT ? imageNode.HEIGHT : 200,
											width: '100%',
											height: 200,
										},
										resizeMode: 'contain',
										uri: imageNode.PREVIEW ? imageNode.PREVIEW : imageNode.LINK,
										named: imageNode.NAME
									})
								);
							});
						}
						//GRID
						if (attach.GRID)
						{
							const gridBlocks = [];
							Array.from(attach.GRID).forEach(gridNode => {
								const display = (gridNode.DISPLAY === 'ROW') ? 'row' : 'column';
								const textColor = gridNode.COLOR ? {color: gridNode.COLOR}: {};

								gridBlocks.push(
									View(
										{
											style: { flexDirection: display, marginBottom: 10, }
										},
										Text({
											style: Object.assign({fontWeight: 'bold', marginRight: 10}, textColor),
											text: gridNode.NAME
										}),
										Text({
											style: textColor,
											text: gridNode.VALUE
										})
									)
								);
							});

							blocks.push(
								View(
									{
										style: {
											flexDirection: (attach.GRID[0].DISPLAY === 'ROW' || attach.GRID[0].DISPLAY === 'BLOCK') ? 'column' : 'row',
											justifyContent: 'space-between',
											flexWrap: 'wrap',
										},
									},
									...gridBlocks
								)
							);
						}
					});
				});

			return blocks;
		}

		render()
		{
			return View(
				{
					style: {
						alignItems: 'flex-start',
						paddingLeft: 10,
						borderLeftWidth: 2,
						borderLeftColor: '#818181'
					},
				},
				...this.renderAttach()
			);
		}


		prepareUserAttachAvatar(userNode)
		{
			let avatarUri = '';
			if (userNode.AVATAR && userNode.AVATAR !== '')
			{
				avatarUri = userNode.AVATAR;
			}
			else if (userNode.AVATAR_TYPE === 'USER')
			{
				avatarUri = this.currentDomain + '/bitrix/mobileapp/mobile/components/bitrix/im.notify/avatar_test.png'; //todo: change icon
			}
			else if (userNode.AVATAR_TYPE === 'CHAT')
			{
				avatarUri = this.currentDomain + '/bitrix/mobileapp/mobile/components/bitrix/im.notify/avatar_test.png'; //todo: change icon
			}
			else if (userNode.AVATAR_TYPE === 'BOT')
			{
				avatarUri = this.currentDomain + '/bitrix/mobileapp/mobile/components/bitrix/im.notify/avatar_test.png'; //todo: change icon
			}

			return avatarUri;
		}
	}

})();
