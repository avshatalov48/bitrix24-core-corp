import { Text, Type } from 'main.core';
import { Dictionary } from '../../../dictionary';
import { filterOutNilValues, getCrmMode } from '../../../helpers';
import type { EventStatus } from '../../../types';

type Element = Dictionary.ELEMENT_DELETE_BUTTON | Dictionary.ELEMENT_GRID_ROW_CONTEXT_MENU;

/**
 * @memberof BX.Crm.Integration.Analytics.Builder.Automation.Type
 */
export class DeleteEvent
{
	#subSection: string;
	#status: EventStatus;
	#isExternal: boolean;
	#element: string;
	#id: ?number;

	setIsExternal(isExternal: boolean): DeleteEvent
	{
		this.#isExternal = Boolean(isExternal);

		return this;
	}

	setSubSection(subSection: ?string): DeleteEvent
	{
		this.#subSection = Type.isNil(subSection) ? null : String(subSection);

		return this;
	}

	setStatus(status: EventStatus): DeleteEvent
	{
		this.#status = status;

		return this;
	}

	setElement(element: Element): DeleteEvent
	{
		this.#element = element;

		return this;
	}

	setId(id: number): DeleteEvent
	{
		this.#id = Text.toInteger(id);
		if (this.#id <= 0)
		{
			this.#id = null;
		}

		return this;
	}

	buildData(): ?Object
	{
		return filterOutNilValues({
			tool: Dictionary.TOOL_CRM,
			category: Dictionary.CATEGORY_AUTOMATION_OPERATIONS,
			event: Dictionary.EVENT_AUTOMATION_DELETE,
			type: Dictionary.TYPE_DYNAMIC,
			c_section: this.#isExternal ? Dictionary.SECTION_AUTOMATION : Dictionary.SECTION_CRM,
			c_sub_section: this.#subSection,
			c_element: this.#element,
			status: this.#status,
			p1: getCrmMode(),
			p2: this.#id > 0 ? `id_${this.#id}` : null,
		});
	}
}
