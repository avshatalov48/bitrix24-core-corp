declare type MessengerItemProps = {
    data: object,
    size: 'L' | 'M',
    isCustomStyle?: boolean,
    nextTo?: boolean,
    isEllipsis?: boolean,
    onClick?: Function;
    onLongClick?: Function;
    onEllipsisClick?: Function;
    additionalComponent?: object
};
