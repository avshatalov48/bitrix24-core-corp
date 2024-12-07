/* eslint-disable no-underscore-dangle */
import { Loc } from 'main.core';
import { BatchManager } from './batch-manager';

/**
 * @memberOf BX.Crm.Autorun
 */
export class BatchSetStageManager extends BatchManager
{
	static messages = {
		// default messages, you can override them via settings.messages
		title: Loc.getMessage('CRM_AUTORUN_BATCH_SET_STAGE_TITLE'),
		summaryCaption: Loc.getMessage('CRM_AUTORUN_BATCH_SET_STAGE_SUMMARY_CAPTION'),
		summarySucceeded: Loc.getMessage('CRM_AUTORUN_BATCH_SET_STAGE_SUMMARY_SUCCEEDED'),
		summaryFailed: Loc.getMessage('CRM_AUTORUN_BATCH_SET_STAGE_SUMMARY_FAILED'),
	};

	static items = {};

	#stageId: string;

	static getItem(id): ?BatchSetStageManager
	{
		return BX.prop.get(BatchSetStageManager.items, id, null);
	}

	static create(id, settings): BatchSetStageManager
	{
		const self = new BatchSetStageManager(id, settings);
		BatchSetStageManager.items[self.getId()] = self;

		return self;
	}

	getIdPrefix(): string
	{
		return 'crm_batch_set_stage_mgr';
	}

	getEventNamespacePostfix(): string
	{
		return 'BatchSetStageManager';
	}

	getPrepareActionName(): string
	{
		return 'crm.api.autorun.setstage.prepare';
	}

	getPrepareActionParams(): Object
	{
		const params = super.getPrepareActionParams();

		params.stageId = this.#stageId;

		return params;
	}

	getProcessActionName(): string
	{
		return 'crm.api.autorun.setstage.process';
	}

	getCancelActionName(): string
	{
		return 'crm.api.autorun.setstage.cancel';
	}

	setStageId(stageId: string): void
	{
		this.#stageId = String(stageId);
	}
}
