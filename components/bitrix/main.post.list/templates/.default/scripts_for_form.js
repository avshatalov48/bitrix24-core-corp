;(function(){
	window["UC"] = (window["UC"] || {});
	if (!!window["FCForm"])
		return;

	window.FCForm = function(arParams)
	{
		this.url = '';
		this.lhe = '';
		this.entitiesId = {};
		this.form = BX(arParams['formId']);
		this.handler = window.LHEPostForm.getHandler(arParams['editorId']);
		this.editorName = arParams['editorName'];
		this.editorId = arParams['editorId'];
		this.windowEvents = {
			OnUCUnlinkForm : BX.delegate(function(entityId) {
				if (!!entityId && !!this.entitiesId[entityId]) {
					var res = {}, empty = true;
					for (var ii in this.entitiesId)
					{
						if (this.entitiesId.hasOwnProperty(ii) && ii != entityId)
						{
							empty = false;
							res[ii] = this.entitiesId[ii];
						}
					}
					this.entitiesId = res;
					if (empty && !!this.windowEvents)
					{
						for (ii in this.windowEvents)
						{
							if (this.windowEvents.hasOwnProperty(ii) && ii)
								BX.removeCustomEvent(window, ii, this.windowEvents[ii]);
						}
						this.windowEventsSet = false;
					}
				}
			}, this),
			OnUCQuote : BX.delegate(function(entityId, author, quotedText) {
				if (
					!this.entitiesId[entityId]
					|| !this._checkTextSafety([entityId, 0])
				)
				{
					return;
				}

				this.show([entityId, 0]);
				var attempts = 0;
				var quoteHandler = function() {
					attempts++;
					if (attempts > 10)
					{
						return;
					}
					if (!this.handler.oEditor || !this.handler.oEditor.action)
					{
						return setTimeout(quoteHandler, 10);
					}
					if (!this.handler.oEditor.toolbar.controls.Quote)
					{
						return;
					}
					if (!author && !quotedText)
					{
						return this.handler.oEditor.action.Exec('quote');
					}

					var haveWrittenText = '';
					if (author)
					{
						haveWrittenText = author.gender ? BX.message("MPL_HAVE_WRITTEN_"+author.gender) : BX.message("MPL_HAVE_WRITTEN");
						if (this.handler.oEditor.GetViewMode() === 'wysiwyg') // BB Codes
						{
							if (author.id > 0)
							{
								author = '<span id="' + this.handler.oEditor.SetBxTag(false, {tag: "postuser", userId: author.id, userName: author.name}) +
									'" class="bxhtmled-metion">' + author.name.replace(/</gi, '&lt;').replace(/>/gi, '&gt;') + '</span>';
							}
							else
							{
								author = '<span>' + author.name.replace(/</gi, '&lt;').replace(/>/gi, '&gt;') + '</span>';
							}
							haveWrittenText = (author + haveWrittenText + '<br/>');
						}
						else if (this.handler.oEditor.bbCode)
						{
							if (author.id > 0)
							{
								author = "[USER=" + author.id + "]" + author.name + "[/USER]";
							}
							else
							{
								author = author.name;
							}
							haveWrittenText = (author !== '' ? (author + haveWrittenText + '\n') : '');
						}
					}

					if (this.handler.oEditor.action.actions.quote.setExternalSelectionFromRange)
					{
						// Here we take selected text via editor tools
						// we don't use "res"
						this.handler.oEditor.action.actions.quote.setExternalSelectionFromRange();
						var extSel = this.handler.oEditor.action.actions.quote.getExternalSelection();

						if (extSel === '' && quotedText !== '')
						{
							extSel = quotedText;
						}
						extSel = haveWrittenText + extSel;
						if (BX.type.isNotEmptyString(extSel))
						{
							this.handler.oEditor.action.actions.quote.setExternalSelection(extSel);
						}
					}
					else
					{
						// For compatibility with old fileman (< 16.0.1)
						this.handler.oEditor.action.actions.quote.setExternalSelection(quotedText);
					}
					this.handler.oEditor.action.Exec('quote');
				}.bind(this);
				this.handler.exec(quoteHandler);
			}, this),
			OnUCReply : function(entityId, authorId, authorName, safeEdit, eventResult) {
				if (!this.entitiesId[entityId]
					|| !this._checkTextSafety([entityId, 0])
					|| eventResult.caught === true
				)
				{
					return false;
				}
				eventResult.caught = true;

				this.show([entityId, 0]);
				if (authorId > 0)
				{
					this.handler.exec(window.BxInsertMention, [{
						item: {entityId: authorId, name: authorName},
						type: 'user',
						formID: this.form.id,
						editorId: this.editorId,
						bNeedComa: true,
						insertHtml: true
					}]);
				}
			}.bind(this),
			OnUCAfterRecordEdit : BX.delegate(function(entityId, id, data, act) {
				if (!!this.entitiesId[entityId]) {
					if (act === "EDIT")
					{
						this.show([entityId, id], data['messageBBCode'], data['messageFields']);
						this.editing = true;
					}
					else
					{
						this.hide(true);
						if (!!data['errorMessage'])
						{
							this.id = [entityId, id];
							this.showError(data['errorMessage']);
						}
						else if (!!data['okMessage'])
						{
							this.id = [entityId, id];
							this.showNote(data['okMessage']);
							this.id = null;
						}
					}
				} }, this)
		};

		this.linkEntity(arParams['entitiesId']);

		BX.remove(BX("micro" + arParams['editorName']));
		BX.remove(BX("micro" + arParams['editorId']));

		this.eventNode = this.handler.eventNode;

		if (this.eventNode)
		{
			BX.addCustomEvent(this.eventNode, 'OnBeforeHideLHE', BX.delegate(function(/*show, obj*/) {
				BX.removeClass(document.documentElement, 'bx-ios-fix-frame-focus');
				if (top && top["document"])
					BX.removeClass(top["document"]["documentElement"], 'bx-ios-fix-frame-focus');
				if (!!this.id && !!BX('uc-writing-' + this.form.id + '-' + this.id[0] + '-area'))
				{
					BX.hide(BX('uc-writing-' + this.form.id + '-' + this.id[0] + '-area'));
				}
			}, this));

			BX.addCustomEvent(this.eventNode, 'OnAfterHideLHE', BX.delegate(function(/*show, obj*/) {
				var node = this._getPlacehoder();
				if (node)
				{
					BX.hide(node);
				}

				node = this._getSwitcher();
				if (node)
				{
					BX.show(node);
					BX.focus(node.firstChild);
				}

				this.__content_length = 0;
				if (!!this.id) {
					BX.onCustomEvent(this.eventNode, 'OnUCFormAfterHide', [this]);
				}
				clearTimeout(this._checkWriteTimeout);
				this._checkWriteTimeout = 0;
				this.clear();
				BX.onCustomEvent(window, "OnUCFeedChanged", [this.id]);
			}, this));

			BX.addCustomEvent(this.eventNode, 'OnBeforeShowLHE', BX.delegate(function(/*show, obj*/) {
				if (BX.browser.IsIOS() && BX.browser.IsMobile())
				{
					BX.addClass(window["document"]["documentElement"], 'bx-ios-fix-frame-focus');
					if (top && top["document"])
						BX.addClass(top["document"]["documentElement"], 'bx-ios-fix-frame-focus');
				}
				var node = this._getPlacehoder();

				if (node)
				{
					BX.removeClass(node, 'feed-com-add-box-no-form');
					BX.removeClass(node, 'feed-com-add-box-header');
					BX.show(node);
				}
				node = this._getSwitcher();
				if (node)
				{
					BX.hide(node);
				}

				if (!!this.id && !!BX('uc-writing-' + this.form.id + '-' + this.id[0] + '-area'))
				{
					BX.hide(BX('uc-writing-' + this.form.id + '-' + this.id[0] + '-area'));
				}
			}, this));
			BX.addCustomEvent(this.eventNode, 'OnAfterShowLHE', function(show, obj){
				this._checkWrite(show, obj);
				BX.onCustomEvent(window, "OnUCFeedChanged", [this.id]);
			}.bind(this));
			BX.addCustomEvent(this.eventNode, 'OnClickSubmit', BX.delegate(this.submit, this));
			BX.addCustomEvent(this.eventNode, 'OnClickCancel', BX.delegate(this.cancel, this));

			BX.onCustomEvent(this.eventNode, 'OnUCFormInit', [this]);
		}
		this.id = null;
		this.jsCommentId = null;

		// Lock the submit button when inserting an image.
		BX.addCustomEvent(window, 'OnImageDataUriHandle', BX.delegate(this.showWait, this));
		BX.addCustomEvent(window, 'OnImageDataUriCaughtUploaded', BX.delegate(this.closeWait, this));
		BX.addCustomEvent(window, 'OnImageDataUriCaughtFailed', BX.delegate(this.closeWait, this));
	};
	window.FCForm.prototype = {
		linkEntity : function(Ent) {
			if (!!Ent)
			{
				for(var ii in Ent)
				{
					if (Ent.hasOwnProperty(ii))
					{
						BX.onCustomEvent(window, 'OnUCUnlinkForm', [ii]);
						this.entitiesId[ii] = Ent[ii];
					}
				}
			}
			if (!this.windowEventsSet && !!this.entitiesId)
			{
				BX.addCustomEvent(window, 'OnUCUnlinkForm', this.windowEvents.OnUCUnlinkForm);
				BX.addCustomEvent(window, 'OnUCReply', BX.debounce(this.windowEvents.OnUCReply, 10));
				BX.addCustomEvent(window, 'OnUCQuote', BX.debounce(this.windowEvents.OnUCQuote, 10));
				BX.addCustomEvent(window, 'OnUCAfterRecordEdit', this.windowEvents.OnUCAfterRecordEdit);
				this.windowEventsSet = true;
			}
		},
		_checkTextSafety : function(id) {
			if (
				this.id
				&& this.id.join('-') !== id.join('-')
				&& this.handler.editorIsLoaded
				&& this.handler.oEditor.IsContentChanged()
			)
			{
				return window.confirm(BX.message('MPL_SAFE_EDIT'));
			}
			return true;
		},
		_checkWrite : function() {
			if (this.handler.editorIsLoaded && this._checkWriteTimeout !== false)
			{
				this.__content_length = (this.__content_length > 0 ? this.__content_length : 0);
				var content = this.handler.oEditor.GetContent(),
					time = 2000;
				if(content.length >= 4 && this.__content_length != content.length && !!this.id)
				{
					BX.onCustomEvent(window, 'OnUCUserIsWriting', [this.id[0], {sent : false}]);
					time = 30000;
				}
				this._checkWriteTimeout = setTimeout(this._checkWrite.bind(this), time);
				this.__content_length = content.length;
			}
		},
		_getPlacehoder : function(res) {res = (!!res ? res : this.id); return (!!res ? BX('record-' + res.join('-') + '-placeholder') : null); },
		_getSwitcher : function(res) {res = (!!res ? res : this.id); return (!!res ? BX('record-' + res[0] + '-switcher') : null); },
		hide : function(quick) {if (this.eventNode.style.display != 'none') { BX.onCustomEvent(this.eventNode, 'OnShowLHE', [(quick === true ? false : 'hide')]); } if (quick) { document.body.appendChild(this.form); }},
		clear : function() {
			//var form = this.form, filesForm = null;
			this.editing = false;
			var res = this._getPlacehoder();
			if (!!res)
				BX.hide(res);

			this.clearNotification(res, 'feed-add-error');

			BX.onCustomEvent(this.eventNode, 'OnUCFormClear', [this]);

			var filesForm = BX.findChild(this.form, {'className': 'wduf-placeholder-tbody' }, true, false);
			if(filesForm !== null && typeof filesForm != 'undefined')
				BX.cleanNode(filesForm, false);
			filesForm = BX.findChild(this.form, {'className': 'wduf-selectdialog' }, true, false);
			if(filesForm !== null && typeof filesForm != 'undefined')
				BX.hide(filesForm);

			filesForm = BX.findChild(this.form, {'className': 'file-placeholder-tbody' }, true, false);
			if(filesForm !== null && typeof filesForm != 'undefined')
				BX.cleanNode(filesForm, false);

			this.id = null;
			this.jsCommentId = null;
		},
		show : function(id, text, data)
		{
			if (this.id && !!id && this.id.join('-') == id.join('-'))
			{
				var placeholderNode = this._getPlacehoder(id);
				this.handler.oEditor.Focus();
				setTimeout(function() {
					var pos = BX.pos(placeholderNode);
					var windowPos = BX.GetWindowSize(document);
					if (windowPos.scrollTop > pos.top)
					{
						placeholderNode.scrollIntoView();
					}
					else if ((windowPos.scrollTop + windowPos.innerHeight) < pos.top)
					{
						placeholderNode.scrollIntoView(false);
					}
				}, 100);
				return true;
			}
			else
			{
				this.hide(true);
			}

			this.id = id;
			this.jsCommentId = BX.util.getRandomString(20);

			var node = this._getPlacehoder();
			BX.removeClass(node, 'feed-com-add-box-no-form');
			BX.removeClass(node, 'feed-com-add-box-header');
			node.appendChild(this.form);
			BX.onCustomEvent(this.eventNode, 'OnUCFormBeforeShow', [this, text, data]);
			BX.onCustomEvent(this.eventNode, 'OnShowLHE', ['show', null, this.id]);
			BX.onCustomEvent(this.eventNode, 'OnUCFormAfterShow', [this, text, data]);
			return true;
		},
		submit : function() {
			if (this.busy === true)
				return 'busy';

			var text = (this.handler.editorIsLoaded ? this.handler.oEditor.GetContent() : '');

			if (!text)
			{
				this.showError(BX.message('JERROR_NO_MESSAGE'));
				return false;
			}
			this.showWait();
			this.busy = true;

			var post_data = {};
			window.convertFormToArray(this.form, post_data);
			post_data['REVIEW_TEXT'] = text;
			post_data['NOREDIRECT'] = "Y";
			post_data['MODE'] = "RECORD";
			post_data['AJAX_POST'] = "Y";
			post_data['id'] = this.id;
			if (this.jsCommentId !== null)
				post_data['COMMENT_EXEMPLAR_ID'] = this.jsCommentId;
			post_data['SITE_ID'] = BX.message("SITE_ID");
			post_data['LANGUAGE_ID'] = BX.message("LANGUAGE_ID");
			post_data["ACTION"] = "ADD";

			if (this.editing === true)
			{
				post_data['REVIEW_ACTION'] = "EDIT"; // @deprecated
				post_data["FILTER"] = {"ID" : this.id[1]};
				post_data["ACTION"] = "EDIT";
				post_data["ID"] = this.id[1]; // comment to edit
			}
			BX.onCustomEvent(this.eventNode, 'OnUCFormSubmit', [this, post_data]);
			BX.onCustomEvent(window, 'OnUCFormSubmit', [this.id[0], this.id[1], this, post_data]);

			var actionUrl = this.form.action;
			actionUrl = BX.util.remove_url_param(actionUrl, [ 'b24statAction' ]);
			actionUrl = BX.util.add_url_param(actionUrl, {
				b24statAction: (this.id[1] > 0 ? 'editComment' : 'addComment')
			});
			this.form.action = actionUrl;

			BX.ajax({
				method: 'POST',
				url: this.form.action,
				data: post_data,
				dataType: 'json',
				onsuccess: BX.proxy(function(data) {
					this.closeWait();
					var true_data = data, ENTITY_XML_ID = this.id[0];
					BX.onCustomEvent(this.eventNode, 'OnUCFormResponse', [this, data]);
					if (!!this.OnUCFormResponseData)
						data = this.OnUCFormResponseData;
					if (!!data)
					{
						if (data['errorMessage'])
						{
							this.showError(data['errorMessage']);
						}
						else if (data["status"] == "error")
						{
							this.showError((BX.type.isNotEmptyString(data["message"]) ? data["message"] : ""));
						}
						else
						{
							BX.onCustomEvent(window, 'OnUCAfterRecordAdd', [this.id[0], data, true_data]);
							this.hide(true);
						}
					}
					this.busy = false;
					BX.onCustomEvent(window, 'OnUCFormResponse', [ENTITY_XML_ID, data["messageId"], this, data]);
				}, this),
				onfailure: BX.delegate(function(){this.closeWait();
					this.busy = false;
					BX.onCustomEvent(window, 'OnUCFormResponse', [this.id[0], this.id[1], this, []]);}, this)
			});
		},
		cancel : function() {},
		clearNotification : function(node, className) {
			var nodes = BX.findChildren(node, {tagName : "DIV", className : className}, true);
			if (!!nodes)
			{
				var res = nodes.pop();
				do {
					BX.remove(res);
					BX.remove(res);
				} while ((res = nodes.pop()) && !!res);
			}
		},
		showError : function(text) {
			if (!text)
				return;

			var node = this._getPlacehoder();
			this.clearNotification(node, 'feed-add-error');
			BX.addClass(node, (!node.firstChild ? 'feed-com-add-box-no-form' : 'feed-com-add-box-header'));

			node.insertBefore(BX.create(
				'div', {
					attrs : {
						class: "feed-add-error"
					},
					html: '<span class="feed-add-info-text"><span class="feed-add-info-icon"></span>' + '<b>' + BX.message('FC_ERROR') + '</b><br />' + text + '</span>'
				}),
				node.firstChild);

			BX.show(node);
		},
		showNote : function(text) {
			if (!text)
				return;

			var node = this._getPlacehoder();
			this.clearNotification(node, 'feed-add-error');
			this.clearNotification(node, 'feed-add-successfully');
			BX.addClass(node, (!node.firstChild ? 'feed-com-add-box-no-form' : 'feed-com-add-box-header'));

			node.insertBefore(BX.create('div', {attrs : {"class": "feed-add-successfully"},
				html: '<span class="feed-add-info-text"><span class="feed-add-info-icon"></span>' + text + '</span>'}),
				node.firstChild);
			BX.addClass(node, 'comment-deleted');
			BX.show(node);
		},
		showWait : function() {
			var el = BX('lhe_button_submit_' + this.form.id);
			this.busy = true;
			if (!!el)
			{
				BX.addClass(el, "ui-btn-clock");
				BX.defer(function(){el.disabled = true})();
			}
		},
		closeWait : function() {
			var el = BX('lhe_button_submit_' + this.form.id);
			this.busy = false;
			if (!!el )
			{
				el.disabled = false ;
				BX.removeClass(el, "ui-btn-clock");
			}
		},
	};

	window.convertFormToArray = function(form, data)
	{
		data = (!!data ? data : []);
		if(!!form){
			var
				i,
				_data = [],
				n = form.elements.length;

			for(i=0; i<n; i++)
			{
				var el = form.elements[i];
				if (el.disabled)
					continue;
				switch(el.type.toLowerCase())
				{
					case 'text':
					case 'textarea':
					case 'password':
					case 'hidden':
					case 'select-one':
						_data.push({name: el.name, value: el.value});
						break;
					case 'radio':
					case 'checkbox':
						if(el.checked)
							_data.push({name: el.name, value: el.value});
						break;
					case 'select-multiple':
						for (var j = 0; j < el.options.length; j++) {
							if (el.options[j].selected)
								_data.push({name : el.name, value : el.options[j].value});
						}
						break;
					default:
						break;
				}
			}

			var current = data;
			i = 0;

			while(i < _data.length)
			{
				var p = _data[i].name.indexOf('[');
				if (p == -1) {
					current[_data[i].name] = _data[i].value;
					current = data;
					i++;
				}
				else
				{
					var name = _data[i].name.substring(0, p);
					var rest = _data[i].name.substring(p+1);
					if(!current[name])
						current[name] = [];

					var pp = rest.indexOf(']');
					if(pp == -1)
					{
						current = data;
						i++;
					}
					else if(pp === 0)
					{
						//No index specified - so take the next integer
						current = current[name];
						_data[i].name = '' + current.length;
					}
					else
					{
						//Now index name becomes and name and we go deeper into the array
						current = current[name];
						_data[i].name = rest.substring(0, pp) + rest.substring(pp+1);
					}
				}
			}
		}
		return data;
	};

	window["fRefreshCaptcha"] = function(form)
	{
		var captchaIMAGE = null,
			captchaHIDDEN = BX.findChild(form, {attr : {'name': 'captcha_code'}}, true),
			captchaINPUT = BX.findChild(form, {attr: {'name':'captcha_word'}}, true),
			captchaDIV = BX.findChild(form, {'className':'comments-reply-field-captcha-image'}, true);
		if (captchaDIV)
			captchaIMAGE = BX.findChild(captchaDIV, {'tag':'img'});
		if (captchaHIDDEN && captchaINPUT && captchaIMAGE)
		{
			captchaINPUT.value = '';
			BX.ajax.getCaptcha(function(result) {
				captchaHIDDEN.value = result["captcha_sid"];
				captchaIMAGE.src = '/bitrix/tools/captcha.php?captcha_code='+result["captcha_sid"];
			});
		}
	};
})();
