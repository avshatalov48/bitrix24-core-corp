/* eslint-disable no-underscore-dangle */
import { Loc } from 'main.core';
import { BatchManager } from './batch-manager';

interface TemplateParams {
	messageBody: string;
	messageTemplate: string;
	fromPhone: ?string;
}

/**
 * @memberOf BX.Crm.Autorun
 */
export class BatchWhatsappMessageManager extends BatchManager
{
	static messages = {
		// default messages, you can override them via settings.messages
		title: Loc.getMessage('CRM_AUTORUN_BATCH_WHATSAPP_MESSAGE_TITLE'),
		summaryCaption: Loc.getMessage('CRM_AUTORUN_BATCH_WHATSAPP_MESSAGE_SUMMARY_CAPTION'),
		summarySucceeded: Loc.getMessage('CRM_AUTORUN_BATCH_WHATSAPP_MESSAGE_SUMMARY_SUCCEEDED'),
		summaryFailed: Loc.getMessage('CRM_AUTORUN_BATCH_WHATSAPP_MESSAGE_SUMMARY_FAILED'),
	};

	#templateParams: ?TemplateParams = null;

	setTemplateParams(templateParams: TemplateParams)
	{
		this.#templateParams = templateParams;
	}

	static #instances: Map<string, BatchWhatsappMessageManager> = new Map();

	static getInstance(id, settings): BatchWhatsappMessageManager
	{
		if (BatchWhatsappMessageManager.#instances.has(id))
		{
			return BatchWhatsappMessageManager.#instances.get(id);
		}

		const instance = new BatchWhatsappMessageManager(id, settings);
		BatchWhatsappMessageManager.#instances.set(instance.getId(), instance);

		return instance;
	}

	getPrepareActionParams(): Object
	{
		if (!this.#templateParams)
		{
			throw new Error('templateParams is required');
		}

		const params = super.getPrepareActionParams();

		return {
			...params,
			extras: {
				messageBody: this.#templateParams.messageBody,
				messageTemplate: this.#templateParams.messageTemplate,
				fromPhone: this.#templateParams.fromPhone,
			},
		};
	}

	getIdPrefix(): string
	{
		return 'crm_batch_whatsappmessage_mgr';
	}

	getEventNamespacePostfix(): string
	{
		return 'BatchWhatsAppMessageManager';
	}

	getPrepareActionName(): string
	{
		return 'crm.api.autorun.whatsappmessage.prepare';
	}

	getProcessActionName(): string
	{
		return 'crm.api.autorun.whatsappmessage.process';
	}

	getCancelActionName(): string
	{
		return 'crm.api.autorun.whatsappmessage.cancel';
	}
}
