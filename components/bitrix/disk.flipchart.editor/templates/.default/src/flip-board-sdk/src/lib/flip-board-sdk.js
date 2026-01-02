import { jwtDecode } from "../../../jwt-decode/build/esm/index";
import { AccessLevel } from './types/SDK';
import { SDKEvents } from './types/events';
export default class WebSDK {
    constructor(params) {
        var _a, _b, _c, _d, _e, _f, _g, _h, _j, _k, _l;
        this.params = params;
        let accessLevel;
        let canEditBoard;
        const boardData = {};
        let jwtParams = {
            user_id: '',
            username: '',
            avatar_url: '',
            access_level: 'read',
            can_edit_board: false,
            webhook_url: '',
            document_id: '',
            download_link: '',
            session_id: '',
            file_name: '',
        };
        if (params.token) {
            try {
                jwtParams = jwtDecode(params.token);
                accessLevel = jwtParams.access_level === 'read' ? AccessLevel.readonly : AccessLevel.editable;
                canEditBoard = jwtParams.can_edit_board;
                boardData.documentId = jwtParams.document_id;
                boardData.fileUrl = jwtParams.download_link;
                boardData.sessionId = jwtParams.session_id;
                boardData.fileName = jwtParams.file_name;
            }
            catch (e) {
                console.error('invalid token');
            }
        }
        this.boardParams = {
            appUrl: encodeURIComponent(params.appUrl),
            accessLevel,
            canEditBoard,
            token: params.token,
            lang: params.lang || 'ru',
            // bitrix partnerId by default
            partnerId: params.partnerId || '0',
            boardUrl: params.boardUrl && encodeURIComponent(params.boardUrl),
            ui: {
                colorTheme: ((_a = params.ui) === null || _a === void 0 ? void 0 : _a.colorTheme) || 'flipOriginLight',
                openTemplatesModal: !!((_b = params.ui) === null || _b === void 0 ? void 0 : _b.openTemplatesModal),
                compactHeader: !!((_c = params.ui) === null || _c === void 0 ? void 0 : _c.compactHeader),
                showCloseButton: !!((_d = params.ui) === null || _d === void 0 ? void 0 : _d.showCloseButton),
                dashboardFlow: ((_e = params.ui) === null || _e === void 0 ? void 0 : _e.dashboardFlow) || undefined,
                exportAsFile: ((_f = params.ui) === null || _f === void 0 ? void 0 : _f.exportAsFile) !== false,
                spinner: (_g = params.ui) === null || _g === void 0 ? void 0 : _g.spinner,
                userKickable: (_h = params.ui) === null || _h === void 0 ? void 0 : _h.userKickable,
                confirmUserKick: (_j = params.ui) === null || _j === void 0 ? void 0 : _j.confirmUserKick,
                scrollToElement: (_k = params.ui) === null || _k === void 0 ? void 0 : _k.scrollToElement,
                features: (_l = params.ui) === null || _l === void 0 ? void 0 : _l.features,
            },
            appContainerDomain: window.location.origin,
            boardData,
        };
        this.iframeEl = document.createElement('iframe');
        this.iframeEl.allow = 'clipboard-read; clipboard-write; fullscreen';
        window.addEventListener('beforeunload', this.destroy.bind(this));
    }
    init() {
        const container = document.getElementById(this.params.containerId);
        if (!container) {
            console.error(`Элемент с id "${this.params.containerId}" не найден.`);
            return;
        }
        this.iframeEl.src = this.createUrl();
        this.iframeEl.style.width = '100%';
        this.iframeEl.style.height = '100%';
        this.iframeEl.style.border = 'none';
        container.appendChild(this.iframeEl);
        this.addEventListener();
        window.FlipBoard = this.getBoardMethods();
    }
    getBoardMethods() {
        return {
            tryToCloseBoard: () => new Promise((resolve, reject) => {
                var _a;
                window.addEventListener('message', (event) => {
                    var _a, _b;
                    if (((_a = event.data) === null || _a === void 0 ? void 0 : _a.event) === SDKEvents.successCloseApp) {
                        resolve();
                    }
                    if (((_b = event.data) === null || _b === void 0 ? void 0 : _b.event) === SDKEvents.errorCloseApp) {
                        reject();
                    }
                });
                (_a = this.iframeEl.contentWindow) === null || _a === void 0 ? void 0 : _a.postMessage({ event: SDKEvents.tryToCloseApp }, '*');
            }),
            renameBoard: (name) => new Promise((resolve, reject) => {
                var _a;
                window.addEventListener('message', (event) => {
                    var _a, _b;
                    if (((_a = event.data) === null || _a === void 0 ? void 0 : _a.event) === SDKEvents.successBoardRenamed) {
                        resolve();
                    }
                    if (((_b = event.data) === null || _b === void 0 ? void 0 : _b.event) === SDKEvents.errorBoardRenamed) {
                        reject();
                    }
                });
                (_a = this.iframeEl.contentWindow) === null || _a === void 0 ? void 0 : _a.postMessage({
                    event: SDKEvents.renameBoard,
                    data: { name },
                }, '*');
            }),
            // Другие методы можно добавить здесь
        };
    }
    createUrl() {
        const url = new URL(`${this.params.appUrl}${this.boardParams.partnerId === '0' ? '/sdkBoard' : ''}`);
        url.searchParams.set('fromSDK', 'true');
        if (this.boardParams.ui.openTemplatesModal)
            url.searchParams.set('openTemplates', 'true');
        if (this.boardParams.ui.spinner && this.boardParams.ui.spinner !== 'default')
            url.searchParams.set('spinner', this.boardParams.ui.spinner);
        url.searchParams.set('dt', Date.now().toString());
        if (this.boardParams.ui.scrollToElement)
            url.searchParams.set('elementId', this.boardParams.ui.scrollToElement);
        return url.toString();
    }
    addEventListener() {
        window.addEventListener('message', this.listenBoardEvents.bind(this));
    }
    listenBoardEvents(event) {
        var _a, _b, _c, _d, _e, _f, _g, _h, _j, _k, _l, _m, _o, _p, _q, _r, _s, _t, _u, _v, _w, _x, _y, _z, _0, _1, _2, _3, _4;
        if (((_a = event.data) === null || _a === void 0 ? void 0 : _a.event) === SDKEvents.waitParams) {
            // @ts-ignore
            (_b = this.iframeEl.contentWindow) === null || _b === void 0 ? void 0 : _b.postMessage({ event: SDKEvents.setParams, data: this.boardParams }, '*');
        }
        if (((_c = event.data) === null || _c === void 0 ? void 0 : _c.event) === SDKEvents.boardChanged) {
            if ((_e = (_d = this.params) === null || _d === void 0 ? void 0 : _d.events) === null || _e === void 0 ? void 0 : _e.onBoardChanged) {
                this.params.events.onBoardChanged();
            }
        }
        if (((_f = event.data) === null || _f === void 0 ? void 0 : _f.event) === SDKEvents.successBoardRenamed) {
            if ((_h = (_g = this.params) === null || _g === void 0 ? void 0 : _g.events) === null || _h === void 0 ? void 0 : _h.onBoardRenamed) {
                this.params.events.onBoardRenamed(((_k = (_j = event.data) === null || _j === void 0 ? void 0 : _j.data) === null || _k === void 0 ? void 0 : _k.name) || '');
            }
        }
        if (((_l = event.data) === null || _l === void 0 ? void 0 : _l.event) === SDKEvents.userIsKicked) {
            if ((_o = (_m = this.params) === null || _m === void 0 ? void 0 : _m.events) === null || _o === void 0 ? void 0 : _o.onUserKicked) {
                (_q = (_p = this.params) === null || _p === void 0 ? void 0 : _p.events) === null || _q === void 0 ? void 0 : _q.onUserKicked();
            }
        }
        if (((_r = event.data) === null || _r === void 0 ? void 0 : _r.event) === SDKEvents.userConfirmKickFromBoard) {
            if ((_t = (_s = this.params) === null || _s === void 0 ? void 0 : _s.events) === null || _t === void 0 ? void 0 : _t.onUserKickConfirmed) {
                (_v = (_u = this.params) === null || _u === void 0 ? void 0 : _u.events) === null || _v === void 0 ? void 0 : _v.onUserKickConfirmed();
            }
        }
        if (((_w = event.data) === null || _w === void 0 ? void 0 : _w.event) === SDKEvents.shareElementWithSocials) {
            if ((_y = (_x = this.params) === null || _x === void 0 ? void 0 : _x.events) === null || _y === void 0 ? void 0 : _y.onShareElementWithSocials) {
                (_0 = (_z = this.params) === null || _z === void 0 ? void 0 : _z.events) === null || _0 === void 0 ? void 0 : _0.onShareElementWithSocials(((_2 = (_1 = event.data) === null || _1 === void 0 ? void 0 : _1.data) === null || _2 === void 0 ? void 0 : _2.link) || '', ((_4 = (_3 = event.data) === null || _3 === void 0 ? void 0 : _3.data) === null || _4 === void 0 ? void 0 : _4.social) || 'telegram');
            }
        }
    }
    destroy() {
        window.removeEventListener('message', this.listenBoardEvents);
    }
}
//# sourceMappingURL=flip-board-sdk.js.map