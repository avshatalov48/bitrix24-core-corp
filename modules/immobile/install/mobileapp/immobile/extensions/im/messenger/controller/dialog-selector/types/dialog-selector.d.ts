import {RecentCarouselItem} from "../../../lib/converter/types/search";

export type RecentUserCarouselItem = {
	type: 'carousel',
	sectionCode: 'custom',
	childItems: Array<RecentCarouselItem>,
	hideBottomLine: true,
}