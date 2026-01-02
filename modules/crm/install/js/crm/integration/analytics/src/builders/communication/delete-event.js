import { Dictionary } from '../../dictionary';
import { filterOutNilValues, getAnalyticsEntityType, getCrmMode } from '../../helpers';
import type { WhatsAppDeleteEvent as WhatsAppDeleteEventStructure } from '../../types';

/**
 * @memberof BX.Crm.Integration.Analytics.Builder.Communication
 */
export class DeleteEvent
{
	#entityType: string | number | null;
	#element: ?WhatsAppDeleteEventStructure['c_element'];

	static createDefault(entityType: string | number): DeleteEvent
	{
		const self = new DeleteEvent();

		self.#entityType = entityType;

		return self;
	}

	setElement(element: ?WhatsAppDeleteEventStructure['c_element']): DeleteEvent
	{
		this.#element = element;

		return this;
	}

	buildData(): ?WhatsAppDeleteEventStructure
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
			event: Dictionary.EVENT_WA_DELETE,
			type: Dictionary.TYPE_WA_ACTIVITY_DELETE,
			c_section: `${type}_section`,
			c_sub_section: Dictionary.SUB_SECTION_DETAILS,
			c_element: this.#element,
			p1: getCrmMode(),
		});
	}
}
