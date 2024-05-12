import { Dictionary } from '../../dictionary';
import { filterOutNilValues, getAnalyticsEntityType, getCrmMode } from '../../helpers';
import type { EntityAddEvent as EntityAddEventStructure } from '../../types';

/**
 * @memberof BX.Crm.Integration.Analytics.Builder.Entity
 */
export class AddEvent
{
	#entityType: string | number | null;
	#subSection: ?EntityAddEventStructure['c_sub_section'];
	#element: ?EntityAddEventStructure['c_element'];

	static createDefault(entityType: string | number): AddEvent
	{
		const self = new AddEvent();

		self.#entityType = entityType;

		return self;
	}

	setSubSection(subSection: ?EntityAddEventStructure['c_sub_section']): AddEvent
	{
		this.#subSection = subSection;

		return this;
	}

	setElement(element: ?EntityAddEventStructure['c_element']): AddEvent
	{
		this.#element = element;

		return this;
	}

	buildData(): ?EntityAddEventStructure
	{
		const type = getAnalyticsEntityType(this.#entityType);
		if (!type)
		{
			console.error('crm.integration.analytics: Unknown entity type');

			return null;
		}

		return filterOutNilValues({
			tool: Dictionary.TOOL_CRM,
			category: Dictionary.CATEGORY_ENTITY_OPERATIONS,
			event: Dictionary.EVENT_ENTITY_ADD,
			type,
			c_section: `${type}_section`,
			c_sub_section: this.#subSection,
			c_element: this.#element,
			p1: getCrmMode(),
		});
	}
}
