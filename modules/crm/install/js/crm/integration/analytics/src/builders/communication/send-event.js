import { Text } from 'main.core';
import { Dictionary } from '../../dictionary';
import { filterOutNilValues, getAnalyticsEntityType, getCrmMode } from '../../helpers';
import type { WhatsAppSendEvent as WhatsAppSendEventStructure } from '../../types';

/**
 * @memberof BX.Crm.Integration.Analytics.Builder.Communication
 */
export class SendEvent
{
	#entityType: string | number | null;
	#event: ?WhatsAppSendEventStructure['event'];
	#element: ?WhatsAppSendEventStructure['c_element'];
	#subSection: ?WhatsAppSendEventStructure['c_sub_section'];
	#contactsCount: string | number | null;
	#templateId: number | null;
	#resend: boolean = false;

	static createDefault(entityType: string | number): SendEvent
	{
		const self = new SendEvent();

		self.#entityType = entityType;

		return self;
	}

	setSubSection(subSection: ?WhatsAppSendEventStructure['c_sub_section']): SendEvent
	{
		this.#subSection = subSection;

		return this;
	}

	setElement(element: ?WhatsAppSendEventStructure['c_element']): SendEvent
	{
		this.#element = element;

		return this;
	}

	setEvent(event: ?WhatsAppSendEventStructure['event']): SendEvent
	{
		this.#event = event;

		return this;
	}

	setContactsCount(count: number | string): SendEvent
	{
		if (count === 'all')
		{
			this.#contactsCount = 'all';
		}
		else
		{
			this.#contactsCount = Text.toInteger(count);
			if (this.#contactsCount <= 0)
			{
				this.#contactsCount = null;
			}
		}

		return this;
	}

	setTemplateId(id: number): SendEvent
	{
		this.#templateId = Text.toInteger(id);
		if (this.#templateId <= 0)
		{
			this.#templateId = null;
		}

		return this;
	}

	setResend(): SendEvent
	{
		this.#resend = true;

		return this;
	}

	buildData(): ?WhatsAppSendEventStructure
	{
		const type = getAnalyticsEntityType(this.#entityType);
		if (!type)
		{
			console.error('crm.integration.analytics: Unknown entity type');

			return null;
		}

		return filterOutNilValues({
			tool: Dictionary.TOOL_CRM,
			category: Dictionary.CATEGORY_COMMUNICATION_OPERATIONS,
			event: this.#event,
			type: Dictionary.TYPE_WA_ACTIVITY_CREATE,
			c_section: `${type}_section`,
			c_sub_section: this.#subSection,
			c_element: this.#element,
			p1: getCrmMode(),
			p2: this.#contactsCount,
			p3: this.#templateId,
			p4: this.#resend ? 'resend' : null,
		});
	}
}
