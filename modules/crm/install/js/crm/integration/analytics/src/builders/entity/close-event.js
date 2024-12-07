import { Dictionary } from '../../dictionary';
import { filterOutNilValues, getAnalyticsEntityType, getCrmMode } from '../../helpers';
import type { EntityCloseEvent as EntityCloseEventStructure } from '../../types';

/**
 * @memberof BX.Crm.Integration.Analytics.Builder.Entity
 */
export class CloseEvent
{
	#entityType: string | number | null;
	#subSection: ?EntityCloseEventStructure['c_sub_section'];
	#element: ?EntityCloseEventStructure['c_element'];
	#entityId: string | number;

	static createDefault(entityType: string | number, entityId: string | number): CloseEvent
	{
		const self = new CloseEvent();

		self.#entityType = entityType;
		self.#entityId = entityId;

		return self;
	}

	setSubSection(subSection: ?EntityCloseEventStructure['c_sub_section']): CloseEvent
	{
		this.#subSection = subSection;

		return this;
	}

	setElement(element: ?EntityCloseEventStructure['c_element']): CloseEvent
	{
		this.#element = element;

		return this;
	}

	buildData(): ?EntityCloseEventStructure
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
			event: Dictionary.EVENT_ENTITY_CLOSE,
			type,
			c_section: `${type}_section`,
			c_sub_section: this.#subSection,
			c_element: this.#element,
			p1: getCrmMode(),
			p2: this.#entityId,
		});
	}
}
