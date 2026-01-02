import { Dictionary } from '../../../dictionary';
import { filterOutNilValues, getCrmMode } from '../../../helpers';
import type { RepeatSaleSegmentViewEvent } from '../../../types';

/**
 * @memberof BX.Crm.Integration.Analytics.Builder.RepeatSale.Segment
 */
export class ViewEvent
{
	#section: RepeatSaleSegmentViewEvent['c_section'] = Dictionary.SECTION_DEAL;

	static createDefault(section: RepeatSaleSegmentViewEvent['subSection']): ViewEvent
	{
		const self: ViewEvent = new ViewEvent();

		self.#section = section;

		return self;
	}

	buildData(): ?RepeatSaleSegmentViewEvent
	{
		return filterOutNilValues({
			tool: Dictionary.TOOL_CRM,
			category: Dictionary.CATEGORY_EDITOR,
			event: Dictionary.EVENT_REPEAT_SALE_SEGMENT_VIEW,
			type: Dictionary.TYPE_REPEAT_SALE_SEGMENT,
			c_section: this.#section,
			p1: getCrmMode(),
		});
	}
}
