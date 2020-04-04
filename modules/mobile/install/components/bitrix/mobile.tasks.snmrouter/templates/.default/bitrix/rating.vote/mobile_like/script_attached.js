;(function(){
	if (window["RatingLikeItems"] || !window["app"])
		return;

	var BXRLC = {}, voteEvents = {};

	window.RatingLikeItems = function(likeId, entityTypeId, entityId, available)
	{
		this.likeId = likeId;
		this.entityTypeId = entityTypeId;
		this.entityId = entityId;
		this.available = (available == 'Y');
		this.likeTimeout = false;
		this.enabled = this.init();
		BXRLC[likeId] = this;
	};
	window.RatingLikeItems.prototype = {
		init : function() {
			this.box = BX('bx-ilike-button-' + this.likeId);
			if (!this.box)
				return false;

			BXMobileApp.addCustomEvent("onPull-main", BX.proxy(function(data) {
				if (data.command == 'rating_vote')
				{
					var p = data.params;
					if (((p.USER_ID + '') != (BX.message('USER_ID') + '')) &&
						this.entityTypeId == p.ENTITY_TYPE_ID && this.entityId == p.ENTITY_ID)
					{
						this.someoneVote((p.TYPE == 'ADD'), p.TOTAL_POSITIVE_VOTES);
					}
				}

			}, this));

			this.voted = BX.hasClass(this.box, 'post-item-likes-liked');
			BX.bind(this.box, 'click', BX.proxy(this.vote, this));

			this.countText = BX('bx-ilike-count-' + this.likeId);
			if (window.app.enableInVersion(2))
				BX.bind(this.countText, 'click', BX.proxy(this.list, this));

			return true;
		},
		vote : function(e) {
			clearTimeout(this.likeTimeout);
			if (BX.type.isBoolean(e) && this.voted == e)
				return false;

			var counterValue = BX.type.isNotEmptyString(this.countText.innerHTML) ? parseInt(this.countText.innerHTML) : 0,
				newValue;

			newValue = this.voted = (BX.type.isBoolean(e) ? e : !this.voted);

			if (this.voted)
			{
				this.countText.innerHTML = counterValue + 1;
				BX.addClass(this.box, 'post-item-likes-liked');
				BX.removeClass(this.box, 'post-item-likes');
			}
			else
			{
				this.countText.innerHTML = counterValue - 1;
				BX.addClass(this.box, 'post-item-likes');
				BX.removeClass(this.box, 'post-item-likes-liked');
			}
			if (BX.type.isBoolean(e))
			{
				return false;
			}
			else
			{
				this.likeTimeout = setTimeout(BX.proxy(function() { this.send(newValue); }, this), 1000);
				BX.eventCancelBubble(e);
				return BX.PreventDefault(e);
			}
		},
		send : function(voteAction) {
			var BMAjaxWrapper = new window.MobileAjaxWrapper();
			BMAjaxWrapper.Wrap({
				'type': 'json',
				'method': 'POST',
				'url': BX.message('SITE_DIR') + 'mobile/ajax.php?mobile_action=like',
				'data': {
					'RATING_VOTE': 'Y',
					'RATING_VOTE_TYPE_ID': this.entityTypeId,
					'RATING_VOTE_ENTITY_ID': this.entityId,
					'RATING_VOTE_ACTION': (voteAction === true ? 'plus' : 'cancel'),
					'sessid': BX.bitrix_sessid()
				},
				'callback': BX.proxy(function(data) {
					if (
						typeof data != 'undefined'
							&& typeof data.action != 'undefined'
							&& typeof data.items_all != 'undefined'
						)
					{
						this.vote((data.action == 'plus'));
						this.countText.innerHTML = data.items_all;
					}
					else
						this.vote(!voteAction);
				}, this),
				'callback_failure': BX.proxy(function() { this.vote(!voteAction); }, this)
			});
		},
		someoneVote : function(vote, votes) {
			this.countText.innerHTML = votes;
			if (votes > 1 || (votes == 1 && !this.voted))
			{
				BX.addClass(this.box, 'post-item-liked');
			}
			else
			{
				BX.removeClass(this.box, 'post-item-liked');
			}
		},
		list : function(e) {
			window.app.openTable({
				callback: function() {},
				url: BX.message('SITE_DIR') + 'mobile/index.php?mobile_action=get_likes&RATING_VOTE_TYPE_ID=' + this.entityTypeId + '&RATING_VOTE_ENTITY_ID=' + this.entityId + '&URL=' + BX.message('RVCPathToUserProfile'),
				markmode: false,
				showtitle: false,
				modal: false,
				cache: false,
				outsection: false,
				cancelname: BX.message('RVCListBack')
			});
			return BX.PreventDefault(e);
		}
	};
	window.RatingLikeItems.getById = function(id)
	{
		return BXRLC[id]
	};
	window.RatingLikeItems.List = function(id)
	{
		if (BXRLC[id])
			BXRLC[id].list();

	};
	if (window["RatingLikeItemsQueue"] && window["RatingLikeItemsQueue"].length > 0)
	{
		var f;
		while((f = window["RatingLikeItemsQueue"].pop()) && f) { f(); }
		delete window["RatingLikeItemsQueue"];
	}
}());