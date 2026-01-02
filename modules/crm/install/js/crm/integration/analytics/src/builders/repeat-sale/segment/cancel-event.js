import { Dictionary } from '../../../dictionary';
import { filterOutNilValues, getCrmMode } from '../../../helpers';
import type { RepeatSaleSegmentCancelEvent } from '../../../types';

/**
 * @memberof BX.Crm.Integration.Analytics.Builder.RepeatSale.Segment
 */
export class CancelEvent
{
	#section: RepeatSaleSegmentCancelEvent['c_section'] = Dictionary.SECTION_DEAL;

	static createDefault(section: RepeatSaleSegmentCancelEvent['subSection']): CancelEvent
	{
		const self: CancelEvent = new CancelEvent();

		self.#section = section;

		return self;
	}

	buildData(): ?RepeatSaleSegmentCancelEvent
	{
		return filterOutNilValues({
			tool: Dictionary.TOOL_CRM,
			category: Dictionary.CATEGORY_EDITOR,
			event: Dictionary.EVENT_REPEAT_SALE_SEGMENT_CANCEL,
			type: Dictionary.TYPE_REPEAT_SALE_SEGMENT,
			c_section: this.#section,
			p1: getCrmMode(),
		});
	}
}
