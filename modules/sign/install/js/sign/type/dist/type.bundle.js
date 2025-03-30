/* eslint-disable */
this.BX = this.BX || {};
(function (exports) {
	'use strict';

	var DocumentInitiated = Object.freeze({
	  employee: 'employee',
	  company: 'company'
	});
	var DocumentMode = Object.freeze({
	  document: 'document',
	  template: 'template'
	});
	var MemberRole = Object.freeze({
	  assignee: 'assignee',
	  signer: 'signer',
	  editor: 'editor',
	  reviewer: 'reviewer'
	});
	var MemberStatus = Object.freeze({
	  done: 'done',
	  wait: 'wait',
	  ready: 'ready',
	  refused: 'refused',
	  stopped: 'stopped',
	  stoppableReady: 'stoppable_ready',
	  processing: 'processing'
	});
	var ProviderCode = Object.freeze({
	  goskey: 'goskey',
	  sesCom: 'ses-com',
	  sesRu: 'ses-ru',
	  external: 'external'
	});
	var Reminder = Object.freeze({
	  none: 'none',
	  oncePerDay: 'oncePerDay',
	  twicePerDay: 'twicePerDay',
	  threeTimesPerDay: 'threeTimesPerDay'
	});

	exports.DocumentInitiated = DocumentInitiated;
	exports.DocumentMode = DocumentMode;
	exports.MemberRole = MemberRole;
	exports.MemberStatus = MemberStatus;
	exports.ProviderCode = ProviderCode;
	exports.Reminder = Reminder;

}((this.BX.Sign = this.BX.Sign || {})));
//# sourceMappingURL=type.bundle.js.map
