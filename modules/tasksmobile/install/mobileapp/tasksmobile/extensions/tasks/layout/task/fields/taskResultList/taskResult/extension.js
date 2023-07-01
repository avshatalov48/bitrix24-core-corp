/**
 * @module tasks/layout/task/fields/taskResultList/taskResult
 */
jn.define('tasks/layout/task/fields/taskResultList/taskResult', (require, exports, module) => {
	const {Loc} = require('loc');
	const {ProfileView} = require('user/profile');
	const {inAppUrl} = require('in-app-url');

	class TaskResult extends LayoutComponent
	{
		constructor(props)
		{
			super(props);
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'column',
						backgroundColor: (this.props.isFirst ? '#f8fbeb' : '#ffffff'),
						paddingHorizontal: 16,
						paddingTop: 14,
						paddingBottom: 23,
					},
				},
				this.renderTitle(),
				this.renderAuthor(),
				this.renderContent(),
			);
		}

		renderTitle()
		{
			return Text({
				style: {
					fontSize: 10,
					fontWeight: '500',
					color: '#a8adb4',
				},
				text: Loc.getMessage('TASKSMOBILE_LAYOUT_TASK_FIELDS_TASK_RESULT_TITLE_MSGVER_1').toLocaleUpperCase(),
			});
		}

		renderAuthor()
		{
			const result = this.props.taskResult;
			const author = result.userInfo[result.createdBy];

			return View(
				{
					style: {
						flexDirection: 'row',
						marginTop: 9,
					},
					onClick: () => {
						this.props.parentWidget
							.openWidget('list', {
								groupStyle: true,
								backdrop: {
									bounceEnable: false,
									swipeAllowed: true,
									showOnTop: true,
									hideNavigationBar: false,
									horizontalSwipeAllowed: false,
								},
							})
							.then(list => ProfileView.open({userId: result.createdBy, isBackdrop: true}, list))
						;
					},
				},
				Image({
					style: {
						width: 24,
						height: 24,
						borderRadius: 12,
						alignSelf: 'center',
					},
					uri: this.getImageUrl(author.avatar),
				}),
				Text({
					style: {
						marginLeft: 6,
						fontSize: 16,
						fontWeight: '400',
						color: '#2066b0',
					},
					text: author.formattedName,
				}),
			);
		}

		renderContent()
		{
			return View(
				{
					style: {
						marginTop: 10,
						marginLeft: 30,
						paddingLeft: 12,
						borderLeftWidth: 3,
						borderLeftColor: '#8dbb00',
					},
				},
				this.renderText(),
				this.renderFiles(),
			);
		}

		renderText()
		{
			return BBCodeText({
				onLinkClick: ({url}) => {
					const fileMatch = url.match(/\/\?openFile&fileId=(\d+)/);
					if (fileMatch && this.props.taskResult.fileInfo[fileMatch[1]])
					{
						const file = this.props.taskResult.fileInfo[fileMatch[1]];
						file.fileType = UI.File.getType(UI.File.getFileMimeType(file.type, file.name));
						this.openFileViewer(file);

						return;
					}

					const webMatch = url.match(/\/\?openWeb&type=(table|video)&id=(\d+)/);
					if (webMatch)
					{
						this.openWebViewer({
							type: webMatch[1],
							id: webMatch[2],
						});

						return;
					}

					const diskMatch = url.match(/\/bitrix\/tools\/disk\/focus.php\?.*(folderId|objectId)=(\d+)/i);
					if (diskMatch)
					{
						BX.postComponentEvent('onDiskFolderOpen', [{folderId: diskMatch[2]}], 'background');

						return;
					}

					inAppUrl.open(url);
				},
				value: this.props.taskResult.parsedText,
			});
		}

		renderFiles()
		{
			if (!Object.keys(this.props.taskResult.fileInfo).length)
			{
				return View({style: {display: 'none'}});
			}

			const images = [];
			const otherFiles = [];

			Object.values(this.props.taskResult.fileInfo).forEach((file) => {
				if (this.getFileType(file) === 'image')
				{
					images.push(file);
				}
				else
				{
					otherFiles.push(file);
				}
			});

			return View(
				{
					style: {
						marginTop: 12,
					},
				},
				this.renderImages(images),
				this.renderOtherFiles(otherFiles),
			);
		}

		renderImages(images)
		{
			images = images.map(image => ({...image, imageUri: image.url}));
			images = images.map(image => UI.File({...image, files: images}));

			return View(
				{
					style: {
						flexDirection: 'row',
						flexWrap: 'wrap',
						marginLeft: -9,
					},
				},
				...images,
			);
		}

		renderOtherFiles(files)
		{
			if (!files.length)
			{
				return View({style: {display: 'none'}});
			}

			files = files.map(file => ({...file, imageUri: file.url}));

			const filesToShowCount = 3;
			const filesToShow = [];

			for (let i = 0; i < filesToShowCount; i++)
			{
				if (files.length)
				{
					filesToShow.push(files.shift());
				}
			}

			return View(
				{
					style: {
						marginTop: 8,
					},
				},
				...filesToShow.map(file => UI.File({...file, isInLine: true})),
				(files.length && View(
					{
						style: {
							marginTop: 8,
							alignItems: 'flex-end',
						},
						onClick: () => this.openFilesList(files),
					},
					Text({
						style: {
							fontSize: 14,
							fontWeight: '400',
							color: '#a8adb4',
						},
						text: Loc.getMessage(
							'TASKSMOBILE_LAYOUT_TASK_FIELDS_TASK_RESULT_FILES_MORE',
							{'#COUNT#': files.length}
						),
					}),
				)),
			);
		}

		getFileType({type, name})
		{
			return UI.File.getType(UI.File.getFileMimeType(type, name));
		}

		getImageUrl(imageUrl)
		{
			if (imageUrl.indexOf(currentDomain) !== 0)
			{
				imageUrl = imageUrl.replace(`${currentDomain}`, '');
				imageUrl = (imageUrl.indexOf('http') !== 0 ? `${currentDomain}${imageUrl}` : imageUrl);
			}

			return encodeURI(imageUrl);
		}

		openFileViewer({fileType, url, name})
		{
			if (!url)
			{
				return;
			}

			switch (fileType)
			{
				case 'video':
					viewer.openVideo(url);
					break;

				case 'image':
					viewer.openImage(url, name);
					break;

				default:
					viewer.openDocument(url, name);
					break;
			}
		}

		openWebViewer({type, id})
		{
			PageManager.openPage({
				url: `${env.siteDir}mobile/tasks/snmrouter/?routePage=fragmentrenderer&FRAGMENT_TYPE=${type}&FRAGMENT_ID=${id}&TASK_ID=${this.props.taskId}&RESULT_ID=${this.props.taskResult.id}`,
				title: Loc.getMessage(
					`TASKSMOBILE_LAYOUT_TASK_FIELDS_TASK_RESULT_CONTENT_${type.toUpperCase()}`,
					{'#INDEX#': Number(id)}
				),
				backdrop: {
					bounceEnable: false,
					swipeAllowed: false,
					showOnTop: true,
					hideNavigationBar: false,
					horizontalSwipeAllowed: false,
				},
			});
		}

		openFilesList(files)
		{
			void this.props.parentWidget.openWidget(
				'layout',
				{
					title: Loc.getMessage(
						'TASKSMOBILE_LAYOUT_TASK_FIELDS_TASK_RESULT_FILES_MORE',
						{'#COUNT#': files.length}
					),
					useLargeTitleMode: true,
					modal: false,
					backdrop: {
						mediumPositionPercent: 75,
						horizontalSwipeAllowed: false,
					},
					onReady: (layoutWidget) => {
						const screenWidth = device.screen.width;
						const fileMeasure = 66;
						const imageSize = (screenWidth > 375 ? fileMeasure : screenWidth * fileMeasure / 375);

						layoutWidget.showComponent(
							new UI.FileAttachment({
								attachments: files,
								layoutWidget,
								showName: true,
								styles: {
									wrapper: {
										marginBottom: 12,
										marginRight: 10,
										paddingRight: 9,
									},
									imagePreview: {
										width: imageSize,
										height: imageSize,
									},
									imageOutline: () => ({
										width: imageSize,
										height: imageSize,
										position: 'absolute',
										top: 8,
										right: 9,
										borderColor: '#333333',
										borderWidth: 1,
										borderRadius: 6,
										opacity: 0.08,
									}),
								},
							}),
						);
					},
				},
			);
		}
	}

	module.exports = {TaskResult};
});