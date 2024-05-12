import { Text } from 'main.core';
import { Dictionary } from '../../dictionary';
import { filterOutNilValues, getAnalyticsEntityType, getCrmMode } from '../../helpers';
import type { EntityConvertBatchEvent as EntityConvertBatchEventStructure, EventStatus } from '../../types';

/**
 * @memberof BX.Crm.Integration.Analytics.Builder.Entity
 */
export class ConvertBatchEvent
{
	#srcEntityType: string | number | null;
	#dstEntityType: string | number | null;
	#section: ?EntityConvertBatchEventStructure['c_section'];
	#subSection: ?EntityConvertBatchEventStructure['c_sub_section'];
	#element: ?EntityConvertBatchEventStructure['c_element'];
	#status: ?EventStatus;

	static createDefault(srcEntityType: string | number, dstEntityType: string | number): ConvertBatchEvent
	{
		const self = new ConvertBatchEvent();

		self.#srcEntityType = srcEntityType;
		self.#dstEntityType = dstEntityType;

		return self;
	}

	setSection(section: ?EntityConvertBatchEventStructure['c_section']): ConvertBatchEvent
	{
		this.#section = section;

		return this;
	}

	setSubSection(subSection: ?EntityConvertBatchEventStructure['c_sub_section']): ConvertBatchEvent
	{
		this.#subSection = subSection;

		return this;
	}

	setElement(element: ?EntityConvertBatchEventStructure['c_element']): ConvertBatchEvent
	{
		this.#element = element;

		return this;
	}

	setStatus(status: ?EventStatus): ConvertBatchEvent
	{
		this.#status = status;

		return this;
	}

	buildData(): ?EntityConvertBatchEventStructure
	{
		const srcType = getAnalyticsEntityType(this.#srcEntityType);
		const dstType = getAnalyticsEntityType(this.#dstEntityType);
		if (!srcType || !dstType)
		{
			console.error('crm.integration.analytics: Unknown entity type');

			return null;
		}

		return filterOutNilValues({
			tool: Dictionary.TOOL_CRM,
			category: Dictionary.CATEGORY_ENTITY_OPERATIONS,
			event: Dictionary.EVENT_ENTITY_CONVERT_BATCH,
			type: dstType,
			c_section: this.#section,
			c_sub_section: this.#subSection,
			c_element: this.#element,
			status: this.#status,
			p1: getCrmMode(),
			p2: `from_${Text.toCamelCase(srcType)}`,
		});
	}
}
