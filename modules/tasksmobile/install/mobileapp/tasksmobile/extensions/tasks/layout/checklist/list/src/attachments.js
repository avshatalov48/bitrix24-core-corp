/**
 * @module tasks/layout/checklist/list/src/attachments
 */
jn.define('tasks/layout/checklist/list/src/attachments', (require, exports, module) => {
	const { RequestExecutor } = require('rest');
	const { withCurrentDomain } = require('utils/url');
	const { FileField } = require('layout/ui/fields/file');

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
			this.bindAttachedInfo(props);
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
			this.bindAttachedInfo(props);
		}

		bindAttachedInfo(props)
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

		render()
		{
			const { parentWidget, diskConfig, onChange } = this.props;
			const { fileInfo } = this.state;
			const value = Object.values(fileInfo);

			return FileField({
				ref: (ref) => {
					this.ref = ref;
				},
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
							paddingTop: value.length > 0 ? 8 : 0,
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
				onChange: (files) => {
					this.addAttachedFiles(files);
					onChange();
				},
			});
		}
	}

	module.exports = { ItemAttachments };
});
