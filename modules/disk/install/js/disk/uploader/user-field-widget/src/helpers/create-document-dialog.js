import { Type } from 'main.core';
import { Uploader } from 'ui.uploader.core';
import 'disk.document';
import { DocumentService, DocumentType } from '../const';

type CreateDocumentOptions = {
	uploader: Uploader,
	documentType: 'docx' | 'xlsx' | 'pptx' | 'board',
	onAddFile: Function,
	documentHandlers: { name: string, code: string, supportsUnifiedLink: boolean }[],
};

export const createDocumentDialog = (options: CreateDocumentOptions = {}): void => {
	const uploader: ?Uploader = (options.uploader instanceof Uploader) ? options.uploader : null;
	const documentType: ?string = Type.isStringFilled(options.documentType) ? options.documentType : null;
	const onAddFile: ?Function = Type.isFunction(options.onAddFile) ? options.onAddFile : null;

	// TODO: load disk and disk.document extensions on demand
	if (!BX.Disk.getDocumentService())
	{
		const service = BX.Disk.isAvailableOnlyOffice() ? DocumentService.OnlyOffice : DocumentService.Local;
		BX.Disk.saveDocumentService(service);
	}

	let newTab = null;
	if (documentType === DocumentType.Board)
	{
		newTab = window.open('', '_blank');
	}

	if (BX.Disk.Document.Local.Instance.isSetWorkWithLocalBDisk() || documentType === 'board')
	{
		BX.Disk.Document.Local.Instance.createFile({ type: documentType }).then((response): void => {
			if (response.status === 'success')
			{
				if (documentType === 'board')
				{
					BX.UI.Analytics.sendData({
						event: 'create',
						tool: 'boards',
						category: 'boards',
						c_element: 'docs_attach_uploader_widget',
					});
				}

				uploader.addFile(
					`n${response.object.id}`,
					{
						name: response.object.name,
						preload: true,
					},
				);

				onAddFile?.();

				if (newTab !== null && response.openUrl)
				{
					newTab.location.href = response.openUrl;
				}
			}
		});
	}
	else
	{
		const documentService = BX.Disk.getDocumentService();
		const byUnifiedLink = options.documentHandlers.some((handler) => handler.supportsUnifiedLink
			&& handler.code === documentService);

		const saveCallback = (response): void => {
			if (response.status !== 'success')
			{
				return;
			}

			const key = response.object ? 'object' : 'data';

			if (response[key])
			{
				uploader.addFile(
					`n${response[key].id}`,
					{
						name: response[key].name,
						size: response[key].size,
						preload: true,
					},
				);

				onAddFile?.();
			}
		};

		const createProcess = new BX.Disk.Document.CreateProcess({
			typeFile: documentType,
			serviceCode: documentService,
			byUnifiedLink,
			onAfterSave: saveCallback,
			onAfterCreateFile: saveCallback,
		});

		createProcess.start();
	}
};
