import { Text, Type } from 'main.core';
import { Dictionary } from '../../../dictionary';
import { filterOutNilValues, getCrmMode } from '../../../helpers';
import type { EventStatus } from '../../../types';

type Element = Dictionary.ELEMENT_CREATE_BUTTON | Dictionary.ELEMENT_CANCEL_BUTTON;

/**
 * @memberof BX.Crm.Integration.Analytics.Builder.Automation.Type
 */
export class CreateEvent
{
	#element: Element;
	#status: EventStatus;
	#isExternal: boolean;
	#id: ?number;
	#preset: string;

	setIsExternal(isExternal: boolean): CreateEvent
	{
		this.#isExternal = Boolean(isExternal);

		return this;
	}

	setElement(element: Element): CreateEvent
	{
		this.#element = element;

		return this;
	}

	setStatus(status: EventStatus): CreateEvent
	{
		this.#status = status;

		return this;
	}

	setId(id: number): CreateEvent
	{
		this.#id = Text.toInteger(id);
		if (this.#id <= 0)
		{
			this.#id = null;
		}

		return this;
	}

	setPreset(presetId: string): CreateEvent
	{
		this.#preset = String(presetId);

		return this;
	}

	buildData(): ?Object
	{
		return filterOutNilValues({
			tool: Dictionary.TOOL_CRM,
			category: Dictionary.CATEGORY_AUTOMATION_OPERATIONS,
			event: Dictionary.EVENT_AUTOMATION_CREATE,
			type: Dictionary.TYPE_DYNAMIC,
			c_section: this.#isExternal ? Dictionary.SECTION_AUTOMATION : Dictionary.SECTION_CRM,
			c_element: this.#element,
			status: this.#status,
			p1: getCrmMode(),
			p2: this.#id > 0 ? `id_${this.#id}` : null,
			p4: Type.isStringFilled(this.#preset) ? `preset_${this.#preset}` : null,
		});
	}
}
