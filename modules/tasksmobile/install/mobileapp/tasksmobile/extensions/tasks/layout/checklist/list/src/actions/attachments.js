/**
 * @module tasks/layout/checklist/list/src/actions/attachments
 */
jn.define('tasks/layout/checklist/list/src/actions/attachments', (require, exports, module) => {
	const AppTheme = require('apptheme');
	const { RequestExecutor } = require('rest');
	const { withCurrentDomain } = require('utils/url');
	const { FileField } = require('layout/ui/fields/file');
	const { useCallback } = require('utils/function');
	const { IconView } = require('ui-system/blocks/icon');

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
			this.handleOnChange = this.handleOnChange.bind(this);
			this.handleOnOpenAttachmentList = this.handleOnOpenAttachmentList.bind(this);
		}

		componentDidMount()
		{
			this.getAttachmentsInfo().then((files) => {
				this.addAttachedFiles(files);
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

		addAttachedFiles(files)
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

		updateAttachments(fileInfo)
		{
			const { item } = this.props;

			item.setAttachments(fileInfo);
			this.setState({ fileInfo });
		}

		addFile()
		{
			this.ref.openFilePicker();
		}

		handleOnOpenAttachmentList()
		{
			this.ref.onOpenAttachmentList();
		}

		handleOnChange(files)
		{
			const { onChange } = this.props;

			this.addAttachedFiles(files);
			onChange();
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
			const { parentWidget, diskConfig } = this.props;
			const { fileInfo } = this.state;
			const value = Object.values(fileInfo);
			const showFileCounter = this.getFilesCount() > 0;

			return View(
				{
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
						iconColor: AppTheme.colors.base3,
						icon: 'attach1',
					}),
					Text({
						style: {
							color: AppTheme.colors.base3,
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
					testId: 'checkList-file-field',
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
					onChange: this.handleOnChange,
				}),
			);
		}
	}

	module.exports = { ItemAttachments };
});
