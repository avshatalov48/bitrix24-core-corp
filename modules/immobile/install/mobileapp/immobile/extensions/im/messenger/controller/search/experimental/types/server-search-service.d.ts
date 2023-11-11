import {RecentProviderItem} from "./search-item";

export type RecentSearchResult = {
	dialog: {
		id: string,
		entities: Array<object>,
		items: Array<RecentProviderItem>,
		recentItems: Array<[string, string | number]>,
		tabs: Array<any>,
	}
};