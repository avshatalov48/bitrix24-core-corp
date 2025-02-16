import { BatchManager } from './batch-manager';
import { Loc } from 'main.core';

export class BatchObserversManager extends BatchManager
{
	static messages = {
		title: Loc.getMessage('CRM_AUTORUN_BATCH_OBSERVERS_TITLE'),
		summaryCaption: Loc.getMessage('CRM_AUTORUN_BATCH_OBSERVERS_SUMMARY_CAPTION'),
		summarySucceeded: Loc.getMessage('CRM_AUTORUN_BATCH_OBSERVERS_SUMMARY_SUCCEEDED'),
		summaryFailed: Loc.getMessage('CRM_AUTORUN_BATCH_OBSERVERS_SUMMARY_FAILED'),
	};

	static items: Object = {};
	#observerIdList: Array<string> = [];

	static getItem(id): ?BatchObserversManager
	{
		return BatchObserversManager.items[id] ?? null;
	}

	static create(id, settings): BatchObserversManager
	{
		const self = new BatchObserversManager(id, settings);
		BatchObserversManager.items[self.getId()] = self;

		return self;
	}

	getCancelActionName(): string
	{
		return 'crm.api.autorun.observers.cancel';
	}

	getEventNamespacePostfix(): string
	{
		return 'BatchObserversManager';
	}

	getIdPrefix(): string
	{
		return 'crm_batch_observers_mgr';
	}

	getPrepareActionName(): string
	{
		return 'crm.api.autorun.observers.prepare';
	}

	getPrepareActionParams(): Object
	{
		const params = super.getPrepareActionParams();

		params.observerIdList = this.#observerIdList;

		return params;
	}

	getProcessActionName(): string
	{
		return 'crm.api.autorun.observers.process';
	}

	setObserverIdList(userIdList: Array<string>): void
	{
		this.#observerIdList = userIdList;
	}
}
