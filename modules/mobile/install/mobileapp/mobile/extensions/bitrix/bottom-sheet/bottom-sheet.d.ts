/**
 * Options object for configuring a widget.
 */
interface BottomSheetWidgetOptions {
	modal: boolean;
	title: string;
	enableNavigationBarBorder: boolean;
	backgroundColor: string;
	backdrop: BottomSheetBackdropOptions;
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
}
