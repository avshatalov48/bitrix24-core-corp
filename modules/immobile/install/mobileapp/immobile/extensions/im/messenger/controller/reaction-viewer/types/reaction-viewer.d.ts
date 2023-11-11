import {ReactionType} from "../../../model/types/messages/reactions";
import {ReactionServiceGetData} from "../../../provider/service/types/reaction";

declare interface ReactionViewerUser {
	id: number;
	reactionId: number;
	name: string;
	color: string;
	avatar?: string;
	reaction: ReactionType;
}

type ReactionViewerProps = {
	// @ts-ignore
	users: Map<ReactionType,ReactionViewerUser[]>,

	// @ts-ignore
	counters: Map<ReactionType, number>
	currentReaction: ReactionType,
	// @ts-ignore
	hasNextPage: Map<ReactionType, boolean>
	onReactionChange: (reactionType: ReactionType) => Promise<ReactionServiceGetData>
	onLoadMore: (reactionType: ReactionType, lastReactionId: number) => Promise<ReactionServiceGetData>
	onReactionUserClick: (userId: number) => void
}

type ReactionViewerState = {

	currentReaction: ReactionType,
	// @ts-ignore
	visibleReactions: Map<ReactionType, string>,
	// @ts-ignore
	counters: Map<ReactionType, number>
	// @ts-ignore
	reactionUsers: Map<ReactionType, ReactionViewerUser[]>
	// @ts-ignore
	hasNextPage: Map<ReactionType, boolean>
}

type ReactionItemProps = {
	reactionType: ReactionType,
	imageUrl: string,
	isCurrent: boolean,
	counter: number,
	onClick: (reactionType: ReactionType) => void,
	// @ts-ignore
	eventEmitter: JNEventEmitter
}

type ReactionItemState = {
	isCurrent: boolean,
}

type ReactionViewerListProps = {
	users: ReactionViewerUser[],
	hasNextPage: boolean,
	onLoadMore: (lastReactionId) => Promise<ReactionServiceGetData>,
	onReactionUserClick: (userId: number) => void,
}

type ReactionViewerListState = {
	users: ReactionViewerListItem[],
}

declare interface ReactionViewerListItem extends ReactionViewerUser
{
	key: string;
	type: 'user';
}