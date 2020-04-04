this.BX = this.BX || {};
this.BX.Messenger = this.BX.Messenger || {};
(function (exports,ui_vue,ui_vue_vuex,im_const,im_utils) {
	'use strict';

	/**
	 * Bitrix Messenger
	 * Application model (Vuex Builder model)
	 *
	 * @package bitrix
	 * @subpackage im
	 * @copyright 2001-2019 Bitrix
	 */

	var ApplicationModel =
	/*#__PURE__*/
	function (_VuexBuilderModel) {
	  babelHelpers.inherits(ApplicationModel, _VuexBuilderModel);

	  function ApplicationModel() {
	    babelHelpers.classCallCheck(this, ApplicationModel);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ApplicationModel).apply(this, arguments));
	  }

	  babelHelpers.createClass(ApplicationModel, [{
	    key: "getName",
	    value: function getName() {
	      return 'application';
	    }
	  }, {
	    key: "getState",
	    value: function getState() {
	      return {
	        common: {
	          host: this.getVariable('common.host', location.protocol + '//' + location.host),
	          siteId: this.getVariable('common.siteId', 'default'),
	          userId: this.getVariable('common.userId', 0),
	          languageId: this.getVariable('common.languageId', 'en')
	        },
	        dialog: {
	          dialogId: this.getVariable('dialog.dialogId', '0'),
	          chatId: this.getVariable('dialog.chatId', 0),
	          diskFolderId: this.getVariable('dialog.diskFolderId', 0),
	          messageLimit: this.getVariable('dialog.messageLimit', 20),
	          enableReadMessages: this.getVariable('dialog.enableReadMessages', true),
	          messageExtraCount: 0
	        },
	        disk: {
	          enabled: false,
	          maxFileSize: 5242880
	        },
	        mobile: {
	          keyboardShow: false
	        },
	        device: {
	          type: this.getVariable('device.type', im_const.DeviceType.desktop),
	          orientation: this.getVariable('device.orientation', im_const.DeviceOrientation.portrait)
	        },
	        options: {
	          quoteEnable: this.getVariable('options.quoteEnable', true),
	          quoteFromRight: this.getVariable('options.quoteFromRight', true),
	          autoplayVideo: this.getVariable('options.autoplayVideo', true),
	          darkBackground: this.getVariable('options.darkBackground', false)
	        },
	        error: {
	          active: false,
	          code: '',
	          description: ''
	        }
	      };
	    }
	  }, {
	    key: "getStateSaveException",
	    value: function getStateSaveException() {
	      return Object.assign({
	        common: this.getVariable('saveException.common', null),
	        dialog: this.getVariable('saveException.dialog', null),
	        mobile: this.getVariable('saveException.mobile', null),
	        device: this.getVariable('saveException.device', null),
	        error: this.getVariable('saveException.error', null)
	      });
	    }
	  }, {
	    key: "getActions",
	    value: function getActions() {
	      var _this = this;

	      return {
	        set: function set(store, payload) {
	          store.commit('set', _this.validate(payload));
	        }
	      };
	    }
	  }, {
	    key: "getMutations",
	    value: function getMutations() {
	      var _this2 = this;

	      return {
	        set: function set(state, payload) {
	          var hasChange = false;

	          for (var group in payload) {
	            if (!payload.hasOwnProperty(group)) {
	              continue;
	            }

	            for (var field in payload[group]) {
	              if (!payload[group].hasOwnProperty(field)) {
	                continue;
	              }

	              state[group][field] = payload[group][field];
	              hasChange = true;
	            }
	          }

	          if (hasChange && _this2.isSaveNeeded(payload)) {
	            _this2.saveState(state);
	          }
	        },
	        increaseDialogExtraCount: function increaseDialogExtraCount(state) {
	          var payload = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
	          var _payload$count = payload.count,
	              count = _payload$count === void 0 ? 1 : _payload$count;
	          state.dialog.messageExtraCount += count;
	        },
	        decreaseDialogExtraCount: function decreaseDialogExtraCount(state) {
	          var payload = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
	          var _payload$count2 = payload.count,
	              count = _payload$count2 === void 0 ? 1 : _payload$count2;
	          var newCounter = state.dialog.messageExtraCount - count;

	          if (newCounter <= 0) {
	            newCounter = 0;
	          }

	          state.dialog.messageExtraCount = newCounter;
	        },
	        clearDialogExtraCount: function clearDialogExtraCount(state) {
	          state.dialog.messageExtraCount = 0;
	        }
	      };
	    }
	  }, {
	    key: "validate",
	    value: function validate(fields) {
	      var result = {};

	      if (babelHelpers.typeof(fields.common) === 'object' && fields.common) {
	        result.common = {};

	        if (typeof fields.common.userId === 'number') {
	          result.common.userId = fields.common.userId;
	        }

	        if (typeof fields.common.languageId === 'string') {
	          result.common.languageId = fields.common.languageId;
	        }
	      }

	      if (babelHelpers.typeof(fields.dialog) === 'object' && fields.dialog) {
	        result.dialog = {};

	        if (typeof fields.dialog.dialogId === 'number') {
	          result.dialog.dialogId = fields.dialog.dialogId.toString();
	          result.dialog.chatId = 0;
	        } else if (typeof fields.dialog.dialogId === 'string') {
	          result.dialog.dialogId = fields.dialog.dialogId;

	          if (typeof fields.dialog.chatId !== 'number') {
	            var chatId = fields.dialog.dialogId;

	            if (chatId.startsWith('chat')) {
	              chatId = fields.dialog.dialogId.substr(4);
	            }

	            chatId = parseInt(chatId);
	            result.dialog.chatId = !isNaN(chatId) ? chatId : 0;
	            fields.dialog.chatId = result.dialog.chatId;
	          }
	        }

	        if (typeof fields.dialog.chatId === 'number') {
	          result.dialog.chatId = fields.dialog.chatId;
	        }

	        if (typeof fields.dialog.diskFolderId === 'number') {
	          result.dialog.diskFolderId = fields.dialog.diskFolderId;
	        }

	        if (typeof fields.dialog.messageLimit === 'number') {
	          result.dialog.messageLimit = fields.dialog.messageLimit;
	        }

	        if (typeof fields.dialog.messageExtraCount === 'number') {
	          result.dialog.messageExtraCount = fields.dialog.messageExtraCount;
	        }

	        if (typeof fields.dialog.enableReadMessages === 'boolean') {
	          result.dialog.enableReadMessages = fields.dialog.enableReadMessages;
	        }
	      }

	      if (babelHelpers.typeof(fields.disk) === 'object' && fields.disk) {
	        result.disk = {};

	        if (typeof fields.disk.enabled === 'boolean') {
	          result.disk.enabled = fields.disk.enabled;
	        }

	        if (typeof fields.disk.maxFileSize === 'number') {
	          result.disk.maxFileSize = fields.disk.maxFileSize;
	        }
	      }

	      if (babelHelpers.typeof(fields.mobile) === 'object' && fields.mobile) {
	        result.mobile = {};

	        if (typeof fields.mobile.keyboardShow === 'boolean') {
	          result.mobile.keyboardShow = fields.mobile.keyboardShow;
	        }
	      }

	      if (babelHelpers.typeof(fields.device) === 'object' && fields.device) {
	        result.device = {};

	        if (typeof fields.device.type === 'string' && typeof im_const.DeviceType[fields.device.type] !== 'undefined') {
	          result.device.type = fields.device.type;
	        }

	        if (typeof fields.device.orientation === 'string' && typeof im_const.DeviceOrientation[fields.device.orientation] !== 'undefined') {
	          result.device.orientation = fields.device.orientation;
	        }
	      }

	      if (babelHelpers.typeof(fields.error) === 'object' && fields.error) {
	        if (typeof fields.error.active === 'boolean') {
	          result.error = {
	            active: fields.error.active,
	            code: fields.error.code.toString() || '',
	            description: fields.error.description.toString() || ''
	          };
	        }
	      }

	      return result;
	    }
	  }]);
	  return ApplicationModel;
	}(ui_vue_vuex.VuexBuilderModel);

	/**
	 * Bitrix Messenger
	 * Message model (Vuex Builder model)
	 *
	 * @package bitrix
	 * @subpackage im
	 * @copyright 2001-2019 Bitrix
	 */
	var IntersectionType = {
	  empty: 'empty',
	  equal: 'equal',
	  none: 'none',
	  found: 'found',
	  foundReverse: 'foundReverse'
	};

	var MessagesModel =
	/*#__PURE__*/
	function (_VuexBuilderModel) {
	  babelHelpers.inherits(MessagesModel, _VuexBuilderModel);

	  function MessagesModel() {
	    babelHelpers.classCallCheck(this, MessagesModel);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(MessagesModel).apply(this, arguments));
	  }

	  babelHelpers.createClass(MessagesModel, [{
	    key: "getName",
	    value: function getName() {
	      return 'messages';
	    }
	  }, {
	    key: "getState",
	    value: function getState() {
	      return {
	        created: 0,
	        collection: {},
	        mutationType: {},
	        saveMessageList: {},
	        saveFileList: {},
	        saveUserList: {}
	      };
	    }
	  }, {
	    key: "getElementState",
	    value: function getElementState() {
	      return {
	        templateId: 0,
	        templateType: 'message',
	        id: 0,
	        chatId: 0,
	        authorId: 0,
	        date: new Date(),
	        text: "",
	        textConverted: "",
	        params: {
	          TYPE: 'default',
	          COMPONENT_ID: 'bx-messenger-message'
	        },
	        push: false,
	        unread: false,
	        sending: false,
	        error: false,
	        retry: false,
	        blink: false
	      };
	    }
	  }, {
	    key: "getGetters",
	    value: function getGetters() {
	      var _this = this;

	      return {
	        getMutationType: function getMutationType(state) {
	          return function (chatId) {
	            if (!state.mutationType[chatId]) {
	              return {
	                initialType: im_const.MutationType.none,
	                appliedType: im_const.MutationType.none
	              };
	            }

	            return state.mutationType[chatId];
	          };
	        },
	        getLastId: function getLastId(state) {
	          return function (chatId) {
	            if (!state.collection[chatId] || state.collection[chatId].length <= 0) {
	              return null;
	            }

	            var lastId = 0;

	            for (var i = 0; i < state.collection[chatId].length; i++) {
	              var element = state.collection[chatId][i];

	              if (element.push || element.sending || element.id.toString().startsWith('temporary')) {
	                continue;
	              }

	              if (lastId < element.id) {
	                lastId = element.id;
	              }
	            }

	            return lastId ? lastId : null;
	          };
	        },
	        getMessage: function getMessage(state) {
	          return function (chatId, messageId) {
	            if (!state.collection[chatId] || state.collection[chatId].length <= 0) {
	              return null;
	            }

	            for (var index = state.collection[chatId].length - 1; index >= 0; index--) {
	              if (state.collection[chatId][index].id === messageId) {
	                return state.collection[chatId][index];
	              }
	            }

	            return null;
	          };
	        },
	        get: function get(state) {
	          return function (chatId) {
	            if (!state.collection[chatId] || state.collection[chatId].length <= 0) {
	              return [];
	            }

	            return state.collection[chatId];
	          };
	        },
	        getBlank: function getBlank(state) {
	          return function (params) {
	            return _this.getElementState();
	          };
	        },
	        getSaveFileList: function getSaveFileList(state) {
	          return function (params) {
	            return state.saveFileList;
	          };
	        },
	        getSaveUserList: function getSaveUserList(state) {
	          return function (params) {
	            return state.saveUserList;
	          };
	        }
	      };
	    }
	  }, {
	    key: "getActions",
	    value: function getActions() {
	      var _this2 = this;

	      return {
	        add: function add(store, payload) {
	          var result = _this2.validate(Object.assign({}, payload));

	          result.params = Object.assign({}, _this2.getElementState().params, result.params);
	          result.id = 'temporary' + new Date().getTime() + store.state.created;
	          result.templateId = result.id;
	          result.unread = false;
	          store.commit('add', Object.assign({}, _this2.getElementState(), result));

	          if (payload.sending !== false) {
	            store.dispatch('actionStart', {
	              id: result.id,
	              chatId: result.chatId
	            });
	          }

	          return result.id;
	        },
	        actionStart: function actionStart(store, payload) {
	          if (/^\d+$/.test(payload.id)) {
	            payload.id = parseInt(payload.id);
	          }

	          payload.chatId = parseInt(payload.chatId);
	          ui_vue.Vue.nextTick(function () {
	            store.commit('update', {
	              id: payload.id,
	              chatId: payload.chatId,
	              fields: {
	                sending: true
	              }
	            });
	          });
	        },
	        actionError: function actionError(store, payload) {
	          if (/^\d+$/.test(payload.id)) {
	            payload.id = parseInt(payload.id);
	          }

	          payload.chatId = parseInt(payload.chatId);
	          ui_vue.Vue.nextTick(function () {
	            store.commit('update', {
	              id: payload.id,
	              chatId: payload.chatId,
	              fields: {
	                sending: false,
	                error: true,
	                retry: payload.retry !== false
	              }
	            });
	          });
	        },
	        actionFinish: function actionFinish(store, payload) {
	          if (/^\d+$/.test(payload.id)) {
	            payload.id = parseInt(payload.id);
	          }

	          payload.chatId = parseInt(payload.chatId);
	          ui_vue.Vue.nextTick(function () {
	            store.commit('update', {
	              id: payload.id,
	              chatId: payload.chatId,
	              fields: {
	                sending: false,
	                error: false,
	                retry: false
	              }
	            });
	          });
	        },
	        set: function set(store, payload) {
	          if (payload instanceof Array) {
	            payload = payload.map(function (message) {
	              return _this2.prepareMessage(message);
	            });
	          } else {
	            var result = _this2.prepareMessage(payload);

	            (payload = []).push(result);
	          }

	          store.commit('set', {
	            insertType: im_const.MutationType.set,
	            data: payload
	          });
	        },
	        setAfter: function setAfter(store, payload) {
	          if (payload instanceof Array) {
	            payload = payload.map(function (message) {
	              return _this2.prepareMessage(message);
	            });
	          } else {
	            var result = _this2.prepareMessage(payload);

	            (payload = []).push(result);
	          }

	          store.commit('set', {
	            insertType: im_const.MutationType.setAfter,
	            data: payload
	          });
	        },
	        setBefore: function setBefore(store, payload) {
	          if (payload instanceof Array) {
	            payload = payload.map(function (message) {
	              return _this2.prepareMessage(message);
	            });
	          } else {
	            var result = _this2.prepareMessage(payload);

	            (payload = []).push(result);
	          }

	          store.commit('set', {
	            insertType: im_const.MutationType.setBefore,
	            data: payload
	          });
	        },
	        update: function update(store, payload) {
	          if (/^\d+$/.test(payload.id)) {
	            payload.id = parseInt(payload.id);
	          }

	          if (/^\d+$/.test(payload.chatId)) {
	            payload.chatId = parseInt(payload.chatId);
	          }

	          store.commit('initCollection', {
	            chatId: payload.chatId
	          });
	          var index = store.state.collection[payload.chatId].findIndex(function (el) {
	            return el.id === payload.id;
	          });

	          if (index < 0) {
	            return false;
	          }

	          var result = _this2.validate(Object.assign({}, payload.fields));

	          if (result.params) {
	            result.params = Object.assign({}, _this2.getElementState().params, store.state.collection[payload.chatId][index].params, result.params);
	          }

	          store.commit('update', {
	            id: payload.id,
	            chatId: payload.chatId,
	            index: index,
	            fields: result
	          });

	          if (payload.fields.blink) {
	            setTimeout(function () {
	              store.commit('update', {
	                id: payload.id,
	                chatId: payload.chatId,
	                fields: {
	                  blink: false
	                }
	              });
	            }, 1000);
	          }

	          return true;
	        },
	        delete: function _delete(store, payload) {
	          if (!(payload.id instanceof Array)) {
	            payload.id = [payload.id];
	          }

	          payload.id = payload.id.map(function (id) {
	            if (/^\d+$/.test(id)) {
	              id = parseInt(id);
	            }

	            return id;
	          });
	          store.commit('delete', {
	            chatId: payload.chatId,
	            elements: payload.id
	          });
	          return true;
	        },
	        clear: function clear(store, payload) {
	          payload.chatId = parseInt(payload.chatId);
	          store.commit('clear', {
	            chatId: payload.chatId
	          });
	          return true;
	        },
	        applyMutationType: function applyMutationType(store, payload) {
	          payload.chatId = parseInt(payload.chatId);
	          store.commit('applyMutationType', {
	            chatId: payload.chatId
	          });
	          return true;
	        },
	        readMessages: function readMessages(store, payload) {
	          payload.readId = parseInt(payload.readId) || 0;
	          payload.chatId = parseInt(payload.chatId);

	          if (typeof store.state.collection[payload.chatId] === 'undefined') {
	            return {
	              count: 0
	            };
	          }

	          var count = 0;

	          for (var index = store.state.collection[payload.chatId].length - 1; index >= 0; index--) {
	            var element = store.state.collection[payload.chatId][index];
	            if (!element.unread) continue;

	            if (payload.readId === 0 || element.id <= payload.readId) {
	              count++;
	            }
	          }

	          store.commit('readMessages', {
	            chatId: payload.chatId,
	            readId: payload.readId
	          });
	          return {
	            count: count
	          };
	        },
	        unreadMessages: function unreadMessages(store, payload) {
	          payload.unreadId = parseInt(payload.unreadId) || 0;
	          payload.chatId = parseInt(payload.chatId);

	          if (typeof store.state.collection[payload.chatId] === 'undefined' || !payload.unreadId) {
	            return {
	              count: 0
	            };
	          }

	          var count = 0;

	          for (var index = store.state.collection[payload.chatId].length - 1; index >= 0; index--) {
	            var element = store.state.collection[payload.chatId][index];
	            if (element.unread) continue;

	            if (element.id >= payload.unreadId) {
	              count++;
	            }
	          }

	          store.commit('unreadMessages', {
	            chatId: payload.chatId,
	            unreadId: payload.unreadId
	          });
	          return {
	            count: count
	          };
	        }
	      };
	    }
	  }, {
	    key: "getMutations",
	    value: function getMutations() {
	      var _this3 = this;

	      return {
	        initCollection: function initCollection(state, payload) {
	          return _this3.initCollection(state, payload);
	        },
	        add: function add(state, payload) {
	          _this3.initCollection(state, {
	            chatId: payload.chatId
	          });

	          _this3.setMutationType(state, {
	            chatId: payload.chatId,
	            initialType: im_const.MutationType.add
	          });

	          state.collection[payload.chatId].push(payload);
	          state.saveMessageList[payload.chatId].push(payload.id);
	          state.created += 1;

	          _this3.saveState(state, payload.chatId);
	        },
	        set: function set(state, payload) {
	          var chats = [];
	          var chatsSave = [];
	          var mutationType = {};
	          mutationType.initialType = payload.insertType;

	          if (payload.insertType === im_const.MutationType.set) {
	            (function () {
	              payload.insertType = im_const.MutationType.setAfter;
	              var elements = {};
	              payload.data.forEach(function (element) {
	                if (!elements[element.chatId]) {
	                  elements[element.chatId] = [];
	                }

	                elements[element.chatId].push(element.id);
	              });

	              var _loop = function _loop(chatId) {
	                if (!elements.hasOwnProperty(chatId)) return "continue";

	                _this3.initCollection(state, {
	                  chatId: chatId
	                });

	                if (state.saveMessageList[chatId].length > elements[chatId].length || elements[chatId].length < im_const.StorageLimit.messages) {
	                  state.collection[chatId] = state.collection[chatId].filter(function (element) {
	                    return elements[chatId].includes(element.id);
	                  });
	                  state.saveMessageList[chatId] = state.saveMessageList[chatId].filter(function (id) {
	                    return elements[chatId].includes(id);
	                  });
	                }

	                var intersection = _this3.manageCacheBeforeSet(babelHelpers.toConsumableArray(state.saveMessageList[chatId].reverse()), elements[chatId]);

	                if (intersection.type === IntersectionType.none) {
	                  if (intersection.foundElements.length > 0) {
	                    state.collection[chatId] = state.collection[chatId].filter(function (element) {
	                      return !intersection.foundElements.includes(element.id);
	                    });
	                    state.saveMessageList[chatId] = state.saveMessageList[chatId].filter(function (id) {
	                      return !intersection.foundElements.includes(id);
	                    });
	                  }

	                  _this3.removeIntersectionCacheElements = state.collection[chatId].map(function (element) {
	                    return element.id;
	                  });
	                  clearTimeout(_this3.removeIntersectionCacheTimeout);
	                  _this3.removeIntersectionCacheTimeout = setTimeout(function () {
	                    state.collection[chatId] = state.collection[chatId].filter(function (element) {
	                      return !_this3.removeIntersectionCacheElements.includes(element.id);
	                    });
	                    state.saveMessageList[chatId] = state.saveMessageList[chatId].filter(function (id) {
	                      return !_this3.removeIntersectionCacheElements.includes(id);
	                    });
	                    _this3.removeIntersectionCacheElements = [];
	                  }, 1000);
	                } else {
	                  if (intersection.type === IntersectionType.foundReverse) {
	                    payload.insertType = im_const.MutationType.setBefore;
	                    payload.data = payload.data.reverse();
	                  }
	                }

	                if (intersection.foundElements.length > 0) {
	                  if (intersection.type === IntersectionType.found && intersection.noneElements[0]) {
	                    mutationType.scrollStickToTop = false;
	                    mutationType.scrollMessageId = intersection.foundElements[intersection.foundElements.length - 1];
	                  } else {
	                    mutationType.scrollStickToTop = false;
	                    mutationType.scrollMessageId = 0;
	                  }
	                } else if (intersection.type === IntersectionType.none) {
	                  mutationType.scrollStickToTop = false;
	                  mutationType.scrollMessageId = payload.data[0].id;
	                }
	              };

	              for (var chatId in elements) {
	                var _ret = _loop(chatId);

	                if (_ret === "continue") continue;
	              }
	            })();
	          }

	          mutationType.appliedType = payload.insertType;
	          var _iteratorNormalCompletion = true;
	          var _didIteratorError = false;
	          var _iteratorError = undefined;

	          try {
	            var _loop2 = function _loop2() {
	              var element = _step.value;

	              _this3.initCollection(state, {
	                chatId: element.chatId
	              });

	              var index = state.collection[element.chatId].findIndex(function (el) {
	                return el.id === element.id;
	              });

	              if (index > -1) {
	                delete element.templateId;
	                state.collection[element.chatId][index] = Object.assign(state.collection[element.chatId][index], element);
	              } else if (payload.insertType === im_const.MutationType.setBefore) {
	                state.collection[element.chatId].unshift(element);
	              } else if (payload.insertType === im_const.MutationType.setAfter) {
	                state.collection[element.chatId].push(element);
	              }

	              chats.push(element.chatId);

	              if (_this3.store.getters['dialogues/canSaveChat'] && _this3.store.getters['dialogues/canSaveChat'](element.chatId)) {
	                chatsSave.push(element.chatId);
	              }
	            };

	            for (var _iterator = payload.data[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
	              _loop2();
	            }
	          } catch (err) {
	            _didIteratorError = true;
	            _iteratorError = err;
	          } finally {
	            try {
	              if (!_iteratorNormalCompletion && _iterator.return != null) {
	                _iterator.return();
	              }
	            } finally {
	              if (_didIteratorError) {
	                throw _iteratorError;
	              }
	            }
	          }

	          chats = babelHelpers.toConsumableArray(new Set(chats));
	          chatsSave = babelHelpers.toConsumableArray(new Set(chatsSave)); // check array for correct order of messages

	          if (mutationType.initialType === im_const.MutationType.set) {
	            chats.forEach(function (chatId) {
	              var lastElementId = 0;
	              var needApplySort = false;

	              for (var i = 0; i < state.collection[chatId].length; i++) {
	                var element = state.collection[chatId][i];

	                if (element.id < lastElementId) {
	                  needApplySort = true;
	                  break;
	                }

	                lastElementId = element.id;
	              }

	              if (needApplySort) {
	                state.collection[chatId].sort(function (a, b) {
	                  return a.id - b.id;
	                });
	              }
	            });
	          }

	          chats.forEach(function (chatId) {
	            _this3.setMutationType(state, babelHelpers.objectSpread({
	              chatId: chatId
	            }, mutationType));
	          });

	          if (mutationType.initialType !== im_const.MutationType.setBefore) {
	            chatsSave.forEach(function (chatId) {
	              _this3.saveState(state, chatId);
	            });
	          }
	        },
	        update: function update(state, payload) {
	          _this3.initCollection(state, {
	            chatId: payload.chatId
	          });

	          var index = -1;

	          if (typeof payload.index !== 'undefined' && state.collection[payload.chatId][payload.index]) {
	            index = payload.index;
	          } else {
	            index = state.collection[payload.chatId].findIndex(function (el) {
	              return el.id === payload.id;
	            });
	          }

	          if (index >= 0) {
	            var isSaveState = state.saveMessageList[payload.chatId].includes(state.collection[payload.chatId][index].id) || payload.fields.id && !payload.fields.id.toString().startsWith('temporary') && state.collection[payload.chatId][index].id.toString().startsWith('temporary');
	            delete payload.fields.templateId;
	            state.collection[payload.chatId][index] = Object.assign(state.collection[payload.chatId][index], payload.fields);

	            if (isSaveState) {
	              _this3.saveState(state, payload.chatId);
	            }
	          }
	        },
	        delete: function _delete(state, payload) {
	          _this3.initCollection(state, {
	            chatId: payload.chatId
	          });

	          _this3.setMutationType(state, {
	            chatId: payload.chatId,
	            initialType: im_const.MutationType.delete
	          });

	          state.collection[payload.chatId] = state.collection[payload.chatId].filter(function (element) {
	            return !payload.elements.includes(element.id);
	          });

	          if (state.saveMessageList[payload.chatId].length > 0) {
	            var _iteratorNormalCompletion2 = true;
	            var _didIteratorError2 = false;
	            var _iteratorError2 = undefined;

	            try {
	              for (var _iterator2 = payload.elements[Symbol.iterator](), _step2; !(_iteratorNormalCompletion2 = (_step2 = _iterator2.next()).done); _iteratorNormalCompletion2 = true) {
	                var id = _step2.value;

	                if (state.saveMessageList[payload.chatId].includes(id)) {
	                  _this3.saveState(state, payload.chatId);

	                  break;
	                }
	              }
	            } catch (err) {
	              _didIteratorError2 = true;
	              _iteratorError2 = err;
	            } finally {
	              try {
	                if (!_iteratorNormalCompletion2 && _iterator2.return != null) {
	                  _iterator2.return();
	                }
	              } finally {
	                if (_didIteratorError2) {
	                  throw _iteratorError2;
	                }
	              }
	            }
	          }
	        },
	        clear: function clear(state, payload) {
	          _this3.initCollection(state, {
	            chatId: payload.chatId
	          });

	          _this3.setMutationType(state, {
	            chatId: payload.chatId,
	            initialType: 'clear'
	          });

	          state.collection[payload.chatId] = [];
	          state.saveMessageList[payload.chatId] = [];
	        },
	        applyMutationType: function applyMutationType(state, payload) {
	          if (typeof state.mutationType[payload.chatId] === 'undefined') {
	            ui_vue.Vue.set(state.mutationType, payload.chatId, {
	              applied: false,
	              initialType: im_const.MutationType.none,
	              appliedType: im_const.MutationType.none,
	              scrollStickToTop: 0,
	              scrollMessageId: 0
	            });
	          }

	          state.mutationType[payload.chatId].applied = true;
	        },
	        readMessages: function readMessages(state, payload) {
	          _this3.initCollection(state, {
	            chatId: payload.chatId
	          });

	          var saveNeeded = false;

	          for (var index = state.collection[payload.chatId].length - 1; index >= 0; index--) {
	            var element = state.collection[payload.chatId][index];
	            if (!element.unread) continue;

	            if (payload.readId === 0 || element.id <= payload.readId) {
	              state.collection[payload.chatId][index] = Object.assign(state.collection[payload.chatId][index], {
	                unread: false
	              });
	              saveNeeded = true;
	            }
	          }

	          if (saveNeeded) {
	            _this3.saveState(state, payload.chatId);
	          }
	        },
	        unreadMessages: function unreadMessages(state, payload) {
	          _this3.initCollection(state, {
	            chatId: payload.chatId
	          });

	          var saveNeeded = false;

	          for (var index = state.collection[payload.chatId].length - 1; index >= 0; index--) {
	            var element = state.collection[payload.chatId][index];
	            if (element.unread) continue;

	            if (element.id >= payload.unreadId) {
	              state.collection[payload.chatId][index] = Object.assign(state.collection[payload.chatId][index], {
	                unread: true
	              });
	              saveNeeded = true;
	            }
	          }

	          if (saveNeeded) {
	            _this3.saveState(state, payload.chatId);

	            _this3.updateSubordinateStates();
	          }
	        }
	      };
	    }
	  }, {
	    key: "initCollection",
	    value: function initCollection(state, payload) {
	      if (typeof payload.chatId === 'undefined') {
	        return false;
	      }

	      if (typeof payload.chatId === 'undefined' || typeof state.collection[payload.chatId] !== 'undefined') {
	        return true;
	      }

	      ui_vue.Vue.set(state.collection, payload.chatId, payload.messages ? [].concat(payload.messages) : []);
	      ui_vue.Vue.set(state.mutationType, payload.chatId, {
	        applied: false,
	        initialType: im_const.MutationType.none,
	        appliedType: im_const.MutationType.none,
	        scrollStickToTop: 0,
	        scrollMessageId: 0
	      });
	      ui_vue.Vue.set(state.saveMessageList, payload.chatId, []);
	      ui_vue.Vue.set(state.saveFileList, payload.chatId, []);
	      ui_vue.Vue.set(state.saveUserList, payload.chatId, []);
	      return true;
	    }
	  }, {
	    key: "setMutationType",
	    value: function setMutationType(state, payload) {
	      var mutationType = {
	        applied: false,
	        initialType: im_const.MutationType.none,
	        appliedType: im_const.MutationType.none,
	        scrollStickToTop: false,
	        scrollMessageId: 0
	      };

	      if (payload.initialType && !payload.appliedType) {
	        payload.appliedType = payload.initialType;
	      }

	      if (typeof state.mutationType[payload.chatId] === 'undefined') {
	        ui_vue.Vue.set(state.mutationType, payload.chatId, mutationType);
	      }

	      state.mutationType[payload.chatId] = babelHelpers.objectSpread({}, mutationType, payload);
	      return true;
	    }
	  }, {
	    key: "prepareMessage",
	    value: function prepareMessage(message) {
	      var result = this.validate(Object.assign({}, message));
	      result.params = Object.assign({}, this.getElementState().params, result.params);
	      result.templateId = result.id;
	      return Object.assign({}, this.getElementState(), result);
	    }
	  }, {
	    key: "manageCacheBeforeSet",
	    value: function manageCacheBeforeSet(cache, elements) {
	      var recursive = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
	      var result = {
	        type: IntersectionType.empty,
	        foundElements: [],
	        noneElements: []
	      };

	      if (!cache || cache.length <= 0) {
	        return result;
	      }

	      var _iteratorNormalCompletion3 = true;
	      var _didIteratorError3 = false;
	      var _iteratorError3 = undefined;

	      try {
	        for (var _iterator3 = elements[Symbol.iterator](), _step3; !(_iteratorNormalCompletion3 = (_step3 = _iterator3.next()).done); _iteratorNormalCompletion3 = true) {
	          var id = _step3.value;

	          if (cache.includes(id)) {
	            if (result.type === IntersectionType.empty) {
	              result.type = IntersectionType.found;
	            }

	            result.foundElements.push(id);
	          } else {
	            if (result.type === IntersectionType.empty) {
	              result.type = IntersectionType.none;
	            }

	            result.noneElements.push(id);
	          }
	        }
	      } catch (err) {
	        _didIteratorError3 = true;
	        _iteratorError3 = err;
	      } finally {
	        try {
	          if (!_iteratorNormalCompletion3 && _iterator3.return != null) {
	            _iterator3.return();
	          }
	        } finally {
	          if (_didIteratorError3) {
	            throw _iteratorError3;
	          }
	        }
	      }

	      if (result.type === IntersectionType.found && cache.length === elements.length && result.foundElements.length === elements.length) {
	        result.type = IntersectionType.equal;
	      } else if (result.type === IntersectionType.none && !recursive && result.foundElements.length > 0) {
	        var reverseResult = this.manageCacheBeforeSet(cache.reverse(), elements.reverse(), true);

	        if (reverseResult.type === IntersectionType.found) {
	          reverseResult.type = IntersectionType.foundReverse;
	          return reverseResult;
	        }
	      }

	      return result;
	    }
	  }, {
	    key: "updateSaveLists",
	    value: function updateSaveLists(state, chatId) {
	      if (!this.isSaveAvailable()) {
	        return true;
	      }

	      if (!chatId || !this.store.getters['dialogues/canSaveChat'] || !this.store.getters['dialogues/canSaveChat'](chatId)) {
	        return false;
	      }

	      this.initCollection(state, {
	        chatId: chatId
	      });
	      var count = 0;
	      var saveMessageList = [];
	      var saveFileList = [];
	      var saveUserList = [];
	      var dialog = this.store.getters['dialogues/getByChatId'](chatId);

	      if (dialog && dialog.type === 'private') {
	        saveUserList.push(parseInt(dialog.dialogId));
	      }

	      for (var index = state.collection[chatId].length - 1; index >= 0; index--) {
	        if (state.collection[chatId][index].id.toString().startsWith('temporary')) {
	          continue;
	        }

	        if (count >= im_const.StorageLimit.messages && !state.collection[chatId][index].unread) {
	          break;
	        }

	        saveMessageList.unshift(state.collection[chatId][index].id);
	        count++;
	      }

	      saveMessageList = saveMessageList.slice(0, im_const.StorageLimit.messages);
	      state.collection[chatId].filter(function (element) {
	        return saveMessageList.includes(element.id);
	      }).forEach(function (element) {
	        if (element.authorId > 0) {
	          saveUserList.push(element.authorId);
	        }

	        if (element.params.FILE_ID instanceof Array) {
	          saveFileList = element.params.FILE_ID.concat(saveFileList);
	        }
	      });
	      state.saveMessageList[chatId] = saveMessageList;
	      state.saveFileList[chatId] = babelHelpers.toConsumableArray(new Set(saveFileList));
	      state.saveUserList[chatId] = babelHelpers.toConsumableArray(new Set(saveUserList));
	      return true;
	    }
	  }, {
	    key: "getSaveTimeout",
	    value: function getSaveTimeout() {
	      return 150;
	    }
	  }, {
	    key: "saveState",
	    value: function saveState(state, chatId) {
	      if (!this.updateSaveLists(state, chatId)) {
	        return false;
	      }

	      babelHelpers.get(babelHelpers.getPrototypeOf(MessagesModel.prototype), "saveState", this).call(this, function () {
	        var storedState = {
	          collection: {},
	          saveMessageList: {},
	          saveUserList: {},
	          saveFileList: {}
	        };

	        var _loop3 = function _loop3(_chatId) {
	          if (!state.saveMessageList.hasOwnProperty(_chatId)) {
	            return "continue";
	          }

	          if (!state.collection[_chatId]) {
	            return "continue";
	          }

	          if (!storedState.collection[_chatId]) {
	            storedState.collection[_chatId] = [];
	          }

	          state.collection[_chatId].filter(function (element) {
	            return state.saveMessageList[_chatId].includes(element.id);
	          }).forEach(function (element) {
	            return storedState.collection[_chatId].push(element);
	          });

	          storedState.saveMessageList[_chatId] = state.saveMessageList[_chatId];
	          storedState.saveFileList[_chatId] = state.saveFileList[_chatId];
	          storedState.saveUserList[_chatId] = state.saveUserList[_chatId];
	        };

	        for (var _chatId in state.saveMessageList) {
	          var _ret2 = _loop3(_chatId);

	          if (_ret2 === "continue") continue;
	        }

	        return storedState;
	      });
	    }
	  }, {
	    key: "updateSubordinateStates",
	    value: function updateSubordinateStates() {
	      this.store.dispatch('users/saveState');
	      this.store.dispatch('files/saveState');
	    }
	  }, {
	    key: "validate",
	    value: function validate(fields) {
	      var result = {};

	      if (typeof fields.id === "number") {
	        result.id = fields.id;
	      } else if (typeof fields.id === "string") {
	        if (fields.id.startsWith('temporary')) {
	          result.id = fields.id;
	        } else {
	          result.id = parseInt(fields.id);
	        }
	      }

	      if (typeof fields.templateId === "number") {
	        result.templateId = fields.templateId;
	      } else if (typeof fields.templateId === "string") {
	        if (fields.templateId.startsWith('temporary')) {
	          result.templateId = fields.templateId;
	        } else {
	          result.templateId = parseInt(fields.templateId);
	        }
	      }

	      if (typeof fields.chat_id !== 'undefined') {
	        fields.chatId = fields.chat_id;
	      }

	      if (typeof fields.chatId === "number" || typeof fields.chatId === "string") {
	        result.chatId = parseInt(fields.chatId);
	      }

	      if (typeof fields.date !== "undefined") {
	        result.date = im_utils.Utils.date.cast(fields.date);
	      } // previous P&P format


	      if (typeof fields.textOriginal === "string" || typeof fields.textOriginal === "number") {
	        result.text = fields.textOriginal.toString();

	        if (typeof fields.text === "string" || typeof fields.text === "number") {
	          result.textConverted = this.convertToHtml({
	            text: fields.text.toString(),
	            isConverted: true
	          });
	        }
	      } else // modern format
	        {
	          if (typeof fields.text_converted !== 'undefined') {
	            fields.textConverted = fields.text_converted;
	          }

	          if (typeof fields.textConverted === "string" || typeof fields.textConverted === "number") {
	            result.textConverted = fields.textConverted.toString();
	          }

	          if (typeof fields.text === "string" || typeof fields.text === "number") {
	            result.text = fields.text.toString();
	            var isConverted = typeof result.textConverted !== 'undefined';
	            result.textConverted = this.convertToHtml({
	              text: isConverted ? result.textConverted : result.text,
	              isConverted: isConverted
	            });
	          }
	        }

	      if (typeof fields.senderId !== 'undefined') {
	        fields.authorId = fields.senderId;
	      } else if (typeof fields.author_id !== 'undefined') {
	        fields.authorId = fields.author_id;
	      }

	      if (typeof fields.authorId === "number" || typeof fields.authorId === "string") {
	        if (fields.system === true || fields.system === 'Y') {
	          result.authorId = 0;
	        } else {
	          result.authorId = parseInt(fields.authorId);
	        }
	      }

	      if (babelHelpers.typeof(fields.params) === "object" && fields.params !== null) {
	        var params = this.validateParams(fields.params);

	        if (params) {
	          result.params = params;
	        }
	      }

	      if (typeof fields.push === "boolean") {
	        result.push = fields.push;
	      }

	      if (typeof fields.sending === "boolean") {
	        result.sending = fields.sending;
	      }

	      if (typeof fields.unread === "boolean") {
	        result.unread = fields.unread;
	      }

	      if (typeof fields.blink === "boolean") {
	        result.blink = fields.blink;
	      }

	      if (typeof fields.error === "boolean" || typeof fields.error === "string") {
	        result.error = fields.error;
	      }

	      if (typeof fields.retry === "boolean") {
	        result.retry = fields.retry;
	      }

	      return result;
	    }
	  }, {
	    key: "validateParams",
	    value: function validateParams(params) {
	      var result = {};

	      try {
	        for (var field in params) {
	          if (!params.hasOwnProperty(field)) {
	            continue;
	          }

	          if (field === 'COMPONENT_ID') {
	            if (typeof params[field] === "string" && BX.Vue.isComponent(params[field])) {
	              result[field] = params[field];
	            }
	          } else if (field === 'LIKE') {
	            if (params[field] instanceof Array) {
	              result['REACTION'] = {
	                like: params[field].map(function (element) {
	                  return parseInt(element);
	                })
	              };
	            }
	          } else if (field === 'CHAT_LAST_DATE') {
	            result[field] = im_utils.Utils.date.cast(params[field]);
	          } else {
	            result[field] = params[field];
	          }
	        }
	      } catch (e) {}

	      var hasResultElements = false;

	      for (var _field in result) {
	        if (!result.hasOwnProperty(_field)) {
	          continue;
	        }

	        hasResultElements = true;
	        break;
	      }

	      return hasResultElements ? result : null;
	    }
	  }, {
	    key: "convertToHtml",
	    value: function convertToHtml() {
	      var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      var _params$quote = params.quote,
	          quote = _params$quote === void 0 ? true : _params$quote,
	          _params$image = params.image,
	          image = _params$image === void 0 ? true : _params$image,
	          _params$text = params.text,
	          text = _params$text === void 0 ? '' : _params$text,
	          _params$highlightText = params.highlightText,
	          highlightText = _params$highlightText === void 0 ? '' : _params$highlightText,
	          _params$isConverted = params.isConverted,
	          isConverted = _params$isConverted === void 0 ? false : _params$isConverted,
	          _params$enableBigSmil = params.enableBigSmile,
	          enableBigSmile = _params$enableBigSmil === void 0 ? true : _params$enableBigSmil;
	      text = text.trim();

	      if (!isConverted) {
	        text = text.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
	      }

	      if (text.startsWith('/me')) {
	        text = "<i>".concat(text.substr(4), "</i>");
	      } else if (text.startsWith('/loud')) {
	        text = "<b>".concat(text.substr(6), "</b>");
	      }

	      var quoteSign = "&gt;&gt;";

	      if (quote && text.indexOf(quoteSign) >= 0) {
	        var textPrepare = text.split(isConverted ? "<br />" : "\n");

	        for (var i = 0; i < textPrepare.length; i++) {
	          if (textPrepare[i].startsWith(quoteSign)) {
	            textPrepare[i] = textPrepare[i].replace(quoteSign, '<div class="bx-im-message-content-quote"><div class="bx-im-message-content-quote-wrap">');

	            while (++i < textPrepare.length && textPrepare[i].startsWith(quoteSign)) {
	              textPrepare[i] = textPrepare[i].replace(quoteSign, '');
	            }

	            textPrepare[i - 1] += '</div></div><br>';
	          }
	        }

	        text = textPrepare.join("<br />");
	      }

	      text = text.replace(/\n/gi, '<br />');
	      text = text.replace(/\t/gi, '&nbsp;&nbsp;&nbsp;&nbsp;');
	      text = this.decodeBbCode(text, false, enableBigSmile);

	      if (quote) {
	        text = text.replace(/------------------------------------------------------<br \/>(.*?)\[(.*?)\]<br \/>(.*?)------------------------------------------------------(<br \/>)?/g, function (whole, p1, p2, p3, p4, offset) {
	          return (offset > 0 ? '<br>' : '') + "<div class=\"bx-im-message-content-quote\"><div class=\"bx-im-message-content-quote-wrap\"><div class=\"bx-im-message-content-quote-name\"><span class=\"bx-im-message-content-quote-name-text\">" + p1 + "</span><span class=\"bx-im-message-content-quote-name-time\">" + p2 + "</span></div>" + p3 + "</div></div><br />";
	        });
	        text = text.replace(/------------------------------------------------------<br \/>(.*?)------------------------------------------------------(<br \/>)?/g, function (whole, p1, p2, p3, offset) {
	          return (offset > 0 ? '<br>' : '') + "<div class=\"bx-im-message-content-quote\"><div class=\"bx-im-message-content-quote-wrap\">" + p1 + "</div></div><br />";
	        });
	      }

	      if (image) {
	        var changed = false;
	        text = text.replace(/<a(.*?)>(http[s]{0,1}:\/\/.*?)<\/a>/ig, function (whole, aInner, text, offset) {
	          if (!text.match(/(\.(jpg|jpeg|png|gif)\?|\.(jpg|jpeg|png|gif)$)/i) || text.indexOf("/docs/pub/") > 0 || text.indexOf("logout=yes") > 0) {
	            return whole;
	          } else {
	            changed = true;
	            return (offset > 0 ? '<br />' : '') + '<a' + aInner + ' target="_blank" class="bx-im-element-file-image"><img src="' + text + '" class="bx-im-element-file-image-source-text" onerror="BX.Messenger.Model.MessagesModel.hideErrorImage(this)"></a></span>';
	          }
	        });

	        if (changed) {
	          text = text.replace(/<\/span>(\n?)<br(\s\/?)>/ig, '</span>').replace(/<br(\s\/?)>(\n?)<br(\s\/?)>(\n?)<span/ig, '<br /><span');
	        }
	      }

	      if (highlightText) {
	        text = text.replace(new RegExp("(" + highlightText.replace(/[\-\[\]\/{}()*+?.\\^$|]/g, "\\$&") + ")", 'ig'), '<span class="bx-messenger-highlight">$1</span>');
	      }

	      if (enableBigSmile) {
	        text = text.replace(/^(\s*<img\s+src=[^>]+?data-code=[^>]+?data-definition="UHD"[^>]+?style="width:)(\d+)(px[^>]+?height:)(\d+)(px[^>]+?class="bx-smile"\s*\/?>\s*)$/, function doubleSmileSize(match, start, width, middle, height, end) {
	          return start + parseInt(width, 10) * 1.7 + middle + parseInt(height, 10) * 1.7 + end;
	        });
	      }

	      if (text.substr(-6) == '<br />') {
	        text = text.substr(0, text.length - 6);
	      }

	      text = text.replace(/<br><br \/>/ig, '<br />');
	      text = text.replace(/<br \/><br>/ig, '<br />');
	      return text;
	    }
	  }, {
	    key: "decodeBbCode",
	    value: function decodeBbCode(text) {
	      var textOnly = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
	      var enableBigSmile = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;
	      return MessagesModel.decodeBbCode({
	        text: text,
	        textOnly: textOnly,
	        enableBigSmile: enableBigSmile
	      });
	    }
	  }], [{
	    key: "decodeBbCode",
	    value: function decodeBbCode() {
	      var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      var text = params.text,
	          _params$textOnly = params.textOnly,
	          textOnly = _params$textOnly === void 0 ? false : _params$textOnly,
	          _params$enableBigSmil2 = params.enableBigSmile,
	          enableBigSmile = _params$enableBigSmil2 === void 0 ? true : _params$enableBigSmil2;
	      var codeReplacement = [];
	      text = text.replace(/\[CODE\]\n?([\s\S]*?)\[\/CODE\]/ig, function (whole, text) {
	        var id = codeReplacement.length;
	        codeReplacement.push(text);
	        return '####REPLACEMENT_MARK_' + id + '####';
	      });
	      text = text.replace(/\[LIKE\]/ig, '<span class="bx-smile bx-im-smile-like"></span>');
	      text = text.replace(/\[DISLIKE\]/ig, '<span class="bx-smile bx-im-smile-dislike"></span>');
	      text = text.replace(/\[USER=([0-9]{1,})\](.*?)\[\/USER\]/ig, function (whole, userId, text) {
	        return '<span class="bx-im-mention" data-type="USER" data-value="' + userId + '">' + text + '</span>';
	      });
	      text = text.replace(/\[CHAT=(imol\|)?([0-9]{1,})\](.*?)\[\/CHAT\]/ig, function (whole, openlines, chatId, text) {
	        return openlines ? text : '<span class="bx-im-mention" data-type="CHAT" data-value="chat' + chatId + '">' + text + '</span>';
	      }); // TODO tag CHAT

	      text = text.replace(/\[CALL(?:=(.+?))?\](.+?)?\[\/CALL\]/ig, function (whole, number, text) {
	        return '<span class="bx-im-mention" data-type="CALL" data-value="' + im_utils.Utils.text.htmlspecialchars(number) + '">' + text + '</span>';
	      }); // TODO tag CHAT

	      text = text.replace(/\[PCH=([0-9]{1,})\](.*?)\[\/PCH\]/ig, function (whole, historyId, text) {
	        return text;
	      }); // TODO tag PCH

	      text = text.replace(/\[SEND(?:=(.+?))?\](.+?)?\[\/SEND\]/ig, function (whole, command, text) {
	        var html = '';
	        text = text ? text : command;
	        command = (command ? command : text).replace('<br />', '\n');

	        if (!textOnly && text) {
	          text = text.replace(/<([\w]+)[^>]*>(.*?)<\\1>/i, "$2", text);
	          text = text.replace(/\[([\w]+)[^\]]*\](.*?)\[\/\1\]/i, "$2", text);
	          html = '<span class="bx-im-message-command-wrap">' + '<span class="bx-im-message-command" data-entity="send">' + text + '</span>' + '<span class="bx-im-message-command-data">' + command + '</span>' + '</span>';
	        } else {
	          html = text;
	        }

	        return html;
	      });
	      text = text.replace(/\[PUT(?:=(.+?))?\](.+?)?\[\/PUT\]/ig, function (whole, command, text) {
	        var html = '';
	        text = text ? text : command;
	        command = (command ? command : text).replace('<br />', '\n');

	        if (!textOnly && text) {
	          text = text.replace(/<([\w]+)[^>]*>(.*?)<\/\1>/i, "$2", text);
	          text = text.replace(/\[([\w]+)[^\]]*\](.*?)\[\/\1\]/i, "$2", text);
	          html = '<span class="bx-im-message-command" data-entity="put">' + text + '</span>';
	          html += '<span class="bx-im-message-command-data">' + command + '</span>';
	        } else {
	          html = text;
	        }

	        return html;
	      });
	      var textElementSize = 0;

	      if (enableBigSmile) {
	        textElementSize = text.replace(/\[icon\=([^\]]*)\]/ig, '').trim().length;
	      }

	      text = text.replace(/\[icon\=([^\]]*)\]/ig, function (whole) {
	        var url = whole.match(/icon\=(\S+[^\s.,> )\];\'\"!?])/i);

	        if (url && url[1]) {
	          url = url[1];
	        } else {
	          return '';
	        }

	        var attrs = {
	          'src': url,
	          'border': 0
	        };
	        var size = whole.match(/size\=(\d+)/i);

	        if (size && size[1]) {
	          attrs['width'] = size[1];
	          attrs['height'] = size[1];
	        } else {
	          var width = whole.match(/width\=(\d+)/i);

	          if (width && width[1]) {
	            attrs['width'] = width[1];
	          }

	          var height = whole.match(/height\=(\d+)/i);

	          if (height && height[1]) {
	            attrs['height'] = height[1];
	          }

	          if (attrs['width'] && !attrs['height']) {
	            attrs['height'] = attrs['width'];
	          } else if (attrs['height'] && !attrs['width']) {
	            attrs['width'] = attrs['height'];
	          } else if (attrs['height'] && attrs['width']) ; else {
	            attrs['width'] = 20;
	            attrs['height'] = 20;
	          }
	        }

	        attrs['width'] = attrs['width'] > 100 ? 100 : attrs['width'];
	        attrs['height'] = attrs['height'] > 100 ? 100 : attrs['height'];

	        if (enableBigSmile && textElementSize === 0 && attrs['width'] === attrs['height'] && attrs['width'] === 20) {
	          attrs['width'] = 40;
	          attrs['height'] = 40;
	        }

	        var title = whole.match(/title\=(.*[^\s\]])/i);

	        if (title && title[1]) {
	          title = title[1];

	          if (title.indexOf('width=') > -1) {
	            title = title.substr(0, title.indexOf('width='));
	          }

	          if (title.indexOf('height=') > -1) {
	            title = title.substr(0, title.indexOf('height='));
	          }

	          if (title.indexOf('size=') > -1) {
	            title = title.substr(0, title.indexOf('size='));
	          }

	          if (title) {
	            attrs['title'] = im_utils.Utils.text.htmlspecialchars(title).trim();
	            attrs['alt'] = attrs['title'];
	          }
	        }

	        var attributes = '';

	        for (var name in attrs) {
	          if (attrs.hasOwnProperty(name)) {
	            attributes += name + '="' + attrs[name] + '" ';
	          }
	        }

	        return '<img class="bx-smile bx-icon" ' + attributes + '>';
	      });
	      codeReplacement.forEach(function (code, index) {
	        text = text.replace('####REPLACEMENT_MARK_' + index + '####', !textOnly ? '<div class="bx-im-message-content-code">' + code + '</div>' : code);
	      });
	      return text;
	    }
	  }, {
	    key: "hideErrorImage",
	    value: function hideErrorImage(element) {
	      if (element.parentNode && element.parentNode) {
	        element.parentNode.innerHTML = '<a href="' + element.src + '" target="_blank">' + element.src + '</a>';
	      }

	      return true;
	    }
	  }]);
	  return MessagesModel;
	}(ui_vue_vuex.VuexBuilderModel);

	/**
	 * Bitrix Messenger
	 * Dialogues model (Vuex Builder model)
	 *
	 * @package bitrix
	 * @subpackage im
	 * @copyright 2001-2019 Bitrix
	 */

	var DialoguesModel =
	/*#__PURE__*/
	function (_VuexBuilderModel) {
	  babelHelpers.inherits(DialoguesModel, _VuexBuilderModel);

	  function DialoguesModel() {
	    babelHelpers.classCallCheck(this, DialoguesModel);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(DialoguesModel).apply(this, arguments));
	  }

	  babelHelpers.createClass(DialoguesModel, [{
	    key: "getName",
	    value: function getName() {
	      return 'dialogues';
	    }
	  }, {
	    key: "getState",
	    value: function getState() {
	      return {
	        host: this.getVariable('host', location.protocol + '//' + location.host),
	        collection: {},
	        saveDialogList: [],
	        saveChatList: []
	      };
	    }
	  }, {
	    key: "getStateSaveException",
	    value: function getStateSaveException() {
	      return {
	        host: null
	      };
	    }
	  }, {
	    key: "getElementStateSaveException",
	    value: function getElementStateSaveException() {
	      return {
	        writingList: null,
	        quoteId: null
	      };
	    }
	  }, {
	    key: "getElementState",
	    value: function getElementState() {
	      return {
	        dialogId: '0',
	        chatId: 0,
	        counter: 0,
	        unreadId: 0,
	        unreadLastId: 0,
	        managerList: [],
	        readedList: [],
	        writingList: [],
	        textareaMessage: "",
	        quoteId: 0,
	        init: false,
	        name: "",
	        owner: 0,
	        extranet: false,
	        avatar: "",
	        color: "#17A3EA",
	        type: "chat",
	        entityType: "",
	        entityId: "",
	        entityData1: "",
	        entityData2: "",
	        entityData3: "",
	        dateCreate: new Date()
	      };
	    }
	  }, {
	    key: "getGetters",
	    value: function getGetters() {
	      var _this = this;

	      return {
	        get: function get(state) {
	          return function (dialogId) {
	            if (!state.collection[dialogId]) {
	              return null;
	            }

	            return state.collection[dialogId];
	          };
	        },
	        getByChatId: function getByChatId(state) {
	          return function (chatId) {
	            chatId = parseInt(chatId);

	            for (var dialogId in state.collection) {
	              if (!state.collection.hasOwnProperty(dialogId)) {
	                continue;
	              }

	              if (state.collection[dialogId].chatId === chatId) {
	                return state.collection[dialogId];
	              }
	            }

	            return null;
	          };
	        },
	        getBlank: function getBlank(state) {
	          return function (params) {
	            return _this.getElementState();
	          };
	        },
	        getQuoteId: function getQuoteId(state) {
	          return function (dialogId) {
	            if (!state.collection[dialogId]) {
	              return 0;
	            }

	            return state.collection[dialogId].quoteId;
	          };
	        },
	        canSaveChat: function canSaveChat(state) {
	          return function (chatId) {
	            if (/^\d+$/.test(chatId)) {
	              chatId = parseInt(chatId);
	            }

	            return state.saveChatList.includes(parseInt(chatId));
	          };
	        },
	        canSaveDialog: function canSaveDialog(state) {
	          return function (dialogId) {
	            return state.saveDialogList.includes(dialogId.toString());
	          };
	        },
	        isPrivateDialog: function isPrivateDialog(state) {
	          return function (dialogId) {
	            dialogId = dialogId.toString();
	            return state.collection[dialogId.toString()] && state.collection[dialogId].type === 'private';
	          };
	        }
	      };
	    }
	  }, {
	    key: "getActions",
	    value: function getActions() {
	      var _this2 = this;

	      return {
	        set: function set(store, payload) {
	          if (payload instanceof Array) {
	            payload = payload.map(function (dialog) {
	              return Object.assign({}, _this2.validate(Object.assign({}, dialog), {
	                host: store.state.host
	              }), {
	                init: true
	              });
	            });
	          } else {
	            var result = [];
	            result.push(Object.assign({}, _this2.validate(Object.assign({}, payload), {
	              host: store.state.host
	            }), {
	              init: true
	            }));
	            payload = result;
	          }

	          store.commit('set', payload);
	        },
	        update: function update(store, payload) {
	          if (typeof store.state.collection[payload.dialogId] === 'undefined' || store.state.collection[payload.dialogId].init === false) {
	            return true;
	          }

	          store.commit('update', {
	            dialogId: payload.dialogId,
	            fields: _this2.validate(Object.assign({}, payload.fields), {
	              host: store.state.host
	            })
	          });
	          return true;
	        },
	        delete: function _delete(store, payload) {
	          store.commit('delete', payload.dialogId);
	          return true;
	        },
	        updateWriting: function updateWriting(store, payload) {
	          if (typeof store.state.collection[payload.dialogId] === 'undefined' || store.state.collection[payload.dialogId].init === false) {
	            return true;
	          }

	          var index = store.state.collection[payload.dialogId].writingList.findIndex(function (el) {
	            return el.userId === payload.userId;
	          });

	          if (payload.action) {
	            if (index >= 0) {
	              return true;
	            } else {
	              var writingList = [].concat(store.state.collection[payload.dialogId].writingList);
	              writingList.unshift({
	                userId: payload.userId,
	                userName: payload.userName
	              });
	              store.commit('update', {
	                actionName: 'updateWriting/1',
	                dialogId: payload.dialogId,
	                fields: _this2.validate({
	                  writingList: writingList
	                }, {
	                  host: store.state.host
	                })
	              });
	            }
	          } else {
	            if (index >= 0) {
	              var _writingList = store.state.collection[payload.dialogId].writingList.filter(function (el) {
	                return el.userId !== payload.userId;
	              });

	              store.commit('update', {
	                actionName: 'updateWriting/2',
	                dialogId: payload.dialogId,
	                fields: _this2.validate({
	                  writingList: _writingList
	                }, {
	                  host: store.state.host
	                })
	              });
	              return true;
	            } else {
	              return true;
	            }
	          }

	          return false;
	        },
	        updateReaded: function updateReaded(store, payload) {
	          if (typeof store.state.collection[payload.dialogId] === 'undefined' || store.state.collection[payload.dialogId].init === false) {
	            return true;
	          }

	          var readedList = store.state.collection[payload.dialogId].readedList.filter(function (el) {
	            return el.userId !== payload.userId;
	          });

	          if (payload.action) {
	            readedList.push({
	              userId: payload.userId,
	              userName: payload.userName || '',
	              messageId: payload.messageId,
	              date: payload.date || new Date()
	            });
	          }

	          store.commit('update', {
	            actionName: 'updateReaded',
	            dialogId: payload.dialogId,
	            fields: _this2.validate({
	              readedList: readedList
	            }, {
	              host: store.state.host
	            })
	          });
	          return false;
	        },
	        increaseCounter: function increaseCounter(store, payload) {
	          if (typeof store.state.collection[payload.dialogId] === 'undefined' || store.state.collection[payload.dialogId].init === false) {
	            return true;
	          }

	          var counter = store.state.collection[payload.dialogId].counter;

	          if (counter === 100) {
	            return true;
	          }

	          var increasedCounter = counter + payload.count;

	          if (increasedCounter > 100) {
	            increasedCounter = 100;
	          }

	          var fields = {
	            counter: increasedCounter
	          };

	          if (typeof payload.unreadLastId !== 'undefined') {
	            fields.unreadLastId = payload.unreadLastId;
	          }

	          store.commit('update', {
	            actionName: 'increaseCounter',
	            dialogId: payload.dialogId,
	            fields: fields
	          });
	          return false;
	        },
	        decreaseCounter: function decreaseCounter(store, payload) {
	          if (typeof store.state.collection[payload.dialogId] === 'undefined' || store.state.collection[payload.dialogId].init === false) {
	            return true;
	          }

	          var counter = store.state.collection[payload.dialogId].counter;

	          if (counter === 100) {
	            return true;
	          }

	          var decreasedCounter = counter - payload.count;

	          if (decreasedCounter < 0) {
	            decreasedCounter = 0;
	          }

	          var unreadId = payload.unreadId > store.state.collection[payload.dialogId].unreadId ? payload.unreadId : store.state.collection[payload.dialogId].unreadId;

	          if (store.state.collection[payload.dialogId].unreadId !== unreadId || store.state.collection[payload.dialogId].counter !== decreasedCounter) {
	            if (decreasedCounter === 0) {
	              unreadId = 0;
	            }

	            store.commit('update', {
	              actionName: 'decreaseCounter',
	              dialogId: payload.dialogId,
	              fields: {
	                counter: decreasedCounter,
	                unreadId: unreadId
	              }
	            });
	          }

	          return false;
	        },
	        saveDialog: function saveDialog(store, payload) {
	          if (typeof store.state.collection[payload.dialogId] === 'undefined' || store.state.collection[payload.dialogId].init === false) {
	            return true;
	          }

	          store.commit('saveDialog', {
	            dialogId: payload.dialogId,
	            chatId: payload.chatId
	          });
	          return false;
	        }
	      };
	    }
	  }, {
	    key: "getMutations",
	    value: function getMutations() {
	      var _this3 = this;

	      return {
	        initCollection: function initCollection(state, payload) {
	          _this3.initCollection(state, payload);
	        },
	        saveDialog: function saveDialog(state, payload) {
	          // TODO if payload.dialogId is IMOL, skip update this flag
	          if (!(payload.chatId > 0 && payload.dialogId.length > 0)) {
	            return false;
	          }

	          var saveDialogList = state.saveDialogList.filter(function (element) {
	            return element !== payload.dialogId;
	          });
	          saveDialogList.unshift(payload.dialogId);
	          saveDialogList = saveDialogList.slice(0, im_const.StorageLimit.dialogues);

	          if (state.saveDialogList.join(',') === saveDialogList.join(',')) {
	            return true;
	          }

	          state.saveDialogList = saveDialogList;
	          var saveChatList = state.saveChatList.filter(function (element) {
	            return element !== payload.chatId;
	          });
	          saveChatList.unshift(payload.chatId);
	          state.saveChatList = saveChatList.slice(0, im_const.StorageLimit.dialogues);

	          _this3.saveState(state);
	        },
	        set: function set(state, payload) {
	          var _iteratorNormalCompletion = true;
	          var _didIteratorError = false;
	          var _iteratorError = undefined;

	          try {
	            for (var _iterator = payload[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
	              var element = _step.value;

	              _this3.initCollection(state, {
	                dialogId: element.dialogId
	              });

	              state.collection[element.dialogId] = Object.assign(_this3.getElementState(), state.collection[element.dialogId], element);
	            } // TODO if payload.dialogId is IMOL, skip update cache

	          } catch (err) {
	            _didIteratorError = true;
	            _iteratorError = err;
	          } finally {
	            try {
	              if (!_iteratorNormalCompletion && _iterator.return != null) {
	                _iterator.return();
	              }
	            } finally {
	              if (_didIteratorError) {
	                throw _iteratorError;
	              }
	            }
	          }

	          _this3.saveState(state);
	        },
	        update: function update(state, payload) {
	          _this3.initCollection(state, payload);

	          state.collection[payload.dialogId] = Object.assign(state.collection[payload.dialogId], payload.fields); // TODO if payload.dialogId is IMOL, skip update cache

	          _this3.saveState(state);
	        },
	        delete: function _delete(state, payload) {
	          delete state.collection[payload.dialogId]; // TODO if payload.dialogId is IMOL, skip update cache

	          _this3.saveState(state);
	        }
	      };
	    }
	  }, {
	    key: "initCollection",
	    value: function initCollection(state, payload) {
	      if (typeof state.collection[payload.dialogId] !== 'undefined') {
	        return true;
	      }

	      ui_vue.Vue.set(state.collection, payload.dialogId, this.getElementState());

	      if (payload.fields) {
	        state.collection[payload.dialogId] = Object.assign(state.collection[payload.dialogId], this.validate(Object.assign({}, payload.fields), {
	          host: state.host
	        }));
	      }

	      return true;
	    }
	  }, {
	    key: "getSaveTimeout",
	    value: function getSaveTimeout() {
	      return 100;
	    }
	  }, {
	    key: "saveState",
	    value: function saveState() {
	      var _this4 = this;

	      var state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};

	      if (!this.isSaveAvailable()) {
	        return true;
	      }

	      babelHelpers.get(babelHelpers.getPrototypeOf(DialoguesModel.prototype), "saveState", this).call(this, function () {
	        var storedState = {
	          collection: {},
	          saveDialogList: [].concat(state.saveDialogList),
	          saveChatList: [].concat(state.saveChatList)
	        };
	        state.saveDialogList.forEach(function (dialogId) {
	          if (!state.collection[dialogId]) return false;
	          storedState.collection[dialogId] = Object.assign(_this4.getElementState(), _this4.cloneState(state.collection[dialogId], _this4.getElementStateSaveException()));
	        });
	        return storedState;
	      });
	    }
	  }, {
	    key: "validate",
	    value: function validate(fields) {
	      var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
	      var result = {};
	      options.host = options.host || this.getState().host;

	      if (typeof fields.dialog_id !== 'undefined') {
	        fields.dialogId = fields.dialog_id;
	      }

	      if (typeof fields.dialogId === "number" || typeof fields.dialogId === "string") {
	        result.dialogId = fields.dialogId.toString();
	      }

	      if (typeof fields.chat_id !== 'undefined') {
	        fields.chatId = fields.chat_id;
	      } else if (typeof fields.id !== 'undefined') {
	        fields.chatId = fields.id;
	      }

	      if (typeof fields.chatId === "number" || typeof fields.chatId === "string") {
	        result.chatId = parseInt(fields.chatId);
	      }

	      if (typeof fields.quoteId === "number") {
	        result.quoteId = parseInt(fields.quoteId);
	      }

	      if (typeof fields.counter === "number" || typeof fields.counter === "string") {
	        result.counter = parseInt(fields.counter);
	      }

	      if (typeof fields.unread_id !== 'undefined') {
	        fields.unreadId = fields.unread_id;
	      }

	      if (typeof fields.unreadId === "number" || typeof fields.unreadId === "string") {
	        result.unreadId = parseInt(fields.unreadId);
	      }

	      if (typeof fields.unread_last_id !== 'undefined') {
	        fields.unreadLastId = fields.unread_last_id;
	      }

	      if (typeof fields.unreadLastId === "number" || typeof fields.unreadLastId === "string") {
	        result.unreadLastId = parseInt(fields.unreadLastId);
	      }

	      if (typeof fields.readed_list !== 'undefined') {
	        fields.readedList = fields.readed_list;
	      }

	      if (typeof fields.readedList !== 'undefined') {
	        result.readedList = [];

	        if (fields.readedList instanceof Array) {
	          fields.readedList.forEach(function (element) {
	            var record = {};

	            if (typeof element.user_id !== 'undefined') {
	              element.userId = element.user_id;
	            }

	            if (typeof element.user_name !== 'undefined') {
	              element.userName = element.user_name;
	            }

	            if (typeof element.message_id !== 'undefined') {
	              element.messageId = element.message_id;
	            }

	            if (!element.userId || !element.userName || !element.messageId) {
	              return false;
	            }

	            record.userId = parseInt(element.userId);
	            record.userName = element.userName.toString();
	            record.messageId = parseInt(element.messageId);
	            record.date = im_utils.Utils.date.cast(element.date);
	            result.readedList.push(record);
	          });
	        }
	      }

	      if (typeof fields.writing_list !== 'undefined') {
	        fields.writingList = fields.writing_list;
	      }

	      if (typeof fields.writingList !== 'undefined') {
	        result.writingList = [];

	        if (fields.writingList instanceof Array) {
	          fields.writingList.forEach(function (element) {
	            var record = {};

	            if (!element.userId) {
	              return false;
	            }

	            record.userId = parseInt(element.userId);
	            record.userName = element.userName;
	            result.writingList.push(record);
	          });
	        }
	      }

	      if (typeof fields.manager_list !== 'undefined') {
	        fields.managerList = fields.manager_list;
	      }

	      if (typeof fields.managerList !== 'undefined') {
	        result.managerList = [];

	        if (fields.managerList instanceof Array) {
	          fields.managerList.forEach(function (userId) {
	            userId = parseInt(userId);

	            if (userId > 0) {
	              result.managerList.push(userId);
	            }
	          });
	        }
	      }

	      if (typeof fields.mute_list !== 'undefined') {
	        fields.muteList = fields.mute_list;
	      }

	      if (typeof fields.muteList !== 'undefined') {
	        result.muteList = [];

	        if (fields.muteList instanceof Array) {
	          fields.muteList.forEach(function (userId) {
	            userId = parseInt(userId);

	            if (userId > 0) {
	              result.muteList.push(userId);
	            }
	          });
	        }
	      }

	      if (typeof fields.textareaMessage !== 'undefined') {
	        result.textareaMessage = fields.textareaMessage.toString();
	      }

	      if (typeof fields.title !== 'undefined') {
	        fields.name = fields.title;
	      }

	      if (typeof fields.name === "string" || typeof fields.name === "number") {
	        result.name = fields.name.toString();
	      }

	      if (typeof fields.owner !== 'undefined') {
	        fields.ownerId = fields.owner;
	      }

	      if (typeof fields.ownerId === "number" || typeof fields.ownerId === "string") {
	        result.ownerId = parseInt(fields.ownerId);
	      }

	      if (typeof fields.extranet === "boolean") {
	        result.extranet = fields.extranet;
	      }

	      if (typeof fields.avatar === 'string') {
	        var avatar;

	        if (!fields.avatar || fields.avatar.endsWith('/js/im/images/blank.gif')) {
	          avatar = '';
	        } else if (fields.avatar.startsWith('http')) {
	          avatar = fields.avatar;
	        } else {
	          avatar = options.host + fields.avatar;
	        }

	        if (avatar) {
	          result.avatar = encodeURI(avatar);
	        }
	      }

	      if (typeof fields.color === "string") {
	        result.color = fields.color.toString();
	      }

	      if (typeof fields.type === "string") {
	        result.type = fields.type.toString();
	      }

	      if (typeof fields.entity_type !== 'undefined') {
	        fields.entityType = fields.entity_type;
	      }

	      if (typeof fields.entityType === "string") {
	        result.entityType = fields.entityType.toString();
	      }

	      if (typeof fields.entity_id !== 'undefined') {
	        fields.entityId = fields.entity_id;
	      }

	      if (typeof fields.entityId === "string" || typeof fields.entityId === "number") {
	        result.entityId = fields.entityId.toString();
	      }

	      if (typeof fields.entity_data_1 !== 'undefined') {
	        fields.entityData1 = fields.entity_data_1;
	      }

	      if (typeof fields.entityData1 === "string") {
	        result.entityData1 = fields.entityData1.toString();
	      }

	      if (typeof fields.entity_data_2 !== 'undefined') {
	        fields.entityData2 = fields.entity_data_2;
	      }

	      if (typeof fields.entityData2 === "string") {
	        result.entityData2 = fields.entityData2.toString();
	      }

	      if (typeof fields.entity_data_3 !== 'undefined') {
	        fields.entityData3 = fields.entity_data_3;
	      }

	      if (typeof fields.entityData3 === "string") {
	        result.entityData3 = fields.entityData3.toString();
	      }

	      if (typeof fields.date_create !== 'undefined') {
	        fields.dateCreate = fields.date_create;
	      }

	      if (typeof fields.dateCreate !== "undefined") {
	        result.dateCreate = im_utils.Utils.date.cast(fields.dateCreate);
	      }

	      if (typeof fields.dateLastOpen !== "undefined") {
	        result.dateLastOpen = im_utils.Utils.date.cast(fields.dateLastOpen);
	      }

	      return result;
	    }
	  }]);
	  return DialoguesModel;
	}(ui_vue_vuex.VuexBuilderModel);

	/**
	 * Bitrix Messenger
	 * User model (Vuex Builder model)
	 *
	 * @package bitrix
	 * @subpackage im
	 * @copyright 2001-2019 Bitrix
	 */

	var UsersModel =
	/*#__PURE__*/
	function (_VuexBuilderModel) {
	  babelHelpers.inherits(UsersModel, _VuexBuilderModel);

	  function UsersModel() {
	    babelHelpers.classCallCheck(this, UsersModel);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(UsersModel).apply(this, arguments));
	  }

	  babelHelpers.createClass(UsersModel, [{
	    key: "getName",
	    value: function getName() {
	      return 'users';
	    }
	  }, {
	    key: "getState",
	    value: function getState() {
	      return {
	        host: this.getVariable('host', location.protocol + '//' + location.host),
	        collection: {}
	      };
	    }
	  }, {
	    key: "getElementState",
	    value: function getElementState() {
	      var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      var _params$id = params.id,
	          id = _params$id === void 0 ? 0 : _params$id,
	          _params$name = params.name,
	          name = _params$name === void 0 ? this.getVariable('default.name', '') : _params$name,
	          _params$firstName = params.firstName,
	          firstName = _params$firstName === void 0 ? this.getVariable('default.name', '') : _params$firstName,
	          _params$lastName = params.lastName,
	          lastName = _params$lastName === void 0 ? '' : _params$lastName;
	      return {
	        id: id,
	        name: name,
	        firstName: firstName,
	        lastName: lastName,
	        workPosition: "",
	        color: "#048bd0",
	        avatar: "",
	        gender: "M",
	        birthday: false,
	        extranet: false,
	        network: false,
	        bot: false,
	        connector: false,
	        externalAuthId: "default",
	        status: "online",
	        idle: false,
	        lastActivityDate: false,
	        mobileLastDate: false,
	        absent: false,
	        departments: [],
	        phones: {
	          workPhone: "",
	          personalMobile: "",
	          personalPhone: "",
	          innerPhone: ""
	        },
	        init: false
	      };
	    }
	  }, {
	    key: "getGetters",
	    value: function getGetters() {
	      var _this = this;

	      return {
	        get: function get(state) {
	          return function (userId) {
	            var getTemporary = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
	            userId = parseInt(userId);

	            if (userId <= 0) {
	              if (getTemporary) {
	                userId = 0;
	              } else {
	                return null;
	              }
	            }

	            if (!getTemporary && (!state.collection[userId] || !state.collection[userId].init)) {
	              return null;
	            }

	            if (!state.collection[userId]) {
	              return _this.getElementState({
	                id: userId
	              });
	            }

	            return state.collection[userId];
	          };
	        },
	        getBlank: function getBlank(state) {
	          return function (params) {
	            return _this.getElementState(params);
	          };
	        }
	      };
	    }
	  }, {
	    key: "getActions",
	    value: function getActions() {
	      var _this2 = this;

	      return {
	        set: function set(store, payload) {
	          if (payload instanceof Array) {
	            payload = payload.map(function (user) {
	              return Object.assign({}, _this2.getElementState(), _this2.validate(Object.assign({}, user), {
	                host: store.state.host
	              }), {
	                init: true
	              });
	            });
	          } else {
	            var result = [];
	            result.push(Object.assign({}, _this2.getElementState(), _this2.validate(Object.assign({}, payload), {
	              host: store.state.host
	            }), {
	              init: true
	            }));
	            payload = result;
	          }

	          store.commit('set', payload);
	        },
	        update: function update(store, payload) {
	          payload.id = parseInt(payload.id);

	          if (typeof store.state.collection[payload.id] === 'undefined' || store.state.collection[payload.id].init === false) {
	            return true;
	          }

	          store.commit('update', {
	            id: payload.id,
	            fields: _this2.validate(Object.assign({}, payload.fields), {
	              host: store.state.host
	            })
	          });
	          return true;
	        },
	        delete: function _delete(store, payload) {
	          store.commit('delete', payload.id);
	          return true;
	        },
	        saveState: function saveState(store, payload) {
	          store.commit('saveState', {});
	          return true;
	        }
	      };
	    }
	  }, {
	    key: "getMutations",
	    value: function getMutations() {
	      var _this3 = this;

	      return {
	        set: function set(state, payload) {
	          var _iteratorNormalCompletion = true;
	          var _didIteratorError = false;
	          var _iteratorError = undefined;

	          try {
	            for (var _iterator = payload[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
	              var element = _step.value;

	              _this3.initCollection(state, {
	                id: element.id
	              });

	              state.collection[element.id] = element;

	              _this3.saveState(state);
	            }
	          } catch (err) {
	            _didIteratorError = true;
	            _iteratorError = err;
	          } finally {
	            try {
	              if (!_iteratorNormalCompletion && _iterator.return != null) {
	                _iterator.return();
	              }
	            } finally {
	              if (_didIteratorError) {
	                throw _iteratorError;
	              }
	            }
	          }
	        },
	        update: function update(state, payload) {
	          _this3.initCollection(state, payload);

	          state.collection[payload.id] = Object.assign(state.collection[payload.id], payload.fields);

	          _this3.saveState(state);
	        },
	        delete: function _delete(state, payload) {
	          delete state.collection[payload.id];

	          _this3.saveState(state);
	        },
	        saveState: function saveState(state, payload) {
	          _this3.saveState(state);
	        }
	      };
	    }
	  }, {
	    key: "initCollection",
	    value: function initCollection(state, payload) {
	      if (typeof state.collection[payload.id] !== 'undefined') {
	        return true;
	      }

	      ui_vue.Vue.set(state.collection, payload.id, this.getElementState());
	      return true;
	    }
	  }, {
	    key: "getSaveUserList",
	    value: function getSaveUserList() {
	      if (!this.db) {
	        return [];
	      }

	      if (!this.store.getters['messages/getSaveUserList']) {
	        return [];
	      }

	      var list = this.store.getters['messages/getSaveUserList']();

	      if (!list) {
	        return [];
	      }

	      return list;
	    }
	  }, {
	    key: "getSaveTimeout",
	    value: function getSaveTimeout() {
	      return 250;
	    }
	  }, {
	    key: "saveState",
	    value: function saveState(state) {
	      var _this4 = this;

	      if (!this.isSaveAvailable()) {
	        return false;
	      }

	      babelHelpers.get(babelHelpers.getPrototypeOf(UsersModel.prototype), "saveState", this).call(this, function () {
	        var list = _this4.getSaveUserList();

	        if (!list) {
	          return false;
	        }

	        var storedState = {
	          collection: {}
	        };
	        var exceptionList = {
	          absent: true,
	          idle: true,
	          mobileLastDate: true,
	          lastActivityDate: true
	        };

	        for (var chatId in list) {
	          if (!list.hasOwnProperty(chatId)) {
	            continue;
	          }

	          list[chatId].forEach(function (userId) {
	            if (!state.collection[userId]) {
	              return false;
	            }

	            storedState.collection[userId] = _this4.cloneState(state.collection[userId], exceptionList);
	          });
	        }

	        return storedState;
	      });
	    }
	  }, {
	    key: "validate",
	    value: function validate(fields) {
	      var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
	      var result = {};
	      options.host = options.host || this.getState().host;

	      if (typeof fields.id === "number" || typeof fields.id === "string") {
	        result.id = parseInt(fields.id);
	      }

	      if (typeof fields.first_name !== "undefined") {
	        fields.firstName = fields.first_name;
	      }

	      if (typeof fields.last_name !== "undefined") {
	        fields.lastName = fields.last_name;
	      }

	      if (typeof fields.name === "string" || typeof fields.name === "number") {
	        result.name = fields.name.toString();

	        if (typeof fields.firstName !== "undefined" && !fields.firstName) {
	          var elementsOfName = fields.name.split(' ');

	          if (elementsOfName.length > 1) {
	            delete elementsOfName[elementsOfName.length - 1];
	            fields.firstName = elementsOfName.join(' ').trim();
	          } else {
	            fields.firstName = result.name;
	          }
	        }

	        if (typeof fields.lastName !== "undefined" && !fields.lastName) {
	          var _elementsOfName = fields.name.split(' ');

	          if (_elementsOfName.length > 1) {
	            fields.lastName = _elementsOfName[_elementsOfName.length - 1];
	          } else {
	            fields.lastName = '';
	          }
	        }
	      }

	      if (typeof fields.firstName === "string" || typeof fields.name === "number") {
	        result.firstName = fields.firstName.toString();
	      }

	      if (typeof fields.lastName === "string" || typeof fields.name === "number") {
	        result.lastName = fields.lastName.toString();
	      }

	      if (typeof fields.work_position !== "undefined") {
	        fields.workPosition = fields.work_position;
	      }

	      if (typeof fields.workPosition === "string" || typeof fields.workPosition === "number") {
	        result.workPosition = fields.workPosition.toString();
	      }

	      if (typeof fields.color === "string") {
	        result.color = fields.color;
	      }

	      if (typeof fields.avatar === 'string') {
	        var avatar;

	        if (!fields.avatar || fields.avatar.endsWith('/js/im/images/blank.gif')) {
	          avatar = '';
	        } else if (fields.avatar.startsWith('http')) {
	          avatar = fields.avatar;
	        } else {
	          avatar = options.host + fields.avatar;
	        }

	        if (avatar) {
	          result.avatar = encodeURI(avatar);
	        }
	      }

	      if (typeof fields.gender !== 'undefined') {
	        result.gender = fields.gender === 'F' ? 'F' : 'M';
	      }

	      if (typeof fields.birthday === "string") {
	        result.birthday = fields.birthday;
	      }

	      if (typeof fields.extranet === "boolean") {
	        result.extranet = fields.extranet;
	      }

	      if (typeof fields.network === "boolean") {
	        result.network = fields.network;
	      }

	      if (typeof fields.bot === "boolean") {
	        result.bot = fields.bot;
	      }

	      if (typeof fields.connector === "boolean") {
	        result.connector = fields.connector;
	      }

	      if (typeof fields.external_auth_id !== "undefined") {
	        fields.externalAuthId = fields.external_auth_id;
	      }

	      if (typeof fields.externalAuthId === "string" && fields.externalAuthId) {
	        result.externalAuthId = fields.externalAuthId;
	      }

	      if (typeof fields.status === "string") {
	        result.status = fields.status;
	      }

	      if (typeof fields.idle !== "undefined") {
	        result.idle = im_utils.Utils.date.cast(fields.idle, false);
	      }

	      if (typeof fields.last_activity_date !== "undefined") {
	        fields.lastActivityDate = fields.last_activity_date;
	      }

	      if (typeof fields.lastActivityDate !== "undefined") {
	        result.lastActivityDate = im_utils.Utils.date.cast(fields.lastActivityDate, false);
	      }

	      if (typeof fields.mobile_last_date !== "undefined") {
	        fields.mobileLastDate = fields.mobile_last_date;
	      }

	      if (typeof fields.mobileLastDate !== "undefined") {
	        result.mobileLastDate = im_utils.Utils.date.cast(fields.mobileLastDate, false);
	      }

	      if (typeof fields.absent !== "undefined") {
	        result.absent = im_utils.Utils.date.cast(fields.absent, false);
	      }

	      if (typeof fields.departments !== 'undefined') {
	        result.departments = [];

	        if (fields.departments instanceof Array) {
	          fields.departments.forEach(function (departmentId) {
	            departmentId = parseInt(departmentId);

	            if (departmentId > 0) {
	              result.departments.push(departmentId);
	            }
	          });
	        }
	      }

	      if (babelHelpers.typeof(fields.phones) === 'object' && fields.phones) {
	        result.phones = {};

	        if (typeof fields.phones.work_phone !== "undefined") {
	          fields.phones.workPhone = fields.phones.work_phone;
	        }

	        if (typeof fields.phones.workPhone === 'string' || typeof fields.phones.workPhone === 'number') {
	          result.phones.workPhone = fields.phones.workPhone.toString();
	        }

	        if (typeof fields.phones.personal_mobile !== "undefined") {
	          fields.phones.personalMobile = fields.phones.personal_mobile;
	        }

	        if (typeof fields.phones.personalMobile === 'string' || typeof fields.phones.personalMobile === 'number') {
	          result.phones.personalMobile = fields.phones.personalMobile.toString();
	        }

	        if (typeof fields.phones.personal_phone !== "undefined") {
	          fields.phones.personalPhone = fields.phones.personal_phone;
	        }

	        if (typeof fields.phones.personalPhone === 'string' || typeof fields.phones.personalPhone === 'number') {
	          result.phones.personalPhone = fields.phones.personalPhone.toString();
	        }

	        if (typeof fields.phones.inner_phone !== "undefined") {
	          fields.phones.innerPhone = fields.phones.inner_phone;
	        }

	        if (typeof fields.phones.innerPhone === 'string' || typeof fields.phones.innerPhone === 'number') {
	          result.phones.innerPhone = fields.phones.innerPhone.toString();
	        }
	      }

	      return result;
	    }
	  }]);
	  return UsersModel;
	}(ui_vue_vuex.VuexBuilderModel);

	/**
	 * Bitrix Messenger
	 * File model (Vuex Builder model)
	 *
	 * @package bitrix
	 * @subpackage im
	 * @copyright 2001-2019 Bitrix
	 */

	var FilesModel =
	/*#__PURE__*/
	function (_VuexBuilderModel) {
	  babelHelpers.inherits(FilesModel, _VuexBuilderModel);

	  function FilesModel() {
	    babelHelpers.classCallCheck(this, FilesModel);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(FilesModel).apply(this, arguments));
	  }

	  babelHelpers.createClass(FilesModel, [{
	    key: "getName",
	    value: function getName() {
	      return 'files';
	    }
	  }, {
	    key: "getState",
	    value: function getState() {
	      return {
	        created: 0,
	        host: this.getVariable('host', location.protocol + '//' + location.host),
	        collection: {},
	        index: {}
	      };
	    }
	  }, {
	    key: "getElementState",
	    value: function getElementState() {
	      var params = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
	      var _params$id = params.id,
	          id = _params$id === void 0 ? 0 : _params$id,
	          _params$chatId = params.chatId,
	          chatId = _params$chatId === void 0 ? 0 : _params$chatId,
	          _params$name = params.name,
	          name = _params$name === void 0 ? this.getVariable('default.name', '') : _params$name;
	      return {
	        id: id,
	        chatId: chatId,
	        name: name,
	        templateId: id,
	        date: new Date(),
	        type: 'file',
	        extension: "",
	        icon: "empty",
	        size: 0,
	        image: false,
	        status: im_const.FileStatus.done,
	        progress: 100,
	        authorId: 0,
	        authorName: "",
	        urlPreview: "",
	        urlShow: "",
	        urlDownload: "",
	        init: false
	      };
	    }
	  }, {
	    key: "getGetters",
	    value: function getGetters() {
	      var _this = this;

	      return {
	        get: function get(state) {
	          return function (chatId, fileId) {
	            var getTemporary = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;

	            if (!chatId || !fileId) {
	              return null;
	            }

	            if (!state.index[chatId] || !state.index[chatId][fileId]) {
	              return null;
	            }

	            if (!getTemporary && !state.index[chatId][fileId].init) {
	              return null;
	            }

	            return state.index[chatId][fileId];
	          };
	        },
	        getList: function getList(state) {
	          return function (chatId) {
	            if (!state.index[chatId]) {
	              return null;
	            }

	            return state.index[chatId];
	          };
	        },
	        getBlank: function getBlank(state) {
	          return function (params) {
	            return _this.getElementState(params);
	          };
	        }
	      };
	    }
	  }, {
	    key: "getActions",
	    value: function getActions() {
	      var _this2 = this;

	      return {
	        add: function add(store, payload) {
	          var result = _this2.validate(Object.assign({}, payload), {
	            host: store.state.host
	          });

	          result.id = 'temporary' + new Date().getTime() + store.state.created;
	          result.templateId = result.id;
	          result.init = true;
	          store.commit('add', Object.assign({}, _this2.getElementState(), result));
	          return result.id;
	        },
	        set: function set(store, payload) {
	          if (payload instanceof Array) {
	            payload = payload.map(function (file) {
	              var result = _this2.validate(Object.assign({}, file), {
	                host: store.state.host
	              });

	              result.templateId = result.id;
	              return Object.assign({}, _this2.getElementState(), result, {
	                init: true
	              });
	            });
	          } else {
	            var result = _this2.validate(Object.assign({}, payload), {
	              host: store.state.host
	            });

	            result.templateId = result.id;
	            payload = [];
	            payload.push(Object.assign({}, _this2.getElementState(), result, {
	              init: true
	            }));
	          }

	          store.commit('set', {
	            insertType: im_const.MutationType.setAfter,
	            data: payload
	          });
	        },
	        setBefore: function setBefore(store, payload) {
	          if (payload instanceof Array) {
	            payload = payload.map(function (file) {
	              var result = _this2.validate(Object.assign({}, file), {
	                host: store.state.host
	              });

	              result.templateId = result.id;
	              return Object.assign({}, _this2.getElementState(), result, {
	                init: true
	              });
	            });
	          } else {
	            var result = _this2.validate(Object.assign({}, payload), {
	              host: store.state.host
	            });

	            result.templateId = result.id;
	            payload = [];
	            payload.push(Object.assign({}, _this2.getElementState(), result, {
	              init: true
	            }));
	          }

	          store.commit('set', {
	            actionName: 'setBefore',
	            insertType: im_const.MutationType.setBefore,
	            data: payload
	          });
	        },
	        update: function update(store, payload) {
	          var result = _this2.validate(Object.assign({}, payload.fields), {
	            host: store.state.host
	          });

	          store.commit('initCollection', {
	            chatId: payload.chatId
	          });
	          var index = store.state.collection[payload.chatId].findIndex(function (el) {
	            return el.id === payload.id;
	          });

	          if (index < 0) {
	            return false;
	          }

	          store.commit('update', {
	            id: payload.id,
	            chatId: payload.chatId,
	            index: index,
	            fields: result
	          });

	          if (payload.fields.blink) {
	            setTimeout(function () {
	              store.commit('update', {
	                id: payload.id,
	                chatId: payload.chatId,
	                fields: {
	                  blink: false
	                }
	              });
	            }, 1000);
	          }

	          return true;
	        },
	        delete: function _delete(store, payload) {
	          store.commit('delete', {
	            id: payload.id,
	            chatId: payload.chatId
	          });
	          return true;
	        },
	        saveState: function saveState(store, payload) {
	          store.commit('saveState', {});
	          return true;
	        }
	      };
	    }
	  }, {
	    key: "getMutations",
	    value: function getMutations() {
	      var _this3 = this;

	      return {
	        initCollection: function initCollection(state, payload) {
	          _this3.initCollection(state, payload);
	        },
	        add: function add(state, payload) {
	          _this3.initCollection(state, payload);

	          state.collection[payload.chatId].push(payload);
	          state.index[payload.chatId][payload.id] = payload;
	          state.created += 1;

	          _this3.saveState(state);
	        },
	        set: function set(state, payload) {
	          var _iteratorNormalCompletion = true;
	          var _didIteratorError = false;
	          var _iteratorError = undefined;

	          try {
	            var _loop = function _loop() {
	              var element = _step.value;

	              _this3.initCollection(state, {
	                chatId: element.chatId
	              });

	              var index = state.collection[element.chatId].findIndex(function (el) {
	                return el.id === element.id;
	              });

	              if (index > -1) {
	                delete element.templateId;
	                state.collection[element.chatId][index] = Object.assign(state.collection[element.chatId][index], element);
	              } else if (payload.insertType === im_const.MutationType.setBefore) {
	                state.collection[element.chatId].unshift(element);
	              } else {
	                state.collection[element.chatId].push(element);
	              }

	              state.index[element.chatId][element.id] = element;

	              _this3.saveState(state);
	            };

	            for (var _iterator = payload.data[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
	              _loop();
	            }
	          } catch (err) {
	            _didIteratorError = true;
	            _iteratorError = err;
	          } finally {
	            try {
	              if (!_iteratorNormalCompletion && _iterator.return != null) {
	                _iterator.return();
	              }
	            } finally {
	              if (_didIteratorError) {
	                throw _iteratorError;
	              }
	            }
	          }
	        },
	        update: function update(state, payload) {
	          _this3.initCollection(state, payload);

	          var index = -1;

	          if (typeof payload.index !== 'undefined' && state.collection[payload.chatId][payload.index]) {
	            index = payload.index;
	          } else {
	            index = state.collection[payload.chatId].findIndex(function (el) {
	              return el.id === payload.id;
	            });
	          }

	          if (index >= 0) {
	            delete payload.fields.templateId;
	            var element = Object.assign(state.collection[payload.chatId][index], payload.fields);
	            state.collection[payload.chatId][index] = element;
	            state.index[payload.chatId][element.id] = element;

	            _this3.saveState(state);
	          }
	        },
	        delete: function _delete(state, payload) {
	          _this3.initCollection(state, payload);

	          state.collection[payload.chatId] = state.collection[payload.chatId].filter(function (element) {
	            return element.id !== payload.id;
	          });
	          delete state.index[payload.chatId][payload.id];

	          _this3.saveState(state);
	        },
	        saveState: function saveState(state, payload) {
	          _this3.saveState(state);
	        }
	      };
	    }
	  }, {
	    key: "initCollection",
	    value: function initCollection(state, payload) {
	      if (typeof state.collection[payload.chatId] !== 'undefined') {
	        return true;
	      }

	      ui_vue.Vue.set(state.collection, payload.chatId, []);
	      ui_vue.Vue.set(state.index, payload.chatId, {});
	      return true;
	    }
	  }, {
	    key: "getLoadedState",
	    value: function getLoadedState(state) {
	      if (!state || babelHelpers.typeof(state) !== 'object') {
	        return state;
	      }

	      if (babelHelpers.typeof(state.collection) !== 'object') {
	        return state;
	      }

	      state.index = {};

	      var _loop2 = function _loop2(chatId) {
	        if (!state.collection.hasOwnProperty(chatId)) {
	          return "continue";
	        }

	        state.index[chatId] = {};
	        state.collection[chatId].filter(function (file) {
	          return file != null;
	        }).forEach(function (file) {
	          state.index[chatId][file.id] = file;
	        });
	      };

	      for (var chatId in state.collection) {
	        var _ret = _loop2(chatId);

	        if (_ret === "continue") continue;
	      }

	      return state;
	    }
	  }, {
	    key: "getSaveFileList",
	    value: function getSaveFileList() {
	      if (!this.db) {
	        return [];
	      }

	      if (!this.store.getters['messages/getSaveFileList']) {
	        return [];
	      }

	      var list = this.store.getters['messages/getSaveFileList']();

	      if (!list) {
	        return [];
	      }

	      return list;
	    }
	  }, {
	    key: "getSaveTimeout",
	    value: function getSaveTimeout() {
	      return 250;
	    }
	  }, {
	    key: "saveState",
	    value: function saveState(state) {
	      var _this4 = this;

	      if (!this.isSaveAvailable()) {
	        return false;
	      }

	      babelHelpers.get(babelHelpers.getPrototypeOf(FilesModel.prototype), "saveState", this).call(this, function () {
	        var list = _this4.getSaveFileList();

	        if (!list) {
	          return false;
	        }

	        var storedState = {
	          collection: {}
	        };

	        var _loop3 = function _loop3(chatId) {
	          if (!list.hasOwnProperty(chatId)) {
	            return "continue";
	          }

	          list[chatId].forEach(function (fileId) {
	            if (!state.index[chatId]) {
	              return false;
	            }

	            if (!state.index[chatId][fileId]) {
	              return false;
	            }

	            if (!storedState.collection[chatId]) {
	              storedState.collection[chatId] = [];
	            }

	            storedState.collection[chatId].push(state.index[chatId][fileId]);
	          });
	        };

	        for (var chatId in list) {
	          var _ret2 = _loop3(chatId);

	          if (_ret2 === "continue") continue;
	        }

	        return storedState;
	      });
	    }
	  }, {
	    key: "validate",
	    value: function validate(fields) {
	      var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
	      var result = {};
	      options.host = options.host || this.getState().host;

	      if (typeof fields.id === "number") {
	        result.id = fields.id;
	      } else if (typeof fields.id === "string") {
	        if (fields.id.startsWith('temporary')) {
	          result.id = fields.id;
	        } else {
	          result.id = parseInt(fields.id);
	        }
	      }

	      if (typeof fields.templateId === "number") {
	        result.templateId = fields.templateId;
	      } else if (typeof fields.templateId === "string") {
	        if (fields.templateId.startsWith('temporary')) {
	          result.templateId = fields.templateId;
	        } else {
	          result.templateId = parseInt(fields.templateId);
	        }
	      }

	      if (typeof fields.chatId === "number" || typeof fields.chatId === "string") {
	        result.chatId = parseInt(fields.chatId);
	      }

	      if (typeof fields.date !== "undefined") {
	        result.date = im_utils.Utils.date.cast(fields.date);
	      }

	      if (typeof fields.type === "string") {
	        result.type = fields.type;
	      }

	      if (typeof fields.extension === "string") {
	        result.extension = fields.extension.toString();

	        if (result.type === 'image') {
	          result.icon = 'img';
	        } else if (result.type === 'video') {
	          result.icon = 'mov';
	        } else {
	          result.icon = FilesModel.getIconType(result.extension);
	        }
	      }

	      if (typeof fields.name === "string" || typeof fields.name === "number") {
	        result.name = fields.name.toString();
	      }

	      if (typeof fields.size === "number" || typeof fields.size === "string") {
	        result.size = parseInt(fields.size);
	      }

	      if (typeof fields.image === 'boolean') {
	        result.image = false;
	      } else if (babelHelpers.typeof(fields.image) === 'object' && fields.image) {
	        result.image = {
	          width: 0,
	          height: 0
	        };

	        if (typeof fields.image.width === "string" || typeof fields.image.width === "number") {
	          result.image.width = parseInt(fields.image.width);
	        }

	        if (typeof fields.image.height === "string" || typeof fields.image.height === "number") {
	          result.image.height = parseInt(fields.image.height);
	        }

	        if (result.image.width <= 0 || result.image.height <= 0) {
	          result.image = false;
	        }
	      }

	      if (typeof fields.status === "string" && typeof im_const.FileStatus[fields.status] !== 'undefined') {
	        result.status = fields.status;
	      }

	      if (typeof fields.progress === "number" || typeof fields.progress === "string") {
	        result.progress = parseInt(fields.progress);
	      }

	      if (typeof fields.authorId === "number" || typeof fields.authorId === "string") {
	        result.authorId = parseInt(fields.authorId);
	      }

	      if (typeof fields.authorName === "string" || typeof fields.authorName === "number") {
	        result.authorName = fields.authorName.toString();
	      }

	      if (typeof fields.urlPreview === 'string') {
	        if (!fields.urlPreview || fields.urlPreview.startsWith('http') || fields.urlPreview.startsWith('bx') || fields.urlPreview.startsWith('file')) {
	          result.urlPreview = fields.urlPreview;
	        } else {
	          result.urlPreview = options.host + fields.urlPreview;
	        }
	      }

	      if (typeof fields.urlDownload === 'string') {
	        if (!fields.urlDownload || fields.urlDownload.startsWith('http') || fields.urlDownload.startsWith('bx') || fields.urlPreview.startsWith('file')) {
	          result.urlDownload = fields.urlDownload;
	        } else {
	          result.urlDownload = options.host + fields.urlDownload;
	        }
	      }

	      if (typeof fields.urlShow === 'string') {
	        if (!fields.urlShow || fields.urlShow.startsWith('http') || fields.urlShow.startsWith('bx') || fields.urlShow.startsWith('file')) {
	          result.urlShow = fields.urlShow;
	        } else {
	          result.urlShow = options.host + fields.urlShow;
	        }
	      }

	      return result;
	    }
	  }], [{
	    key: "getType",
	    value: function getType(type) {
	      type = type.toString().toLowerCase().split('.').splice(-1)[0];

	      switch (type) {
	        case 'png':
	        case 'jpe':
	        case 'jpg':
	        case 'jpeg':
	        case 'gif':
	        case 'heic':
	        case 'bmp':
	          return im_const.FileType.image;

	        case 'mp4':
	        case 'mkv':
	        case 'webm':
	        case 'mpeg':
	        case 'hevc':
	        case 'avi':
	        case '3gp':
	        case 'flv':
	        case 'm4v':
	        case 'ogg':
	        case 'wmv':
	        case 'mov':
	          return im_const.FileType.video;

	        case 'mp3':
	          return im_const.FileType.audio;
	      }

	      return im_const.FileType.file;
	    }
	  }, {
	    key: "getIconType",
	    value: function getIconType(extension) {
	      var icon = 'empty';

	      switch (extension.toString()) {
	        case 'png':
	        case 'jpe':
	        case 'jpg':
	        case 'jpeg':
	        case 'gif':
	        case 'heic':
	        case 'bmp':
	          icon = 'img';
	          break;

	        case 'mp4':
	        case 'mkv':
	        case 'webm':
	        case 'mpeg':
	        case 'hevc':
	        case 'avi':
	        case '3gp':
	        case 'flv':
	        case 'm4v':
	        case 'ogg':
	        case 'wmv':
	        case 'mov':
	          icon = 'mov';
	          break;

	        case 'txt':
	          icon = 'txt';
	          break;

	        case 'doc':
	        case 'docx':
	          icon = 'doc';
	          break;

	        case 'xls':
	        case 'xlsx':
	          icon = 'xls';
	          break;

	        case 'php':
	          icon = 'php';
	          break;

	        case 'pdf':
	          icon = 'pdf';
	          break;

	        case 'ppt':
	        case 'pptx':
	          icon = 'ppt';
	          break;

	        case 'rar':
	          icon = 'rar';
	          break;

	        case 'zip':
	        case '7z':
	        case 'tar':
	        case 'gz':
	        case 'gzip':
	          icon = 'zip';
	          break;

	        case 'set':
	          icon = 'set';
	          break;

	        case 'conf':
	        case 'ini':
	        case 'plist':
	          icon = 'set';
	          break;
	      }

	      return icon;
	    }
	  }]);
	  return FilesModel;
	}(ui_vue_vuex.VuexBuilderModel);

	exports.ApplicationModel = ApplicationModel;
	exports.MessagesModel = MessagesModel;
	exports.DialoguesModel = DialoguesModel;
	exports.UsersModel = UsersModel;
	exports.FilesModel = FilesModel;

}((this.BX.Messenger.Model = this.BX.Messenger.Model || {}),BX,BX,BX.Messenger.Const,BX.Messenger));
//# sourceMappingURL=registry.bundle.js.map
