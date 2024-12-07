/**
 * @module tasks/layout/checklist/list/src/actions/attachments
 */
jn.define('tasks/layout/checklist/list/src/actions/attachments', (require, exports, module) => {
	const { Color, Indent } = require('tokens');
	const { RequestExecutor } = require('rest');
	const { withCurrentDomain } = require('utils/url');
	const { FileField } = require('layout/ui/fields/file');
	const { IconView, Icon } = require('ui-system/blocks/icon');
	const { isEqual } = require('utils/object');
	const { Text4 } = require('ui-system/typography/text');
	const { NotifyManager } = require('notify-manager');

	const ICON_SIZE = 24;

	/**
	 * @class ItemAttachments
	 */
	class ItemAttachments extends LayoutComponent
	{
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

			const attachmentsData = await new RequestExecutor('mobile.disk.getattachmentsdata', { attachmentsIds })
				.call(true).then((data) => {
					if (data?.errors)
					{
						console.error(data?.errors);
					}

					if (data?.result && Array.isArray(data.result) && data.result.length > 0)
					{
						return this.prepareAttachmentData(data.result);
					}

					return [];
				}).catch(console.error);

			return [...attachmentsInfo, ...attachmentsData];
		}

		prepareAttachmentData(attachments)
		{
			return attachments.map((attachment) => {
				const { ID: id, NAME: name, EXTENSION: type, URL, OBJECT_ID: fileId } = attachment;
				const url = withCurrentDomain(URL);

				return { id, name, url, type, fileId: Number(id), serverFileId: `n${fileId}`, isUploading: false };
			});
		}

		handleUpdateAttachedFiles = (files) => {
			const fileInfo = this.getFileInfo();
			const attachedFiles = Array.isArray(files) ? files : [];
			const attachedFilesInfo = {};

			attachedFiles.filter(Boolean).forEach((file) => {
				let fileId = file?.id;
				let attachedFileInfo = file;

				if (BX.type.isNumber(Number(file)))
				{
					fileId = file;
					attachedFileInfo = fileInfo[fileId];
				}

				attachedFilesInfo[fileId] = attachedFileInfo;
			});

			void this.handleOnChangeInfo(attachedFilesInfo);
		};

		isChangedFiles(fileInfo)
		{
			const stateFileInfo = this.getFileInfo();

			return !isEqual(this.prepareUploadingInfo(fileInfo), this.prepareUploadingInfo(stateFileInfo));
		}

		prepareUploadingInfo = (info) => Object.values(info).map((file) => ({
			id: file?.id,
			isUploading: file?.isUploading,
		}));

		async handleOnChangeInfo(fileInfo)
		{
			const isChanged = this.isChangedFiles(fileInfo);
			const prevFilesCount = this.getFilesCount();
			const shouldRender = prevFilesCount === 0 || this.getFilesCount() === 0;
			this.setFileInfo(fileInfo);

			if (isChanged && this.getFilesCount() > 0)
			{
				this.setState({
					fileInfo: this.prepareUploadingInfo(fileInfo),
				}, () => {
					this.handleOnChange(shouldRender);
				});
			}
		}

		handleOnChange(shouldRender = true)
		{
			const { item, onChange } = this.props;

			if (onChange)
			{
				return onChange({ item, shouldRender });
			}

			return Promise.resolve();
		}

		addFile()
		{
			this.getFieldRef().openFilePicker();
		}

		handleOnOpenAttachmentList = async () => {
			let fileInfo = this.getFileInfo();
			const isUploadedFileInfo = Object.values(fileInfo).every((attachment) => typeof attachment?.isUploading === 'boolean' && !attachment.isUploading);

			if (!isUploadedFileInfo)
			{
				void NotifyManager.showLoadingIndicator(true);
				fileInfo = await this.getAttachmentsInfo();
				NotifyManager.hideLoadingIndicatorWithoutFallback();
			}

			const { layoutWidget } = await this.getFieldRef().onOpenAttachmentList();

			layoutWidget.preventBottomSheetDismiss(true);
			layoutWidget.on('preventDismiss', () => {
				this.handleOnChange();

				layoutWidget.close();
			});

			void this.handleOnChangeInfo(fileInfo);
		};

		getFilesCount()
		{
			const { item } = this.props;

			return item.getAttachmentsCount();
		}

		getFileInfo()
		{
			const { item } = this.props;

			return item.getAttachments();
		}

		setFileInfo(fileInfo)
		{
			const { item } = this.props;

			return item.setAttachments(fileInfo);
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

		setFieldRef = (ref) => {
			const { item } = this.props;

			if (!layout.fileFieldRef)
			{
				layout.fileFieldRef = new Map();
			}

			layout.fileFieldRef.set(item.getNodeId(), ref);
		};

		/**
		 * @return {FileField}
		 */
		getFieldRef()
		{
			const { item } = this.props;

			return layout.fileFieldRef.get(item.getNodeId());
		}

		render()
		{
			const { testId } = this.props;

			return View(
				{
					testId,
					style: {
						flexDirection: 'row',
					},
				},
				this.renderFileField(),
			);
		}

		renderFileField()
		{
			const { parentWidget, diskConfig, readOnly } = this.props;
			const fileInfo = this.getFileInfo();
			const value = Object.values(fileInfo);

			if (!diskConfig?.folderId)
			{
				console.warn('Checklist: folderId not found, cannot save');
			}

			return FileField({
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
				readOnly,
				onChange: this.handleUpdateAttachedFiles,
				ThemeComponent: ({ field }) => {
					this.setFieldRef(field);

					return this.renderFileCounter();
				},
			});
		}

		renderFileCounter()
		{
			if (!this.getFilesCount())
			{
				return null;
			}

			return View(
				{
					style: {
						flexDirection: 'row',
						marginRight: Indent.M.toNumber(),
					},
					onClick: this.handleOnOpenAttachmentList,
				},
				IconView({
					size: ICON_SIZE,
					color: Color.base3,
					icon: Icon.ATTACH,
				}),
				Text4({
					color: Color.base3,
					text: this.getAttachmentsCount(),
				}),
			);
		}
	}

	module.exports = { ItemAttachments };
});
