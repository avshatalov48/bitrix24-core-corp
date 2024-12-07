import {RawChat} from "../../../../provider/service/src/types/sync-list-result";

declare type imV2RecentCopilotResult = {
    hasMore: boolean,
    hasMorePages: boolean,
    items: Array<RecentItemData>,
    copilot: CopilotRecentItemData,
}

declare type RecentItemData = {
    avatar: number,
    chat: RawChat,
    pinned: boolean,
    unread: boolean,
    chat_id: number,
    counter: number,
    date_last_activity: string,
    message: object,
    options: [],
    title: string,
    type: string,
    user: object,
}

declare type CopilotRecentItemData = {
    chats: Array<object>,
    messages: Array<object>,
    recommendedRoles: Array<object>,
    roles: object,
}