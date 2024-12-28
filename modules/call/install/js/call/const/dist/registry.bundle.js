/* eslint-disable */
this.BX = this.BX || {};
this.BX.Call = this.BX.Call || {};
(function (exports,im_public) {
	'use strict';

	const CallTypes = {
	  video: {
	    id: 'video',
	    locCode: 'CALL_CONTENT_CHAT_HEADER_VIDEOCALL',
	    start: dialogId => {
	      im_public.Messenger.startVideoCall(dialogId);
	    }
	  },
	  audio: {
	    id: 'audio',
	    locCode: 'CALL_CONTENT_CHAT_HEADER_CALL_MENU_AUDIO',
	    start: dialogId => {
	      im_public.Messenger.startVideoCall(dialogId, false);
	    }
	  }
	};

	exports.CallTypes = CallTypes;

}((this.BX.Call.Const = this.BX.Call.Const || {}),BX.Messenger.v2.Lib));
//# sourceMappingURL=registry.bundle.js.map
