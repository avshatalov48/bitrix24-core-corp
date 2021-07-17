import {BaseEvent, EventEmitter} from "main.core.events";
import CommonGrid from "./grid/common-grid";
import {Options as GridOptions} from "./options";
import {Reflection} from "main.core";

export default class Toolbar
{
	constructor()
	{

	}

	static reloadGridAndFocus(rowId: ?number)
	{
		let gridInstance;
		const gridId = GridOptions.getGridId();
		if (Reflection.getClass('BX.Main.gridManager') && BX.Main.gridManager.getInstanceById(gridId))
		{
			gridInstance = BX.Main.gridManager.getInstanceById(gridId);
		}
		else if (Reflection.getClass('BX.Main.tileGridManager') && BX.Main.tileGridManager.getInstanceById(gridId))
		{
			gridInstance = BX.Main.tileGridManager.getInstanceById(gridId);
		}

		const commonGrid = new CommonGrid({
			gridInstance: gridInstance,
		});

		if (gridInstance)
		{
			commonGrid.reload();
		}
	}

	static runCreating(documentType, service): void
	{
		if (BX.message('disk_restriction'))
		{
			//this.blockFeatures();
			return;
		}

		if (service === 'l' && BX.Disk.Document.Local.Instance.isEnabled())
		{
			BX.Disk.Document.Local.Instance.createFile({
				type: documentType,
			}).then((response) => {
				this.reloadGridAndFocus(response.object.id);
			});

			return;
		}

		const createProcess = new BX.Disk.Document.CreateProcess({
			typeFile: documentType,
			serviceCode: service,
			onAfterSave: (response) => {
				if (response.status === 'success')
				{
					this.reloadGridAndFocus(response.object.id);
				}
			}
		});

		createProcess.start();
	}

	static createDocx(service)
	{
		this.runCreating('docx', service);
	}

	static createXlsx(service)
	{
		this.runCreating('xlsx', service);
	}

	static createPptx(service)
	{
		this.runCreating('pptx', service);
	}

	static createByDefault(service)
	{
		console.log('createByDefault: ', service);
		console.log('try to upload just for the test');
	}
}