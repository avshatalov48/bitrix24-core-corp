import { Dictionary } from '../../dictionary';
import { filterOutNilValues, getAnalyticsEntityType, getCrmMode } from '../../helpers';
import type { BlockEnableEvent as BlockEnableEventStructure } from '../../types';

/**
 * @memberof BX.Crm.Integration.Analytics.Builder.Block
 */
export class EnableEvent
{
	#entityType: string | number | null;
	#subSection: ?BlockEnableEventStructure['c_sub_section'];
	#element: ?BlockEnableEventStructure['c_element'];
	#type: ?BlockEnableEventStructure['type'] = Dictionary.TYPE_CONTACT_CENTER;

	static createDefault(entityType: string | number): EnableEvent
	{
		const self = new EnableEvent();

		self.#entityType = entityType;

		return self;
	}

	setSubSection(subSection: ?BlockEnableEventStructure['c_sub_section']): EnableEvent
	{
		this.#subSection = subSection;

		return this;
	}

	setElement(element: ?BlockEnableEventStructure['c_element']): EnableEvent
	{
		this.#element = element;

		return this;
	}

	setType(type: ?BlockEnableEventStructure['type']): EnableEvent
	{
		this.#type = type;

		return this;
	}

	buildData(): ?BlockEnableEventStructure
	{
		const type = getAnalyticsEntityType(this.#entityType);
		if (!type)
		{
			console.error('crm.integration.analytics: Unknown entity type');

			return null;
		}

		return filterOutNilValues({
			tool: Dictionary.TOOL_CRM,
			category: Dictionary.CATEGORY_KANBAN_OPERATIONS,
			event: Dictionary.EVENT_BLOCK_ENABLE,
			type: this.#type,
			c_section: `${type}_section`,
			c_sub_section: this.#subSection,
			c_element: this.#element,
			p1: getCrmMode(),
		});
	}
}
