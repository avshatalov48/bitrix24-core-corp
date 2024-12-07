/* eslint-disable no-underscore-dangle */
import { Loc } from 'main.core';
import { BatchManager } from './batch-manager';

/**
 * @memberOf BX.Crm.Autorun
 */
export class BatchExclusionManager extends BatchManager
{
	static messages = {
		// default messages, you can override them via settings.messages
		title: Loc.getMessage('CRM_AUTORUN_BATCH_EXCLUSION_TITLE'),
		summaryCaption: Loc.getMessage('CRM_AUTORUN_BATCH_EXCLUSION_SUMMARY_CAPTION'),
		summarySucceeded: Loc.getMessage('CRM_AUTORUN_BATCH_EXCLUSION_SUMMARY_SUCCEEDED'),
		summaryFailed: Loc.getMessage('CRM_AUTORUN_BATCH_EXCLUSION_SUMMARY_FAILED'),
	};

	static items = {};

	static getItem(id): ?BatchExclusionManager
	{
		return BX.prop.get(BatchExclusionManager.items, id, null);
	}

	static create(id, settings): BatchExclusionManager
	{
		const self = new BatchExclusionManager(id, settings);
		BatchExclusionManager.items[self.getId()] = self;

		return self;
	}

	getIdPrefix(): string
	{
		return 'crm_batch_exclusion_mgr';
	}

	getEventNamespacePostfix(): string
	{
		return 'BatchExclusionManager';
	}

	getPrepareActionName(): string
	{
		return 'crm.api.autorun.exclusion.prepare';
	}

	getProcessActionName(): string
	{
		return 'crm.api.autorun.exclusion.process';
	}

	getCancelActionName(): string
	{
		return 'crm.api.autorun.exclusion.cancel';
	}
}
