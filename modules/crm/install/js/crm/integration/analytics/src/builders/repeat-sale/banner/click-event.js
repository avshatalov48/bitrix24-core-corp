import { Dictionary } from '../../../dictionary';
import { filterOutNilValues, getCrmMode } from '../../../helpers';
import type { RepeatSaleBannerClickEvent } from '../../../types';

/**
 * @memberof BX.Crm.Integration.Analytics.Builder.RepeatSale.Banner
 */
export class ClickEvent
{
	#type: RepeatSaleBannerClickEvent['type'];
	#section: RepeatSaleBannerClickEvent['c_section'] = Dictionary.SECTION_DEAL;
	#subSection: RepeatSaleBannerClickEvent['c_sub_section'] = Dictionary.SUB_SECTION_KANBAN;
	#element: RepeatSaleBannerClickEvent['c_element'];
	#period: number;

	static createDefault(
		type: RepeatSaleBannerClickEvent['type'],
		subSection: RepeatSaleBannerClickEvent['subSection'],
	): ClickEvent
	{
		const self: ClickEvent = new ClickEvent();

		self.#type = type;
		self.#subSection = subSection;

		return self;
	}

	setElement(element: RepeatSaleBannerClickEvent['c_element']): ClickEvent
	{
		this.#element = element;

		return this;
	}

	setPeriod(period: number): ClickEvent
	{
		this.#period = period;

		return this;
	}

	setSection(section: RepeatSaleBannerClickEvent['c_section']): ClickEvent
	{
		this.#section = section;

		return this;
	}

	buildData(): ?RepeatSaleBannerClickEvent
	{
		return filterOutNilValues({
			tool: Dictionary.TOOL_CRM,
			category: Dictionary.CATEGORY_BANNERS,
			event: Dictionary.EVENT_REPEAT_SALE_BANNER_CLICK,
			type: this.#type,
			c_section: this.#section,
			c_sub_section: this.#subSection,
			c_element: this.#element,
			p1: getCrmMode(),
			p3: this.#period ? `period_${this.#period}` : null,
		});
	}
}
