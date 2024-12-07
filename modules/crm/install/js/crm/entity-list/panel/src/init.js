import { ProgressBarRepository } from 'crm.autorun';
import { Extension, Reflection, Type } from 'main.core';
import { Router } from './event/router';

import './event/handlers-registry';

/**
 * @memberOf BX.Crm.EntityList.Panel
 */
export function init({ gridId, progressBarContainerId }): void
{
	if (!Reflection.getClass('BX.Main.gridManager.getInstanceById'))
	{
		console.error('BX.Main.gridManager is not found on page');

		return;
	}

	const grid = BX.Main.gridManager.getInstanceById(gridId);
	if (!grid)
	{
		console.error('grid not found', gridId);

		return;
	}

	const progressBarContainer = document.getElementById(progressBarContainerId);
	if (!Type.isElementNode(progressBarContainer))
	{
		console.error('progressBarContainer not found', progressBarContainerId);

		return;
	}

	const progressBarRepo = new ProgressBarRepository(progressBarContainer);

	const settings = Extension.getSettings('crm.entity-list.panel');

	const eventRouter = new Router(grid, progressBarRepo, settings);

	eventRouter.startListening();
}
