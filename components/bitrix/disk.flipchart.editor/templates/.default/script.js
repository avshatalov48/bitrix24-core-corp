/* eslint-disable */
this.BX = this.BX || {};
this.BX.Disk = this.BX.Disk || {};
(function (exports,ui_buttons,main_popup,disk_externalLink,disk_sharingLegacyPopup) {
    'use strict';

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

    var AccessLevel;
    (function (AccessLevel) {
      AccessLevel["private"] = "private";
      AccessLevel["readonly"] = "readonly";
      AccessLevel["editable"] = "editable";
    })(AccessLevel || (AccessLevel = {}));
    var DashboardFlow;
    (function (DashboardFlow) {
      DashboardFlow["short"] = "short";
    })(DashboardFlow || (DashboardFlow = {}));

    var SDKEvents;
    (function (SDKEvents) {
      SDKEvents["waitParams"] = "waitSDKParams";
      SDKEvents["setParams"] = "setParams";
      SDKEvents["boardChanged"] = "boardChanged";
      SDKEvents["tryToCloseApp"] = "tryToCloseApp";
      SDKEvents["successCloseApp"] = "successCloseApp";
      SDKEvents["errorCloseApp"] = "errorCloseApp";
      SDKEvents["renameBoard"] = "renameBoard";
      SDKEvents["successBoardRenamed"] = "successBoardRenamed";
      SDKEvents["errorBoardRenamed"] = "errorBoardRenamed";
      SDKEvents["userIsKicked"] = "userIsKicked";
      SDKEvents["userConfirmKickFromBoard"] = "userConfirmKickFromBoard";
      SDKEvents["shareElementWithSocials"] = "shareElementWithSocials";
    })(SDKEvents || (SDKEvents = {}));

    var WebSDK = /*#__PURE__*/function () {
      function WebSDK(params) {
        babelHelpers.classCallCheck(this, WebSDK);
        var _a, _b, _c, _d, _e, _f, _g, _h, _j, _k, _l;
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
            accessLevel = jwtParams.access_level === 'read' ? AccessLevel.readonly : AccessLevel.editable;
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
            features: (_l = params.ui) === null || _l === void 0 ? void 0 : _l.features
          },
          appContainerDomain: window.location.origin,
          boardData: boardData
        };
        this.iframeEl = document.createElement('iframe');
        this.iframeEl.allow = 'clipboard-read; clipboard-write; fullscreen';
        window.addEventListener('beforeunload', this.destroy.bind(this));
      }
      babelHelpers.createClass(WebSDK, [{
        key: "init",
        value: function init() {
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
        }
      }, {
        key: "getBoardMethods",
        value: function getBoardMethods() {
          var _this = this;
          return {
            tryToCloseBoard: function tryToCloseBoard() {
              return new Promise(function (resolve, reject) {
                var _a;
                window.addEventListener('message', function (event) {
                  var _a, _b;
                  if (((_a = event.data) === null || _a === void 0 ? void 0 : _a.event) === SDKEvents.successCloseApp) {
                    resolve();
                  }
                  if (((_b = event.data) === null || _b === void 0 ? void 0 : _b.event) === SDKEvents.errorCloseApp) {
                    reject();
                  }
                });
                (_a = _this.iframeEl.contentWindow) === null || _a === void 0 ? void 0 : _a.postMessage({
                  event: SDKEvents.tryToCloseApp
                }, '*');
              });
            },
            renameBoard: function renameBoard(name) {
              return new Promise(function (resolve, reject) {
                var _a;
                window.addEventListener('message', function (event) {
                  var _a, _b;
                  if (((_a = event.data) === null || _a === void 0 ? void 0 : _a.event) === SDKEvents.successBoardRenamed) {
                    resolve();
                  }
                  if (((_b = event.data) === null || _b === void 0 ? void 0 : _b.event) === SDKEvents.errorBoardRenamed) {
                    reject();
                  }
                });
                (_a = _this.iframeEl.contentWindow) === null || _a === void 0 ? void 0 : _a.postMessage({
                  event: SDKEvents.renameBoard,
                  data: {
                    name: name
                  }
                }, '*');
              });
            }
            // Другие методы можно добавить здесь
          };
        }
      }, {
        key: "createUrl",
        value: function createUrl() {
          var url = new URL("".concat(this.params.appUrl).concat(this.boardParams.partnerId === '0' ? '/sdkBoard' : ''));
          url.searchParams.set('fromSDK', 'true');
          if (this.boardParams.ui.openTemplatesModal) url.searchParams.set('openTemplates', 'true');
          if (this.boardParams.ui.spinner && this.boardParams.ui.spinner !== 'default') url.searchParams.set('spinner', this.boardParams.ui.spinner);
          url.searchParams.set('dt', Date.now().toString());
          if (this.boardParams.ui.scrollToElement) url.searchParams.set('elementId', this.boardParams.ui.scrollToElement);
          return url.toString();
        }
      }, {
        key: "addEventListener",
        value: function addEventListener() {
          window.addEventListener('message', this.listenBoardEvents.bind(this));
        }
      }, {
        key: "listenBoardEvents",
        value: function listenBoardEvents(event) {
          var _a, _b, _c, _d, _e, _f, _g, _h, _j, _k, _l, _m, _o, _p, _q, _r, _s, _t, _u, _v, _w, _x, _y, _z, _0, _1, _2, _3, _4;
          if (((_a = event.data) === null || _a === void 0 ? void 0 : _a.event) === SDKEvents.waitParams) {
            // @ts-ignore
            (_b = this.iframeEl.contentWindow) === null || _b === void 0 ? void 0 : _b.postMessage({
              event: SDKEvents.setParams,
              data: this.boardParams
            }, '*');
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
      }, {
        key: "destroy",
        value: function destroy() {
          window.removeEventListener('message', this.listenBoardEvents);
        }
      }]);
      return WebSDK;
    }();

    var Board = /*#__PURE__*/function () {
      function Board(options) {
        babelHelpers.classCallCheck(this, Board);
        babelHelpers.defineProperty(this, "setupSharingButton", null);
        babelHelpers.defineProperty(this, "data", null);
        babelHelpers.defineProperty(this, "unifiedLinkAccessOnly", false);
        this.setupSharingButton = ui_buttons.ButtonManager.createByUniqId(options.panelButtonUniqIds.setupSharing);
        this.data = options.boardData;
        this.sharingControlType = options.sharingControlType;
        this.unifiedLinkAccessOnly = options.unifiedLinkAccessOnly;
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
          if (this.unifiedLinkAccessOnly) {
            disk_externalLink.ExternalLinkForUnifiedLink.showPopup(this.data.uniqueCode);
          } else {
            disk_externalLink.ExternalLink.showPopup(this.data.id);
          }
        }
      }, {
        key: "handleClickSharing",
        value: function handleClickSharing() {
          this.setupSharingButton.getMenuWindow().close();
          this.showSharingRightsPopup();
        }
      }, {
        key: "showSharingRightsPopup",
        value: function showSharingRightsPopup() {
          var popup = new disk_sharingLegacyPopup.LegacyPopup();
          var popupOptions = {
            object: {
              id: this.data.id,
              name: this.data.name
            }
          };
          switch (this.sharingControlType) {
            case disk_sharingLegacyPopup.SharingControlType.WITH_CHANGE_RIGHTS:
            case disk_sharingLegacyPopup.SharingControlType.WITH_SHARING:
              popup.showSharingDetailWithChangeRights(popupOptions);
              break;
            case disk_sharingLegacyPopup.SharingControlType.WITHOUT_EDIT:
              popup.showSharingDetailWithoutEdit(popupOptions);
              break;
            default:
              break;
          }
        }
      }]);
      return Board;
    }();

    var SDK = WebSDK;

    exports.Board = Board;
    exports.SDK = SDK;

}((this.BX.Disk.Flipchart = this.BX.Disk.Flipchart || {}),BX.UI,BX.Main,BX.Disk,BX.Disk.Sharing));
//# sourceMappingURL=script.js.map
