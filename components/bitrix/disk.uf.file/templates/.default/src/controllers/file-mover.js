import {Uri, Loc} from 'main.core';
import {EventEmitter, BaseEvent} from 'main.core.events';
import Backend from '../backend';
import Item from "../items/item";

export default class FileMover
{
	static subscribed = false;
	static instance = null;
	dialogName: string = 'moveFile';
	item: ?Item;
	timeout: ?number;

	constructor()
	{
		this.openSection = this.openSection.bind(this);
		this.loadFolder = this.loadFolder.bind(this);
		this.stopLoadingFolder = this.stopLoadingFolder.bind(this);
		this.checkFileName = this.checkFileName.bind(this);
		this.onApply = this.onApply.bind(this);
		this.onCancel = this.onCancel.bind(this);
	}

	fire(item: Item): void
	{
		if (this.item !== null && this.item !== item)
		{
			this.onCancel();
		}

		this.item = item;
		Backend.getSelectedFile(
			item.getFileId(),
			item.getData('NAME'),
			this.dialogName
		)
		.then(() => {
			setTimeout(() => {
				BX.DiskFileDialog.obCallback[this.dialogName] = {saveButton: this.onApply, cancelButton: this.onCancel};
				BX.DiskFileDialog.openDialog(this.dialogName);
			}, 10)
		});

		EventEmitter.subscribe(BX.DiskFileDialog, 'loadItems', this.openSection);
		//EventEmitter.subscribe(BX.DiskFileDialog, 'loadItemsDone', this.checkFileName);
		EventEmitter.subscribe(BX.DiskFileDialog, 'selectItem', this.loadFolder);
		EventEmitter.subscribe(BX.DiskFileDialog, 'unSelectItem', this.stopLoadingFolder);
	}

	openSection({data: [link, someDialogName]}): void
	{
		if (someDialogName === this.dialogName)
		{
			BX.DiskFileDialog.target[someDialogName] = Uri.addParam(link, {dialog2: 'Y'});
		}
	}

	loadFolder({data: [element, itemId: string, someDialogName]}): void
	{
		if (someDialogName !== this.dialogName)
		{
			return;
		}
		Backend
			.loadFolder(itemId.substr(1), this.item.getData('NAME'), this.dialogName)
			.then((result) => {
				const documentExists = (result.permission === true && result["okmsg"] !== '');

				if (this.timeout > 0)
				{
					clearTimeout(this.timeout);
				}
				this.timeout = setTimeout(() => {
					if (documentExists)
					{
						BX.DiskFileDialog.showNotice(Loc.getMessage('WDUF_FILE_IS_EXISTS'), this.dialogName);
					}
					else
					{
						BX.DiskFileDialog.closeNotice(this.dialogName);
					}
				}, 200);
			});
	}

	stopLoadingFolder({data: someData}): void
	{
		if (this.timeout > 0)
		{
			clearTimeout(this.timeout);
		}
		this.timeout = setTimeout(this.checkFileName, 200);
	}

	onApply(tab, path, selected, folderByPath)
	{
		EventEmitter.unsubscribe(BX.DiskFileDialog, 'loadItems', this.openSection);
		//EventEmitter.unsubscribe(BX.DiskFileDialog, 'loadItemsDone', this.checkFileName);
		EventEmitter.unsubscribe(BX.DiskFileDialog, 'selectItem', this.loadFolder);
		EventEmitter.unsubscribe(BX.DiskFileDialog, 'unSelectItem', this.stopLoadingFolder);

		if (!this.item)
		{
			return;
		}

		const id = this.item.getId();
		let moved = false;

		const moveQuery = (id, targetFolderId, sectionProperties, sectionPath) => {
			Backend
				.moveFile(id, targetFolderId)
				.then((response) => {
					if (!response || response.status !== 'success')
					{
						BX.Disk.showModalWithStatusAction(response);
						return;
					}
					this.showMovedFile(id, sectionProperties, sectionPath);
				});
		};

		let sectionPath, sectionProperties;
		for (let i in selected)
		{
			if (selected.hasOwnProperty(i) && selected[i].type === 'folder')
			{
				sectionPath = tab.name + selected[i].path;
				sectionProperties = { sectionID : i, iblockID : tab.iblock_id };

				moveQuery(id, selected[i].id, sectionProperties, sectionPath);
				moved = true;
			}
		}

		if (!moved)
		{
			sectionPath = tab.name;
			sectionProperties = { sectionID : tab.section_id, iblockID : tab.iblock_id };
			if (!!folderByPath && !!folderByPath.path && folderByPath.path !== '/')
			{
				sectionPath += folderByPath.path;
				sectionProperties.sectionID = folderByPath.id;
				if(!!folderByPath)
				{
					moveQuery(id, folderByPath.id, sectionProperties, sectionPath);
				}
			}
		}
	}

	checkFileName(someDialogName): void
	{
		if (this.timeout > 0)
		{
			clearTimeout(this.timeout);
		}

		if (someDialogName !== this.dialogName || !this.item)
		{
			return;
		}

		const fileName = this.item.getData('NAME');
		let exist = false;

		for (let i in BX.DiskFileDialog.obItems[this.dialogName])
		{
			if (BX.DiskFileDialog.obItems[this.dialogName].hasOwnProperty(i)
				&& BX.DiskFileDialog.obItems[this.dialogName][i]['name'] === fileName)
			{
				exist = true;
				break;
			}
		}

		if (exist)
		{
			BX.DiskFileDialog.showNotice(Loc.getMessage('WDUF_FILE_IS_EXISTS'), this.dialogName);
		}
		else
		{
			BX.DiskFileDialog.closeNotice(this.dialogName);
		}
	}

	showMovedFile(id, sectionProperties, sectionPath): void
	{
		if (this.item)
		{
			this.item.emit('onMoved', sectionPath);
		}
		this.item = null;
	}

	onCancel(): void
	{
		EventEmitter.unsubscribe(BX.DiskFileDialog, 'loadItems', this.openSection);
		//EventEmitter.unsubscribe(BX.DiskFileDialog, 'loadItemsDone', this.checkFileName);
		EventEmitter.unsubscribe(BX.DiskFileDialog, 'selectItem', this.loadFolder);
		EventEmitter.unsubscribe(BX.DiskFileDialog, 'unSelectItem', this.stopLoadingFolder);
		this.item = null;
		if (this.timeout > 0)
		{
			clearTimeout(this.timeout);
		}
		this.timeout = null;
	}

	static subscribe(): void
	{
		if (BX.DiskFileDialog.subscribed !== true)
		{
			BX.DiskFileDialog.subscribed = true;
			EventEmitter.subscribe(BX.DiskFileDialog, 'onFileNeedsToMove', (event: BaseEvent) => {
				event.stopImmediatePropagation();
				FileMover.getInstance().fire([...event.getData()].shift());
			})
		}
	}

	static getInstance(): FileMover
	{
		if (this.instance === null)
		{
			this.instance = new FileMover();
		}
		return this.instance;
	}
}
