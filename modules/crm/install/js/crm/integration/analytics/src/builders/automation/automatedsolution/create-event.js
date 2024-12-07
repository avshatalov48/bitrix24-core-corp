import { Text, Type } from 'main.core';
import { Dictionary } from '../../../dictionary';
import { filterOutNilValues, getCrmMode } from '../../../helpers';
import type { EventStatus } from '../../../types';

type Element = Dictionary.ELEMENT_CREATE_BUTTON | Dictionary.ELEMENT_CANCEL_BUTTON | Dictionary.ELEMENT_ESC_BUTTON;

/**
 * @memberof BX.Crm.Integration.Analytics.Builder.Automation.AutomatedSolution
 */
export class CreateEvent
{
	#element: Element;
	#status: EventStatus;
	#id: ?number;
	#typeIds: number[] = [];

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

	setTypeIds(ids: number[]): CreateEvent
	{
		if (Type.isArrayFilled(ids))
		{
			this.#typeIds = ids
				.map((id) => Text.toInteger(id))
				.filter((id) => id > 0)
				.sort()
			;
		}

		return this;
	}

	buildData(): ?Object
	{
		return filterOutNilValues({
			tool: Dictionary.TOOL_CRM,
			category: Dictionary.CATEGORY_AUTOMATION_OPERATIONS,
			event: Dictionary.EVENT_AUTOMATION_CREATE,
			type: Dictionary.TYPE_AUTOMATED_SOLUTION,
			c_section: Dictionary.SECTION_AUTOMATION,
			c_element: this.#element,
			status: this.#status,
			p1: getCrmMode(),
			p2: this.#id > 0 ? `id_${this.#id}` : null,
			p3: Type.isArrayFilled(this.#typeIds) ? `typeIds_${this.#typeIds.join(',')}` : null,
		});
	}
}
