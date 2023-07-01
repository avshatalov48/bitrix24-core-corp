/**
 * @module crm/timeline/item/ui/body/blocks/file-list
 */
jn.define('crm/timeline/item/ui/body/blocks/file-list', (require, exports, module) => {
	const { TimelineItemBodyBlock } = require('crm/timeline/item/ui/body/blocks/base');
	const { TodoActivityConfig } = require('crm/timeline/services/file-selector-configs');
	const { EasyIcon } = require('layout/ui/file/icon');
	const { FileSelector } = require('layout/ui/file/selector');
	const {
		NativeViewerMediaTypes,
		getNativeViewerMediaTypeByFileExt,
		getExtension,
		openNativeViewer,
	} = require('utils/file');
	const { Loc } = require('loc');

	const borderNone = {};
	const borderDashed = {
		borderStyle: 'dash',
		borderDashSegmentLength: 3,
		borderDashGapLength: 2,
		borderBottomWidth: 1,
		borderBottomColor: '#a8adb4',
	};

	class TimelineItemBodyFileList extends TimelineItemBodyBlock
	{
		constructor(...props)
		{
			super(...props);

			this.onReceiveOpenFileManagerRequest = this.onReceiveOpenFileManagerRequest.bind(this);

			this.maxDisplayedFiles = 3;

			this.state = {
				expanded: false,
			};
		}

		componentDidMount()
		{
			this.itemScopeEventBus.on('Crm.Timeline.Item.OpenFileManagerRequest', this.onReceiveOpenFileManagerRequest);
		}

		componentWillUnmount()
		{
			this.itemScopeEventBus.off('Crm.Timeline.Item.OpenFileManagerRequest', this.onReceiveOpenFileManagerRequest);
		}

		onReceiveOpenFileManagerRequest()
		{
			this.openFileManager({ focused: true });
		}

		/**
		 * @return {TimelineFileListFile[]}
		 */
		get files()
		{
			return this.props.files || [];
		}

		render()
		{
			return View(
				{
					style: {
						flexDirection: 'row',
					},
				},
				this.renderTitle(),
				this.renderFileList(),
			);
		}

		renderTitle()
		{
			const title = this.props.numberOfFiles
				? `${this.props.title} (${this.props.numberOfFiles})`
				: `${this.props.title}`;

			return View(
				{},
				View(
					{
						style: {
							flexDirection: 'row',
						},
						onClick: () => this.openFileManager(),
					},
					View(
						{
							style: this.canOpenFileManager() ? borderDashed : borderNone,
						},
						Text({
							text: title,
							style: {
								color: '#828b95',
								fontSize: 13,
							},
						}),
					),
					this.canOpenFileManager() && View(
						{
							style: {
								marginHorizontal: 6,
							},
						},
						Image({
							svg: {
								content: '<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M12.3269 3.77612L14.2398 5.70918L6.75656 13.1723L4.84363 11.2392L12.3269 3.77612ZM3.76918 14.0045C3.75109 14.0729 3.77047 14.1453 3.81957 14.1956C3.86996 14.246 3.94231 14.2654 4.01079 14.246L6.14919 13.6699L4.34544 11.8667L3.76918 14.0045Z" fill="black" fill-opacity="0.2"/></svg>',
							},
							style: {
								width: 18,
								height: 18,
							},
						}),
					),
				),
			);
		}

		renderFileList()
		{
			const visibleFiles = this.state.expanded
				? this.files
				: this.files.slice(0, this.maxDisplayedFiles);

			return View(
				{
					style: {
						marginLeft: 32,
						flexShrink: 2,
					},
				},
				...visibleFiles.map((file) => View(
					{
						style: {
							flexDirection: 'row',
							marginBottom: 4,
						},
						onClick: () => this.openFile(file),
					},
					this.renderFileIcon(file),
					Text({
						text: file.name,
						ellipsize: 'middle',
						numberOfLines: 1,
						style: {
							color: '#2066b0',
							fontSize: 14,
							flexShrink: 2,
						},
					}),
				)),
				this.renderExpandButton(),
			);
		}

		renderExpandButton()
		{
			if (this.files.length <= this.maxDisplayedFiles)
			{
				return null;
			}

			const text = this.state.expanded
				? Loc.getMessage('M_CRM_TIMELINE_LIST_COLLAPSE')
				: Loc.getMessage('M_CRM_TIMELINE_LIST_SHOW_ALL');

			return View(
				{
					style: {
						paddingTop: 5,
						paddingBottom: 5,
						marginBottom: 5,
						flexDirection: 'row',
						justifyContent: 'flex-start',
					},
					onClick: () => this.setState({ expanded: !this.state.expanded }),
				},
				View(
					{
						style: borderDashed,
					},
					Text({
						text,
						style: {
							color: '#828b95',
							fontSize: 13,
						},
					}),
				),
			);
		}

		/**
		 * @param {TimelineFileListFile} file
		 * @return {object}
		 */
		renderFileIcon(file)
		{
			return View(
				{
					style: {
						marginRight: 6,
					},
				},
				EasyIcon(getExtension(file.name), 20),
			);
		}

		/**
		 * @param {TimelineFileListFile} file
		 */
		openFile(file)
		{
			openNativeViewer({
				fileType: getNativeViewerMediaTypeByFileExt(file.extension),
				url: file.viewUrl,
				name: file.name,
				images: this.getGallery(file),
			});
		}

		/**
		 * @param {TimelineFileListFile} currentFile
		 * @return {{url: string, default: bool, description: string}[]}
		 */
		getGallery(currentFile)
		{
			const onlyImage = (file) => getNativeViewerMediaTypeByFileExt(file.extension) === NativeViewerMediaTypes.IMAGE;

			return this.files.filter(onlyImage).map((file) => ({
				url: file.viewUrl,
				default: file.id === currentFile.id ? true : undefined,
				description: file.name,
			}));
		}

		canOpenFileManager()
		{
			return this.props.hasOwnProperty('updateParams') && !this.isReadonly;
		}

		openFileManager(options = {})
		{
			if (!this.canOpenFileManager())
			{
				return;
			}

			const { focused = false } = options;
			const { updateParams } = this.props;

			FileSelector.open(TodoActivityConfig({
				focused,
				files: this.files.map((file) => ({
					id: file.sourceFileId,
					name: file.name,
					type: file.extension,
					url: file.viewUrl,
					previewUrl: file.previewUrl,
				})),
				entityTypeId: updateParams.ownerTypeId,
				entityId: updateParams.ownerId,
				activityId: updateParams.entityId,
			}));
		}
	}

	module.exports = { TimelineItemBodyFileList };
});
