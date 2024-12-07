/* eslint-disable no-underscore-dangle */
import { Loc, Type } from 'main.core';
import { MessageBox } from 'ui.dialogs.messagebox';
import { BatchManager } from './batch-manager';

/**
 * @memberOf BX.Crm.Autorun
 * @alias BX.Crm.BatchDeletionManager
 */
export class BatchDeletionManager extends BatchManager
{
	static messages = {
		// default messages, you can override them via settings.messages
		title: Loc.getMessage('CRM_AUTORUN_BATCH_DELETE_TITLE'), // default message for all entity types
		confirmation: Loc.getMessage('CRM_AUTORUN_BATCH_DELETE_CONFIRMATION'),
		confirmationTitle: Loc.getMessage('CRM_AUTORUN_BATCH_DELETE_CONFIRMATION_TITLE'), // default message for all entity types
		confirmationYesCaption: Loc.getMessage('CRM_AUTORUN_BATCH_DELETE_CONFIRMATION_YES_CAPTION'),
		summaryCaption: Loc.getMessage('CRM_AUTORUN_BATCH_DELETE_SUMMARY_CAPTION'),
		summarySucceeded: Loc.getMessage('CRM_AUTORUN_BATCH_DELETE_SUMMARY_SUCCEEDED'),
		summaryFailed: Loc.getMessage('CRM_AUTORUN_BATCH_DELETE_SUMMARY_FAILED'),
	};

	static items = {};

	static getItem(id): ?BatchDeletionManager
	{
		return BX.prop.get(BatchDeletionManager.items, id, null);
	}

	static create(id, settings): BatchDeletionManager
	{
		const self = new BatchDeletionManager(id, settings);
		BatchDeletionManager.items[self.getId()] = self;

		return self;
	}

	getDefaultMessages(): { [p: string]: string }
	{
		const messages = super.getDefaultMessages();

		const entityTypeName = BX.CrmEntityType.resolveName(this._entityTypeId);

		/**
		 * CRM_AUTORUN_BATCH_DELETE_TITLE_LEAD
		 * CRM_AUTORUN_BATCH_DELETE_TITLE_DEAL
		 * CRM_AUTORUN_BATCH_DELETE_TITLE_CONTACT
		 * CRM_AUTORUN_BATCH_DELETE_TITLE_COMPANY
		 *
		 * CRM_AUTORUN_BATCH_DELETE_CONFIRMATION_TITLE_LEAD
		 * CRM_AUTORUN_BATCH_DELETE_CONFIRMATION_TITLE_DEAL
		 * CRM_AUTORUN_BATCH_DELETE_CONFIRMATION_TITLE_CONTACT
		 * CRM_AUTORUN_BATCH_DELETE_CONFIRMATION_TITLE_COMPANY
		 */

		const specificTitle = Loc.getMessage(
			`CRM_AUTORUN_BATCH_DELETE_TITLE_${entityTypeName}`,
		);
		if (Type.isStringFilled(specificTitle))
		{
			messages.title = specificTitle;
		}

		const specificConfirmationTitle = Loc.getMessage(
			`CRM_AUTORUN_BATCH_DELETE_CONFIRMATION_TITLE_${entityTypeName}`,
		);
		if (Type.isStringFilled(specificConfirmationTitle))
		{
			messages.confirmationTitle = specificConfirmationTitle;
		}

		return messages;
	}

	getIdPrefix(): string
	{
		return 'crm_batch_deletion_mgr';
	}

	getEventNamespacePostfix(): string
	{
		return 'BatchDeletionManager';
	}

	getPrepareActionName(): string
	{
		return 'crm.api.autorun.delete.prepare';
	}

	getProcessActionName(): string
	{
		return 'crm.api.autorun.delete.process';
	}

	getCancelActionName(): string
	{
		return 'crm.api.autorun.delete.cancel';
	}

	execute()
	{
		MessageBox.confirm(
			this.getMessage('confirmation'),
			this.getMessage('confirmationTitle', '') || this.getMessage('title'),
			() => {
				super.execute();

				// to close messagebox
				return true;
			},
			this.getMessage('confirmationYesCaption'),
		);
	}
}
