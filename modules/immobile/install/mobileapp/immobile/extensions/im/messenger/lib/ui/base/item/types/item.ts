export type ItemProps = {
    data: object,
    size: 'L' | 'M',
    isCustomStyle?: boolean,
    nextTo?: boolean,
    isEllipsis?: boolean,
    onClick?: Function;
    onLongClick?: Function;
    onEllipsisClick?: Function;
};
