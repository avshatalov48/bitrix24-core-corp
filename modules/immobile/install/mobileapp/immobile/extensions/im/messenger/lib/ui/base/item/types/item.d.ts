import {DialogId} from "../../../../../types/common";

export type MessengerItemProps = {
    data: MessengerItemPropsData,
    size: 'L' | 'M',
    isCustomStyle?: boolean,
    isPressed: boolean,
    nextTo?: boolean,
    isEllipsis?: boolean,
    onClick?: (params: MessengerItemOnClickParams) => any;
    onLongClick?: (params: MessengerItemProps['data']) => any;
    additionalComponent?: LayoutComponent;
    onEllipsisClick?: Function;
    isWithPressed?: boolean,
    isSuperEllipseAvatar?: boolean,
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

declare type MessengerItemPropsData = {
    id: string | number,
    key?: string,
    title: string,
    subtitle: string,
    description: string,
    avatar: AvatarProps,
    avatarUri: string | undefined,
    avatarColor: string,
    isYouTitle: string,
    size: string,
    status: string,
    iconSubtitle: string,
    crownStatus: string,
	style: object,
}
