import { Dictionary } from '../../dictionary';
import { filterOutNilValues, getAnalyticsEntityType, getCrmMode } from '../../helpers';
import type { WhatsAppFormEvent as WhatWhatsAppFormEventStructure } from '../../types';

/**
 * @memberof BX.Crm.Integration.Analytics.Builder.Communication
 */
export class FormEvent
{
	#entityType: string | number | null;
	#event: ?WhatWhatsAppFormEventStructure['event'];
	#element: ?WhatWhatsAppFormEventStructure['c_element'];
	#subSection: ?WhatWhatsAppFormEventStructure['c_sub_section'];

	static createDefault(entityType: string | number): FormEvent
	{
		const self = new FormEvent();

		self.#entityType = entityType;

		return self;
	}

	setSubSection(subSection: ?WhatWhatsAppFormEventStructure['c_sub_section']): FormEvent
	{
		this.#subSection = subSection;

		return this;
	}

	setElement(element: ?WhatWhatsAppFormEventStructure['c_element']): FormEvent
	{
		this.#element = element;

		return this;
	}

	setEvent(event: ?WhatWhatsAppFormEventStructure['event']): FormEvent
	{
		this.#event = event;

		return this;
	}

	buildData(): ?WhatWhatsAppFormEventStructure
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
			type: Dictionary.TYPE_WA_EDIT,
			c_section: `${type}_section`,
			c_sub_section: this.#subSection,
			c_element: this.#element,
			p1: getCrmMode(),
		});
	}
}
