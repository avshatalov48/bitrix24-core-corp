this.BX = this.BX || {};
(function (exports,main_core) {
	'use strict';

	var MobileCommentsRatingLike = /*#__PURE__*/function () {
	  function MobileCommentsRatingLike(likeId, entityTypeId, entityId, available) {
	    babelHelpers.classCallCheck(this, MobileCommentsRatingLike);
	    this.likeId = likeId;
	    this.entityTypeId = entityTypeId;
	    this.entityId = entityId;
	    this.available = available === 'Y';
	    this.likeTimeout = false;
	    this.enabled = this.init();
	    MobileCommentsRatingLike.setInstance(likeId, this);
	  }

	  babelHelpers.createClass(MobileCommentsRatingLike, [{
	    key: "init",
	    value: function init() {
	      var _this = this;

	      this.box = BX('bx-ilike-button-' + this.likeId);

	      if (!this.box) {
	        return false;
	      }

	      BXMobileApp.addCustomEvent('onPull-main', function (data) {
	        if (data.command !== 'rating_vote') {
	          return;
	        }

	        var p = data.params;

	        if (p.USER_ID + '' != BX.message('USER_ID') + '' && _this.entityTypeId == p.ENTITY_TYPE_ID && _this.entityId == p.ENTITY_ID) {
	          _this.someoneVote(p.TYPE == 'ADD', p.TOTAL_POSITIVE_VOTES);
	        }
	      });
	      this.voted = BX.hasClass(this.box, 'post-comment-likes-liked');
	      BX.bind(this.box, 'click', BX.proxy(this.vote, this));
	      this.countText = BX('bx-ilike-count-' + this.likeId);
	      BX.bind(this.countText, 'click', BX.proxy(this.list, this));
	      return true;
	    }
	  }, {
	    key: "vote",
	    value: function vote(e) {
	      clearTimeout(this.likeTimeout);
	      if (BX.type.isBoolean(e) && this.voted == e) return false;
	      var counterValue = BX.type.isNotEmptyString(this.countText.innerHTML) ? parseInt(this.countText.innerHTML) : 0,
	          newValue;
	      newValue = this.voted = BX.type.isBoolean(e) ? e : !this.voted;

	      if (this.voted) {
	        this.countText.innerHTML = counterValue + 1;
	        BX.addClass(this.box, 'post-comment-likes-liked');
	        BX.removeClass(this.box, 'post-comment-likes');
	        var likeNode = BX.clone(this.box);
	        var box = this.box;
	        BX.adjust(box.parentNode, {
	          style: {
	            position: 'relative'
	          }
	        });
	        BX.adjust(likeNode, {
	          attrs: {
	            id: 'bx-ilike-button-animation'
	          },
	          style: {
	            position: 'absolute',
	            minWidth: 0
	          }
	        });
	        BX.adjust(box, {
	          style: {
	            visibility: 'hidden'
	          }
	        });
	        box.parentNode.insertBefore(likeNode, box);
	        new BX.easing({
	          duration: 120,
	          start: {
	            scale: 100
	          },
	          finish: {
	            scale: 130
	          },
	          transition: BX.easing.transitions.quad,
	          step: function step(state) {
	            likeNode.style.transform = "scale(" + state.scale / 100 + ")";
	          },
	          complete: function complete() {
	            new BX.easing({
	              duration: 120,
	              start: {
	                scale: 130
	              },
	              finish: {
	                scale: 100
	              },
	              transition: BX.easing.transitions.quad,
	              step: function step(state) {
	                likeNode.style.transform = "scale(" + state.scale / 100 + ")";
	              },
	              complete: function complete() {
	                likeNode.parentNode.removeChild(likeNode);
	                BX.adjust(box, {
	                  style: {
	                    visibility: 'visible'
	                  }
	                });
	                BX.adjust(box.parentNode, {
	                  style: {
	                    position: 'static'
	                  }
	                });
	              }
	            }).animate();
	          }
	        }).animate();
	      } else {
	        this.countText.innerHTML = counterValue - 1;
	        BX.addClass(this.box, 'post-comment-likes');
	        BX.removeClass(this.box, 'post-comment-likes-liked');
	      }

	      if (BX.type.isBoolean(e)) {
	        return false;
	      } else {
	        this.likeTimeout = setTimeout(BX.proxy(function () {
	          this.send(newValue);
	        }, this), 1000);
	        BX.eventCancelBubble(e);
	        return BX.PreventDefault(e);
	      }
	    }
	  }, {
	    key: "send",
	    value: function send(voteAction) {
	      var BMAjaxWrapper = new window.MobileAjaxWrapper();
	      BMAjaxWrapper.Wrap({
	        type: 'json',
	        method: 'POST',
	        url: BX.message('SITE_DIR') + 'mobile/ajax.php?mobile_action=like',
	        data: {
	          RATING_VOTE: 'Y',
	          RATING_VOTE_TYPE_ID: this.entityTypeId,
	          RATING_VOTE_ENTITY_ID: this.entityId,
	          RATING_VOTE_ACTION: voteAction === true ? 'plus' : 'cancel',
	          sessid: BX.bitrix_sessid()
	        },
	        callback: BX.proxy(function (data) {
	          if (typeof data != 'undefined' && typeof data.action != 'undefined' && typeof data.items_all != 'undefined') {
	            this.vote(data.action == 'plus');
	            this.countText.innerHTML = data.items_all;
	          } else this.vote(!voteAction);
	        }, this),
	        callback_failure: BX.proxy(function () {
	          this.vote(!voteAction);
	        }, this)
	      });
	    }
	  }, {
	    key: "someoneVote",
	    value: function someoneVote(vote, votes) {
	      this.countText.innerHTML = votes;

	      if (votes > 1 || votes == 1 && !this.voted) {
	        BX.addClass(this.box, 'post-comment-liked');
	      } else {
	        BX.removeClass(this.box, 'post-comment-liked');
	      }
	    }
	  }, {
	    key: "list",
	    value: function list(e) {
	      if (window["app"]) {
	        var pathToUserProfile = BX.message('RVPathToUserProfile') ? BX.message('RVPathToUserProfile') : BX.message('RVCPathToUserProfile');
	        window.app.openTable({
	          callback: function callback() {},
	          url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + 'mobile/index.php?mobile_action=get_likes&RATING_VOTE_TYPE_ID=' + this.entityTypeId + '&RATING_VOTE_ENTITY_ID=' + this.entityId + '&URL=' + pathToUserProfile,
	          markmode: false,
	          showtitle: false,
	          modal: false,
	          cache: false,
	          outsection: false,
	          cancelname: BX.message('RVCListBack')
	        });
	      }

	      return BX.PreventDefault(e);
	    }
	  }], [{
	    key: "setInstance",
	    value: function setInstance(likeId, likeInstance) {
	      this.repo.set(likeId, likeInstance);
	      window.BXRLC[likeId] = likeInstance;
	    }
	  }, {
	    key: "getById",
	    value: function getById(likeId) {
	      return this.getInstance(likeId);
	    }
	  }, {
	    key: "getInstance",
	    value: function getInstance(likeId) {
	      return this.repo.get(likeId);
	    }
	  }, {
	    key: "List",
	    value: function List(likeId) {
	      var instance = this.getInstance(likeId);

	      if (!instance) {
	        return;
	      }

	      instance.list();
	    }
	  }]);
	  return MobileCommentsRatingLike;
	}();
	babelHelpers.defineProperty(MobileCommentsRatingLike, "repo", new Map());

	if (main_core.Type.isUndefined(window.BXRLC)) {
	  window.BXRLC = {};
	}

	window.RatingLikeComments = MobileCommentsRatingLike;

	if (!main_core.Type.isUndefined(window.RatingLikeCommentsQueue) && window.RatingLikeCommentsQueue.length > 0) {
	  var f;

	  while ((f = window.RatingLikeCommentsQueue.pop()) && f) {
	    f();
	  }

	  delete window.RatingLikeCommentsQueue;
	}

}((this.BX.Mobile = this.BX.Mobile || {}),BX));
//# sourceMappingURL=mobile.rating.comment.js.map
