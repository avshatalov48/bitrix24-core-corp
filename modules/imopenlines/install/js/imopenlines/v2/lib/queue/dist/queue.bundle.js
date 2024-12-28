/* eslint-disable */
this.BX = this.BX || {};
this.BX.OpenLines = this.BX.OpenLines || {};
this.BX.OpenLines.v2 = this.BX.OpenLines.v2 || {};
(function (exports,im_v2_application_core) {
	'use strict';

	const QueueType = {
	  all: 'all',
	  strictly: 'strictly',
	  evenly: 'evenly'
	};
	const QueueManager = {
	  getQueueType(queueId) {
	    const {
	      queueConfig = {}
	    } = im_v2_application_core.Core.getApplicationData();
	    const queueItem = Object.values(queueConfig).find(queue => queue.id === queueId);
	    return queueItem ? queueItem.type : null;
	  }
	};

	exports.QueueType = QueueType;
	exports.QueueManager = QueueManager;

}((this.BX.OpenLines.v2.Lib = this.BX.OpenLines.v2.Lib || {}),BX.Messenger.v2.Application));
//# sourceMappingURL=queue.bundle.js.map
