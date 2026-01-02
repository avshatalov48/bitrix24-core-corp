import { Dictionary } from '../../dictionary';
import { filterOutNilValues, getAnalyticsEntityType, getCrmMode } from '../../helpers';
import type { EntityChangeStageEvent as EntityChangeStageEventStructure } from '../../types';

/**
 * @memberof BX.Crm.Integration.Analytics.Builder.Entity
 */
export class ChangeStageEvent
{
	#entityType: string | number | null;
	#subSection: ?EntityChangeStageEventStructure['c_sub_section'];
	#element: ?EntityChangeStageEventStructure['c_element'];
	#status: ?EntityChangeStageEventStructure['status'];
	#countEntityChangeStage: number;

	static createDefault(entityType: string | number, countEntityChangeStage: number = 1): ChangeStageEvent
	{
		const self = new ChangeStageEvent();

		self.#entityType = entityType;
		self.#countEntityChangeStage = countEntityChangeStage;

		return self;
	}

	setSubSection(subSection: ?EntityChangeStageEventStructure['c_sub_section']): ChangeStageEvent
	{
		this.#subSection = subSection;

		return this;
	}

	setElement(element: ?EntityChangeStageEventStructure['c_element']): ChangeStageEvent
	{
		this.#element = element;

		return this;
	}

	setStatus(status: ?EntityChangeStageEventStructure['status']): ChangeStageEvent
	{
		this.#status = status;

		return this;
	}

	buildData(): ?EntityChangeStageEventStructure
	{
		const type = getAnalyticsEntityType(this.#entityType);
		if (!type)
		{
			console.error('crm.integration.analytics: Unknown entity type');

			return null;
		}

		const analyticsData = {
			tool: Dictionary.TOOL_CRM,
			category: Dictionary.CATEGORY_ENTITY_OPERATIONS,
			event: Dictionary.EVENT_ENTITY_CHANGE_STAGE,
			type,
			c_section: `${type}_section`,
			c_sub_section: this.#subSection,
			c_element: this.#element,
			status: this.#status,
			p1: getCrmMode(),
		};

		if (this.#countEntityChangeStage > 1)
		{
			analyticsData.p2 = `entityCount_${this.#countEntityChangeStage}`;
		}

		return filterOutNilValues(analyticsData);
	}
}
