/* eslint-disable */
this.BX = this.BX || {};
this.BX.OpenLines = this.BX.OpenLines || {};
this.BX.OpenLines.v2 = this.BX.OpenLines.v2 || {};
this.BX.OpenLines.v2.Provider = this.BX.OpenLines.v2.Provider || {};
(function (exports,main_core,im_public,im_v2_const,im_v2_lib_layout,im_v2_lib_logger,im_v2_application_core) {
	'use strict';

	class LinesPullHandler {
	  constructor() {
	    this.store = im_v2_application_core.Core.getStore();
	  }
	  getModuleId() {
	    return 'im';
	  }
	  handleMessage(params, extra) {
	    this.handleMessageAdd(params, extra);
	  }
	  handleMessageChat(params) {
	    this.handleMessageAdd(params);
	    this.updateUnloadedLinesCounter(params);
	  }
	  handleMessageAdd(params) {
	    if (!params.lines) {
	      return;
	    }
	    const userId = im_v2_application_core.Core.getUserId();
	    const userInChat = params.userInChat[params.chatId];
	    const isClosed = params.lines.isClosed;
	    if (userInChat.includes(userId) && !isClosed) {
	      void this.store.dispatch('recentOpenLines/set', {
	        id: params.dialogId,
	        messageId: params.message.id,
	        sessionId: params.lines.id
	      });
	    }
	    void this.store.dispatch('sessions/set', {
	      ...params.lines,
	      chatId: params.chatId,
	      status: params.lines.statusGroup
	    });
	  }
	  handleReadMessageChat(params) {
	    this.updateUnloadedLinesCounter(params);
	  }
	  handleUnreadMessageChat(params) {
	    this.updateUnloadedLinesCounter(params);
	  }
	  handleChatHide(params) {
	    this.updateUnloadedLinesCounter({
	      dialogId: params.dialogId,
	      chatId: params.chatId,
	      lines: params.lines,
	      counter: 0
	    });
	    const recentItem = this.store.getters['recentOpenLines/get'](params.dialogId);
	    if (!recentItem) {
	      return;
	    }
	    void this.store.dispatch('recentOpenLines/delete', {
	      id: params.dialogId
	    });
	  }
	  updateUnloadedLinesCounter(params) {
	    const {
	      dialogId,
	      chatId,
	      counter,
	      lines
	    } = params;
	    if (!lines || main_core.Type.isUndefined(counter)) {
	      return;
	    }
	    im_v2_lib_logger.Logger.warn('LinesPullHandler: updateUnloadedLinesCounter:', {
	      dialogId,
	      chatId,
	      counter
	    });
	    void this.store.dispatch('counters/setUnloadedLinesCounters', {
	      [chatId]: counter
	    });
	  }
	  handleChatUserLeave(params) {
	    const recentItem = this.store.getters['recentOpenLines/get'](params.dialogId);
	    const chatIsOpened = im_v2_application_core.Core.getStore().getters['application/isLinesChatOpen'](params.dialogId);
	    const userId = im_v2_application_core.Core.getUserId();
	    if (chatIsOpened && params.userId === userId) {
	      void im_public.Messenger.openLines();
	      im_v2_lib_layout.LayoutManager.getInstance().setLastOpenedElement(im_v2_const.Layout.openlinesV2.name, '');
	    }
	    if (!recentItem || params.userId !== im_v2_application_core.Core.getUserId()) {
	      return;
	    }
	    void this.store.dispatch('recentOpenLines/delete', {
	      id: params.dialogId
	    });
	  }
	}

	class SessionPullHandler {
	  constructor() {
	    this.store = im_v2_application_core.Core.getStore();
	  }
	  getModuleId() {
	    return 'imopenlines';
	  }
	  handleUpdateSessionStatus(params) {
	    const sessionItem = params.session;
	    const isClosed = sessionItem.isClosed;
	    if (!isClosed) {
	      void this.store.dispatch('recentOpenLines/set', {
	        id: params.chat.dialogId,
	        messageId: params.message.id,
	        sessionId: sessionItem.id
	      });
	    }
	    void this.store.dispatch('sessions/set', sessionItem);
	  }
	}

	class QueuePullHandler {
	  constructor() {
	    this.store = im_v2_application_core.Core.getStore();
	  }
	  getModuleId() {
	    return 'imopenlines';
	  }
	  handleQueueItemUpdate(params) {
	    void this.store.dispatch('queue/set', params);
	  }
	  handleQueueItemDelete(params) {
	    void this.store.dispatch('queue/delete', params.id);
	  }
	}

	const OpenLinesHandlers = [LinesPullHandler, SessionPullHandler, QueuePullHandler];

	exports.OpenLinesHandlers = OpenLinesHandlers;

}((this.BX.OpenLines.v2.Provider.Pull = this.BX.OpenLines.v2.Provider.Pull || {}),BX,BX.Messenger.v2.Lib,BX.Messenger.v2.Const,BX.Messenger.v2.Lib,BX.Messenger.v2.Lib,BX.Messenger.v2.Application));
//# sourceMappingURL=registry.bundle.js.map
