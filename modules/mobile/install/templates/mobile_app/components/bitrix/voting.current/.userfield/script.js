;(function(window){
	if (BX && BX["Mobile"] && BX["Mobile"]["Vote"] && BX["Mobile"]["Vote"]["init"])
		return;
	BX.namespace("BX.Mobile.Vote");
	var repo = {};

	window.voteGetID = function() {
		return 'vote' + new Date().getTime();
	};

	var VCLinkCloseWait = function() { return app.hidePopupLoader(); },
		VCLinkShowWait = function() { return app.showPopupLoader(); },
		BVoteChecker = (function(){
		var d = function(params)
		{
			if (!params["url"])
				throw "Vote error: url is empty.";
			else if(!params["CID"])
				throw "Vote error: id is empty.";
			else if(!BX(params["controller"]))
				throw "Vote error: controller is absent.";

			this.CID = params["CID"];
			this.voteId = params["voteId"];
			this.url = params["url"];
			this.controller = params["controller"];
			this.form = params['form'];

			this.bind();

			this.period = [1, 5, 10, 30];
			params["startCheck"] = parseInt(params["startCheck"]);
			this.lastVote = (params["startCheck"] > 0 ? params["startCheck"] / 60 : 0);

			if (this.lastVote <= 0)
				this.check();
		};
		d.prototype = {
			status : "ready",
			check: function(now) {
				var time = (now === true ? 0 : false), i;
				if(now !== true) {
					for(i = 0; i < this.period.length; i++) {
						if(this.lastVote <= this.period[i]) {
							time = this.period[i];
							break;
						}
					}
				}
				if(time !== false)
					setTimeout(BX.proxy(function() {
						this.send()
					}, this), time * 60 * 1000);
			},
			send: function() {
				BX.ajax({
					url: this.url.
					replace(/.AJAX_RESULT=Y/g, '').
					replace(/.AJAX_POST=Y/g, '').
					replace(/.sessid=[^&]*/g, '').
					replace(/.VOTE_ID=([\d]+)/, '').
					replace(/.view_form=Y/g, '').
					replace(/.view_result=Y/g, ''),
					method: 'POST',
					dataType: 'json',
					data: {
						VOTE_ID: this.voteId,
						AJAX_RESULT: 'Y',
						view_result: 'Y',
						dataType: 'json',
						sessid: BX.bitrix_sessid()
					},
					onsuccess: BX.proxy(function(data) {
						this.start(data);
					}, this),
					onfailure: function(data) {
					}
				});
			},
			start: function(data) {
				this.lastVote = parseInt(data["LAST_VOTE"] / 60);
				this.changeData(data);
				this.check();
			},
			changeData: function(data) {
				data = data["QUESTIONS"];
				var question, answer;
				BX.onCustomEvent(this.controller, 'OnBeforeChangeData');
				for(var q in data) {
					if(data.hasOwnProperty(q)) {
						question = BX.findChild(this.controller, {"attr": {"id": "question" + q}}, true);
						if(question) {
							for(var i in data[q]) {
								if(data[q].hasOwnProperty(i)) {
									answer = BX.findChild(question, {"attr": {"id": ("answer" + i)}}, true);
									if(answer) {
										BX.adjust(answer, {attrs: {"bx-voters-count": data[q][i]["COUNTER"]}});
										BX.unbindAll(answer, "click");
										if(!this.form)
											BX.bind(answer, "click", voteGetUsersL);

										BX.adjust(BX.findChild(answer, {
												"tagName": "DIV",
												"className": "bx-vote-data-percent"
											}, true),
											{"html": '<span>' + parseInt(data[q][i]["PERCENT"]) + '</span><span class="post-vote-color">%</span>'});
										BX.adjust(BX.findChild(answer, {
												"tagName": "DIV",
												"className": "bx-vote-answer-bar"
											}, true),
											{"style": {"width": parseInt(data[q][i]["PERCENT"]) + '%'}});
									}
								}
							}
						}
					}
				}
				BX.adjust(BX.findChild(this.controller, {"tagName": "TD", "className": "bx-vote-events-count"}, true),
					{"html": '<span>' + parseInt(data["COUNTER"]) + '</span><span class="post-vote-color">%</span>'});
				BX.onCustomEvent(this.controller, 'OnAfterChangeData');
			},
			replace: function(url, data) {
				url = url.
				replace(/.AJAX_RESULT=Y/g,'').
				replace(/.AJAX_POST=Y/g,'').
				replace(/.sessid=[^&]*/g, '').
				replace(/.VOTE_ID=([\d]+)/,'').
				replace(/.view_form=Y/g, '').
				replace(/.view_result=Y/g, '');
				this.post(url, data);
			},
			getXHR : function() {
				this.BMAjaxWrapper = (this.BMAjaxWrapper || new MobileAjaxWrapper());
				return this.BMAjaxWrapper;
			},
			post : function(url, data) {
				if (this.status == "busy")
					return;
				var empty = true, i;
				if (data)
				{
					for (i in data)
					{
						if (data.hasOwnProperty(i))
						{
							empty = false;
							break;
						}
					}
				}
				if (empty)
					return;

				data["VOTE_ID"] = this.voteId;
				data["AJAX_POST"] = "Y";
				data["sessid"] = BX.bitrix_sessid();

				VCLinkShowWait();
				this.status = "busy";

				this.postRelease = BX.proxy(function() {
					VCLinkCloseWait();
					this.status = "ready";
				}, this);

				this.getXHR().Wrap({
					type: 'html',
					method: 'POST',
					url: url,
					data: data,
					processData: false,
					callback: BX.proxy(function(result){
						this.postRelease();
						if (data && ((data == '{"status":"failed"}') || (data.status == 'failed')))
						{
							app.BasicAuth({
								success: BX.proxy(function(){this.post(url, data)}, this),
								failure: function(){ }
							});
						}
						else
						{
							var ob = BX.processHTML(result, false),
								res = this.node.block;
							res.innerHTML = "";
							res.innerHTML = ob.HTML;

							BX.removeClass(res, "bx-vote-block-result");
							BX.removeClass(res, "bx-vote-block-result-view");

							if (ob.HTML.indexOf('<form') < 0) {
								BX.addClass(res, "bx-vote-block-result");
							}

							BX.defer(function(){
								BX.ajax.processScripts(ob.SCRIPT);
							})();
						}
					}, this),
					callback_failure : this.postRelease
				});
			},
			node : {
				revote : null,
				vote : null,
				results : null,
				block : null
			},
			bind : function() {
				//BX.addCustomEvent("onVoteEntityWasChanged", BX.proxy());
				this.node.revote = BX('vote-revote-' + this.CID);
				if (this.node.revote)
				{
					BX.unbindAll(this.node.revote);
					BX.bind(this.node.revote, "click", BX.delegate(function(e) {
						BX.eventCancelBubble(e);
						this.replace(e.target.getAttribute("href"), {view_form : "Y"});
						return BX.PreventDefault(e);
					}, this));
				}
				this.node.vote = BX('vote-do-' + this.CID);
				if (this.node.vote && this.form)
				{
					BX.unbindAll(this.node.vote);
					BX.bind(this.node.vote, "click", BX.proxy(function(e) {
						BX.eventCancelBubble(e);
						this.replace(this.form.action, BX.ajax.prepareForm(this.form).data);
						return BX.PreventDefault(e);
					}, this));
				}
				this.node.results = BX('vote-view-' + this.CID);
				this.node.block = BX.findParent(this.controller, {tagName : "DIV", className : "bx-vote-block"});
				if (this.node.results)
				{
					BX.unbindAll(this.node.results);

					BX.bind(this.node.results, "click", BX.proxy(function(e) {
						BX.eventCancelBubble(e);
						VCLinkShowWait();

						this.node.resultsf = BX.proxy(function() {
							BX.addClass(this.node.block, "bx-vote-block-result");
							BX.addClass(this.node.block, "bx-vote-block-result-view");
							VCLinkCloseWait();
							BX.removeCustomEvent(this.controller, 'OnBeforeChangeData', this.node.resultsf);
						}, this);
						BX.addCustomEvent(this.controller, 'OnBeforeChangeData', this.node.resultsf);

						this.node.resultsf1 = BX.proxy(function(){
							BX.hide(BX("vote-view-" + this.CID));
							BX.removeCustomEvent(this.controller, 'OnAfterChangeData', this.node.resultsf1);
						}, this);
						BX.addCustomEvent(this.controller, 'OnAfterChangeData', this.node.resultsf1);

						this.lastVote = 0;
						this.check(true);

						return BX.PreventDefault(e);
					}, this));
				}
				this.onPullEvent = BX.delegate(function(data) {
					var params = data.params;
					if (data.command == "voting" && params["VOTE_ID"] == this.voteId)
					{
						var res = BX.findParent(this.controller, {"className" : "bx-vote-block"});
						if (res && BX.hasClass(res, "bx-vote-block-result"))
						{
							this.changeData(params);
						}
					}
				}, this);
				BX.addCustomEvent(window, 'onPull-vote', this.onPullEvent);
				BXMobileApp.onCustomEvent('onPullExtendWatch', {id: 'VOTE_' + this.voteId}, true);
			},
			unbind : function() {
				delete this.form;
				BX.unbindAll(this.node.revote);
				delete this.node.revote;
				BX.unbindAll(this.node.vote);
				delete this.node.vote;
				BX.unbindAll(this.node.results);
				delete this.node.results;
				delete this.node.block;
				BX.removeCustomEvent(window, 'onPull-vote', this.onPullEvent);
			}
		};
		return d;
	})(),
		voteGetUsers = function(e, node) {
			if (!node || !node.hasAttribute("bx-voters-count") || parseInt(node.getAttribute("bx-voters-count")) <= 0)
				return false;
			BX.PreventDefault(e);
			var id = node.getAttribute("id").replace("answer", ""),
				url = "/bitrix/templates/mobile_app/components/bitrix/voting.current/.userfield/users.php?answer_id="+id+"&sessid="+BX.bitrix_sessid();
			app.openBXTable({
				url: url,
				TABLE_SETTINGS : {
					markmode : false,
					cache: false
				}
			});
		},
		voteGetUsersL = function(e) { voteGetUsers(e, this); };


	BX.Mobile.Vote.init = function(params) {

		params["CID"] = params["id"];
		params["controller"] = BX('vote-' + params["id"]);
		params["form"] = BX('vote-form-' + params["id"]);
		if (repo[params["id"]])
			repo[params["id"]].unbind();

		repo[params["id"]] = new BVoteChecker(params);

		var rows = BX.findChildren(params.controller, {"tagName" : "TR", "className" : "bx-vote-answer-item"}, true);
		if (!params["form"] && rows && rows.length > 0)
		{
			for (var ii = 0; ii < rows.length; ii++)
			{
				BX.unbindAll(rows[ii]);
				BX.bind(rows[ii], "click", voteGetUsersL);
			}
		}
	};

})(window);