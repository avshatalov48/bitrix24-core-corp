import { Dictionary } from '../../../dictionary';
import { filterOutNilValues, getCrmMode } from '../../../helpers';
import type { RepeatSaleBannerViewEvent } from '../../../types';

/**
 * @memberof BX.Crm.Integration.Analytics.Builder.RepeatSale.Banner
 */
export class ViewEvent
{
	#section: RepeatSaleBannerViewEvent['c_section'] = Dictionary.SECTION_DEAL;
	#subSection: RepeatSaleBannerViewEvent['c_sub_section'] = Dictionary.SUB_SECTION_KANBAN;
	#type: RepeatSaleBannerViewEvent['type'];

	static createDefault(
		type: RepeatSaleBannerViewEvent['type'],
		subSection: RepeatSaleBannerViewEvent['subSection'],
	): ViewEvent
	{
		const self: ViewEvent = new ViewEvent();

		self.#type = type;
		self.#subSection = subSection;

		return self;
	}

	setSection(section: RepeatSaleBannerViewEvent['c_section']): ViewEvent
	{
		this.#section = section;

		return this;
	}

	buildData(): ?RepeatSaleBannerViewEvent
	{
		return filterOutNilValues({
			tool: Dictionary.TOOL_CRM,
			category: Dictionary.CATEGORY_BANNERS,
			event: Dictionary.EVENT_REPEAT_SALE_BANNER_VIEW,
			type: this.#type,
			c_section: this.#section,
			c_sub_section: this.#subSection,
			p1: getCrmMode(),
		});
	}
}
