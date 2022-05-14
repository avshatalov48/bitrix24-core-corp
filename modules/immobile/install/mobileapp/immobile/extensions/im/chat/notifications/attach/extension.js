(() => {

	var styles = {
		attachContainer: {
			alignItems: 'flex-start',
			paddingLeft: 10,
			borderLeftWidth: 2,
			borderLeftColor: '#818181'
		},
		tableSeparator: {
			height: 1,
			marginBottom: 10
		},
		attachAvatar: {
			width: 22,
			height: 22,
			marginRight: 10,
			borderRadius: 10,
			backgroundColor: '#17a0ea'
		},
		fileSize: {
			fontSize: 12,
			color: '#777',
			marginLeft: 6
		}
	}

	this.getAttachUserParams = (userNode) =>
	{
		const params = {
			resizeMode: 'cover',
			style: {},
		}

		if (userNode.AVATAR && userNode.AVATAR !== '')
		{
			params.uri = encodeURI(userNode.AVATAR);
		}
		else if (userNode.AVATAR_TYPE === 'USER')
		{
			params.svg = {
				content: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path fill="#fff" d="M72.786 62.254c0-2.31-3.03-4.95-9-6.489a20.3 20.3 0 0 1-5.7-2.584c-.383-.218-.325-2.236-.325-2.236l-1.922-.292c0-.164-.164-2.584-.164-2.584 2.3-.77 2.063-5.314 2.063-5.314 1.46.807 2.41-2.784 2.41-2.784 1.729-4.994-.86-4.693-.86-4.693.823-3.738 0-9.2 0-9.2-1.15-10.116-18.47-7.37-16.416-4.065-5.062-.934-3.907 10.55-3.907 10.55l1.1 2.97c-2.156 1.392-.658 3.079-.585 5.02.106 2.865 1.86 2.272 1.86 2.272.11 4.728 2.447 5.35 2.447 5.35.44 2.969.166 2.464.166 2.464l-2.082.25a8.223 8.223 0 0 1-.164 2.013c-2.45 1.093-2.971 1.727-5.406 2.793-4.7 2.053-9.808 4.723-10.715 8.317C24.679 67.606 23 75.995 23 75.995h53l-3.215-13.74z"/></svg>'
			};
		}
		else if (userNode.AVATAR_TYPE === 'CHAT')
		{
			params.svg = {
				content: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path fill="#fff" d="M55.03 42.705s-.474 1.492-.55 1.692c-.171.256-.316.53-.432.816.1 0 1.107.47 1.107.47l3.917 1.227-.057 1.86a4.639 4.639 0 0 0-1.894 1.4c-.19.413-.42.805-.69 1.17 3.568 1.45 5.655 3.573 5.74 5.949.058.423 2.223 8.206 2.347 9.959H81c.014-.073-.5-10.142-.5-10.217 0 0-.946-2.425-2.446-2.672a11.739 11.739 0 0 1-4.233-1.388 15.618 15.618 0 0 0-2.721-1.252 4.069 4.069 0 0 1-1.095-1.555 4.616 4.616 0 0 0-1.894-1.4l-.056-1.861 3.917-1.226s1.01-.471 1.107-.471a7.533 7.533 0 0 0-.54-.947c-.074-.2-.443-1.554-.443-1.554a10.07 10.07 0 0 0 1.992 1.933 27.952 27.952 0 0 1-1.708-3.877 24.708 24.708 0 0 1-.653-3.754 55.8 55.8 0 0 0-1.255-6.987 6.567 6.567 0 0 0-2.072-2.923 9.593 9.593 0 0 0-4.742-1.685h-.2c-1.7.13-3.334.712-4.733 1.685a6.589 6.589 0 0 0-2.071 2.925 55.45 55.45 0 0 0-1.254 6.987 24.1 24.1 0 0 1-.622 3.84 24.6 24.6 0 0 1-1.737 3.792 10.093 10.093 0 0 0 1.988-1.936z"/><path fill="#fff" d="M60.272 57.434c0-1.84-2.4-3.941-7.135-5.165a16.073 16.073 0 0 1-4.517-2.057c-.3-.174-.258-1.78-.258-1.78l-1.525-.235c0-.131-.13-2.057-.13-2.057 1.824-.613 1.636-4.23 1.636-4.23 1.158.645 1.912-2.213 1.912-2.213 1.37-3.976-.682-3.736-.682-3.736a25.034 25.034 0 0 0 0-7.323c-.912-8.054-14.646-5.868-13.018-3.241-4.014-.744-3.1 8.4-3.1 8.4l.87 2.364c-1.71 1.108-.521 2.45-.463 4 .084 2.28 1.476 1.808 1.476 1.808.086 3.764 1.939 4.259 1.939 4.259.349 2.364.132 1.962.132 1.962l-1.651.2a6.565 6.565 0 0 1-.13 1.6c-1.945.866-2.36 1.374-4.287 2.219-3.726 1.634-7.777 3.76-8.5 6.62C22.118 61.692 21 70.998 21 70.998h42l-2.73-13.563z"/></svg>'
			};
		}
		else if (userNode.AVATAR_TYPE === 'BOT')
		{
			params.svg = {
				content: '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="88" viewBox="-25 -18 100 88"><path d="M41.994 12.743l2.8-9.598a2.497 2.497 0 0 0-.54-2.375 2.316 2.316 0 0 0-2.262-.705c-.822.2-1.478.836-1.722 1.67l-2.398 8.239A24.944 24.944 0 0 0 14.08 10l-2.41-8.265C11.294.445 9.976-.285 8.726.105c-1.25.389-1.956 1.75-1.58 3.04l2.812 9.635C3.645 17.99-.022 25.89 0 34.235c0 15.06 11.636 11.364 26 11.364s26 3.696 26-11.364c.025-8.367-3.661-16.286-10.006-21.492zM25.976 32.188c-10.253 0-18.57 1.098-18.57-3.389s8.317-8.13 18.57-8.13c10.254 0 18.582 3.64 18.582 8.13s-8.316 3.39-18.582 3.39zm-8.493-8.158c-1.464.276-2.467 1.682-2.288 3.207.179 1.525 1.477 2.643 2.962 2.551 1.486-.092 2.645-1.362 2.645-2.899-.165-1.728-1.642-3-3.319-2.859zm14.695 3.214c.182 1.525 1.482 2.641 2.967 2.547 1.486-.094 2.644-1.365 2.644-2.902-.166-1.733-1.65-3.007-3.331-2.859-1.463.281-2.462 1.69-2.28 3.214z" fill="#FFF" fill-rule="evenodd"/></svg>'
			};
		}

		params.style = Object.assign({}, styles.attachAvatar, params.style);

		return params;
	}

	this.AttachUser = ({ userNode }) => View(
		{
			style: {
				flexDirection: 'row',
				marginBottom: 5
			},
			onClick: () => {
				if (userNode.LINK)
				{
					Utils.openUrl(userNode.LINK);
				}
				else if (userNode.USER_ID)
				{
					const url = `/company/personal/user/${userNode.USER_ID}/`;
					Utils.openUrl(url);
				}
				else if (userNode.CHAT_ID)
				{
					BX.postComponentEvent("onOpenDialog", [{dialogId: `chat${userNode.CHAT_ID}`}, true], 'im.recent');
				}
			}
		},
		Image(this.getAttachUserParams(userNode)),
		Text({
			style: {
				fontWeight: 'bold',
				fontSize: 16,
				color: '#000000'
			},
			text: userNode.NAME,
		})
	);

	this.shouldShowDescription = (linkNode) => linkNode.DESC && linkNode.DESC !== ''

	this.AttachLinkDescription = (linkNode) =>
		shouldShowDescription(linkNode) && Text({
			text: linkNode.DESC
		})

	this.shouldShowPreview = (linkNode) => linkNode.PREVIEW && linkNode.PREVIEW !== ''

	this.AttachLinkPreview = (linkNode) =>
		shouldShowPreview(linkNode) && Image({
			style: {
				width: 100,
				height: 100,
				borderColor: "#c6cdd3",
				borderRadius: 10,
				borderWidth: 1,
			},
			resizeMode: 'contain',
			uri: encodeURI(linkNode.PREVIEW)
		})

	this.AttachLink = ({ linkNode }) => View(
		{
			style: {
				flexDirection: 'column',
			},
			clickable: false
		},
		Button({
			style: {
				fontWeight: 'bold',
				color: '#1d54a2',
				marginBottom: 5,
				height: 20,
				fontSize: 14,
			},
			text: linkNode.NAME,
			onClick: () => {
				if (linkNode.LINK)
				{
					PageManager.openPage({'url': linkNode.LINK});
				}
			}
		}),
		AttachLinkDescription(linkNode),
		AttachLinkPreview(linkNode)
	);

	this.AttachMessage = ({ text }) => BBCodeText({
		value: Utils.decodeCustomBbCode(text),
		style: { fontSize: 13 },
	});

	this.getFileSize = (fileNode) => Math.floor(fileNode.SIZE / 1024 / 1024 * 100) / 100;

	this.AttachFile = ({ fileNode }) => View(
		{
			style: {
				flexDirection: 'row'
			},
		},
		Button({
			style: {
				fontWeight: 'bold',
				color: '#1d54a2'
			},
			text: fileNode.NAME,
			onClick: () => {
				PageManager.openPage({'url': fileNode.LINK});
			}
		}),
		Text({
			style: styles.fileSize,
			text: getFileSize(fileNode) + ' ' + BX.message('MOBILE_EXT_NOTIFICATION_ATTACH_FILE_SIZE_MB')
		})
	);

	this.AttachDelimiter = ({ delimiter }) => {
		const size = delimiter.SIZE ? delimiter.SIZE : 200;
		const color = delimiter.COLOR ? delimiter.COLOR : '#d4d4d5';

		return View({
			style: Object.assign({width: size, backgroundColor:color}, styles.tableSeparator)
		});
	}

	this.AttachImage = ({ imageNode }) => Image({
		style: {
			// width: imageNode.WIDTH ? imageNode.WIDTH : '100%',
			// height: imageNode.HEIGHT ? imageNode.HEIGHT : 200,
			width: '100%',
			height: 200
		},
		resizeMode: 'contain',
		uri: imageNode.PREVIEW ? encodeURI(imageNode.PREVIEW) : encodeURI(imageNode.LINK),
		named: imageNode.NAME
	});

	this.AttachGridBlock = ({ gridNode }) => {
		const display = (gridNode.DISPLAY === 'ROW') ? 'row' : 'column';
		const textColor = gridNode.COLOR ? {color: gridNode.COLOR}: {};

		return View(
			{
				style: { flexDirection: display, paddingBottom: 10, flexWrap: 'wrap' }
			},
			Text({
				style: Object.assign({ fontWeight: 'bold', marginRight: 10, fontSize: 13 }, textColor),
				text: Utils.decodeCustomBbCode(gridNode.NAME)
			}),
			Text({
				style: Object.assign({ fontSize: 13 }, textColor),
				text: Utils.decodeCustomBbCode(gridNode.VALUE)
			})
		);
	}

	this.getAttachGridBlocks = (grid) => grid.map(gridNode => AttachGridBlock({ gridNode: gridNode }))

	this.AttachGrid = ({ grid }) => View(
		{
			style: {
				flexDirection: (grid[0].DISPLAY === 'ROW' || grid[0].DISPLAY === 'BLOCK') ? 'column' : 'row',
				justifyContent: 'space-between',
				flexWrap: 'wrap'
			}
		},
		...getAttachGridBlocks(grid)
	);

	this.getSubAttachments = (attach) => {
		if (attach.USER) {
			return attach.USER.map(userNode => AttachUser({ userNode: userNode }));
		}
		if (attach.LINK) {
			return attach.LINK.map(linkNode => AttachLink({ linkNode: linkNode }));
		}
		if (attach.MESSAGE) {
			return [ AttachMessage({ text: attach.MESSAGE }) ];
		}
		if (attach.HTML) {
			return [ AttachMessage({ text: attach.BB_CODE }) ];
		}
		if (attach.FILE) {
			return attach.FILE.map(fileNode => AttachFile({ fileNode: fileNode }));
		}
		if (attach.DELIMITER) {
			return [ AttachDelimiter({ delimiter: attach.DELIMITER }) ];
		}
		if (attach.IMAGE) {
			return attach.IMAGE.map(imageNode => AttachImage({ imageNode: imageNode }));
		}
		if (attach.GRID) {
			return [ AttachGrid({ grid: attach.GRID }) ];
		}
		return [];
	}

	this.getAttachments = (props) => {
		if (!props.params.hasOwnProperty('ATTACH')) {
			return [];
		}

		return props.params.ATTACH.reduce(
			(acc, attachBlock) => acc.concat(attachBlock.BLOCKS.reduce(
				(acc, attach) => acc.concat(getSubAttachments(attach)),
				[])
			),
		[]);
	}

	this.Attach = (props) => View(
		{
			style: styles.attachContainer,
		},
		...getAttachments(props)
	);

})();
