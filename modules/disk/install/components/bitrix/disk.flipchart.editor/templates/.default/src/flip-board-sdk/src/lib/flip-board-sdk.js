"use strict";
import SDK from "./types/SDK"
import { jwtDecode } from "../../../jwt-decode/build/esm/index";
Object.defineProperty(exports, "__esModule", { value: true });
exports.WebSDK = void 0;
var WebSDK = /** @class */ (function () {
    function WebSDK(params) {
        var _a, _b, _c, _d, _e, _f, _g;
        this.params = params;
        var accessLevel;
        var canEditBoard;
        var boardData = {};
        var jwtParams = {
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
                accessLevel = jwtParams.access_level === 'read' ? SDK.AccessLevel.readonly : SDK.AccessLevel.editable;
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
            accessLevel: accessLevel,
            canEditBoard: canEditBoard,
            token: params.token,
            lang: params.lang || 'ru',
            // bitrix partnerId by default
            partnerId: params.partnerId || '0',
            ui: {
                colorTheme: ((_a = params.ui) === null || _a === void 0 ? void 0 : _a.colorTheme) || 'flipOriginLight',
                openTemplatesModal: !!((_b = params.ui) === null || _b === void 0 ? void 0 : _b.openTemplatesModal),
                compactHeader: !!((_c = params.ui) === null || _c === void 0 ? void 0 : _c.compactHeader),
                showCloseButton: !!((_d = params.ui) === null || _d === void 0 ? void 0 : _d.showCloseButton),
                dashboardFlow: ((_e = params.ui) === null || _e === void 0 ? void 0 : _e.dashboardFlow) || undefined,
                exportAsFile: ((_f = params.ui) === null || _f === void 0 ? void 0 : _f.exportAsFile) !== false,
                spinner: (_g = params.ui) === null || _g === void 0 ? void 0 : _g.spinner,
            },
            appContainerDomain: window.location.origin,
            boardData: boardData,
        };
        this.iframeEl = document.createElement('iframe');
        this.iframeEl.allow = 'clipboard-read; clipboard-write';
        window.addEventListener('beforeunload', this.destroy.bind(this));
    }
    WebSDK.prototype.init = function () {
        var container = document.getElementById(this.params.containerId);
        if (!container) {
            console.error("\u042D\u043B\u0435\u043C\u0435\u043D\u0442 \u0441 id \"".concat(this.params.containerId, "\" \u043D\u0435 \u043D\u0430\u0439\u0434\u0435\u043D."));
            return;
        }
        this.iframeEl.src = this.createUrl();
        this.iframeEl.style.width = '100%';
        this.iframeEl.style.height = '100%';
        this.iframeEl.style.border = 'none';
        container.appendChild(this.iframeEl);
        this.addEventListener();
        window.FlipBoard = this.getBoardMethods();
    };
    WebSDK.prototype.getBoardMethods = function () {
        var _this = this;
        return {
            tryToCloseBoard: function () {
                return new Promise(function (resolve, reject) {
                    var _a;
                    window.addEventListener('message', function (event) {
                        var _a, _b;
                        if (((_a = event.data) === null || _a === void 0 ? void 0 : _a.event) === WebSDK.SUCCESS_CLOSE_BOARD_EVENT_NAME) {
                            resolve();
                        }
                        if (((_b = event.data) === null || _b === void 0 ? void 0 : _b.event) === WebSDK.ERROR_CLOSE_BOARD_EVENT_NAME) {
                            reject();
                        }
                    });
                    (_a = _this.iframeEl.contentWindow) === null || _a === void 0 ? void 0 : _a.postMessage({ event: WebSDK.TRY_TO_CLOSE_BOARD_EVENT_NAME }, '*');
                });
            },
            renameFlip: function (name) {
                return new Promise(function (resolve, reject) {
                    var _a;
                    window.addEventListener('message', function (event) {
                        var _a, _b;
                        if (((_a = event.data) === null || _a === void 0 ? void 0 : _a.event) === WebSDK.SUCCESS_FLIP_RENAMED_EVENT_NAME) {
                            resolve();
                        }
                        if (((_b = event.data) === null || _b === void 0 ? void 0 : _b.event) === WebSDK.ERROR_FLIP_RENAMED_EVENT_NAME) {
                            reject();
                        }
                    });
                    (_a = _this.iframeEl.contentWindow) === null || _a === void 0 ? void 0 : _a.postMessage({
                        event: WebSDK.RENAME_FLIP_EVENT,
                        data: { name: name },
                    }, '*');
                });
            },
            // Другие методы можно добавить здесь
        };
    };
    WebSDK.prototype.createUrl = function () {
        var url = new URL("".concat(this.params.appUrl).concat(this.boardParams.partnerId === '0' ? '/sdkBoard' : ''));
        url.searchParams.set('fromSDK', 'true');
        if (this.boardParams.ui.openTemplatesModal)
            url.searchParams.set('openTemplates', 'true');
        if (this.boardParams.ui.spinner && this.boardParams.ui.spinner !== 'default')
            url.searchParams.set('spinner', this.boardParams.ui.spinner);
        return url.toString();
    };
    WebSDK.prototype.addEventListener = function () {
        window.addEventListener('message', this.listenBoardEvents.bind(this));
    };
    WebSDK.prototype.listenBoardEvents = function (event) {
        var _a, _b, _c, _d, _e, _f, _g, _h, _j, _k;
        if (((_a = event.data) === null || _a === void 0 ? void 0 : _a.event) === WebSDK.WAIT_PARAMS_EVENT_NAME) {
            // @ts-ignore
            (_b = this.iframeEl.contentWindow) === null || _b === void 0 ? void 0 : _b.postMessage({ event: WebSDK.SET_PARAMS_EVENT_NAME, data: this.boardParams }, '*');
        }
        if (((_c = event.data) === null || _c === void 0 ? void 0 : _c.event) === WebSDK.BOARD_CHANGED_EVENT_NAME) {
            if ((_e = (_d = this.params) === null || _d === void 0 ? void 0 : _d.events) === null || _e === void 0 ? void 0 : _e.onBoardChanged) {
                this.params.events.onBoardChanged();
            }
        }
        if (((_f = event.data) === null || _f === void 0 ? void 0 : _f.event) === WebSDK.SUCCESS_FLIP_RENAMED_EVENT_NAME) {
            if ((_h = (_g = this.params) === null || _g === void 0 ? void 0 : _g.events) === null || _h === void 0 ? void 0 : _h.onFlipRenamed) {
                this.params.events.onFlipRenamed(((_k = (_j = event.data) === null || _j === void 0 ? void 0 : _j.data) === null || _k === void 0 ? void 0 : _k.name) || '');
            }
        }
    };
    WebSDK.prototype.destroy = function () {
        window.removeEventListener('message', this.listenBoardEvents);
    };
    WebSDK.WAIT_PARAMS_EVENT_NAME = 'waitSDKParams';
    WebSDK.BOARD_CHANGED_EVENT_NAME = 'boardChanged';
    WebSDK.SET_PARAMS_EVENT_NAME = 'setSDKParams';
    WebSDK.TRY_TO_CLOSE_BOARD_EVENT_NAME = 'tryToCloseApp';
    WebSDK.SUCCESS_CLOSE_BOARD_EVENT_NAME = 'successCloseApp';
    WebSDK.ERROR_CLOSE_BOARD_EVENT_NAME = 'errorCloseApp';
    WebSDK.SUCCESS_FLIP_RENAMED_EVENT_NAME = 'successFlipRenamed';
    WebSDK.ERROR_FLIP_RENAMED_EVENT_NAME = 'errorFlipRenamed';
    WebSDK.RENAME_FLIP_EVENT = 'renameFlip';
    return WebSDK;
}());
exports.WebSDK = WebSDK;

export default WebSDK;
