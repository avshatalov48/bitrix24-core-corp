import {DialogId} from "../../../../../types/common";

export type MessengerItemProps = {
    data: {
        id: DialogId,
        key: string,
        title: string,
        description: string,
        avatarUri: string,
        avatarColor: string,
        isYouTitle: boolean,
        subtitle: string,
        status: string,
        iconSubtitle: string,
        crownStatus: string,
        style: object,
    },
    size: 'L' | 'M',
    isCustomStyle?: boolean,
    isPressed: boolean,
    nextTo?: boolean,
    isEllipsis?: boolean,
    onClick?: (params: MessengerItemOnClickParams) => any;
    onLongClick?: (params: MessengerItemProps['data']) => any;
    additionalComponent?: LayoutComponent;
};

export type MessengerItemOnClickParams = {
    dialogId: DialogId,
    dialogTitleParams: {
        key: string,
        name: string,
        description: string,
        avatar: string,
        color: string,
    }
}
