import { Text, Type } from 'main.core';
import { Dictionary } from '../../../dictionary';
import { filterOutNilValues, getCrmMode } from '../../../helpers';
import type { EventStatus } from '../../../types';

type Element = Dictionary.ELEMENT_CREATE_BUTTON | Dictionary.ELEMENT_CANCEL_BUTTON;

/**
 * @memberof BX.Crm.Integration.Analytics.Builder.Automation.Type
 */
export class EditEvent
{
	#subSection: string;
	#element: Element;
	#status: EventStatus;
	#isExternal: boolean;
	#id: ?number;
	#preset: string;

	setIsExternal(isExternal: boolean): EditEvent
	{
		this.#isExternal = Boolean(isExternal);

		return this;
	}

	setSubSection(subSection: string): EditEvent
	{
		this.#subSection = String(subSection);

		return this;
	}

	setElement(element: Element): EditEvent
	{
		this.#element = element;

		return this;
	}

	setStatus(status: EventStatus): EditEvent
	{
		this.#status = status;

		return this;
	}

	setId(id: number): EditEvent
	{
		this.#id = Text.toInteger(id);
		if (this.#id <= 0)
		{
			this.#id = null;
		}

		return this;
	}

	setPreset(presetId: string): EditEvent
	{
		this.#preset = String(presetId);

		return this;
	}

	buildData(): ?Object
	{
		return filterOutNilValues({
			tool: Dictionary.TOOL_CRM,
			category: Dictionary.CATEGORY_AUTOMATION_OPERATIONS,
			event: Dictionary.EVENT_AUTOMATION_EDIT,
			type: Dictionary.TYPE_DYNAMIC,
			c_section: this.#isExternal ? Dictionary.SECTION_AUTOMATION : Dictionary.SECTION_CRM,
			c_sub_section: this.#subSection,
			c_element: this.#element,
			status: this.#status,
			p1: getCrmMode(),
			p2: this.#id > 0 ? `id_${this.#id}` : null,
			p4: Type.isStringFilled(this.#preset) ? `preset_${this.#preset}` : null,
		});
	}
}
