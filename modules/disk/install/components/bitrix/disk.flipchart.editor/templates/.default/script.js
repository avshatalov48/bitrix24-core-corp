/* eslint-disable */
this.BX = this.BX || {};
this.BX.Disk = this.BX.Disk || {};
(function (exports,ui_buttons,main_popup,disk_externalLink,disk_sharingLegacyPopup) {
    'use strict';

    Object.defineProperty(exports, "__esModule", {
      value: true
    });
    exports.DashboardFlow = exports.AccessLevel = void 0;
    var AccessLevel;
    (function (AccessLevel) {
      AccessLevel["private"] = "private";
      AccessLevel["readonly"] = "readonly";
      AccessLevel["editable"] = "editable";
    })(AccessLevel || (exports.AccessLevel = AccessLevel = {}));
    var DashboardFlow;
    (function (DashboardFlow) {
      DashboardFlow["short"] = "short";
    })(DashboardFlow || (exports.DashboardFlow = DashboardFlow = {}));

    var InvalidTokenError = /*#__PURE__*/function (_Error) {
      babelHelpers.inherits(InvalidTokenError, _Error);
      function InvalidTokenError() {
        babelHelpers.classCallCheck(this, InvalidTokenError);
        return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(InvalidTokenError).apply(this, arguments));
      }
      return InvalidTokenError;
    }( /*#__PURE__*/babelHelpers.wrapNativeSuper(Error));
    InvalidTokenError.prototype.name = "InvalidTokenError";
    function b64DecodeUnicode(str) {
      return decodeURIComponent(atob(str).replace(/(.)/g, function (m, p) {
        var code = p.charCodeAt(0).toString(16).toUpperCase();
        if (code.length < 2) {
          code = "0" + code;
        }
        return "%" + code;
      }));
    }
    function base64UrlDecode(str) {
      var output = str.replace(/-/g, "+").replace(/_/g, "/");
      switch (output.length % 4) {
        case 0:
          break;
        case 2:
          output += "==";
          break;
        case 3:
          output += "=";
          break;
        default:
          throw new Error("base64 string is not of the correct length");
      }
      try {
        return b64DecodeUnicode(output);
      } catch (err) {
        return atob(output);
      }
    }
    function jwtDecode(token, options) {
      if (typeof token !== "string") {
        throw new InvalidTokenError("Invalid token specified: must be a string");
      }
      options || (options = {});
      var pos = options.header === true ? 0 : 1;
      var part = token.split(".")[pos];
      if (typeof part !== "string") {
        throw new InvalidTokenError("Invalid token specified: missing part #".concat(pos + 1));
      }
      var decoded;
      try {
        decoded = base64UrlDecode(part);
      } catch (e) {
        throw new InvalidTokenError("Invalid token specified: invalid base64 for part #".concat(pos + 1, " (").concat(e.message, ")"));
      }
      try {
        return JSON.parse(decoded);
      } catch (e) {
        throw new InvalidTokenError("Invalid token specified: invalid json for part #".concat(pos + 1, " (").concat(e.message, ")"));
      }
    }

    Object.defineProperty(exports, "__esModule", {
      value: true
    });
    exports.WebSDK = void 0;
    var WebSDK = /** @class */function () {
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
          file_name: ''
        };
        if (params.token) {
          try {
            jwtParams = jwtDecode(params.token);
            accessLevel = jwtParams.access_level === 'read' ? exports.AccessLevel.readonly : exports.AccessLevel.editable;
            canEditBoard = jwtParams.can_edit_board;
            boardData.documentId = jwtParams.document_id;
            boardData.fileUrl = jwtParams.download_link;
            boardData.sessionId = jwtParams.session_id;
            boardData.fileName = jwtParams.file_name;
          } catch (e) {
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
            spinner: (_g = params.ui) === null || _g === void 0 ? void 0 : _g.spinner
          },
          appContainerDomain: window.location.origin,
          boardData: boardData
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
          tryToCloseBoard: function tryToCloseBoard() {
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
              (_a = _this.iframeEl.contentWindow) === null || _a === void 0 ? void 0 : _a.postMessage({
                event: WebSDK.TRY_TO_CLOSE_BOARD_EVENT_NAME
              }, '*');
            });
          },
          renameFlip: function renameFlip(name) {
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
                data: {
                  name: name
                }
              }, '*');
            });
          }
          // Другие методы можно добавить здесь
        };
      };

      WebSDK.prototype.createUrl = function () {
        var url = new URL("".concat(this.params.appUrl).concat(this.boardParams.partnerId === '0' ? '/sdkBoard' : ''));
        url.searchParams.set('fromSDK', 'true');
        if (this.boardParams.ui.openTemplatesModal) url.searchParams.set('openTemplates', 'true');
        if (this.boardParams.ui.spinner && this.boardParams.ui.spinner !== 'default') url.searchParams.set('spinner', this.boardParams.ui.spinner);
        return url.toString();
      };
      WebSDK.prototype.addEventListener = function () {
        window.addEventListener('message', this.listenBoardEvents.bind(this));
      };
      WebSDK.prototype.listenBoardEvents = function (event) {
        var _a, _b, _c, _d, _e, _f, _g, _h, _j, _k;
        if (((_a = event.data) === null || _a === void 0 ? void 0 : _a.event) === WebSDK.WAIT_PARAMS_EVENT_NAME) {
          // @ts-ignore
          (_b = this.iframeEl.contentWindow) === null || _b === void 0 ? void 0 : _b.postMessage({
            event: WebSDK.SET_PARAMS_EVENT_NAME,
            data: this.boardParams
          }, '*');
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
    }();
    exports.WebSDK = WebSDK;

    var Board = /*#__PURE__*/function () {
      function Board(options) {
        babelHelpers.classCallCheck(this, Board);
        babelHelpers.defineProperty(this, "setupSharingButton", null);
        babelHelpers.defineProperty(this, "data", null);
        this.setupSharingButton = ui_buttons.ButtonManager.createByUniqId(options.panelButtonUniqIds.setupSharing);
        this.data = options.boardData;
        this.bindEvents();
      }
      babelHelpers.createClass(Board, [{
        key: "bindEvents",
        value: function bindEvents() {
          if (this.setupSharingButton) {
            var menuWindow = this.setupSharingButton.getMenuWindow();
            var extLinkOptions = menuWindow.getMenuItem('ext-link').options;
            extLinkOptions.onclick = this.handleClickSharingByExternalLink.bind(this);
            menuWindow.removeMenuItem('ext-link');
            menuWindow.addMenuItem(extLinkOptions);
            var sharingOptions = menuWindow.getMenuItem('sharing').options;
            sharingOptions.onclick = this.handleClickSharing.bind(this);
            menuWindow.removeMenuItem('sharing');
            menuWindow.addMenuItem(sharingOptions);
          }
        }
      }, {
        key: "handleClickSharingByExternalLink",
        value: function handleClickSharingByExternalLink(event, menuItem) {
          this.setupSharingButton.getMenuWindow().close();
          if (menuItem.dataset.shouldBlockExternalLinkFeature) {
            eval(menuItem.dataset.blockerExternalLinkFeature);
            return;
          }
          disk_externalLink.ExternalLink.showPopup(this.data.id);
        }
      }, {
        key: "handleClickSharing",
        value: function handleClickSharing() {
          this.setupSharingButton.getMenuWindow().close();
          new disk_sharingLegacyPopup.LegacyPopup().showSharingDetailWithChangeRights({
            object: {
              id: this.data.id,
              name: this.data.name
            }
          });
        }
      }]);
      return Board;
    }();

    var SDK = WebSDK;

    exports.Board = Board;
    exports.SDK = SDK;

}((this.BX.Disk.Flipchart = this.BX.Disk.Flipchart || {}),BX.UI,BX.Main,BX.Disk,BX.Disk.Sharing));
//# sourceMappingURL=script.js.map
