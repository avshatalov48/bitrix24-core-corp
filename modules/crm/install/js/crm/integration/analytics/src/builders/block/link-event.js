import { Dictionary } from '../../dictionary';
import { filterOutNilValues, getAnalyticsEntityType, getCrmMode } from '../../helpers';
import type { BlockLinkEvent as BlockLinkEventStructure } from '../../types';

/**
 * @memberof BX.Crm.Integration.Analytics.Builder.Block
 */
export class LinkEvent
{
	#entityType: string | number | null;
	#element: ?BlockLinkEventStructure['c_element'] = Dictionary.ELEMENT_ITEM_CONTACT_CENTER;
	#type: ?BlockLinkEventStructure['type'] = Dictionary.TYPE_CONTACT_CENTER;

	static createDefault(entityType: string | number): LinkEvent
	{
		const self = new LinkEvent();

		self.#entityType = entityType;

		return self;
	}

	setElement(element: ?BlockLinkEventStructure['c_element']): LinkEvent
	{
		this.#element = element;

		return this;
	}

	setType(type: ?BlockLinkEventStructure['type']): LinkEvent
	{
		this.#type = type;

		return this;
	}

	buildData(): ?BlockLinkEventStructure
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
			event: Dictionary.EVENT_BLOCK_LINK,
			type: this.#type,
			c_section: `${type}_section`,
			c_sub_section: Dictionary.SUB_SECTION_KANBAN,
			c_element: this.#element,
			p1: getCrmMode(),
		});
	}
}
