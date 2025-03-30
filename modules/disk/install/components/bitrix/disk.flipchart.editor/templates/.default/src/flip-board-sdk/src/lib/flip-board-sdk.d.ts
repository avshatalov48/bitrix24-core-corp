import { IBoard, SDKParams } from './types/SDK';
declare global {
    interface Window {
        FlipBoard: IBoard;
    }
}
export declare class WebSDK {
    private readonly params;
    private readonly boardParams;
    private readonly iframeEl;
    private static WAIT_PARAMS_EVENT_NAME;
    private static BOARD_CHANGED_EVENT_NAME;
    private static SET_PARAMS_EVENT_NAME;
    private static TRY_TO_CLOSE_BOARD_EVENT_NAME;
    private static SUCCESS_CLOSE_BOARD_EVENT_NAME;
    private static ERROR_CLOSE_BOARD_EVENT_NAME;
    private static SUCCESS_FLIP_RENAMED_EVENT_NAME;
    private static ERROR_FLIP_RENAMED_EVENT_NAME;
    private static RENAME_FLIP_EVENT;
    constructor(params: SDKParams);
    init(): void;
    private getBoardMethods;
    private createUrl;
    private addEventListener;
    private listenBoardEvents;
    destroy(): void;
}
