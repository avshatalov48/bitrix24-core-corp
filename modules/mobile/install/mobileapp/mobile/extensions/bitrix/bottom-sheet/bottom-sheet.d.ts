/**
 * Options object for configuring a widget.
 */
interface BottomSheetWidgetOptions {
	modal: boolean;
	// deprecated - title: string;
	titleParams: Partial<TitleParams>;
	enableNavigationBarBorder: boolean;
	backgroundColor: string;
	backdrop: Partial<BottomSheetBackdropOptions>;
}

/**
 * Options object for configuring a navigation title.
 */
interface TitleParams {
	type: 'section' | 'entity' | 'wizard' | 'dialog' | 'common';
	text: string;
	textColor: string;
	detailText: string;
	detailTextColor: string;
	useLargeTitleMode: boolean;
	useProgress: boolean;
	svg: object;
	isRounded: boolean;
	tintColor: string;
}

/**
 * Options object for configuring a backdrop.
 */
interface BottomSheetBackdropOptions {
	showOnTop: boolean;
	topPosition: number;
	onlyMediumPosition: boolean;
	mediumPositionPercent: number;
	mediumPositionHeight: number;
	hideNavigationBar: boolean;
	navigationBarColor: string;
	swipeAllowed: boolean;
	swipeContentAllowed: boolean;
	horizontalSwipeAllowed: boolean;
	forceDismissOnSwipeDown: boolean;
	shouldResizeContent: boolean;
	helpUrl: string;
	bounceEnable: boolean;
	adoptHeightByKeyboard: boolean;
}
