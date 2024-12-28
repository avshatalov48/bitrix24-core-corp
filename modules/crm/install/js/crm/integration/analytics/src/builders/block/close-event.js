import { Dictionary } from '../../dictionary';
import { filterOutNilValues, getAnalyticsEntityType, getCrmMode } from '../../helpers';
import type { BlockCloseEvent as BlockCloseEventStructure } from '../../types';

/**
 * @memberof BX.Crm.Integration.Analytics.Builder.Block
 */
export class CloseEvent
{
	#entityType: string | number | null;
	#subSection: ?BlockCloseEventStructure['c_sub_section'] = Dictionary.SUB_SECTION_KANBAN;
	#element: ?BlockCloseEventStructure['c_element'];
	#type: ?BlockCloseEventStructure['type'] = Dictionary.TYPE_CONTACT_CENTER;

	static createDefault(entityType: string | number): CloseEvent
	{
		const self = new CloseEvent();

		self.#entityType = entityType;

		return self;
	}

	setSubSection(subSection: ?BlockCloseEventStructure['c_sub_section']): CloseEvent
	{
		this.#subSection = subSection;

		return this;
	}

	setElement(element: ?BlockCloseEventStructure['c_element']): CloseEvent
	{
		this.#element = element;

		return this;
	}

	setType(type: ?BlockCloseEventStructure['type']): CloseEvent
	{
		this.#type = type;

		return this;
	}

	buildData(): ?BlockCloseEventStructure
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
			event: Dictionary.EVENT_BLOCK_CLOSE,
			type: this.#type,
			c_section: `${type}_section`,
			c_sub_section: this.#subSection,
			c_element: this.#element,
			p1: getCrmMode(),
		});
	}
}