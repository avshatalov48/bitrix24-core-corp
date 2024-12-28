/* eslint-disable */
this.BX = this.BX || {};
this.BX.OpenLines = this.BX.OpenLines || {};
this.BX.OpenLines.v2 = this.BX.OpenLines.v2 || {};
(function (exports,im_v2_application_core,ui_vue3_vuex,imopenlines_v2_const,main_core,im_v2_model) {
	'use strict';

	const sessionsFieldsConfig = [{
	  fieldName: ['id', 'sessionId'],
	  targetFieldName: 'id',
	  checkFunction: main_core.Type.isNumber,
	  formatFunction: im_v2_model.convertToNumber
	}, {
	  fieldName: 'chatId',
	  targetFieldName: 'chatId',
	  checkFunction: main_core.Type.isNumber,
	  formatFunction: im_v2_model.convertToNumber
	}, {
	  fieldName: 'operatorId',
	  targetFieldName: 'operatorId',
	  checkFunction: main_core.Type.isNumber,
	  formatFunction: im_v2_model.convertToNumber
	}, {
	  fieldName: 'status',
	  targetFieldName: 'status',
	  checkFunction: main_core.Type.isString,
	  formatFunction: im_v2_model.convertToString
	}, {
	  fieldName: 'queueId',
	  targetFieldName: 'queueId',
	  checkFunction: main_core.Type.isNumber,
	  formatFunction: im_v2_model.convertToNumber
	}, {
	  fieldName: 'pinned',
	  targetFieldName: 'pinned',
	  checkFunction: main_core.Type.isBoolean
	}, {
	  fieldName: 'isClosed',
	  targetFieldName: 'isClosed',
	  checkFunction: main_core.Type.isBoolean
	}];

	var _formatFields = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("formatFields");
	class SessionsModel extends ui_vue3_vuex.BuilderModel {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _formatFields, {
	      value: _formatFields2
	    });
	  }
	  getName() {
	    return 'sessions';
	  }
	  getState() {
	    return {
	      collection: {}
	    };
	  }
	  getElementState() {
	    return {
	      id: 0,
	      chatId: 0,
	      operatorId: 0,
	      status: '',
	      queueId: 0,
	      pinned: false,
	      isClosed: false
	    };
	  }
	  getGetters() {
	    return {
	      /** @function sessions/getById */
	      getById: state => (id, getBlank = false) => {
	        if (!state.collection[id] && getBlank) {
	          return this.getElementState();
	        }
	        if (!state.collection[id] && !getBlank) {
	          return null;
	        }
	        return state.collection[id];
	      },
	      /** @function sessions/getByChatId */
	      getByChatId: state => (chatId, getBlank = false) => {
	        const session = Object.values(state.collection).find(item => item.chatId === chatId);
	        if (!session && getBlank) {
	          return this.getElementState();
	        }
	        if (!session && !getBlank) {
	          return null;
	        }
	        return session;
	      }
	    };
	  }
	  getActions() {
	    return {
	      /** @function sessions/set */
	      set: (store, payload) => {
	        let sessions = payload;
	        if (!Array.isArray(sessions) && main_core.Type.isPlainObject(sessions)) {
	          sessions = [sessions];
	        }
	        const itemsToAdd = [];
	        sessions.map(element => {
	          return babelHelpers.classPrivateFieldLooseBase(this, _formatFields)[_formatFields](element);
	        }).forEach(element => {
	          const existingItem = store.state.collection[element.id];
	          if (existingItem) {
	            store.commit('update', {
	              id: existingItem.id,
	              fields: {
	                ...element
	              }
	            });
	          } else {
	            itemsToAdd.push({
	              ...this.getElementState(),
	              ...element
	            });
	          }
	        });
	        if (itemsToAdd.length > 0) {
	          store.commit('add', itemsToAdd);
	        }
	      },
	      /** @function sessions/pin */
	      pin: (store, payload) => {
	        const existingItem = store.state.collection[payload.id];
	        if (!existingItem) {
	          return;
	        }
	        store.commit('update', {
	          id: existingItem.id,
	          fields: {
	            pinned: payload.action
	          }
	        });
	      }
	    };
	  }
	  getMutations() {
	    return {
	      add: (state, payload) => {
	        const sessions = payload;
	        const sessionsState = state;
	        const sessionChatId = sessions[0].chatId;
	        const result = Object.values(sessionsState.collection).find(item => item.chatId === sessionChatId);
	        if (result) {
	          delete sessionsState.collection[result.id];
	        }
	        sessions.forEach(item => {
	          sessionsState.collection[item.id] = item;
	        });
	      },
	      update: (state, payload) => {
	        const sessionsState = state;
	        const currentElement = state.collection[payload.id];
	        sessionsState.collection[payload.id] = {
	          ...currentElement,
	          ...payload.fields
	        };
	      }
	    };
	  }
	}
	function _formatFields2(rawFields) {
	  return im_v2_model.formatFieldsWithConfig(rawFields, sessionsFieldsConfig);
	}

	const recentFieldsConfig = [{
	  fieldName: ['id', 'dialogId'],
	  targetFieldName: 'dialogId',
	  checkFunction: im_v2_model.isNumberOrString,
	  formatFunction: im_v2_model.convertToString
	}, {
	  fieldName: ['chatId'],
	  targetFieldName: 'chatId',
	  checkFunction: main_core.Type.isNumber,
	  formatFunction: im_v2_model.convertToNumber
	}, {
	  fieldName: 'messageId',
	  targetFieldName: 'messageId',
	  checkFunction: im_v2_model.isNumberOrString
	}, {
	  fieldName: 'sessionId',
	  targetFieldName: 'sessionId',
	  checkFunction: main_core.Type.isNumber,
	  formatFunction: im_v2_model.convertToNumber
	}, {
	  fieldName: 'draft',
	  targetFieldName: 'draft',
	  checkFunction: main_core.Type.isPlainObject,
	  formatFunction: im_v2_model.prepareDraft
	}, {
	  fieldName: 'unread',
	  targetFieldName: 'unread',
	  checkFunction: main_core.Type.isBoolean
	}, {
	  fieldName: 'pinned',
	  targetFieldName: 'pinned',
	  checkFunction: main_core.Type.isBoolean
	}, {
	  fieldName: 'liked',
	  targetFieldName: 'liked',
	  checkFunction: main_core.Type.isBoolean
	}];

	var _formatFields$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("formatFields");
	class OpenLinesRecentModel extends ui_vue3_vuex.BuilderModel {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _formatFields$1, {
	      value: _formatFields2$1
	    });
	  }
	  getName() {
	    return 'recentOpenLines';
	  }
	  getState() {
	    return {
	      collection: {}
	    };
	  }
	  getElementState() {
	    return {
	      dialogId: '0',
	      chatId: 0,
	      messageId: 0,
	      sessionId: 0,
	      draft: {
	        text: '',
	        date: null
	      },
	      unread: false,
	      pinned: false,
	      liked: false
	    };
	  }
	  getGetters() {
	    return {
	      /** @function recentOpenLines/getOpenLinesCollection */
	      getOpenLinesCollection: state => {
	        const openLinesItems = [];
	        Object.keys(state.collection).forEach(dialogId => {
	          const dialog = this.store.getters['chats/get'](dialogId);
	          if (dialog) {
	            openLinesItems.push(state.collection[dialogId]);
	          }
	        });
	        return openLinesItems;
	      },
	      /** @function recentOpenLines/getSession */
	      getSession: state => (dialogId, getBlank = false) => {
	        const session = state.collection[dialogId];
	        if (!session && getBlank) {
	          return this.getElementState();
	        }
	        if (!session && !getBlank) {
	          return null;
	        }
	        const sessionId = session.sessionId;
	        return im_v2_application_core.Core.getStore().getters['sessions/getById'](sessionId);
	      },
	      /** @function recentOpenLines/get */
	      get: state => dialogId => {
	        if (!state.collection[dialogId]) {
	          return null;
	        }
	        return state.collection[dialogId];
	      },
	      /** @function recentOpenLines/getChatIdByDialogId */
	      getChatIdByDialogId: state => dialogId => {
	        if (!state.collection[dialogId]) {
	          return null;
	        }
	        return state.collection[dialogId].chatId;
	      }
	    };
	  }
	  getActions() {
	    return {
	      /** @function recentOpenLines/set */
	      set: (store, payload) => {
	        let openLines = payload;
	        if (!Array.isArray(openLines) && main_core.Type.isPlainObject(openLines)) {
	          openLines = [openLines];
	        }
	        const itemsToAdd = [];
	        const itemsToUpdate = [];
	        openLines.map(element => {
	          return babelHelpers.classPrivateFieldLooseBase(this, _formatFields$1)[_formatFields$1](element);
	        }).forEach(element => {
	          const existingItem = store.state.collection[element.dialogId];
	          if (existingItem) {
	            itemsToUpdate.push({
	              dialogId: existingItem.dialogId,
	              fields: {
	                ...element
	              }
	            });
	          } else {
	            itemsToAdd.push({
	              ...this.getElementState(),
	              ...element
	            });
	          }
	        });
	        if (itemsToAdd.length > 0) {
	          store.commit('add', itemsToAdd);
	        }
	        if (itemsToUpdate.length > 0) {
	          store.commit('update', itemsToUpdate);
	        }
	      },
	      /** @function recentOpenLines/delete */
	      delete: (store, payload) => {
	        const existingItem = store.state.collection[payload.id];
	        if (!existingItem) {
	          return;
	        }
	        store.commit('delete', {
	          id: existingItem.dialogId
	        });
	      }
	    };
	  }
	  getMutations() {
	    return {
	      add: (state, payload) => {
	        let openLines = payload;
	        const openLinesState = state;
	        if (!Array.isArray(openLines) && main_core.Type.isPlainObject(openLines)) {
	          openLines = [openLines];
	        }
	        openLines.forEach(item => {
	          openLinesState.collection[item.dialogId] = item;
	        });
	      },
	      update: (state, payload) => {
	        let openLines = payload;
	        const openLinesState = state;
	        if (!Array.isArray(openLines) && main_core.Type.isPlainObject(openLines)) {
	          openLines = [openLines];
	        }
	        openLines.forEach(({
	          dialogId,
	          fields
	        }) => {
	          const currentElement = state.collection[dialogId];
	          openLinesState.collection[dialogId] = {
	            ...currentElement,
	            ...fields
	          };
	        });
	      },
	      delete: (state, payload) => {
	        // eslint-disable-next-line no-param-reassign
	        delete state.collection[payload.id];
	      }
	    };
	  }
	}
	function _formatFields2$1(rawFields) {
	  return im_v2_model.formatFieldsWithConfig(rawFields, recentFieldsConfig);
	}

	const queueFieldsConfig = [{
	  fieldName: ['id', 'queueId'],
	  targetFieldName: 'id',
	  formatFunction: im_v2_model.convertToNumber
	}, {
	  fieldName: ['lineName', 'name'],
	  targetFieldName: 'lineName',
	  checkFunction: main_core.Type.isString,
	  formatFunction: im_v2_model.convertToString
	}, {
	  fieldName: ['type', 'queueType'],
	  targetFieldName: 'type',
	  checkFunction: main_core.Type.isString,
	  formatFunction: im_v2_model.convertToString
	}, {
	  fieldName: ['isActive'],
	  targetFieldName: 'isActive',
	  checkFunction: main_core.Type.isBoolean
	}];

	var _formatFields$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("formatFields");
	class QueueModel extends ui_vue3_vuex.BuilderModel {
	  constructor(...args) {
	    super(...args);
	    Object.defineProperty(this, _formatFields$2, {
	      value: _formatFields2$2
	    });
	  }
	  getName() {
	    return 'queue';
	  }
	  getState() {
	    return {
	      collection: {}
	    };
	  }
	  getElementState() {
	    return {
	      id: 0,
	      lineName: '',
	      type: '',
	      isActive: true
	    };
	  }
	  getGetters() {
	    return {
	      /** @function queue/getTypeById */
	      getTypeById: state => (id, getBlank = false) => {
	        if (!state.collection[id] && getBlank) {
	          return this.getElementState();
	        }
	        if (!state.collection[id] && !getBlank) {
	          return null;
	        }
	        return state.collection[id].type;
	      }
	    };
	  }
	  getActions() {
	    return {
	      /** @function queue/set */
	      set: (store, payload) => {
	        let queues = payload;
	        if (!Array.isArray(queues) && main_core.Type.isPlainObject(queues)) {
	          queues = [queues];
	        }
	        const itemsToAdd = [];
	        queues.map(element => {
	          return babelHelpers.classPrivateFieldLooseBase(this, _formatFields$2)[_formatFields$2](element);
	        }).forEach(element => {
	          const existingItem = store.state.collection[element.id];
	          if (existingItem) {
	            store.commit('update', {
	              id: existingItem.id,
	              fields: {
	                ...element
	              }
	            });
	          } else {
	            itemsToAdd.push({
	              ...this.getElementState(),
	              ...element
	            });
	          }
	        });
	        if (itemsToAdd.length > 0) {
	          store.commit('add', itemsToAdd);
	        }
	      },
	      /** @function queue/delete */
	      delete: (store, payload) => {
	        const existingItem = store.state.collection[payload.id];
	        if (!existingItem) {
	          return;
	        }
	        store.commit('delete', {
	          id: existingItem.dialogId
	        });
	      }
	    };
	  }
	  getMutations() {
	    return {
	      add: (state, payload) => {
	        const queues = payload;
	        const queueState = state;
	        queues.forEach(item => {
	          queueState.collection[item.id] = item;
	        });
	      },
	      update: (state, payload) => {
	        const queueState = state;
	        const currentElement = state.collection[payload.id];
	        queueState.collection[payload.id] = {
	          ...currentElement,
	          ...payload.fields
	        };
	      },
	      delete: (state, payload) => {
	        // eslint-disable-next-line no-param-reassign
	        delete state.collection[payload.id];
	      }
	    };
	  }
	}
	function _formatFields2$2(rawFields) {
	  return im_v2_model.formatFieldsWithConfig(rawFields, queueFieldsConfig);
	}

	const OpenLinesModels = [OpenLinesRecentModel, SessionsModel, QueueModel];

	exports.SessionsModel = SessionsModel;
	exports.OpenLinesRecentModel = OpenLinesRecentModel;
	exports.QueueModel = QueueModel;
	exports.OpenLinesModels = OpenLinesModels;

}((this.BX.OpenLines.v2.Model = this.BX.OpenLines.v2.Model || {}),BX.Messenger.v2.Application,BX.Vue3.Vuex,BX.OpenLines.v2.Const,BX,BX.Messenger.v2.Model));
//# sourceMappingURL=registry.bundle.js.map
