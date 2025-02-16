/* eslint-disable no-underscore-dangle */
import { Loc } from 'main.core';
import { BatchManager } from './batch-manager';

/**
 * @memberOf BX.Crm.Autorun
 */
export class BatchRestartAutomationManager extends BatchManager
{
	static messages = {
		// default messages, you can override them via settings.messages
		title: Loc.getMessage('CRM_AUTORUN_BATCH_RESTART_AUTOMATION_TITLE'),
		summaryCaption: Loc.getMessage('CRM_AUTORUN_BATCH_RESTART_AUTOMATION_SUMMARY_CAPTION'),
		summarySucceeded: Loc.getMessage('CRM_AUTORUN_BATCH_RESTART_AUTOMATION_SUMMARY_SUCCEEDED'),
		summaryFailed: Loc.getMessage('CRM_AUTORUN_BATCH_RESTART_AUTOMATION_SUMMARY_FAILED'),
	};

	static items = {};

	static getItem(id): ?BatchRestartAutomationManager
	{
		return BX.prop.get(BatchRestartAutomationManager.items, id, null);
	}

	static create(id, settings): BatchRestartAutomationManager
	{
		const self = new BatchRestartAutomationManager(id, settings);
		BatchRestartAutomationManager.items[self.getId()] = self;

		return self;
	}

	getIdPrefix(): string
	{
		return 'crm_batch_restart_automation_mgr';
	}

	getEventNamespacePostfix(): string
	{
		return 'BatchRestartAutomationManager';
	}

	getPrepareActionName(): string
	{
		return 'crm.api.autorun.restartAutomation.prepare';
	}

	getProcessActionName(): string
	{
		return 'crm.api.autorun.restartAutomation.process';
	}

	getCancelActionName(): string
	{
		return 'crm.api.autorun.restartAutomation.cancel';
	}
}
