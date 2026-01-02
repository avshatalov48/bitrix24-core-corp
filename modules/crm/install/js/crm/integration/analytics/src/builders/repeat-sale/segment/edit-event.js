import { Dictionary } from '../../../dictionary';
import { filterOutNilValues } from '../../../helpers';
import type { RepeatSaleSegmentEditEvent } from '../../../types';

/**
 * @memberof BX.Crm.Integration.Analytics.Builder.RepeatSale.Segment
 */
export class EditEvent
{
	#section: RepeatSaleSegmentEditEvent['c_section'] = Dictionary.SECTION_DEAL;
	#isActivityTextChanged: boolean = false;
	#isEntityTitlePatternChanged: boolean = false;
	#isCopilotEnabled: ?boolean = null;
	#segmentCode: ?string = null;

	static createDefault(section: RepeatSaleSegmentEditEvent['subSection']): EditEvent
	{
		const self: EditEvent = new EditEvent();

		self.#section = section;

		return self;
	}

	setIsCopilotEnabled(isCopilotEnabled: boolean): EditEvent
	{
		this.#isCopilotEnabled = isCopilotEnabled;

		return this;
	}

	setIsActivityTextChanged(isActivityTextChanged: boolean): EditEvent
	{
		this.#isActivityTextChanged = isActivityTextChanged;

		return this;
	}

	setIsEntityTitlePatternChanged(isEntityTitlePatternChanged: boolean): EditEvent
	{
		this.#isEntityTitlePatternChanged = isEntityTitlePatternChanged;

		return this;
	}

	setSegmentCode(code: string): EditEvent
	{
		this.#segmentCode = code;

		return this;
	}

	#getP5(): ?string
	{
		switch (this.#segmentCode)
		{
			case 'deal_activity_less_12_month':
				return 'deal-activity-less-12m';
			case 'deal_lost_more_12_month':
				return 'deal-lost-more-12m';
			case 'deal_every_year':
				return 'deal-annual';
			case 'deal_every_half_year':
				return 'deal-semiannual';
			case 'deal_every_month_year':
				return 'deal-month-yr';
			default:
				return null;
		}
	}

	buildData(): ?RepeatSaleSegmentEditEvent
	{
		let p1 = null;
		if (this.#isCopilotEnabled === true)
		{
			p1 = 'scenario-copilot-enable-on';
		}
		else if (this.#isCopilotEnabled === false)
		{
			p1 = 'scenario-copilot-enable-off';
		}

		return filterOutNilValues({
			tool: Dictionary.TOOL_CRM,
			category: Dictionary.CATEGORY_EDITOR,
			event: Dictionary.EVENT_REPEAT_SALE_SEGMENT_EDIT,
			type: Dictionary.TYPE_REPEAT_SALE_SEGMENT,
			c_section: this.#section,
			p1,
			p2: this.#isActivityTextChanged ? 'scenario-text-deal-box' : null,
			p3: this.#isEntityTitlePatternChanged ? 'scenario-deal-name' : null,
			p5: this.#getP5(),
		});
	}
}
