import { BatchConversionManager, ProcessRegistry } from 'crm.autorun';
import { Type } from 'main.core';
import { showAnotherProcessRunningNotification } from '../../../utils';
import { BaseHandler } from '../base-handler';

export class ExecuteConversion extends BaseHandler
{
	#valueElement: HTMLElement;

	constructor({ valueElementId })
	{
		super();

		this.#valueElement = document.getElementById(valueElementId);
		if (!Type.isElementNode(this.#valueElement))
		{
			throw new Error('value element not found');
		}
	}

	static getEventName(): string
	{
		return 'BatchManager:executeConversion';
	}

	execute(grid: BX.Main.grid, selectedIds: number[], forAll: boolean): void
	{
		const manager = BatchConversionManager.getItem(grid.getId());
		if (!manager)
		{
			console.error(`BatchConversionManager with id ${grid.getId()} not found`);

			return;
		}

		if (manager.isRunning())
		{
			return;
		}

		if (ProcessRegistry.isProcessRunning(grid.getId()))
		{
			showAnotherProcessRunningNotification();

			return;
		}

		const schemeName = this.#valueElement.dataset.value || BX.CrmLeadConversionScheme.dealcontactcompany;
		manager.setConfig(BX.CrmLeadConversionScheme.createConfig(schemeName));

		if (forAll)
		{
			manager.resetEntityIds();
		}
		else
		{
			manager.setEntityIds(selectedIds);
		}

		manager.execute();
	}
}
