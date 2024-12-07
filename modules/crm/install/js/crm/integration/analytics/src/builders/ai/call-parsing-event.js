import { Text } from 'main.core';
import { Dictionary } from '../../dictionary';
import { filterOutNilValues, getAnalyticsEntityType, getCrmMode } from '../../helpers';
import type { AICallParsingEvent as CallParsingEventStructure } from '../../types';

/**
 * @memberof BX.Crm.Integration.Analytics.Builder.AI
 */
export class CallParsingEvent
{
	#entityType: string | number | null;
	#tool: CallParsingEventStructure['tool'] = Dictionary.TOOL_AI;
	#category: CallParsingEventStructure['category'] = Dictionary.CATEGORY_CRM_OPERATIONS;
	#type: CallParsingEventStructure['type'] = Dictionary.TYPE_MANUAL;
	#element: ?CallParsingEventStructure['c_element'];
	#activityId: number;
	#activityDirection: ?string;
	#status: CallParsingEventStructure['status'];

	static createDefault(
		entityType: string | number,
		activityId: number,
		status: CallParsingEventStructure['status'],
	): CallParsingEvent
	{
		const self = new CallParsingEvent();

		self.#entityType = entityType;
		self.#activityId = Text.toInteger(activityId);
		self.#status = status;

		return self;
	}

	setTool(tool: CallParsingEventStructure['tool']): CallParsingEvent
	{
		this.#tool = tool;

		return this;
	}

	setCategory(category: CallParsingEventStructure['category']): CallParsingEvent
	{
		this.#category = category;

		return this;
	}

	setType(type: CallParsingEventStructure['type']): CallParsingEvent
	{
		this.#type = type;

		return this;
	}

	setElement(element: ?CallParsingEventStructure['c_element']): CallParsingEvent
	{
		this.#element = element;

		return this;
	}

	setActivityDirection(direction: 'incoming' | 'outgoing'): CallParsingEvent
	{
		this.#activityDirection = direction;

		return this;
	}

	buildData(): ?CallParsingEventStructure
	{
		const analyticsEntityType = getAnalyticsEntityType(this.#entityType);
		if (!analyticsEntityType)
		{
			console.error('crm.integration.analytics: Unknown entity type');

			return null;
		}

		if (this.#activityId <= 0)
		{
			console.error('crm.integration.analytics: invalid activity id');

			return null;
		}

		if (this.#activityDirection !== 'incoming' && this.#activityDirection !== 'outgoing')
		{
			console.error('crm.integration.analytics: invalid activity direction', this.#activityDirection);

			return null;
		}

		return filterOutNilValues({
			tool: this.#tool,
			category: this.#category,
			event: Dictionary.EVENT_CALL_PARSING,
			type: this.#type,
			c_section: Dictionary.SECTION_CRM,
			c_sub_section: analyticsEntityType,
			c_element: this.#element,
			status: this.#status,
			p1: getCrmMode(),
			p2: `callDirection_${this.#activityDirection}`,
			p5: `idCall_${this.#activityId}`,
		});
	}
}
