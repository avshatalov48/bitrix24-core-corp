import {ReactionType} from "../../../model/types/messages/reactions";
import {ReactionServiceGetData} from "../../../provider/service/types/reaction";

declare enum SummaryReaction
{
	all = 'all'
}

declare type AllReactions = ReactionType | SummaryReaction;
declare interface ReactionViewerUser {
	id: number;
	reactionId: number;
	name: string;
	color: string;
	avatar?: string;
	reaction: ReactionType;
	dateCreate: string,
}

type ReactionViewerProps = {
	// @ts-ignore
	users: Map<AllReactions,ReactionViewerUser[]>,

	// @ts-ignore
	counters: Map<AllReactions, number>
	currentReaction: AllReactions,
	// @ts-ignore
	hasNextPage: Map<ReactionType, boolean>
	onReactionChange: (reactionType: AllReactions) => Promise<ReactionServiceGetData>
	onLoadMore: (reactionType: AllReactions, lastReactionId: number) => Promise<ReactionServiceGetData>
	onReactionUserClick: (userId: number) => void
}

type ReactionViewerState = {

	currentReaction: AllReactions,
	// @ts-ignore
	visibleReactions: Map<AllReactions, string>,
	// @ts-ignore
	counters: Map<AllReactions, number>
	// @ts-ignore
	reactionUsers: Map<AllReactions, ReactionViewerUser[]>
	// @ts-ignore
	hasNextPage: Map<AllReactions, boolean>
}

type ReactionItemProps = {
	reactionType: AllReactions,
	imageUrl: string,
	isCurrent: boolean,
	counter: number,
	onClick: (reactionType: AllReactions) => void,
	// @ts-ignore
}

type ReactionItemState = {
	isCurrent: boolean,
}

type ReactionViewerListProps = {
	assets: Record<ReactionType, string>
	currentReaction: AllReactions,
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