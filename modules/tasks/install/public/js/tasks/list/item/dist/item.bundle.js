this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
(function (exports) {
	'use strict';

	var Item =
	/*#__PURE__*/
	function () {
	  babelHelpers.createClass(Item, null, [{
	    key: "statusList",
	    get: function get() {
	      return {
	        pending: 2,
	        inProgress: 3,
	        waitCtrl: 4,
	        completed: 5,
	        deferred: 6
	      };
	    }
	  }, {
	    key: "counterColors",
	    get: function get() {
	      return {
	        green: '#9DCF00',
	        red: '#FF5752',
	        gray: '#AFB3B8'
	      };
	    }
	  }]);

	  function Item(userId) {
	    babelHelpers.classCallCheck(this, Item);
	    this.id = "tmp-id-".concat(new Date().getTime());
	    this.userId = userId;
	    this.deadline = null;
	    this.changedDate = null;
	    this.status = Item.statusList.pending;
	    this.subStatus = Item.statusList.pending;
	    this.isMuted = false;
	    this.isPinned = false;
	    this.notViewed = false;
	    this.messageCount = 0;
	    this.commentsCount = 0;
	    this.newCommentsCount = 0;
	    this.accomplices = [];
	    this.auditors = [];
	    this.params = {};
	    this.params.allowChangeDeadline = true;
	    this.rawAccess = {};
	    this.counter = null;
	  }

	  babelHelpers.createClass(Item, [{
	    key: "setData",
	    value: function setData(row) {
	      this.id = row.id;
	      this.title = row.title;
	      this.groupId = row.groupId;
	      this.status = row.realStatus;
	      this.subStatus = row.status || this.status;
	      this.createdBy = row.createdBy;
	      this.responsibleId = row.responsibleId;
	      this.accomplices = row.accomplices || [];
	      this.auditors = row.auditors || [];
	      this.commentsCount = row.commentsCount;
	      this.newCommentsCount = row.newCommentsCount;
	      this.isMuted = row.isMuted === 'Y';
	      this.isPinned = row.isPinned === 'Y';
	      this.notViewed = row.notViewed === 'Y';
	      this.rawAccess = row.action;
	      var deadline = Date.parse(row.deadline);
	      var changedDate = Date.parse(row.changedDate);
	      this.deadline = deadline > 0 ? deadline : null;
	      this.changedDate = changedDate > 0 ? changedDate : null;
	    }
	  }, {
	    key: "isCreator",
	    value: function isCreator() {
	      var userId = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
	      return Number(userId || this.userId) === Number(this.createdBy);
	    }
	  }, {
	    key: "isResponsible",
	    value: function isResponsible() {
	      var userId = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
	      return Number(userId || this.userId) === Number(this.responsibleId);
	    }
	  }, {
	    key: "isAccomplice",
	    value: function isAccomplice() {
	      var userId = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
	      return this.accomplices.includes(Number(userId || this.userId));
	    }
	  }, {
	    key: "isAuditor",
	    value: function isAuditor() {
	      var userId = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
	      return this.auditors.includes(Number(userId || this.userId));
	    }
	  }, {
	    key: "isMember",
	    value: function isMember() {
	      var userId = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
	      return this.isCreator(userId) || this.isResponsible(userId) || this.isAccomplice(userId) || this.isAuditor(userId);
	    }
	  }, {
	    key: "isDoer",
	    value: function isDoer() {
	      var userId = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
	      return this.isResponsible(userId) || this.isAccomplice(userId);
	    }
	  }, {
	    key: "isPureDoer",
	    value: function isPureDoer() {
	      var userId = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
	      return this.isDoer(userId) && !this.isCreator(userId);
	    }
	  }, {
	    key: "getCounterData",
	    // counter instance
	    value: function getCounterData() {
	      var counterColor = BX.UI.Counter.Color;
	      var value = this.newCommentsCount || 0;
	      var color = counterColor.SUCCESS;

	      if (this.isExpired && !this.isCompletedCounts && !this.isWaitCtrlCounts && !this.isDeferred) {
	        value += 1;
	        color = counterColor.DANGER;
	      }

	      if (this.isMuted) {
	        color = counterColor.GRAY;
	      }

	      return {
	        value: value,
	        color: color
	      };
	    }
	  }, {
	    key: "checkCounterInstance",
	    value: function checkCounterInstance() {
	      return this.counter !== null;
	    }
	  }, {
	    key: "getCounterInstance",
	    value: function getCounterInstance() {
	      if (!this.checkCounterInstance()) {
	        this.counter = new BX.UI.Counter({
	          animate: true
	        });
	        this.updateCounterInstance();
	      }

	      return this.counter;
	    }
	  }, {
	    key: "updateCounterInstance",
	    value: function updateCounterInstance() {
	      var counterData = this.getCounterData();

	      if (counterData.value !== this.counter.getValue()) {
	        this.counter.update(counterData.value);
	      }

	      this.counter.setColor(counterData.color);
	    }
	  }, {
	    key: "removeCounterInstance",
	    value: function removeCounterInstance() {
	      this.counter = null;
	    }
	  }, {
	    key: "isWaitCtrl",
	    get: function get() {
	      return this.status === Item.statusList.waitCtrl;
	    }
	  }, {
	    key: "isWaitCtrlCounts",
	    get: function get() {
	      return this.isWaitCtrl && this.isCreator() && !this.isResponsible();
	    }
	  }, {
	    key: "isCompleted",
	    get: function get() {
	      return this.status === Item.statusList.completed;
	    }
	  }, {
	    key: "isCompletedCounts",
	    get: function get() {
	      return this.isCompleted || this.isWaitCtrl && !this.isCreator();
	    }
	  }, {
	    key: "isDeferred",
	    get: function get() {
	      return this.status === Item.statusList.deferred;
	    }
	  }, {
	    key: "isExpired",
	    get: function get() {
	      var date = new Date();
	      return this.deadline && this.deadline <= date.getTime();
	    }
	  }, {
	    key: "isExpiredCounts",
	    get: function get() {
	      return this.isExpired && this.isPureDoer() && !this.isCompletedCounts;
	    }
	  }]);
	  return Item;
	}();

	exports.Item = Item;

}((this.BX.Tasks.List = this.BX.Tasks.List || {})));
//# sourceMappingURL=item.bundle.js.map
