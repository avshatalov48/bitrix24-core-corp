/* eslint-disable no-underscore-dangle */
import { Loc, Type } from 'main.core';
import { BatchManager } from './batch-manager';

/**
 * @memberOf BX.Crm.Autorun
 */
export class BatchSetOpenedManager extends BatchManager
{
	static messages = {
		// default messages, you can override them via settings.messages
		title: Loc.getMessage('CRM_AUTORUN_BATCH_SET_OPENED_TITLE'),
		summaryCaption: Loc.getMessage('CRM_AUTORUN_BATCH_SET_OPENED_SUMMARY_CAPTION'),
		summarySucceeded: Loc.getMessage('CRM_AUTORUN_BATCH_SET_OPENED_SUMMARY_SUCCEEDED'),
		summaryFailed: Loc.getMessage('CRM_AUTORUN_BATCH_SET_OPENED_SUMMARY_FAILED'),
	};

	static items = {};

	#isOpened: boolean;

	static getItem(id): ?BatchSetOpenedManager
	{
		return BX.prop.get(BatchSetOpenedManager.items, id, null);
	}

	static create(id, settings): BatchSetOpenedManager
	{
		const self = new BatchSetOpenedManager(id, settings);
		BatchSetOpenedManager.items[self.getId()] = self;

		return self;
	}

	getIdPrefix(): string
	{
		return 'crm_batch_set_stage_mgr';
	}

	getEventNamespacePostfix(): string
	{
		return 'BatchSetOpenedManager';
	}

	getPrepareActionName(): string
	{
		return 'crm.api.autorun.setopened.prepare';
	}

	getPrepareActionParams(): Object
	{
		const params = super.getPrepareActionParams();

		params.isOpened = this.#isOpened ? 'Y' : 'N';

		return params;
	}

	getProcessActionName(): string
	{
		return 'crm.api.autorun.setopened.process';
	}

	getCancelActionName(): string
	{
		return 'crm.api.autorun.setopened.cancel';
	}

	setIsOpened(isOpened: boolean | 'Y' | 'N'): void
	{
		if (Type.isString(isOpened))
		{
			this.#isOpened = isOpened === 'Y';
		}
		else
		{
			this.#isOpened = Boolean(isOpened);
		}
	}
}
