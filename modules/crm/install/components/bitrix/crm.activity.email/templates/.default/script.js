
;(function() {



	if (window.BXCrmActivityEmailController)
		return;
	var BXCrmActivityEmailController = {};

	BXCrmActivityEmailController.init = function (options)
	{
		var ctrl = this;

		if (this.__inited)
			return;

		this.options = options;

		this.__dummyNode = document.createElement('DIV');

		this.templates = {
			'0': {'FROM': '', 'SUBJECT': '', 'BODY': ''}
		};

		this.templateLoader = new BX.Loader({
			target: document.querySelector(".crm-activity-planner-slider-header-control-select"),
			size: 20,
			mode: 'inline',
			offset: {
				left: '4%',
				top: '-2%'
			}
		});

		if ('edit' != this.options.type)
		{
			if (this.options.pageSize < 1 || this.options.pageSize > 100)
				this.options.pageSize = 5;

			this.__log = {'a': 0, 'b': 0};

			var details = BX('crm-activity-email-details-'+this.options.activityId);

			var moreA = BX.findChildByClassName(details.parentNode, 'crm-task-list-mail-more-a', true);
			BX.bind(moreA, 'click', this.handleLogClick.bind(this, 'a'));

			var moreB = BX.findChildByClassName(details.parentNode, 'crm-task-list-mail-more-b', true);
			BX.bind(moreB, 'click', this.handleLogClick.bind(this, 'b'));

			var items = BX.findChildrenByClassName(details.parentNode, 'crm-task-list-mail-item', true);
			for (var i in items)
			{
				var log = items[i].getAttribute('data-log').toLowerCase();
				if (typeof this.__log[log] != 'undefined')
					this.__log[log]++;

				BX.bind(items[i], 'click', this.handleLogItemClick.bind(this, items[i].getAttribute('data-id')));
			}

			BX.Event.EventEmitter.subscribe(
				'BXMailMessageActions:CRM_EXCLUDE',
				function (event)
				{
					if (ctrl.options.mailMessageId == event.getData().messageId)
					{
						var slider = top.BX.SidePanel.Instance.getSliderByWindow(window);
						slider.setCacheable(false);
						slider.close();
					}
				}
			);
		}

		this.__inited = true;
	};

	BXCrmActivityEmailController.initScrollable = function()
	{
		if (!this.__scrollable)
		{
			if (document.scrollingElement)
				this.__scrollable = document.scrollingElement;
		}

		if (!this.__scrollable)
		{
			if (document.documentElement.scrollTop > 0 || document.documentElement.scrollLeft > 0)
				this.__scrollable = document.documentElement;
			else if (document.body.scrollTop > 0 || document.body.scrollLeft > 0)
				this.__scrollable = document.body;
		}

		if (!this.__scrollable)
		{
			window.scrollBy(1, 1);

			if (document.documentElement.scrollTop > 0 || document.documentElement.scrollLeft > 0)
				this.__scrollable = document.documentElement;
			else if (document.body.scrollTop > 0 || document.body.scrollLeft > 0)
				this.__scrollable = document.body;

			window.scrollBy(-1, -1);
		}

		return this.__scrollable;
	}

	BXCrmActivityEmailController.scrollWrapper = function (pos)
	{
		var ctrl = this;

		if (!this.initScrollable())
			return;

		if (this.__scrollable.__animation)
		{
			clearInterval(this.__scrollable.__animation);
			this.__scrollable.__animation = null;
		}

		var start = this.__scrollable.scrollTop;
		var delta = pos - start;
		var step = 0;
		this.__scrollable.__animation = setInterval(function()
		{
			step++;
			ctrl.__scrollable.scrollTop = start + delta * step/8;

			if (step >= 8)
			{
				clearInterval(ctrl.__scrollable.__animation);
				ctrl.__scrollable.__animation = null;
			}
		}, 20);
	};

	BXCrmActivityEmailController.scrollTo = function (node1, node2)
	{
		if (!this.initScrollable())
			return;

		var pos0 = BX.pos(this.__scrollable);

		pos0.top    += this.__scrollable.scrollTop;
		pos0.bottom += this.__scrollable.scrollTop;

		var pos1 = BX.pos(node1);
		var pos2 = typeof node2 == 'undefined' || node2 === node1 ? pos1 : BX.pos(node2);

		if (pos1.top < pos0.top)
		{
			this.scrollWrapper(this.__scrollable.scrollTop - (pos0.top - pos1.top));
		}
		else if (pos2.bottom > pos0.bottom)
		{
			this.scrollWrapper(Math.min(
				this.__scrollable.scrollTop - (pos0.top - pos1.top),
				this.__scrollable.scrollTop + (pos2.bottom - pos0.bottom)
			));
		}
	};

	BXCrmActivityEmailController.handleLogClick = function (log, event)
	{
		BX.PreventDefault(event);

		var button = BX.findChildByClassName(
			BX('crm-activity-email-details-'+this.options.activityId).parentNode,
			'crm-task-list-mail-more-'+log,
			true
		);
		this.loadLog(log, button);
	};

	BXCrmActivityEmailController.loadLog = function (log, button)
	{
		var ctrl = this;

		var separator = button.parentNode;

		if (this['__loadingLog'+log])
			return;

		this['__loadingLog'+log] = true;
		BX.ajax({
			method: 'POST',
			url: this.options.ajaxUrl,
			data: {
				act: 'log',
				id: this.options.activityId,
				log: log + this.__log[log],
				size: this.options.pageSize,
				template: 'slider'
			},
			dataType: 'json',
			onsuccess: function(json)
			{
				ctrl['__loadingLog'+log] = false;

				if (json.result != 'error')
				{
					ctrl.__dummyNode.innerHTML = json.html;

					var marker = log == 'a' ? BX.findNextSibling(separator, {'tag': 'div'}) : separator;
					while (ctrl.__dummyNode.childNodes.length > 0)
					{
						var item = separator.parentNode.insertBefore(ctrl.__dummyNode.childNodes[0], marker);
						if (item.nodeType == 1 && BX.hasClass(item, 'crm-task-list-mail-item'))
						{
							ctrl.__log[log]++;

							BX.addClass(item, 'crm-activity-email-show-animation-rev');
							BX.bind(item, 'click', ctrl.handleLogItemClick.bind(ctrl, item.getAttribute('data-id')));
						}
					}

					if (json.count < ctrl.options.pageSize)
						separator.style.display = 'none';

					if (log == 'b')
						ctrl.scrollWrapper(ctrl.__scrollable.scrollHeight);

					ctrl.__dummyNode.innerHTML = '';
				}
			},
			onfailure: function()
			{
				ctrl['__loadingLog'+log] = false;
			}
		});
	};

	BXCrmActivityEmailController.handleLogItemClick = function (activityId, event)
	{
		event = event || window.event;
		if (event.target && event.target.tagName && event.target.tagName.toUpperCase() == 'A')
			return;

		if (window.getSelection)
		{
			if (window.getSelection().toString().trim() != '')
				return;
		}
		else if (document.selection)
		{
			if (document.selection.createRange().htmlText.trim() != '')
				return;
		}

		BX.PreventDefault(event);

		this.toggleLogItem(activityId);
	};

	BXCrmActivityEmailController.toggleLogItem = function (activityId)
	{
		var ctrl = this;

		var wrapper = BX('crm-activity-email-details-'+this.options.activityId).parentNode;

		var logItem = BX.findChildByClassName(wrapper, 'crm-activity-email-logitem-'+activityId, false);
		var details = BX.findChildByClassName(wrapper, 'crm-activity-email-details-'+activityId, false);

		var opened  = BX.hasClass(logItem, 'crm-task-list-mail-item-open');

		BX.toggleClass(logItem, 'crm-task-list-mail-item-open');

		if (opened)
		{
			details.style.display = 'none';

			BX.addClass(logItem, 'crm-activity-email-show-animation-rev');
			logItem.style.display = '';
		}
		else
		{
			BX.removeClass(details, 'crm-activity-email-show-animation-rev');
			BX.addClass(details, 'crm-activity-email-show-animation');
			details.style.display = '';

			if (details.getAttribute('data-empty'))
			{
				BX.ajax({
					method: 'POST',
					url: this.options.ajaxUrl,
					data: {
						act: 'logitem',
						id: activityId,
						template: 'slider'
					},
					dataType: 'json',
					onsuccess: function (json)
					{
						if (json.result == 'error')
						{
							details.innerHTML = json.error;
							return;
						}

						var response = BX.processHTML(json.html);

						BX.removeClass(details, 'crm-activity-email-show-animation');
						BX.removeClass(details, 'crm-activity-email-show-animation-rev');
						setTimeout(function ()
						{
							details.style.textAlign = '';
							details.innerHTML = response.HTML;

							if (details.offsetHeight > 0)
								logItem.style.display = 'none';

							BX.ajax.processScripts(response.SCRIPT);

							BX.addClass(details, 'crm-activity-email-show-animation-rev');

							var button = BX.findChildByClassName(details, 'crm-task-list-mail-item-inner-header', true);
							BX.bind(button, 'click', ctrl.handleLogItemClick.bind(ctrl, activityId));

							ctrl.scrollTo(details);
						}, 10);

						details.removeAttribute('data-empty');
					}
				});

				this.scrollTo(logItem, details);
			}
			else
			{
				logItem.style.display = 'none';

				this.scrollTo(details);
			}
		}
	};

	BXCrmActivityEmailController.removeLogItem = function (activityId)
	{
		var wrapper = BX('crm-activity-email-details-'+this.options.activityId).parentNode;

		var logItem = BX.findChildByClassName(wrapper, 'crm-activity-email-logitem-'+activityId, false);
		var details = BX.findChildByClassName(wrapper, 'crm-activity-email-details-'+activityId, false);

		var log = logItem.getAttribute('data-log').toLowerCase();
		if (typeof this.__log[log] != 'undefined')
			this.__log[log]--;

		setTimeout(function()
		{
			wrapper.removeChild(details);
			wrapper.removeChild(logItem);
		}, 200);

		details.style.maxHeight = (details.offsetHeight*1.5)+'px';
		details.style.transition = 'max-height .2s ease-in';
		details.offsetHeight;
		details.style.maxHeight = '0px';

		BX.removeClass(details, 'crm-activity-email-show-animation');
		BX.removeClass(details, 'crm-activity-email-show-animation-rev');
		BX.addClass(details, 'crm-activity-email-close-animation');
	};

	BXCrmActivityEmailController.applyTemplate = function (id, ownerType, ownerId, callback)
	{
		var ctrl = this;

		var key = id > 0 ? [id, ownerType, ownerId].join(':') : id;

		if (this.templates[key])
		{
			callback(this.templates[key]);
			return;
		}

		BX.ajax({
			'url': '/bitrix/components/bitrix/crm.activity.editor/ajax.php?action=prepare_mail_template&templateid='+id,
			'method': 'POST',
			'dataType': 'json',
			'data': {
				sessid: BX.bitrix_sessid(),
				ACTION: 'PREPARE_MAIL_TEMPLATE',
				TEMPLATE_ID: id,
				OWNER_TYPE: ownerType,
				OWNER_ID: ownerId,
				CONTENT_TYPE: 'HTML'
			},
			onsuccess: function(data)
			{
				if (data.DATA)
				{
					ctrl.templates[key] = data.DATA;
					callback(data.DATA);
				}
			}
		});
	}

	var BXCrmActivityEmail = function (options)
	{
		var self = this;

		this.ctrl = BXCrmActivityEmailController;
		this.options = options;
		this.progressPercent = 0;

		if (!(this.ctrl.options.templates && this.ctrl.options.templates.length > 0))
		{
			this.ctrl.options.templates = this.options.templates;
		}

		this.__dummyNode = document.createElement('DIV');

		this.htmlForm = BX(this.options.formId);
		this.htmlForm.__wrapper = this.htmlForm.parentNode;

		if (this.htmlForm.__inited)
			return;

		if ('edit' != this.ctrl.options.type)
		{
			this.__wrapper = BX('crm-activity-email-details-'+this.ctrl.options.activityId);
			if (this.options.activityId != this.ctrl.options.activityId)
				this.__wrapper = BX.findChildByClassName(this.__wrapper.parentNode, 'crm-activity-email-details-'+this.options.activityId, false);

			BX.addCustomEvent(
				'CrmActivityEmail:replyButtonClick',
				function (source)
				{
					if (source !== self)
						self.hideReplyForm();
				}
			);

			this.initMessageBody();

			// show hidden rcpt items
			var rcptMore = BX.findChildrenByClassName(this.__wrapper, 'crm-task-list-mail-item-to-list-more');
			for (var i in rcptMore)
			{
				BX.bind(rcptMore[i], 'click', function (event)
				{
					BX.findChildByClassName(this.parentNode, 'crm-task-list-mail-item-to-list-hidden', false).style.display = 'inline';
					this.style.display = 'none';

					BX.PreventDefault(event);
				});
			}

			// outgoing message read confirmed handler
			BX.addCustomEvent('onPullEvent-crm', function (command, params)
			{
				if (command != 'activity_email_read_confirmed')
					return;
				if (params.ID != self.options.activityId)
					return;

				var items = BX.findChildrenByClassName(self.__wrapper, 'read-confirmed-datetime', true);
				if (items && items.length > 0)
				{
					for (var i in items)
						BX.adjust(items[i], {text: BX.message('CRM_ACT_EMAIL_VIEW_READ_CONFIRMED_SHORT')});
				}
			});

			var replyButton  = BX.findChildByClassName(this.__wrapper, 'crm-task-list-mail-message-panel', true);
			var replyLink    = BX.findChildByClassName(this.__wrapper, 'crm-task-list-mail-item-control-reply', true);
			var replyAllLink = BX.findChildByClassName(this.__wrapper, 'crm-task-list-mail-item-control-icon-answertoall', true);
			var forwardLink  = BX.findChildByClassName(this.__wrapper, 'crm-task-list-mail-item-control-icon-resend', true);
			var skipLink     = BX.findChildByClassName(this.__wrapper, 'crm-task-list-mail-item-control-icon-skip', true);
			var spamLink     = BX.findChildByClassName(this.__wrapper, 'crm-task-list-mail-item-control-icon-spam', true);
			var deleteLink   = BX.findChildByClassName(this.__wrapper, 'crm-task-list-mail-item-control-icon-delete', true);

			BX.bind(replyButton, 'click', this.showReplyForm.bind(this));
			BX.bind(replyAllLink, 'click', this.showReplyForm.bind(this, true));
			BX.bind(replyLink, 'click', this.showReplyForm.bind(this));

			BX.bind(forwardLink, 'click', function ()
			{
				var typeId = (BX.CrmActivityType || top.BX.CrmActivityType || { 'email': 4 }).email;
				window.location.href = '/bitrix/components/bitrix/crm.activity.planner/slider.php'
					+ '?site_id=' + BX.message('SITE_ID') + '&ajax_action=ACTIVITY_EDIT'
					+ '&TYPE_ID=' + typeId + '&FROM_ACTIVITY_ID=' + self.options.activityId
					+ '&MESSAGE_TYPE=FWD&IFRAME=Y&IFRAME_TYPE=SIDE_SLIDER';
			});

			BX.bind(skipLink, 'click', this.delete.bind(this, 'skip'));
			BX.bind(spamLink, 'click', this.delete.bind(this, 'spam'));
			BX.bind(deleteLink, 'click', this.delete.bind(this));

			if (options.isAjaxBody && options.bodyElementId)
			{
				this.ajaxLoadMessageBody();
			}
		}

		var mailForm = BXMainMailForm.getForm(this.options.formId);

		mailForm.options.ownerId = this.ctrl.options.ownerId;
		mailForm.options.ownerType = this.ctrl.options.ownerType;

		BX.addCustomEvent(mailForm, 'MailForm:footer:buttonClick', BXCrmActivityEmail.handleFooterButtonClick.bind(this));
		BX.addCustomEvent(mailForm, 'MailForm:submit', BXCrmActivityEmail.handleFormSubmit.bind(this));
		BX.addCustomEvent(mailForm, 'MailForm:submit:ajaxSuccess', BXCrmActivityEmail.handleFormSubmitSuccess.bind(this));

		this.htmlForm.__inited = true;
	};

	BXCrmActivityEmail.prototype.initMessageBody = function ()
	{
		var emailContainerId = 'activity_' + this.options.activityId + '_body';

		initEmailLinks(emailContainerId);
		initQuotes(emailContainerId);
	}

	function initEmailLinks(emailContainerId)
	{
		// target links
		const emailLinks = typeof document.querySelectorAll != 'undefined'
			? document.querySelectorAll('#' + emailContainerId + ' a')
			: BX.findChildren(BX(emailContainerId), { tag: 'a' }, true);
		for (const i in emailLinks)
		{
			if (!emailLinks.hasOwnProperty(i))
				continue;

			if (emailLinks[i] && emailLinks[i].setAttribute)
				emailLinks[i].setAttribute('target', '_blank');
		}
	}

	function initQuotes(emailContainerId)
	{
		// unfold quotes
		const quotesList = typeof document.querySelectorAll != 'undefined'
			? document.querySelectorAll('#' + emailContainerId + ' blockquote')
			: BX.findChildren(BX(emailContainerId), { tag: 'blockquote' }, true);
		for (const i in quotesList)
		{
			if (!quotesList.hasOwnProperty(i))
				continue;

			BX.bind(quotesList[i], 'click', function ()
			{
				BX.addClass(this, 'crm-email-quote-unfolded');
			});
		}
	}

	BXCrmActivityEmail.handleFooterButtonClick = function (form, button)
	{
		if (BX.hasClass(button, 'main-mail-form-cancel-button'))
		{
			if ('edit' == this.ctrl.options.type)
			{
				top.BX.SidePanel.Instance.getSliderByWindow(window).close();
			}
			else
			{
				this.hideReplyForm();
			}
		}
	};

	BXCrmActivityEmail.handleFormSubmit = function (form, event)
	{
		var fields = this.htmlForm.elements;
		var emptyRcpt = true;
		for (var i = 0; i < fields.length; i++)
		{
			if ('DATA[to][]' == fields[i].name && fields[i].value.length > 0)
				emptyRcpt = false;
		}
		if (emptyRcpt)
		{
			// @TODO: hide on select
			form.showError(BX.message('CRM_ACT_EMAIL_REPLY_EMPTY_RCPT'));
			return BX.PreventDefault(event);
		}

		// @TODO: use events
		var uploads, items, totalSize = 0;
		for (var i in form.postForm.controllers)
		{
			if (!form.postForm.controllers.hasOwnProperty(i))
				continue;

			if (form.postForm.controllers[i].storage != 'disk')
				continue;

			try
			{
				uploads = 0;
				uploads = form.postForm.controllers[i].handler.agent.upload.filesCount;
			}
			catch (err) {}

			if (uploads > 0)
			{
				// @TODO: hide on complete
				form.showError(BX.message('CRM_ACT_EMAIL_REPLY_UPLOADING'));
				return BX.PreventDefault(event);
			}

			if (BX.message('CRM_ACT_EMAIL_MAX_SIZE') > 0)
			{
				try
				{
					items = form.postForm.controllers[i].handler.agent.queue.items.items;
					totalSize = Object.keys(items).reduce(
						function (sum, k)
						{
							return sum + (items[k].file ? parseInt(items[k].file.sizeInt || items[k].file.size) : 0);
						},
						totalSize
					);
				}
				catch (err) {}
			}
		}

		if (BX.message('CRM_ACT_EMAIL_MAX_SIZE') > 0 && BX.message('CRM_ACT_EMAIL_MAX_SIZE') <= Math.ceil(totalSize / 3) * 4) // base64 coef.
		{
			form.showError(BX.message('CRM_ACT_EMAIL_MAX_SIZE_EXCEED'));
			return BX.PreventDefault(event);
		}

		if ('edit' == this.ctrl.options.type)
		{
			var hiddenWrapper = BX('crm_act_email_create_hidden');
			hiddenWrapper.innerHTML = '';

			var fields = BX.findChildren(document, {'tag': 'input'}, true);
			for (var i = 0, clone; i < fields.length; i++)
			{
				if (fields[i].name && fields[i].name.indexOf('__crm_activity_planner[') >= 0)
				{
					clone = fields[i].cloneNode(true);
					clone.removeAttribute('id');
					clone.setAttribute('name', 'DATA'+fields[i].name.substr('__crm_activity_planner'.length));

					hiddenWrapper.appendChild(clone);
				}
			}
		}
	};

	BXCrmActivityEmail.handleFormSubmitSuccess = function (form, data)
	{
		if (data.ERROR && data.ERROR.length > 0 || data.ERROR_HTML && data.ERROR_HTML.length > 0)
		{
			data.ERROR = !data.ERROR? [] : data.ERROR;
			data.ERROR = !BX.type.isArray(data.ERROR)? [data.ERROR]  : data.ERROR;

			var errorNode = document.createElement('DIV');
			for (var i = 0; i < data.ERROR.length; i++)
			{
				errorNode.appendChild(document.createTextNode(data.ERROR[i]));
				errorNode.appendChild(document.createElement('BR'));
			}

			data.ERROR_HTML = !data.ERROR_HTML? [] : data.ERROR_HTML;
			data.ERROR_HTML = !BX.type.isArray(data.ERROR_HTML)? [data.ERROR_HTML]  : data.ERROR_HTML;
			for (var j = 0; j < data.ERROR_HTML.length; ++j)
			{
				errorNode.innerHTML += data.ERROR_HTML[i] + "<br>";
			}

			form.showError(errorNode.innerHTML);
		}
		else
		{
			top.BX.onCustomEvent(
				'Bitrix24.Slider:postMessage',
				[
					window,
					{
						action: 'ACTIVITY_CREATE',
						source_id: this.ctrl.options.activityId,
						target_id: data.ACTIVITY.ID
					}
				]
			);

			if ('edit' != this.ctrl.options.type)
			{
				this.hideReplyForm();
			}

			var slider = top.BX.SidePanel.Instance.getSliderByWindow(window);
			slider.setCacheable(false);
			slider.close();
		}
	};

	BXCrmActivityEmail.prototype.showReplyForm = function(isReplyAll)
	{
		var mailForm = BXMainMailForm.getForm(this.options.formId);
		var replyButton = BX.findChildByClassName(this.__wrapper, 'crm-task-list-mail-message-panel', true);

		if (this.htmlForm.parentNode === this.__dummyNode)
			this.htmlForm.__wrapper.appendChild(this.htmlForm);

		var isInit = mailForm.init({
			isReplyAll,
		});

		if (isInit === false)
		{
			if (isReplyAll === true)
			{
				mailForm.fillFieldsForReplyAll();
			}
			else
			{
				mailForm.fillFieldsForReply();
			}
		}

		BX.onCustomEvent('CrmActivityEmail:replyButtonClick', [this]);

		BX.addClass(this.htmlForm, 'crm-activity-email-show-animation');
		this.htmlForm.style.display = '';

		replyButton.style.display = 'none';

		BX.onCustomEvent(mailForm, 'MailForm:show', []);

		this.ctrl.scrollTo(this.htmlForm);
	};

	BXCrmActivityEmail.prototype.hideReplyForm = function ()
	{
		var mailForm = BXMainMailForm.getForm(this.options.formId);
		var replyButton = BX.findChildByClassName(this.__wrapper, 'crm-task-list-mail-message-panel', true);

		BX.addClass(replyButton, 'crm-activity-email-show-animation-rev');
		replyButton.style.display = '';

		this.htmlForm.style.display = 'none';

		BX.onCustomEvent(mailForm, 'MailForm:hide', []);

		this.__dummyNode.appendChild(this.htmlForm);
	};

	BXCrmActivityEmail.prototype.delete = function (act)
	{
		var self = this;

		var warnId = 'CRM_ACT_EMAIL_DELETE_CONFIRM';
		switch (act)
		{
			case 'skip':
				warnId = 'CRM_ACT_EMAIL_SKIP_CONFIRM';
				break;
			case 'spam':
				warnId = 'CRM_ACT_EMAIL_SPAM_CONFIRM';
				break;
		}

		if (!window.confirm(BX.message(warnId)))
			return false;

		var deleteLink = BX.findChildByClassName(this.__wrapper, 'crm-task-list-mail-item-control-icon-delete', true);

		var data = {
			sessid: BX.bitrix_sessid(),
			ACTION: 'DELETE',
			IS_SKIP: 'skip' == act ? 'Y' : 'N',
			IS_SPAM: 'spam' == act ? 'Y' : 'N',
			ITEM_ID: this.options.activityId
		};

		var fields = BX.findChildren(deleteLink.parentNode, {tag: 'input'}, true);
		for (var i = 0; i < fields.length; i++)
		{
			if (fields[i].name)
				data[fields[i].name] = fields[i].value;
		}

		BX.ajax({
			'url': '/bitrix/components/bitrix/crm.activity.editor/ajax.php?id='+this.options.activityId+'&action=delete',
			'method': 'POST',
			'dataType': 'json',
			'data': data,
			onsuccess: function(data)
			{
				top.BX.onCustomEvent(
					'Bitrix24.Slider:postMessage',
					[
						window,
						{
							action: 'ACTIVITY_DELETE',
							source_id: self.ctrl.options.activityId,
							target_id: self.options.activityId
						}
					]
				);

				if (self.ctrl.options.activityId != self.options.activityId)
				{
					self.ctrl.removeLogItem(self.options.activityId);
				}
				else
				{
					var slider = top.BX.SidePanel.Instance.getSliderByWindow(window);
					slider.setCacheable(false);
					slider.close();
				}
			}
		});
	};

	BXCrmActivityEmail.prototype.batch = function (batch)
	{
		var mailForm = BXMainMailForm.getForm(this.options.formId);

		mailForm.getField('DATA[cc]')[batch?'hide':'show']();
		mailForm.getField('DATA[bcc]')[batch?'hide':'show']();
	};

	BXCrmActivityEmail.prototype.activateTemplate = function(event, item, formId, ownerType, ownerId, selector)
	{
		var mailForm = BXMainMailForm.getForm(formId);
		var self = this;
		self.ctrl.templateLoader.show();
		self.ctrl.applyTemplate(item.__id, ownerType, ownerId, function (data)
		{
			self.hasTemplateSignature = false;
			if (data.FROM && data.FROM.length > 0)
			{
				var fromField = mailForm.getField('DATA[from]');

				fromField.setValue(data.FROM);
				if (fromField.params.folded)
					fromField.unfold();
			}

			const htmlFields = self.htmlForm.elements;
			const forwardedId = htmlFields['DATA[FORWARDED_ID]'] ? htmlFields['DATA[FORWARDED_ID]'].value : 0;
			const repliedId = htmlFields['DATA[REPLIED_ID]'] ? htmlFields['DATA[REPLIED_ID]'].value : 0;

			if (!(forwardedId > 0 || repliedId > 0))
			{
				const subjectField = mailForm.getField('DATA[subject]');

				subjectField.setValue(data.SUBJECT ?? '');
				if (subjectField.params.folded)
				{
					subjectField.unfold();
				}
			}

			const filesInfo = data.FILES_INFO && data.FILES_INFO.length > 0 ? data.FILES_INFO : [];
			mailForm.getField('DATA[__diskfiles]').setValue(filesInfo);
			let message = data.BODY && data.BODY.length > 0 ? `<div>${data.BODY}</div>` : '<br>';
			message = updateSignatureNodeId(self, message, mailForm.formId, mailForm.signatureNodeId);
			if (mailForm.sharingLinkNodeClass)
			{
				message = mailForm.updateSharingLinkNode(message, self.options.calendarLink);
			}
			mailForm.getField('DATA[message]').setValue(message, {quote: true, signature: !self.hasTemplateSignature, filesInfo: filesInfo});
			self.ctrl.templateLoader.hide();

			const range = mailForm.editor.selection.GetRange();
			const firstNode = mailForm.editor.GetIframeDoc().body.firstChild;
			const firstNodeTagName = firstNode.tagName;

			if (firstNodeTagName === 'BR')
			{
				return;
			}

			if (firstNodeTagName === 'DIV' && firstNode.firstChild)
			{
				range.setStartBefore(firstNode.firstChild);
				range.setStartBefore(firstNode.firstChild);
				mailForm.editor.selection.SetSelection(range);

				return;
			}

			range.setStartBefore(firstNode);
			range.setStartBefore(firstNode);
			mailForm.editor.selection.SetSelection(range);
		});

		BX.adjust(selector, {html: item.text});
		if (item.menuWindow)
		{
			item.menuWindow.close();
		}

	};

	const updateSignatureNodeId = function(self, template, formId, newSignatureNodeId)
	{
		const crmSignaturePattern = '"main_mail_form+\\w+_signature_+\\w+"';
		const regExp = new RegExp(crmSignaturePattern, 'gi');
		if (regExp.test(template))
		{
			self.hasTemplateSignature = true;

			return template.replace(regExp, `"${newSignatureNodeId}"`);
		}

		return template;
	};

	BXCrmActivityEmail.prototype.templateMenu = function (ownerType, ownerId, selector)
	{
		var self = this;
		const templates = [];
		if (this.ctrl.options.templates)
		{
			this.ctrl.options.templates.forEach((template) => {
				if (template.entityType === ownerType || template.entityType === '')
				{
					templates.push(template);
				}
			});
		}

		const acceptClass = 'lenta-sort-item-selected';
		let saveTemplateClassName = 'save-last-template-toggle lenta-sort-item';

		if(this.ctrl.options.saveLastUsedTemplate === 'Y')
		{
			saveTemplateClassName += ' ' + acceptClass;
		}

		this.classSeparator = 'main-buttons-submenu-delimiter';
		this.classHiddenLabel = 'main-buttons-hidden-label';
		this.classSubmenuItem = 'main-buttons-submenu-item';

		const items = [];

		const lastUsedTemplateIdNode = BX('crm_act_email_create_last_used_template_id');

		if (this.ctrl.options.isEnabledSavingLastUsedTemplate === 'Y')
		{
			items.push(
				{
					delimiter: true,
					html: '<span>' + BX.message('CRM_ACT_EMAIL_TEMPLATE_SETTINGS_TITLE') + '</span>',
					className: [
						this.classSeparator,
						this.classSubmenuItem,
						this.classHiddenLabel
					].join(' '),
				},
				{
					text: BX.message('CRM_ACT_EMAIL_TEMPLATE_SAVE_LAST_TEMPLATE'),
					className: saveTemplateClassName,
					onclick: function (e, item){
						const element = item.getContainer();
						if(element.classList.contains(acceptClass))
						{
							BX.removeClass(element, acceptClass);
						}
						else
						{
							BX.addClass(element, acceptClass);
						}
						BX.ajax.runAction('crm.api.mail.MailTemplate.toggleSaveLastUsedTemplate');
					}
				},
				{
					text: BX.message('CRM_ACT_EMAIL_TEMPLATE_SETTINGS'),
					className: 'lenta-sort-item',
					onclick: function (e){
						BX.SidePanel.Instance.open("/crm/configs/mailtemplate/", {
							cacheable: false,
							width: 1080,
							events: {
								onOpen(){
									BX.PopupMenu.getCurrentMenu().close();
								},
								onClose(){
									self.ctrl.templateLoader.show();
									BX.ajax.runAction('crm.api.mail.MailTemplate.getTitleList',{
										data: {
											ownerTypeId: self.ctrl.options.activityOwnerTypeId
										}
									}).then((response)=> {
										const templates = response.data;
										BX.PopupMenu.getCurrentMenu().destroy();
										self.ctrl.templates = {
											'0': {'FROM': '', 'SUBJECT': '', 'BODY': ''}
										};
										self.ctrl.options.templates = templates;
										self.ctrl.templateLoader.hide();
									});
								}
							}
						});
					}
				},
				{
					delimiter: true,
					html: '<span>' + BX.message('CRM_ACT_EMAIL_TEMPLATE_LIST_TITLE') + '</span>',
					className: [
						this.classSeparator,
						this.classSubmenuItem,
						this.classHiddenLabel
					].join(' ')
				},
			);
		}

		items.push({
			__id: 0,
			text: BX.message('CRM_ACT_EMAIL_CREATE_NOTEMPLATE'),
			className: 'menu-popup-no-icon',
			onclick: function (e, item) {
				this.activateTemplate(null, item, self.options.formId, ownerType, ownerId, selector);
				if (lastUsedTemplateIdNode)
				{
					BX.adjust(lastUsedTemplateIdNode, {props: {value: 0}});
				}
			}.bind(this)
		});

		for (var i in templates)
		{
			items.push({
				__id: templates[i].id,
				text: BX.util.htmlspecialchars(templates[i].title),
				title: BX.util.htmlspecialchars(templates[i].title),
				className: 'menu-popup-no-icon',
				onclick: function (e, item) {
					if (lastUsedTemplateIdNode)
					{
						BX.adjust(lastUsedTemplateIdNode, {props: {value: item.__id}})
					}
					this.activateTemplate(null, item, self.options.formId, ownerType, ownerId, selector);

				}.bind(this)
			});
		}

		BX.PopupMenu.show(
			'crm-activity-email-'+this.options.activityId+'-template-menu',
			selector, items,
			{
				maxWidth: 300,
				maxHeight: 600,
				offsetLeft: 40,
				angle: true,
				closeByEsc: true,
				events: {
					onPopupShow: function(){
						BX.Event.EventEmitter.emit(this,'CrmActivityEmail::onShowTemplatesList', {
							target: '.save-last-template-toggle',
							stepId: 'step-save-last-used-template',
						})
					}.bind(this),
				}
			}
		);
	};

	BXCrmActivityEmail.prototype.ajaxLoadMessageBody = function ()
	{
		const activityId = this.options.activityId;
		if (!activityId)
		{
			return;
		}
		this.bindErrorClose();
		this.startProgressTimer();

		BX.ajax.runAction('bitrix:crm.mail.message.getDescriptionAndQuote', {
			data: { id: activityId },
		}).then((response) => {
			if (BX.type.isNotEmptyObject(response.data))
			{
				this.handleSuccessResponse(response.data);
			}
			else
			{
				this.handleFailedResponse();
			}
		}, () => {
			this.handleFailedResponse();
		});
	}

	BXCrmActivityEmail.prototype.handleSuccessResponse = function (data)
	{
		this.stopProgress();
		if (BX.type.isNotEmptyObject(data) && BX.type.isString(data.descriptionHtml))
		{
			this.insertBodyText(data.descriptionHtml);

			if (BX.type.isString(data.quote))
			{
				this.insertQuoteText(data.quote);
			}
		}
		safeHide(this.options.warningWaitElementId);

		this.showControls();
	}

	BXCrmActivityEmail.prototype.insertQuoteText = function(quote)
	{
		const options = this.options;
		if (BX.type.isString(quote)
			&& BX.type.isString(options.formId)
			&& BX.type.isString(options.formQuoteFieldName)
			&& BX.type.isObject(BXMainMailForm)
			&& BX.type.isObject(BXMainMailForm.getForm(options.formId))
			&& BX.type.isArray(BXMainMailForm.getForm(options.formId).fields))
		{
			const fields = BXMainMailForm.getForm(options.formId).fields;
			for (const i in fields)
			{
				if (fields.hasOwnProperty(i))
				{
					if (BX.type.isObject(fields[i]) && fields[i].name === options.formQuoteFieldName)
					{
						fields[i].value = quote;
						break;
					}
				}
			}
		}
	};

	BXCrmActivityEmail.prototype.insertBodyText = function (html)
	{
		const messageBodyElement = document.getElementById(this.options.bodyElementId);
		if (messageBodyElement)
		{
			messageBodyElement.innerHTML = html;
			this.initMessageBody();
		}
	}

	BXCrmActivityEmail.prototype.showError = function ()
	{
		safeHide(this.options.warningWaitElementId);
		safeShow(this.options.warningFailElementId);
	}

	BXCrmActivityEmail.prototype.startProgressTimer = function ()
	{
		const options = this.options;

		if (!options.bodyLoaderElementId || !options.bodyLoaderMaxTime)
		{
			return;
		}

		const progressContainer = document.getElementById(options.bodyLoaderElementId);
		if (!progressContainer)
		{
			return;
		}

		const myProgress = new BX.UI.ProgressBar({
			maxValue: 100,
			value: 0,
		});
		myProgress.renderTo(BX(options.bodyLoaderElementId));

		const stepTime = options.bodyLoaderMaxTime / 100 * 1000;

		this.progressInterval = setInterval(() =>
		{
			if (this.progressPercent >= 100)
			{
				this.stopProgress();
				this.progressPercent = 100;
			}
			else
			{
				this.progressPercent += 1;
			}
			myProgress.setValue(this.progressPercent);
			myProgress.update();
		}, stepTime);
	}

	BXCrmActivityEmail.prototype.stopProgress = function ()
	{
		clearInterval(this.progressInterval);
	}

	BXCrmActivityEmail.prototype.handleFailedResponse = function ()
	{
		this.stopProgress();
		this.showError();
		this.showControls();
	}

	BXCrmActivityEmail.prototype.showControls = function ()
	{
		safeShow(this.options.controlElementId);
		safeShow(this.options.replyElementId);
	}

	BXCrmActivityEmail.prototype.bindErrorClose = function ()
	{
		if (!this.options.warningFailElementId)
		{
			return;
		}
		const errorContainer = document.getElementById(this.options.warningFailElementId);
		if (!errorContainer)
		{
			return;
		}
		const closeElement = errorContainer.querySelector('.ui-alert-close-btn');
		if (!closeElement)
		{
			return;
		}
		BX.bind(closeElement, 'click', function ()
		{
			BX.hide(errorContainer);
		});
	}

	function safeHide(elementId)
	{
		if (elementId)
		{
			const element = document.getElementById(elementId);
			if (element)
			{
				BX.hide(element);
			}
		}
	}

	function safeShow(elementId)
	{
		if (elementId)
		{
			const element = document.getElementById(elementId);
			if (element && element.style && element.style.display === 'none')
			{
				element.style.display = '';
			}
		}
	}

	window.BXCrmActivityEmailController = BXCrmActivityEmailController;
	window.BXCrmActivityEmail = BXCrmActivityEmail;

})();
