/**
 * @module crm/timeline/item/ui/body/blocks/file-list
 */
jn.define('crm/timeline/item/ui/body/blocks/file-list', (require, exports, module) => {
	const { TimelineItemBodyBlock } = require('crm/timeline/item/ui/body/blocks/base');
	const { TodoActivityConfig, CommentConfig } = require('crm/timeline/services/file-selector-configs');
	const { EasyIcon } = require('layout/ui/file/icon');
	const { FileSelector } = require('layout/ui/file/selector');
	const {
		NativeViewerMediaTypes,
		getNativeViewerMediaTypeByFileExt,
		getExtension,
		openNativeViewer,
	} = require('utils/file');
	const { transparent } = require('utils/color');
	const { withCurrentDomain } = require('utils/url');
	const { Loc } = require('loc');
	const { AudioPlayer } = require('layout/ui/audio-player');

	class TimelineItemBodyFileList extends TimelineItemBodyBlock
	{
		constructor(...props)
		{
			super(...props);

			this.onReceiveOpenFileManagerRequest = this.onReceiveOpenFileManagerRequest.bind(this);
			this.onAudioPlayerPlay = this.onAudioPlayerPlay.bind(this);

			this.maxDisplayedFiles = 5;

			this.state = {
				expanded: false,
			};
		}

		componentDidMount()
		{
			this.itemScopeEventBus.on('Crm.Timeline.Item.OpenFileManagerRequest', this.onReceiveOpenFileManagerRequest);
			this.itemScopeEventBus.on('AudioPlayer::onPlay', this.onAudioPlayerPlay);
		}

		componentWillUnmount()
		{
			this.itemScopeEventBus.off('Crm.Timeline.Item.OpenFileManagerRequest', this.onReceiveOpenFileManagerRequest);
			this.itemScopeEventBus.off('AudioPlayer::onPlay', this.onAudioPlayerPlay);
		}

		onReceiveOpenFileManagerRequest()
		{
			this.openFileManager({ focused: true });
		}

		onAudioPlayerPlay({ duration, currentTime, speed, uri, title })
		{
			if (duration)
			{
				this.openDetailCardTopToolbar('AudioPlayer', {
					uri,
					duration,
					currentTime,
					speed,
					title,
					play: true,
					uid: this.itemScopeEventBus.getUid(),
				});
			}
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
					testId: 'TimelineItemBodyFileListContainer',
				},
				this.renderFileList(),
				View(
					{
						style: {
							flexDirection: 'row',
							marginTop: 8,
						},
					},
					this.renderExpandButton(),
					this.renderEditButton(),
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
						flexShrink: 2,
					},
				},
				...visibleFiles.map((file, index) => View(
					{
						testId: `TimelineItemBodyFileListItem_${index}`,
						style: {
							flexDirection: 'row',
							marginBottom: 8,
							alignItems: 'center',
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
					file.hasAudioPlayer && this.renderAudioPlayer(file),
				)),
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

			return this.renderButton({
				text,
				testId: 'TimelineItemBodyFileListExpand',
				onClick: () => this.setState({ expanded: !this.state.expanded }),
				icon: this.state.expanded ? SvgIcons.collapse : SvgIcons.expand,
			});
		}

		renderEditButton()
		{
			if (!this.canOpenFileManager())
			{
				return null;
			}

			return this.renderButton({
				testId: 'TimelineItemBodyFileListTitle',
				onClick: () => this.openFileManager(),
				text: Loc.getMessage('M_CRM_TIMELINE_FILES_EDIT'),
				icon: SvgIcons.edit,
			});
		}

		renderButton({ testId, onClick, text, icon })
		{
			return View(
				{
					testId,
					onClick,
					style: {
						flexDirection: 'row',
						marginRight: 16,
					},
				},
				View(
					{
						style: Styles.borderDashed,
					},
					Text({
						text,
						style: {
							color: transparent('#000', 0.4),
							fontSize: 14,
						},
					}),
				),
				Image({
					svg: {
						content: icon,
					},
					style: {
						width: 18,
						height: 18,
					},
				}),
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
				EasyIcon(getExtension(file.name), 24),
			);
		}

		/**
		 * @param {TimelineFileListFile} file
		 * @return {object}
		 */
		renderAudioPlayer(file)
		{
			return View(
				{
					style: {
						marginLeft: 7,
					},
				},
				new AudioPlayer({
					uri: withCurrentDomain(file.viewUrl),
					uid: this.itemScopeEventBus.getUid(),
					fileName: file.name,
					title: file.name,
					compact: true,
				}),
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
			const isImage = (file) => getNativeViewerMediaTypeByFileExt(file.extension) === NativeViewerMediaTypes.IMAGE;

			return this.files
				.filter((file) => isImage(file))
				.map((file) => ({
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

			FileSelector.open(this.getFileSelectorConfig(options));
		}

		getFileSelectorConfig({ focused = false })
		{
			const { updateParams } = this.props;

			const params = {
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
			};

			const { type, entityId } = updateParams;
			if (type === 'Comment')
			{
				params.id = entityId;

				return CommentConfig(params);
			}

			params.activityId = entityId;

			return TodoActivityConfig(params);
		}
	}

	const Styles = {
		borderDashed: {
			borderStyle: 'dash',
			borderDashSegmentLength: 3,
			borderDashGapLength: 2,
			borderBottomWidth: 1,
			borderBottomColor: '#a8adb4',
		},
	};

	const SvgIcons = {
		edit: '<svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M12.3269 3.77612L14.2398 5.70918L6.75656 13.1723L4.84363 11.2392L12.3269 3.77612ZM3.76918 14.0045C3.75109 14.0729 3.77047 14.1453 3.81957 14.1956C3.86996 14.246 3.94231 14.2654 4.01079 14.246L6.14919 13.6699L4.34544 11.8667L3.76918 14.0045Z" fill="black" fill-opacity="0.2"/></svg>',
		expand: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M17.6676 8.15039L13.1405 12.6774L12 13.8003L10.8811 12.6774L6.35404 8.15039L4.75657 9.74786L12.0107 17.002L19.2649 9.74786L17.6676 8.15039Z" fill="black" fill-opacity="0.3"/></svg>',
		collapse: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.35393 15.8506L10.881 11.3235L12 10.1999L13.1404 11.3235L17.6674 15.8506L19.2649 14.2531L12.0108 6.99896L4.75659 14.2531L6.35393 15.8506Z" fill="black" fill-opacity="0.3"/></svg>',
	};

	module.exports = { TimelineItemBodyFileList };
});
