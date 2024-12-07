import { Loc } from 'main.core';
import { BatchManager } from './batch-manager';

/**
 * @memberOf BX.Crm.Autorun
 */
export class BatchRefreshAccountingDataManager extends BatchManager
{
	static messages = {
		// default messages, you can override them via settings.messages
		title: Loc.getMessage('CRM_AUTORUN_BATCH_REFRESH_ACCOUNTING_DATA_TITLE'),
		summaryCaption: Loc.getMessage('CRM_AUTORUN_BATCH_REFRESH_ACCOUNTING_DATA_SUMMARY_CAPTION'),
		summarySucceeded: Loc.getMessage('CRM_AUTORUN_BATCH_REFRESH_ACCOUNTING_DATA_SUMMARY_SUCCEEDED'),
		summaryFailed: Loc.getMessage('CRM_AUTORUN_BATCH_REFRESH_ACCOUNTING_DATA_SUMMARY_FAILED'),
	};

	static items = {};

	static getItem(id): ?BatchRefreshAccountingDataManager
	{
		return BX.prop.get(BatchRefreshAccountingDataManager.items, id, null);
	}

	static create(id, settings): BatchRefreshAccountingDataManager
	{
		const self = new BatchRefreshAccountingDataManager(id, settings);
		BatchRefreshAccountingDataManager.items[self.getId()] = self;

		return self;
	}

	getIdPrefix(): string
	{
		return 'crm_batch_refresh_accounting_data_mgr';
	}

	getEventNamespacePostfix(): string
	{
		return 'BatchRefreshAccountingDataManager';
	}

	getPrepareActionName(): string
	{
		return 'crm.api.autorun.refreshaccountingdata.prepare';
	}

	getProcessActionName(): string
	{
		return 'crm.api.autorun.refreshaccountingdata.process';
	}

	getCancelActionName(): string
	{
		return 'crm.api.autorun.refreshaccountingdata.cancel';
	}
}
