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
    constructor(params: SDKParams);
    init(): void;
    private getBoardMethods;
    private createUrl;
    private addEventListener;
    private listenBoardEvents;
    destroy(): void;
}
