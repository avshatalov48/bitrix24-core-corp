/* eslint-disable no-unused-vars */

import { DialogId } from '../../../types/common';

type SidebarWidgetHeaderButton = {
	type: string,
	id: string,
	testId: string,
};

type SidebarMenuItem = {
	id: string,
	title: string,
	icon: object, // @see assets/icons/src/main
	testId: string,
	isDestructive?: boolean,
	onItemSelected: () => {},
}

type SidebarContextMenuItem = SidebarMenuItem & {
	sort: number,
};

type SidebarPrimaryActionButton = {
	id: string,
	title: string,
	icon: object, // @see assets/icons/src/main
	onClick: () => {},
	selected?: boolean,
	disabled?: boolean,
	counter?: number,
	testIdSuffix?: string,
};

type SidebarViewDefaultProps = {
	dialogId: string,
	primaryActionButtons: SidebarPrimaryActionButton[],
	tabs: SidebarBaseTab[],
	widget: LayoutWidget,
	widgetNavigator: SidebarWidgetNavigator,
	widgetTitle: string,
	chatTitle: ChatTitle,
	isMessagesAutoDeleteDelayEnabled: boolean,
	callbacks?: {
		callUserProfile?: () => any,
	}
};

type SidebarPermissionManagerDefaultProps = {
	dialogId: string,
	dialogHelper: object,
};

type SidebarControllerProps = {
	dialogId: DialogId;
	dialogLocator: Object;
	config?: SidebarHeaderIntegrationConfigParams | {};
};

type SidebarViewTheme = {
	titleGap?: number,
};

type SidebarWidgetNavigator = {
	backToChat: () => Promise<any>,
	backToChatMessage: (number) => Promise<any>,
	backToDialogs: () => Promise<any>,
};

type SidebarTabProps = SidebarControllerProps

type UserActionProps = {
	dialogId: DialogId;
	store: Object;
}

type UserActionRestServicePropsType = UserActionProps
