/**
 * @bxjs_lang_path js/transcript.php
 */
(function()
{
	var AJAX_URL = "/bitrix/components/bitrix/voximplant.transcript.view/ajax.php";
	BX.namespace('BX.Voximplant');

	BX.Voximplant.Transcript = function(params)
	{
		this.callId = params.callId;
		this.bindNode = BX.type.isDomNode(params.bindNode) || null;

		this.popupId = "vi-transcript-" + BX.util.getRandomString(5);
		this.popup = null;
		this.rootNode = null;
		this.messages = [];
	};

	BX.Voximplant.Transcript.create = function(params)
	{
		return new BX.Voximplant.Transcript(params);
	};

	BX.Voximplant.Transcript.prototype.getNode = function(name, scope)
	{
		if (!scope)
			scope = this.rootNode;

		return scope ? scope.querySelector('[data-role="'+name+'"]') : null;
	};

	BX.Voximplant.Transcript.prototype.show = function()
	{
		var self = this;
		this.load(function(html)
		{
			self.showPopup({
				title: BX.message('VOX_TRANSCRIPT_POPUP_TITLE'),
				content: html
			});
		});
	};

	BX.Voximplant.Transcript.prototype.load = function(next)
	{
		var params = {
			'sessid': BX.bitrix_sessid(),
			'action': 'getTranscript',
			'params': {
				'callId': this.callId
			}
		};

		next = BX.type.isFunction(next) ? next : BX.DoNothing;
		
		BX.ajax({
			url: AJAX_URL,
			method: "POST",
			dataType: "html",
			data:params,
			onsuccess: function(html)
			{
				next(html);
			},
			onfailure: function()
			{
				console.log('Something gone wrong');
			}
		});
	};

	BX.Voximplant.Transcript.prototype.showPopup = function (params)
	{
		var self = this;
		this.popup = new BX.PopupWindow(this.popupId, this.bindNode, {
			closeIcon: true,
			closeByEsc: true,
			autoHide: false,
			titleBar: params.title,
			content: params.content,
			contentColor: 'white',
			contentNoPaddings: true,
			draggable: { restrict: true },
			resizable: {
				minWidth: 560,
				minHeight: 400
			},
			width: 560,
			height: 400,
			overlay: {
				color: 'gray',
				opacity: 30
			},
			events: {
				onPopupClose: function() {
					this.destroy();
				},
				onPopupDestroy: function() {
					self.popup = null;
				}
			}
		});
		this.rootNode = this.popup.contentContainer;
		this.popup.show();
		this.bindEvents();
	};

	BX.Voximplant.Transcript.prototype.bindEvents = function()
	{
		var dialogNode = this.getNode("dialog");
		if(dialogNode)
		{
			var messageNodes = dialogNode.querySelectorAll('.vox-transcript-line-container');
			for(var i = 0; i < messageNodes.length; i++)
			{
				this.messages.push({
					_node: messageNodes[i],
					text: messageNodes[i].dataset.text
				})
			}
		}

		var filterNode = this.getNode("input-filter");
		if(filterNode)
		{
			BX.bind(filterNode, "bxchange", this._onFilterChange.bind(this))
		}
	};

	BX.Voximplant.Transcript.prototype._onFilterChange = function(e)
	{
		var searchString = e.target.value.toLowerCase();
		var self = this;
		this.messages.forEach(function(message)
		{
			var text = message.text.toLowerCase();
			if(text.indexOf(searchString) === -1)
			{
				self._hide(message._node);
			}
			else
			{
				self._show(message._node);
			}
		});
	};


	BX.Voximplant.Transcript.prototype._hide = function(node)
	{
		node.style.display = 'none';
	};

	BX.Voximplant.Transcript.prototype._show = function(node)
	{
		node.style.removeProperty('display');
	};
})();