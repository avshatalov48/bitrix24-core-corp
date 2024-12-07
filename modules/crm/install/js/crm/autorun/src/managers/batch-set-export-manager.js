/* eslint-disable no-underscore-dangle */
import { Loc, Type } from 'main.core';
import { BatchManager } from './batch-manager';

/**
 * @memberOf BX.Crm.Autorun
 */
export class BatchSetExportManager extends BatchManager
{
	static messages = {
		// default messages, you can override them via settings.messages
		title: Loc.getMessage('CRM_AUTORUN_BATCH_SET_EXPORT_TITLE'), // default message for all entity types
		summaryCaption: Loc.getMessage('CRM_AUTORUN_BATCH_SET_EXPORT_SUMMARY_CAPTION'),
		summarySucceeded: Loc.getMessage('CRM_AUTORUN_BATCH_SET_EXPORT_SUMMARY_SUCCEEDED'),
		summaryFailed: Loc.getMessage('CRM_AUTORUN_BATCH_SET_EXPORT_SUMMARY_FAILED'),
	};

	static items = {};

	#export: boolean;

	static getItem(id): ?BatchSetExportManager
	{
		return BX.prop.get(BatchSetExportManager.items, id, null);
	}

	static create(id, settings): BatchSetExportManager
	{
		const self = new BatchSetExportManager(id, settings);
		BatchSetExportManager.items[self.getId()] = self;

		return self;
	}

	getDefaultMessages(): { [p: string]: string }
	{
		const messages = super.getDefaultMessages();

		const entityTypeName = BX.CrmEntityType.resolveName(this._entityTypeId);

		/**
		 * CRM_AUTORUN_BATCH_SET_EXPORT_TITLE_CONTACT
		 */
		const specificTitle = Loc.getMessage(
			`CRM_AUTORUN_BATCH_SET_EXPORT_TITLE_${entityTypeName}`,
		);
		if (Type.isStringFilled(specificTitle))
		{
			messages.title = specificTitle;
		}

		return messages;
	}

	getIdPrefix(): string
	{
		return 'crm_batch_set_export_mgr';
	}

	getEventNamespacePostfix(): string
	{
		return 'BatchSetExportManager';
	}

	getPrepareActionName(): string
	{
		return 'crm.api.autorun.setexport.prepare';
	}

	getPrepareActionParams(): Object
	{
		const params = super.getPrepareActionParams();

		params.export = this.#export ? 'Y' : 'N';

		return params;
	}

	getProcessActionName(): string
	{
		return 'crm.api.autorun.setexport.process';
	}

	getCancelActionName(): string
	{
		return 'crm.api.autorun.setexport.cancel';
	}

	setExport(isExport: boolean | 'Y' | 'N'): void
	{
		if (Type.isString(isExport))
		{
			this.#export = isExport === 'Y';
		}
		else
		{
			this.#export = Boolean(isExport);
		}
	}
}
