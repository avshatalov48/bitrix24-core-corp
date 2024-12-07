/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
(function (exports,sign_v2_api,main_core) {
	'use strict';

	var _api = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("api");
	var _fireEvent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("fireEvent");
	class DocumentSend extends main_core.Event.EventEmitter {
	  constructor(options = {}) {
	    super();
	    Object.defineProperty(this, _fireEvent, {
	      value: _fireEvent2
	    });
	    Object.defineProperty(this, _api, {
	      writable: true,
	      value: void 0
	    });
	    this.setEventNamespace('bx:sign:v2:documentsend');
	    babelHelpers.classPrivateFieldLooseBase(this, _api)[_api] = new sign_v2_api.Api();
	  }
	  loadStatus(memberIds) {
	    if (memberIds.length > 0) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].memberLoadReadyForMessageStatus(memberIds).then(response => {
	        var _response$readyMember;
	        const readyMembers = (_response$readyMember = response == null ? void 0 : response.readyMembers) != null ? _response$readyMember : [];
	        babelHelpers.classPrivateFieldLooseBase(this, _fireEvent)[_fireEvent](readyMembers);
	        return readyMembers;
	      });
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _fireEvent)[_fireEvent]([]);
	    return Promise.resolve([]);
	  }
	  send(memberIds) {
	    if (memberIds.length > 0) {
	      return babelHelpers.classPrivateFieldLooseBase(this, _api)[_api].memberResendMessage(memberIds);
	    }
	    return Promise.reject(new Error('empty members'));
	  }
	}
	function _fireEvent2(readyMembers) {
	  this.emit('ready', new main_core.Event.BaseEvent({
	    data: {
	      readyMembers
	    }
	  }));
	}

	exports.DocumentSend = DocumentSend;

}((this.BX.Sign.V2 = this.BX.Sign.V2 || {}),BX.Sign.V2,BX));
//# sourceMappingURL=document-send.bundle.js.map
