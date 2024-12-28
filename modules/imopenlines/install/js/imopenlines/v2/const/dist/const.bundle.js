/* eslint-disable */
this.BX = this.BX || {};
this.BX.OpenLines = this.BX.OpenLines || {};
this.BX.OpenLines.v2 = this.BX.OpenLines.v2 || {};
(function (exports) {
	'use strict';

	const RestMethod = Object.freeze({
	  linesV2RecentList: 'imopenlines.v2.Recent.list',
	  linesV2SessionAnswer: 'imopenlines.v2.Session.answer',
	  linesV2SessionSkip: 'imopenlines.v2.Session.skip',
	  linesV2SessionMarkSpam: 'imopenlines.v2.Session.markSpam',
	  linesV2SessionPin: 'imopenlines.v2.Session.pin',
	  linesV2SessionUnpin: 'imopenlines.v2.Session.unpin',
	  linesV2SessionIntercept: 'imopenlines.v2.Session.intercept',
	  linesV2SessionFinish: 'imopenlines.v2.Session.finish',
	  linesV2SessionStart: 'imopenlines.v2.Session.start',
	  linesV2SessionJoin: 'imopenlines.v2.Session.join',
	  linesV2SessionTransfer: 'imopenlines.v2.Session.transfer'
	});

	const StatusGroup = {
	  answered: 'ANSWERED',
	  new: 'NEW',
	  work: 'WORK'
	};

	const OpenLinesMessageComponent = Object.freeze({
	  StartDialogMessage: 'StartDialogMessage',
	  HiddenMessage: 'HiddenMessage',
	  FeedbackFormMessage: 'FeedbackFormMessage',
	  ImOpenLinesForm: 'bx-imopenlines-form',
	  ImOpenLinesMessage: 'bx-imopenlines-message'
	});
	const FormType = Object.freeze({
	  none: 'none',
	  like: 'like',
	  welcome: 'welcome',
	  offline: 'offline',
	  history: 'history'
	});

	const QueueType = {
	  all: 'all',
	  strictly: 'strictly',
	  evenly: 'evenly'
	};

	exports.RestMethod = RestMethod;
	exports.StatusGroup = StatusGroup;
	exports.OpenLinesMessageComponent = OpenLinesMessageComponent;
	exports.FormType = FormType;
	exports.QueueType = QueueType;

}((this.BX.OpenLines.v2.Const = this.BX.OpenLines.v2.Const || {})));
//# sourceMappingURL=const.bundle.js.map
