import { Text } from 'main.core';
import { Dictionary } from '../../dictionary';
import { filterOutNilValues, getAnalyticsEntityType, getCrmMode } from '../../helpers';
import type { EntityConvertEvent as EntityConvertEventStructure, EventStatus } from '../../types';

/**
 * @memberof BX.Crm.Integration.Analytics.Builder.Entity
 */
export class ConvertEvent
{
	#srcEntityType: string | number | null;
	#dstEntityType: string | number | null;
	#section: ?EntityConvertEventStructure['c_section'];
	#subSection: ?EntityConvertEventStructure['c_sub_section'];
	#element: ?EntityConvertEventStructure['c_element'];
	#status: ?EventStatus;

	static createDefault(srcEntityType: string | number, dstEntityType: string | number): ConvertEvent
	{
		const self = new ConvertEvent();

		self.#srcEntityType = srcEntityType;
		self.#dstEntityType = dstEntityType;

		return self;
	}

	setSection(section: ?EntityConvertEventStructure['c_section']): ConvertEvent
	{
		this.#section = section;

		return this;
	}

	setSubSection(subSection: ?EntityConvertEventStructure['c_sub_section']): ConvertEvent
	{
		this.#subSection = subSection;

		return this;
	}

	setElement(element: ?EntityConvertEventStructure['c_element']): ConvertEvent
	{
		this.#element = element;

		return this;
	}

	setStatus(status: ?EventStatus): ConvertEvent
	{
		this.#status = status;

		return this;
	}

	buildData(): ?EntityConvertEventStructure
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
			event: Dictionary.EVENT_ENTITY_CONVERT,
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
