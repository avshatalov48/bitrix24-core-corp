/* eslint-disable no-underscore-dangle */
import { Loc, Text } from 'main.core';
import { BatchManager } from './batch-manager';

/**
 * @memberOf BX.Crm.Autorun
 */
export class BatchAssignmentManager extends BatchManager
{
	static messages = {
		// default messages, you can override them via settings.messages
		title: Loc.getMessage('CRM_AUTORUN_BATCH_ASSIGN_TITLE'),
		summaryCaption: Loc.getMessage('CRM_AUTORUN_BATCH_ASSIGN_SUMMARY_CAPTION'),
		summarySucceeded: Loc.getMessage('CRM_AUTORUN_BATCH_ASSIGN_SUMMARY_SUCCEEDED'),
		summaryFailed: Loc.getMessage('CRM_AUTORUN_BATCH_ASSIGN_SUMMARY_FAILED'),
	};

	static items = {};

	#assignedById: number;

	static getItem(id): ?BatchAssignmentManager
	{
		return BX.prop.get(BatchAssignmentManager.items, id, null);
	}

	static create(id, settings): BatchAssignmentManager
	{
		const self = new BatchAssignmentManager(id, settings);
		BatchAssignmentManager.items[self.getId()] = self;

		return self;
	}

	getIdPrefix(): string
	{
		return 'crm_batch_assignment_mgr';
	}

	getEventNamespacePostfix(): string
	{
		return 'BatchAssignmentManager';
	}

	getPrepareActionName(): string
	{
		return 'crm.api.autorun.assign.prepare';
	}

	getPrepareActionParams(): Object
	{
		const params = super.getPrepareActionParams();

		params.assignedById = this.#assignedById;

		return params;
	}

	getProcessActionName(): string
	{
		return 'crm.api.autorun.assign.process';
	}

	getCancelActionName(): string
	{
		return 'crm.api.autorun.assign.cancel';
	}

	setAssignedById(assignedById: number): void
	{
		this.#assignedById = Text.toInteger(assignedById);
	}
}
