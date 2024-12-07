/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
this.BX.Sign.V2 = this.BX.Sign.V2 || {};
(function (exports,im_public,sign_v2_api,sign_featureResolver) {
	'use strict';

	var _api = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	class KanbanEntityCreateGroupChat {
	  constructor() {
	    Object.defineProperty(this, _api, {
	      writable: true,
	      value: void 0
	    });
	  }
	  init() {
	    babelHelpers.classPrivateFieldLooseBase(this, _api)[_api] = new sign_v2_api.Api();
	  }
	  async onCreateGroupChatButtonClickHandler(event) {
	    const featureResolver = sign_featureResolver.FeatureResolver.instance();
	    if (featureResolver.released('createDocumentChat')) {
	      const button = event.currentTarget;
	      const parentElement = button.closest('[data-id]');
	      const documentId = parentElement.getAttribute('data-id');
	      const chatType = button.getAttribute('chat-type');
	      const chatId = (await babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].createDocumentChat(chatType, documentId, true)).chatId;
	      im_public.Messenger.openChat(`chat${chatId}`);
	    }
	  }
	}

	exports.KanbanEntityCreateGroupChat = KanbanEntityCreateGroupChat;

}((this.BX.Sign.V2.B2e = this.BX.Sign.V2.B2e || {}),BX.Messenger.v2.Lib,BX.Sign.V2,BX.Sign));
//# sourceMappingURL=kanban-entity-create-group-chat.bundle.js.map
