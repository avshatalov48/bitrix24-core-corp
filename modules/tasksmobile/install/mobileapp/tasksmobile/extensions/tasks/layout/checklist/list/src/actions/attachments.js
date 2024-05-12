/**
 * @module tasks/layout/checklist/list/src/actions/attachments
 */
jn.define('tasks/layout/checklist/list/src/actions/attachments', (require, exports, module) => {
	const { Color } = require('tokens');
	const { RequestExecutor } = require('rest');
	const { withCurrentDomain } = require('utils/url');
	const { FileField } = require('layout/ui/fields/file');
	const { useCallback } = require('utils/function');
	const { IconView, iconTypes } = require('ui-system/blocks/icon');
	const { isEqual } = require('utils/object');

	const ICON_SIZE = 24;

	/**
	 * @class ItemAttachments
	 */
	class ItemAttachments extends LayoutComponent
	{
		constructor(props)
		{
			super(props);

			/** @type {FileField} */
			this.ref = null;
			this.initialAttachedInfo(props);
			this.handleAddAttachedFiles = this.handleAddAttachedFiles.bind(this);
			this.handleOnOpenAttachmentList = this.handleOnOpenAttachmentList.bind(this);
		}

		componentDidMount()
		{
			this.getAttachmentsInfo().then((files) => {
				this.handleAddAttachedFiles(files);
			}).catch((e) => {
				if (e)
				{
					console.error(e);
				}
			});
		}

		componentWillReceiveProps(props)
		{
			this.initialAttachedInfo(props);
		}

		initialAttachedInfo(props)
		{
			const { item } = props;

			this.state = {
				fileInfo: item.getAttachments(),
			};
		}

		async getAttachmentsInfo()
		{
			const { item } = this.props;

			const attachmentsIds = [];
			const attachmentsInfo = [];
			const itemAttachments = item.getAttachments();

			if (Object.keys(itemAttachments).length === 0)
			{
				return [];
			}

			Object.keys(itemAttachments).forEach((id) => {
				if (itemAttachments[id])
				{
					attachmentsInfo.push(itemAttachments[id]);
				}
				else
				{
					attachmentsIds.push(id);
				}
			});

			const attachmentsData = await new RequestExecutor('mobile.disk.getattachmentsdata', {
				attachmentsIds,
			}).call(true).then((data) => {
				if (data?.errors)
				{
					console.error(data?.errors);
				}

				if (data?.result && Array.isArray(data.result) && data.result.length > 0)
				{
					return this.prepareAttachmentData(data.result);
				}

				return [];
			});

			return [...attachmentsInfo, ...attachmentsData];
		}

		prepareAttachmentData(attachments)
		{
			return attachments.map((attachment) => {
				const { ID: id, NAME: name, EXTENSION: type, URL, OBJECT_ID: fileId } = attachment;
				const url = withCurrentDomain(URL);

				return { id, name, url, type, fileId: Number(id), serverFileId: `n${fileId}` };
			});
		}

		handleAddAttachedFiles(files)
		{
			const { fileInfo } = this.state;

			const attachedFiles = Array.isArray(files) ? files : [];
			const attachedFilesInfo = {};

			attachedFiles.forEach((file) => {
				let fileId = file?.id;
				let attachedFileInfo = file;

				if (BX.type.isNumber(Number(file)))
				{
					fileId = file;
					attachedFileInfo = fileInfo[file];
				}

				attachedFilesInfo[fileId] = attachedFileInfo;
			});

			this.updateAttachments(attachedFilesInfo);
		}

		isChangedFiles(fileInfo)
		{
			const { fileInfo: stateFileInfo } = this.state;

			return !isEqual(Object.keys(fileInfo), Object.keys(stateFileInfo));
		}

		updateAttachments(fileInfo)
		{
			const { fileInfo: stateFileInfo } = this.state;
			const { item, onChange } = this.props;

			if (!this.isChangedFiles(fileInfo, stateFileInfo))
			{
				return;
			}

			item.setAttachments(fileInfo);
			this.setState({ fileInfo }, () => {
				if (onChange)
				{
					onChange(item);
				}
			});
		}

		addFile()
		{
			this.ref.openFilePicker();
		}

		handleOnOpenAttachmentList()
		{
			this.ref.onOpenAttachmentList();
		}

		getFilesCount()
		{
			const { fileInfo } = this.state;

			return Object.keys(fileInfo).length;
		}

		getAttachmentsCount()
		{
			const count = this.getFilesCount();

			if (count > 100)
			{
				return '99+';
			}

			return String(count);
		}

		render()
		{
			const { parentWidget, diskConfig, testId } = this.props;
			const { fileInfo } = this.state;
			const value = Object.values(fileInfo);
			const showFileCounter = this.getFilesCount() > 0;

			if (!diskConfig?.folderId)
			{
				console.error('FolderId not found');
			}

			return View(
				{
					testId,
					style: {
						flexDirection: 'row',
					},
				},
				showFileCounter && View(
					{
						style: {
							flexDirection: 'row',
							marginRight: 14,
						},
						onClick: this.handleOnOpenAttachmentList,
					},
					IconView({
						iconSize: ICON_SIZE,
						iconColor: Color.base3,
						icon: iconTypes.outline.attach1,
					}),
					Text({
						style: {
							color: Color.base3,
							fontSize: 14,
						},
						text: this.getAttachmentsCount(),
					}),
				),
				FileField({
					ref: useCallback((ref) => {
						this.ref = ref;
					}),
					value,
					showTitle: false,
					showAddButton: false,
					multiple: true,
					showLeftIcon: false,
					hasHiddenEmptyView: true,
					config: {
						deepMergeStyles: {
							wrapper: {
								display: 'none',
								paddingTop: 0,
								paddingBottom: 0,
							},
						},
						mediaType: 'file',
						parentWidget,
						controller: {
							options: {
								folderId: diskConfig?.folderId,
							},
							endpoint: 'disk.uf.integration.diskUploaderController',
						},
					},
					readOnly: false,
					onChange: this.handleAddAttachedFiles,
				}),
			);
		}
	}

	module.exports = { ItemAttachments };
});
