import {MessengerItemOnClickParams, MessengerItemProps} from "../../base/item/types/item";

declare interface SingleSelectorProps
{
	itemList: Array<MessengerItemProps>;
	buttons: Array<object>;
	onItemSelected: (params: MessengerItemOnClickParams) => any;
	searchMode: 'inline' | 'overlay';
	onSearchItemSelected: SingleSelectorProps['searchMode'] extends 'overlay'
		? (params: MessengerItemOnClickParams) => any
		: undefined
	;
	onSearchShow?: () => any; // searchMode === 'inline'
	onChangeText?: (text: string) => any; // searchMode === 'inline'
	openWithLoader?: boolean; // searchMode === 'inline' default false
	openingLoaderTitle?: string;
	ref: (ref: object) => any;
	recentText: string,
}

declare interface SingleSelectorState
{
	isSearchActive: boolean
}
