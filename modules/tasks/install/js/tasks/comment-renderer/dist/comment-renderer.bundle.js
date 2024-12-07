/* eslint-disable */
this.BX = this.BX || {};
(function (exports,main_core,socialnetwork_commentaux) {
	'use strict';

	var CommentRenderer = /*#__PURE__*/function () {
	  function CommentRenderer() {
	    babelHelpers.classCallCheck(this, CommentRenderer);
	  }
	  babelHelpers.createClass(CommentRenderer, null, [{
	    key: "getCommentPart",
	    value: function getCommentPart(entity) {
	      var _this = this;
	      var message = '';
	      try {
	        message = main_core.Loc.getMessage(entity.CODE);
	      } catch (e) {}
	      if (!main_core.Type.isStringFilled(message) || !main_core.Type.isPlainObject(entity.REPLACE_LIST)) {
	        return message;
	      }
	      var liveData = {};
	      if (main_core.Type.isPlainObject(entity.REPLACE_LIST.LIVE_DATA)) {
	        liveData = entity.REPLACE_LIST.LIVE_DATA;
	        delete entity.REPLACE_LIST.LIVE_DATA;
	      }
	      Object.keys(entity.REPLACE_LIST).forEach(function (search) {
	        message = message.replace(search, entity.REPLACE_LIST[search]);
	      });
	      message = message.replaceAll(/\[USER=(\d+)\](.+?)\[\/USER\]/g, function (match, id, name) {
	        return socialnetwork_commentaux.CommentAux.renderEntity({
	          ENTITY_TYPE: 'U',
	          NAME: name,
	          LINK: main_core.Loc.getMessage('SONET_EXT_COMMENTAUX_USER_PATH').replace('#user_id#', id)
	        });
	      });
	      var userId = Number(main_core.Loc.getMessage('USER_ID'));
	      var actionList = ['EFFICIENCY', 'DEADLINE', 'DEADLINE_CHANGE', 'TASK_APPROVE', 'TASK_DISAPPROVE', 'TASK_COMPLETE', 'TASK_CHANGE_RESPONSIBLE'];
	      actionList.forEach(function (action) {
	        var start = "#".concat(action, "_START#");
	        var end = "#".concat(action, "_END#");
	        if (message.indexOf(start) === -1 && message.indexOf(end) === -1) {
	          return;
	        }
	        switch (action) {
	          case 'EFFICIENCY':
	            if (liveData.EFFICIENCY_MEMBERS.includes(userId)) {
	              var efficiencyUrlStart = main_core.Loc.getMessage('SONET_RENDERPARTS_EFFICIENCY_PATH');
	              efficiencyUrlStart = efficiencyUrlStart.replace('#user_id#', userId);
	              efficiencyUrlStart = "<a href=\"".concat(efficiencyUrlStart, "\" target=\"_blank\">");
	              message = message.replace(start, efficiencyUrlStart);
	              message = message.replace(end, '</a>');
	            } else {
	              message = _this.removeAnchors(message, start, end);
	            }
	            break;
	          case 'DEADLINE':
	            var regExp = new RegExp("".concat(start, "\\d+").concat(end), 'g');
	            message = message.replaceAll(regExp, function (timestamp) {
	              if (timestamp) {
	                timestamp = _this.removeAnchors(timestamp, start, end);
	                return BX.date.format(liveData.DATE_FORMAT, Number(timestamp));
	              }
	            });
	            message = _this.removeAnchors(message, start, end);
	            break;
	          case 'DEADLINE_CHANGE':
	          case 'TASK_APPROVE':
	          case 'TASK_DISAPPROVE':
	          case 'TASK_COMPLETE':
	          case 'TASK_CHANGE_RESPONSIBLE':
	            if (!main_core.Type.isUndefined(liveData.TASK_ID) && Number(liveData.TASK_ID) > 0 && Object.keys(liveData.RIGHTS[action]).map(function (id) {
	              return Number(id);
	            }).includes(userId) && liveData.RIGHTS[action][userId]) {
	              var taskActionLink = _this.getTaskActionLink({
	                action: action,
	                userId: userId,
	                taskId: liveData.TASK_ID,
	                deadline: liveData.DEADLINE || null
	              });
	              message = message.replace(start, "<a href=\"".concat(taskActionLink, "\">"));
	              message = message.replace(end, '</a>');
	            } else {
	              message = _this.removeAnchors(message, start, end);
	            }
	            break;
	          default:
	            message = _this.removeAnchors(message, start, end);
	            break;
	        }
	      });
	      return message.replace("\n", '<br>');
	    }
	  }, {
	    key: "getTaskActionLink",
	    value: function getTaskActionLink(params) {
	      var actionMap = {
	        DEADLINE_CHANGE: 'deadlineChange',
	        TASK_APPROVE: 'taskApprove',
	        TASK_DISAPPROVE: 'taskDisapprove',
	        TASK_COMPLETE: 'taskComplete',
	        TASK_CHANGE_RESPONSIBLE: 'taskChangeResponsible'
	      };
	      var link = main_core.Loc.getMessage('SONET_RENDERPARTS_TASK_PATH');
	      link = link.replace('#user_id#', main_core.Loc.getMessage('USER_ID')).replace('#task_id#', params.taskId);
	      link = main_core.Uri.addParam(link, {
	        commentAction: actionMap[params.action]
	      });
	      if (params.action === 'DEADLINE_CHANGE' && params.deadline) {
	        link = main_core.Uri.addParam(link, {
	          deadline: params.deadline
	        });
	      }
	      return link;
	    }
	  }, {
	    key: "removeAnchors",
	    value: function removeAnchors(message, start, end) {
	      message = message.replace(start, '');
	      message = message.replace(end, '');
	      return message;
	    }
	  }]);
	  return CommentRenderer;
	}();

	exports.CommentRenderer = CommentRenderer;

}((this.BX.Tasks = this.BX.Tasks || {}),BX,BX));
//# sourceMappingURL=comment-renderer.bundle.js.map
