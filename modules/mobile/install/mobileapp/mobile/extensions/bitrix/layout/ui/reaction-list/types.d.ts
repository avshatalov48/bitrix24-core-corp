import { PageManager } from '../../../../../../../../dev/janative/api';

export interface ReactionListViewProps {
	scrollPanelProps: ScrollPanelProps,
	userListProps: Omit<UserListProps, 'layout' | 'selectedTab'>,
	backdropMediumPositionPercent?: number,
}

export type ReactionListControllerProps = { voteSignToken: string } & ScrollPanelProps;

type ScrollPanelProps = ScrollPanelPropsWithoutRedux | ScrollPanelPropsWithRedux;

interface ScrollPanelPropsWithRedux extends Entity {
	withRedux: true;
}

interface ScrollPanelPropsWithoutRedux {
	withRedux: false;
	reactions: ReactionProps[];
}

interface ReactionProps {
	reactionId: string,
	userIds: Array<number>,
}

export interface UserListProps {
	layout: PageManager,
	itemTypeWithRedux?: boolean,
	selectedTab: string,
	actions: Record<string, string>,
	actionParams: (selectedTab: string) => Record<string, object>,
	onBeforeItemsRender: (items: object[], selectedTab: string, parentWidget: PageManager) => object,
	actionCallbacks?: Record<string, () => any>,
	itemsLoadLimit?: number,
	actionResponseAdapter?: (response: object) => object,
	pull?: {
		moduleId: string,
		callback: (items: Array<any>, selectedTab: string, parentWidget: PageManager) => any,
		shouldReloadDynamically: boolean,
	},
}

interface Entity {
	entityType: string,
	entityId: string,
}
