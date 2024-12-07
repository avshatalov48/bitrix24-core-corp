/* eslint-disable no-underscore-dangle */
import { Loc, Text } from 'main.core';
import { BatchManager } from './batch-manager';

/**
 * @memberOf BX.Crm.Autorun
 */
export class BatchSetCategoryManager extends BatchManager
{
	static messages = {
		// default messages, you can override them via settings.messages
		title: Loc.getMessage('CRM_AUTORUN_BATCH_SET_CATEGORY_TITLE'),
		summaryCaption: Loc.getMessage('CRM_AUTORUN_BATCH_SET_CATEGORY_SUMMARY_CAPTION'),
		summarySucceeded: Loc.getMessage('CRM_AUTORUN_BATCH_SET_CATEGORY_SUMMARY_SUCCEEDED'),
		summaryFailed: Loc.getMessage('CRM_AUTORUN_BATCH_SET_CATEGORY_SUMMARY_FAILED'),
	};

	static items = {};

	#categoryId: number;

	static getItem(id): ?BatchSetCategoryManager
	{
		return BX.prop.get(BatchSetCategoryManager.items, id, null);
	}

	static create(id, settings): BatchSetCategoryManager
	{
		const self = new BatchSetCategoryManager(id, settings);
		BatchSetCategoryManager.items[self.getId()] = self;

		return self;
	}

	getIdPrefix(): string
	{
		return 'crm_batch_set_category_mgr';
	}

	getEventNamespacePostfix(): string
	{
		return 'BatchSetCategoryManager';
	}

	getPrepareActionName(): string
	{
		return 'crm.api.autorun.setcategory.prepare';
	}

	getPrepareActionParams(): Object
	{
		const params = super.getPrepareActionParams();

		params.categoryId = this.#categoryId;

		return params;
	}

	getProcessActionName(): string
	{
		return 'crm.api.autorun.setcategory.process';
	}

	getCancelActionName(): string
	{
		return 'crm.api.autorun.setcategory.cancel';
	}

	setCategoryId(categoryId: number): void
	{
		this.#categoryId = Text.toInteger(categoryId);
	}
}
