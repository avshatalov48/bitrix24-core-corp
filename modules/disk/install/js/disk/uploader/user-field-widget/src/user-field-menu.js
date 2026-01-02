import { Dom, Loc } from 'main.core';
import { Menu, type MenuOptions, type MenuItemOptions, type MenuSectionOptions } from 'ui.system.menu';
import { Uploader } from 'ui.uploader.core';
import { FileIcon } from 'ui.icons.generator';
import { Outline } from 'ui.icon-set.api.core';
import 'ui.icon-set.outline';

import { DocumentService, DocumentType } from './const';
import { openDiskFileDialog, openCloudFileDialog, createDocumentDialog } from './helpers/index';
import { userFieldSettings } from './user-field-settings';

type Params = {
	dialogId: string,
	uploader: Uploader,
	menuOptions: MenuOptions,
	compact?: boolean,
};

const sectionCreateDocument = 'create-document';
const sectionCreateDocumentService = 'create-document-service';
const importServices = userFieldSettings.getImportServices();
const createServices = userFieldSettings.getDocumentServices();

export class UserFieldMenu
{
	#params: Params;
	#menu: Menu;
	#browseElement: HTMLElement;

	constructor(params: Params)
	{
		this.#params = params;

		if (!BX.Disk.getDocumentService())
		{
			const service = BX.Disk.isAvailableOnlyOffice() ? DocumentService.OnlyOffice : DocumentService.Local;
			BX.Disk.saveDocumentService(service);
		}
	}

	getMenu(): Menu
	{
		return this.#menu;
	}

	show(bindElement: HTMLElement): void
	{
		this.#menu ??= new Menu({
			id: `disk-user-field-menu-${Date.now()}`,
			minWidth: 250,
			sections: this.#getSections(),
			items: this.#getItems(),
			...this.#params.menuOptions,
		});

		this.#menu.show(bindElement);
	}

	#getSections(): MenuSectionOptions[]
	{
		return [
			{
				code: sectionCreateDocument,
				title: Loc.getMessage('DISK_UF_WIDGET_CREATE'),
			},
			{
				code: sectionCreateDocumentService,
			},
		];
	}

	#getItems(): MenuItemOptions[]
	{
		const items = [
			{
				title: Loc.getMessage('DISK_UF_WIDGET_UPLOAD_FILES'),
				icon: Outline.DOWNLOAD,
				onClick: () => this.#browse(),
			},
			{
				title: Loc.getMessage('DISK_UF_WIDGET_MY_DRIVE'),
				icon: Outline.UPLOAD,
				onClick: () => openDiskFileDialog({
					dialogId: this.#params.dialogId,
					uploader: this.#params.uploader,
				}),
			},
		];

		if (this.#params.compact === true)
		{
			return items;
		}

		items.push(
			this.#getSelectImportServiceItem(),
			this.#getCreateDocumentItem(DocumentType.Docx),
			this.#getCreateDocumentItem(DocumentType.Xlsx),
			this.#getCreateDocumentItem(DocumentType.Pptx),
			this.#getCreateDocumentItem(DocumentType.Board),
			this.#getSelectCreateServiceItem(),
		);

		return items;
	}

	#browse(): void
	{
		if (!this.#browseElement)
		{
			this.#browseElement = document.createElement('div');
			this.#params.uploader.assignBrowse(this.#browseElement);
		}

		this.#browseElement.click();
	}

	#getSelectImportServiceItem(): ?MenuItemOptions
	{
		const items = [
			this.#getImportDocumentItem(DocumentService.Google),
			this.#getImportDocumentItem(DocumentService.Office),
			this.#getImportDocumentItem(DocumentService.Dropbox),
		].filter((it) => it);
		if (items.length === 0)
		{
			return null;
		}

		return {
			title: Loc.getMessage('DISK_UF_WIDGET_EXTERNAL_DRIVES'),
			subMenu: { items },
		};
	}

	#getImportDocumentItem(documentService: string): ?MenuItemOptions
	{
		if (!importServices[documentService])
		{
			return null;
		}

		return {
			title: importServices[documentService].name,
			onClick: () => openCloudFileDialog({
				dialogId: this.#params.dialogId,
				uploader: this.#params.uploader,
				serviceId: documentService,
			}),
		};
	}

	#getCreateDocumentItem(documentType: string): ?MenuItemOptions
	{
		const cantCreateDocuments = !this.#canCreateDocument();
		const boardsDisabled = (documentType === DocumentType.Board) && !userFieldSettings.isBoardsEnabled();
		if (cantCreateDocuments || boardsDisabled)
		{
			return null;
		}

		return {
			sectionCode: sectionCreateDocument,
			title: this.#getCreateDocumentTitle(documentType),
			svg: this.#getDocumentSvg(documentType),
			onClick: () => createDocumentDialog({
				uploader: this.#params.uploader,
				documentType,
				documentHandlers: Object.values(userFieldSettings.getDocumentServices()),
			}),
		};
	}

	#getCreateDocumentTitle(documentType: string): string
	{
		return {
			[DocumentType.Docx]: Loc.getMessage('DISK_UF_WIDGET_CREATE_DOCX'),
			[DocumentType.Xlsx]: Loc.getMessage('DISK_UF_WIDGET_CREATE_XLSX'),
			[DocumentType.Pptx]: Loc.getMessage('DISK_UF_WIDGET_CREATE_PPTX'),
			[DocumentType.Board]: Loc.getMessage('DISK_UF_WIDGET_CREATE_BOARD'),
		}[documentType];
	}

	#getDocumentSvg(name: string): SVGElement
	{
		const size = 20;
		const svg = new FileIcon({ name, size }).generate();

		Dom.style(svg, 'width', `${size}px`);
		Dom.style(svg, 'height', `${size}px`);

		return svg;
	}

	#getSelectCreateServiceItem(): ?MenuItemOptions
	{
		if (!this.#canCreateDocument())
		{
			return null;
		}

		return {
			sectionCode: sectionCreateDocumentService,
			title: Loc.getMessage('DISK_UF_WIDGET_CREATE_WITH'),
			subtitle: createServices[BX.Disk.getDocumentService()].name,
			closeOnSubItemClick: false,
			subMenu: {
				items: Object.keys(createServices).map((documentService: string) => ({
					title: createServices[documentService].name,
					isSelected: BX.Disk.getDocumentService() === documentService,
					onClick: (): void => {
						BX.Disk.saveDocumentService(documentService);
						this.#menu.updateItems(this.#getItems());
					},
				})),
			},
		};
	}

	#canCreateDocument(): boolean
	{
		return userFieldSettings.canCreateDocuments() && Object.keys(createServices).length > 0;
	}
}
