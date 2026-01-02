export interface IBoard {
    tryToCloseBoard: () => Promise<void>;
    renameBoard: (name: string) => Promise<void>;
}
type ShareElementSocial = 'telegram' | 'vk' | 'whatsapp' | 'bitrix';
export interface JWTParams {
    user_id: string;
    username: string;
    avatar_url: string;
    access_level: 'read' | 'write';
    can_edit_board: boolean;
    webhook_url: string;
    document_id: string;
    download_link: string;
    session_id: string;
    file_name: string;
}
export declare enum AccessLevel {
    private = "private",
    readonly = "readonly",
    editable = "editable"
}
export interface UIParams {
    colorTheme?: 'flipOriginLight' | 'flipBitrixLight';
    compactHeader: boolean;
    openTemplatesModal?: boolean;
    showCloseButton?: boolean;
    dashboardFlow?: DashboardFlow;
    exportAsFile?: boolean;
    spinner?: 'circular' | 'default';
    userKickable?: boolean;
    confirmUserKick?: boolean;
    scrollToElement?: string;
    shareElementSocials?: ShareElementSocial[];
    features?: {
        shareElementInSocials?: boolean;
        elementLink?: boolean;
    };
}
export interface BoardData {
    boardId?: string;
    spaceId?: string;
    teamId?: string;
    fileUrl?: string;
    documentId?: string;
    sessionId?: string;
    fileName?: string;
}
export declare enum DashboardFlow {
    short = "short"
}
export interface BoardParams {
    accessLevel?: AccessLevel;
    canEditBoard?: boolean;
    boardData: BoardData;
    token?: string;
    appUrl: string;
    lang?: string;
    boardUrl?: string;
    partnerId: string;
    appContainerDomain: string;
    ui: UIParams;
}
export interface SDKParams {
    containerId: string;
    token?: string;
    appUrl: string;
    lang?: string;
    ui?: UIParams;
    partnerId: string;
    boardUrl?: string;
    events?: {
        onBoardChanged?: () => void;
        onBoardRenamed?: (name: string) => void;
        onUserKickConfirmed?: () => void;
        onUserKicked?: () => void;
        onShareElementWithSocials?: (link: string, social: ShareElementSocial) => void;
    };
}
export {};
