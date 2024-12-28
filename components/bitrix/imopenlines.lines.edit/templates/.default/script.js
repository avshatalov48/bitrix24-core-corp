;(function(window){
	if (!!window.BX.OpenLinesConfigEdit)
		return;

	var selectorQueueInstance = null;

	window.BX.OpenLinesConfigEdit = {
		init : function(params)
		{
			BX.imolTrialHandler.init();
			BX.OpenLinesConfigEdit.addEventForTooltip();
			BX.OpenLinesConfigEdit.bindEvents();
			BX.OpenLinesConfigEdit.onLoad();
			if(params.isSuccessSendForm === true)
			{
				window.addEventListener('load', function() {
					BX.OpenLinesConfigEdit.formSuccessSendForm();
					params.isSuccessSendForm = false;
				});
			}
		},
		loadKpiTimeMenus : function(params)
		{
			if (params)
			{
				if (params.kpiFirstAnswer)
				{
					BX.ready(function(){
						new BX.ImolKpiTimeMenu({
							element: params.kpiFirstAnswer.element,
							bindElement: params.kpiFirstAnswer.bindElement,
							inputElement: params.kpiFirstAnswer.inputElement,
							items: params.kpiFirstAnswer.items,
							customInputId: 'imol_kpi_first_answer_time_custom_input',
							fullBlockName: 'imol_kpi_first_answer_full_block'
						});
					});
				}
				if (params.kpiFurtherAnswer)
				{
					BX.ready(function(){
						new BX.ImolKpiTimeMenu({
							element: params.kpiFurtherAnswer.element,
							bindElement: params.kpiFurtherAnswer.bindElement,
							inputElement: params.kpiFurtherAnswer.inputElement,
							items: params.kpiFurtherAnswer.items,
							customInputId: 'imol_kpi_further_answer_time_custom_input',
							fullBlockName: 'imol_kpi_further_answer_full_block'
						});
					});
				}
			}
		},
		loadKpiEntitySelector : function(params)
		{
			if (params)
			{
				if(params.firstAnswer)
				{
					var selectorFirstAnswer = new kpiSelector(params.firstAnswer);

					selectorFirstAnswer.setInput();
				}
				if(params.furtherAnswer)
				{
					var selectorFurtherAnswer = new kpiSelector(params.furtherAnswer);

					selectorFurtherAnswer.setInput();
				}
			}
		},
		initQueue : function(nodes, params)
		{
			if (selectorQueueInstance === null)
			{
				selectorQueueInstance = new Queue(params);
			}
			selectorQueueInstance.setInput(BX(nodes.queueInputNode));
			selectorQueueInstance.setUserInput(BX(nodes.userInputNode));
			selectorQueueInstance.setDefaultUserInput(BX(nodes.defaultUserInputNode));
		},
		formSuccessSendForm : function()
		{
			BX.OpenLinesConfigEdit.sendUsersData();
			BX.OpenLinesConfigEdit.updateLinesList();
		},
		successSendForm : function()
		{
			BX.OpenLinesConfigEdit.sendUsersData();
			BX.OpenLinesConfigEdit.updateLinesList();
		},
		sendUsersData : function()
		{
			if (selectorQueueInstance !== null)
			{
				BX.SidePanel.Instance.postMessage(window, 'ImOpenlines:reloadUsersList', selectorQueueInstance.selector.getDialog().getSelectedItems());
			}
		},
		updateLinesList : function()
		{
			var configId = BX('imol_config_id').value;
			BX.SidePanel.Instance.postMessage(window, 'ImOpenlines:updateLinesSubmit', configId);
		},
		addEventForTooltip : function()
		{
			BX.UI.Hint.init(BX('imopenlines-field-container'));
		},
		getUrlParam: function(url, paramName)
		{
			if (url.charAt(0) === '/')
			{
				url = window.location.protocol + '//' + window.location.host + url;
			}

			var objUrl = new URL(url);
			var page = objUrl.searchParams.get(paramName);

			return BX.util.htmlspecialchars(page);
		},
		getPageParam: function (url)
		{
			return this.getUrlParam(url, 'PAGE');
		},
		visualReload : function(page)
		{
			var pages = document.querySelectorAll('[data-imol-page]');
			for (var i=0; i < pages.length; i++)
			{
				if ((pages[i].dataset.imolPage === page || !pages[i].classList.contains('invisible')) &&
					!(pages[i].dataset.imolPage === page && !pages[i].classList.contains('invisible')))
				{
					BX.animationHandler.smoothShowHide(pages[i]);

					if (pages[i].dataset.imolPage === page)
					{
						var titleNode = BX(pages[i]).querySelector('[data-imol-title]');
						var title = titleNode.dataset.imolTitle.toString();

						if (BX('pagetitle') && title !== '')
						{
							BX('pagetitle').innerHTML = title;
						}
					}
				}
			}
		},
		reloadHandler : function(e)
		{
			e.preventDefault();
			var context = BX.proxy_context;
			var page = this.getPageParam(context.getAttribute('href').toString());
			this.changeHistoryPage(page);
			this.visualReload(page);
		},
		changeHistory : function(params)
		{
			var currentUrl = BX('imol_config_edit_form').getAttribute('action');
			if (currentUrl.charAt(0) === '/')
			{
				currentUrl = window.location.protocol + '//' + window.location.host + currentUrl;
			}

			var objUrl = new URL(currentUrl);
			var param;

			for (var key in params)
			{
				param = BX.util.htmlspecialchars(params[key]);
				objUrl.searchParams.set(key, param);
			}

			var href = objUrl.href;
			top.window.history.replaceState({}, "", href);
			BX('imol_config_edit_form').setAttribute('action', href);
		},
		changeHistoryPage: function(page)
		{
			var params = {
				'PAGE': page
			};
			BX('imol_config_current_page').value = page;
			//this.changeHistory(params);
		},
		changeRatingRequest: function(ratingRequest)
		{
			var params = {
				'rating-request': ratingRequest
			};

			this.changeHistory(params);
		},
		botmarketButtonAction : function(e)
		{
			e.preventDefault();
			BX.SidePanel.Instance.open('/market/collection/openline_bot/', {allowChangeHistory: false});
			top.BX.addCustomEvent(top, 'Rest:AppLayout:ApplicationInstall', function(installed, eventResult){
				eventResult.redirect = false;
				BX.OpenLinesConfigEdit.botAddHandler();
			});
		},
		botButtonAction : function(e)
		{
			e.preventDefault();
			BX.rest.Marketplace.open({
				PLACEMENT: 'OPENLINE_BOT'
			});
			top.BX.addCustomEvent(top, 'Rest:AppLayout:ApplicationInstall', function(installed, eventResult){
				eventResult.redirect = false;
				BX.OpenLinesConfigEdit.botAddHandler();
			});
		},
		botAddHandler : function()
		{
			window.location.reload();
		},

		//actions
		actionClose: function()
		{
			BX.OpenLinesConfigEdit.toggleSelectFormOrText(
				BX('imol_action_close'),
				BX('imol_action_close_form'),
				BX('imol_action_close_text')
			);
		},
		actionAutoClose: function()
		{
			BX.OpenLinesConfigEdit.toggleSelectFormOrText(
				BX('imol_action_auto_close'),
				BX('imol_action_auto_close_form'),
				BX('imol_action_auto_close_text')
			);
		},
		changeQueueTypeValue: function(selector)
		{
			if (selector.options[selector.selectedIndex].value == 'all')
			{
				if(BX.message('IMOL_CONFIG_EDIT_LIMIT_QUEUE_ALL') == 'Y')
				{
					BX.UI.InfoHelper.show(BX.message('IMOL_CONFIG_EDIT_LIMIT_INFO_HELPER_MESSAGE_TO_ALL'));
					selector.selectedIndex = 0;
				}
				else
				{
					BX.animationHandler.fadeSlideToggleByClass(BX('imol_limitation_max_chat_block'), false);
					BX.animationHandler.fadeSlideToggleByClass(BX('imol_workers_time_block'), false);
				}
			}
			else
			{
				if(BX.hasClass(BX('imol_workers_time_block'), 'invisible'))
				{
					BX.animationHandler.fadeSlideToggleByClass(BX('imol_limitation_max_chat_block'), true);
					if(BX('imol_limitation_max_chat').checked == true)
					{
						BX.animationHandler.fadeSlideToggleByClass(BX('imol_max_chat'), true);
					}
					BX.animationHandler.fadeSlideToggleByClass(BX('imol_workers_time_block'), true);
				}
			}
		},
		toggleKpiFirstBlock: function()
		{
			BX.animationHandler.fadeSlideToggleByClass(BX('imol_kpi_first_answer_inner_block'))
		},
		toggleKpiFurtherBlock: function()
		{
			BX.animationHandler.fadeSlideToggleByClass(BX('imol_kpi_further_answer_inner_block'))
		},
		toggleCrmBlock: function()
		{
			BX.animationHandler.fadeSlideToggleByClass(BX('imol_crm_block'))
		},
		toggleCrmSourceRule: function()
		{
			var selector = BX('imol_crm_create');
			if (selector.options[selector.selectedIndex].value == 'lead')
			{
				BX.animationHandler.fadeSlideToggleByClass(BX('imol_crm_source_rule'), true);

				BX.removeClass(BX('imol_crm_transfer_change_title'), 'invisible');
				BX.addClass(BX('imol_crm_transfer_change_title_deal'), 'invisible');

				BX.removeClass(BX('imol_crm_source_title'), 'invisible');
				BX.addClass(BX('imol_crm_source_title_deal'), 'invisible');

				BX.animationHandler.fadeSlideToggleByClass(BX('imol_crm_create_second_deal'), false);
			}
			else if (selector.options[selector.selectedIndex].value == 'deal')
			{
				BX.animationHandler.fadeSlideToggleByClass(BX('imol_crm_source_rule'), true);

				BX.addClass(BX('imol_crm_transfer_change_title'), 'invisible');
				BX.removeClass(BX('imol_crm_transfer_change_title_deal'), 'invisible');

				BX.addClass(BX('imol_crm_source_title'), 'invisible');
				BX.removeClass(BX('imol_crm_source_title_deal'), 'invisible');

				BX.animationHandler.fadeSlideToggleByClass(BX('imol_crm_create_second_deal'), true);
			}
			else
			{
				BX.animationHandler.fadeSlideToggleByClass(BX('imol_crm_source_rule'), false);

				BX.removeClass(BX('imol_crm_source_title'), 'invisible');
				BX.addClass(BX('imol_crm_source_title_deal'), 'invisible');

				BX.animationHandler.fadeSlideToggleByClass(BX('imol_crm_create_second_deal'), false);
			}
		},
		toggleQueueSettingsBlock: function(e)
		{
			e.preventDefault();
			BX.animationHandler.fadeSlideToggleByClass(BX('imol_queue_settings_block'));
			//BX.OpenLinesConfigEdit.toggleBoolInputValue(BX('imol_queue_settings_input'));
		},
		toggleWelcomeMessageBlock: function()
		{
			if(BX('imol_welcome_message').checked)
			{
				BX.removeClass(BX('imol_welcome_message_block'), 'invisible');
			}
			else
			{
				BX.addClass(BX('imol_welcome_message_block'), 'invisible');
			}
		},
		toggleAutomaticMessageBlock: function()
		{
			BX.animationHandler.fadeSlideToggleByClass(BX('imol_action_automatic_message'))
		},
		toggleVoteTimeLimitBlock: function()
		{
			BX.animationHandler.fadeSlideToggleByClass(BX('imol_action_vote_time_limit'))
		},
		toggleAgreementBlock: function()
		{
			BX.animationHandler.fadeSlideToggleByClass(BX('imol_agreement_message_block'))
		},
		toggleVoteBlock: function()
		{
			if(BX('imol_vote_message').getAttribute('data-limit') != 'Y')
			{
				BX.animationHandler.fadeSlideToggleByClass(BX('imol_vote_message_block'));

				var ratingRequest = BX(this).checked === true ? 'Y' : 'N';
				BX.OpenLinesConfigEdit.changeRatingRequest(ratingRequest);
			}
		},
		toggleWorkTimeBlock: function()
		{
			if(BX('imol_worktime_checkbox').getAttribute('data-limit') != 'Y')
			{
				if(BX('imol_worktime_checkbox').checked)
				{
					BX('imol_worktime_block').classList.remove('invisible');
				}
				else
				{
					BX('imol_worktime_block').classList.add('invisible');
				}
			}

			if (
				BX('imol_check_available').checked ||
				BX('imol_worktime_checkbox').checked
			)
			{
				BX('imol_worktime_answer_block').classList.remove('invisible');
			}
			else
			{
				BX('imol_worktime_answer_block').classList.add('invisible');
			}
		},
		toggleBotBlock: function()
		{
			BX.animationHandler.fadeSlideToggleByClass(BX('imol_welcome_bot_block'))
		},
		toggleQueueUsersBlock: function()
		{
			BX.OpenLinesConfigEdit.toggleSelectOperatorData(
				BX('imol_operator_data'),
				BX('users_for_queue_data'),
				BX('default_user_data')
			);
		},
		toggleWorkersTimeBlock: function(e)
		{
			//e.preventDefault();
			BX.animationHandler.fadeSlideToggleByClass(BX('imol_workers_time_block'));
			//BX.OpenLinesConfigEdit.toggleBoolInputValue(BX('imol_workers_time_input'));
		},
		toggleNoAnswerRule: function()
		{
			BX.OpenLinesConfigEdit.toggleSelectFormText(
				BX('imol_no_answer_rule'),
				BX('imol_no_answer_rule_form_form'),
				BX('imol_no_answer_rule_text')
			);
		},
		toggleQueueMaxChat: function()
		{
			BX.animationHandler.fadeSlideToggleByClass(BX('imol_max_chat'))
		},
		toggleWorktimeDayoffRule: function()
		{
			BX.OpenLinesConfigEdit.toggleSelectFormText(
				BX('imol_worktime_dayoff_rule'),
				BX('imol_worktime_dayoff_rule_form'),
				BX('imol_worktime_dayoff_rule_text')
			);
		},
		toggleWelcomeFormBlock: function()
		{
			if (BX('imol_form_welcome').checked)
			{
				BX('imol_form_welcome_block').classList.remove("invisible");
			}
			else
			{
				BX('imol_form_welcome_block').classList.add("invisible");
			}
		},
		toggleWelcomeFormDelayHint: function()
		{
			if (BX('imol_form_welcome_delay').value === 'Y')
			{
				BX('imol_form_no_delay_description').classList.add("invisible");
				BX('imol_form_delay_description').classList.remove("invisible");
			}
			else
			{
				BX('imol_form_no_delay_description').classList.remove("invisible");
				BX('imol_form_delay_description').classList.add("invisible");
			}
		},
		deleteOpenLine: function()
		{
			var configId = BX('imol_config_id').value;
			BX.ajax.runComponentAction('bitrix:imopenlines.lines.edit', 'deleteOpenLine', {
				mode: 'ajax',
				data: {
					configId: configId
				}
			}).then(function (response) {
				BX.SidePanel.Instance.closeAll();
				window.top.BX.UI.Notification.Center.notify({
					content: BX.message('IMOL_CONFIG_EDIT_DELETE_NOTIFICATION_SUCCESS'),
				});
				location.href = BX.SidePanel.Instance.pageUrl;
			}, function (response) {
				BX('imol-alert-popup-text').innerHTML = BX.message('IMOL_CONFIG_EDIT_DELETE_FAIL');
			});

		},
		addNewForm: function(event) {
			event.preventDefault();
			BX.SidePanel.Instance.open('/crm/webform/edit/0/?ACTIVE=Y',
				{events:
					{
						onClose: function(e){
							// BX.SidePanel.Instance.postMessage(
							// 	e.getSlider(), 'ContactCenter:reloadItem', {moduleId:'crm',itemCode:'form'}
							// )
							// e.denyAction();
							// e.getSlider().close();
						}
					}
				}
			);
		},
		showPopupDeleteConfirm: function()
		{
			var popup = new BX.PopupWindow({
				closeByEsc: true,
				content: BX('imol_delete_openline_popup'),
				closeIcon: true,
				className: 'imopenlines-control-alert-popup',
				buttons: [
					new BX.UI.Button({
						text : BX.message('IMOL_CONFIG_EDIT_DELETE_THIS_OPENLINE_BUTTON'),
						color: BX.UI.Button.Color.DANGER,
						onclick: function(button) {
							button.setWaiting();
							BX.OpenLinesConfigEdit.deleteOpenLine();
						}
					}),
					new BX.UI.CancelButton({
						events: {
							click: function(button){
								button.getContext().close();
							}
						}
					}),
				]
			});
			popup.show();
		},

		//toggle providers
		toggleSelectFormText: function(selector, form, textarea)
		{
			if (!!selector)
			{
				if (selector.options[selector.selectedIndex].value == 'form')
				{
					BX.animationHandler.fadeSlideToggleByClass(form, true);
					BX.animationHandler.fadeSlideToggleByClass(textarea, true);
				}
				else if (selector.options[selector.selectedIndex].value == 'text')
				{
					BX.animationHandler.fadeSlideToggleByClass(form, false);
					BX.animationHandler.fadeSlideToggleByClass(textarea, true);
				}
				else
				{
					BX.animationHandler.fadeSlideToggleByClass(form, false);
					BX.animationHandler.fadeSlideToggleByClass(textarea, false);
				}
			}
		},
		toggleSelectFormOrText: function(selector, form, textarea)
		{
			if (!!selector)
			{
				if (selector.options[selector.selectedIndex].value == 'form')
				{
					BX.animationHandler.fadeSlideToggleByClass(form, true);
					BX.animationHandler.fadeSlideToggleByClass(textarea, false);
				}
				else if (selector.options[selector.selectedIndex].value == 'text' || selector.options[selector.selectedIndex].value == 'quality')
				{
					BX.animationHandler.fadeSlideToggleByClass(form, false);
					BX.animationHandler.fadeSlideToggleByClass(textarea, true);
				}
				else
				{
					BX.animationHandler.fadeSlideToggleByClass(form, false);
					BX.animationHandler.fadeSlideToggleByClass(textarea, false);
				}
			}
		},
		toggleSelectOperatorData: function(selector, listQueue, listHide)
		{
			if (selector.options[selector.selectedIndex].value == 'queue')
			{
				BX.animationHandler.fadeSlideToggleByClass(listQueue, true);
				BX.animationHandler.fadeSlideToggleByClass(listHide, false);
			}
			else if(selector.options[selector.selectedIndex].value == 'hide')
			{
				BX.animationHandler.fadeSlideToggleByClass(listHide, true);
				BX.animationHandler.fadeSlideToggleByClass(listQueue, false);
			}
			else
			{
				BX.animationHandler.fadeSlideToggleByClass(listHide, false);
				BX.animationHandler.fadeSlideToggleByClass(listQueue, false);
			}
		},
		toggleExtraContainer: function()
		{
			BX.toggleClass(BX('imol_extra_btn'), ['imopenlines-extra-btn-container-active', '']);
			BX.toggleClass(BX('imol_extra_container'), ['ui-form-border-bottom', '']);
			BX.animationHandler.fadeSlideToggleByClass(BX('imol_setting_container'));

			var value =  BX.hasClass(BX('imol_extra_btn'),'imopenlines-extra-btn-container-active') ? 'Y' : 'N';
			BX('imol_config_opened').setAttribute('value', value);
		},
		toggleBoolInputValue: function(input)
		{
			input.value = input.value === 'Y' ? 'N' : 'Y';
		},
		openQuickAnswers: function(button)
		{
			var url = button.dataset.url;
			url.toString();

			if (url != '')
			{
				//BX.SidePanel.Instance.open(url, {allowChangeHistory: false});
				window.open(url,'_blank');
			}
		},


		//binders
		bindEvents: function()
		{
			BX.bind(
				BX('imol_crm_create'),
				'change',
				BX.OpenLinesConfigEdit.toggleCrmSourceRule
			);
			BX.bind(
				BX('imol_extra_btn'),
				'click',
				BX.OpenLinesConfigEdit.toggleExtraContainer
			);
			BX.bind(
				BX('imol_no_answer_rule'),
				'change',
				BX.OpenLinesConfigEdit.toggleNoAnswerRule
			);
			BX.bind(
				BX('imol_worktime_dayoff_rule'),
				'change',
				BX.OpenLinesConfigEdit.toggleWorktimeDayoffRule
			);
			BX.bind(
				BX('imol_queue_settings_link'),
				'click',
				BX.OpenLinesConfigEdit.toggleQueueSettingsBlock
			);
			BX.bind(
				BX('imol_workers_time_link'),
				'click',
				BX.OpenLinesConfigEdit.toggleWorkersTimeBlock
			);
			BX.bind(
				BX('imol_agreement_message'),
				'change',
				BX.OpenLinesConfigEdit.toggleAgreementBlock
			);
			BX.bind(
				BX('imol_vote_message'),
				'change',
				BX.OpenLinesConfigEdit.toggleVoteBlock
			);
			BX.bind(
				BX('imol_worktime_checkbox'),
				'change',
				BX.OpenLinesConfigEdit.toggleWorkTimeBlock
			);
			BX.bind(
				BX('imol_crm_checkbox'),
				'change',
				BX.OpenLinesConfigEdit.toggleCrmBlock
			);
			BX.bind(
				BX('imol_operator_data'),
				'change',
				BX.OpenLinesConfigEdit.toggleQueueUsersBlock
			);
			BX.bind(
				BX('imol_welcome_message'),
				'change',
				BX.OpenLinesConfigEdit.toggleWelcomeMessageBlock
			);
			BX.bind(
				BX('imol_automatic_message'),
				'change',
				BX.OpenLinesConfigEdit.toggleAutomaticMessageBlock
			);
			BX.bind(
				BX('imol_vote_time_limit'),
				'change',
				BX.OpenLinesConfigEdit.toggleVoteTimeLimitBlock
			);
			BX.bind(
				BX('imol_check_available'),
				'change',
				BX.OpenLinesConfigEdit.toggleWorkTimeBlock
			);
			BX.bind(
				BX('imol_action_close'),
				'change',
				BX.OpenLinesConfigEdit.actionClose
			);
			BX.bind(
				BX('imol_action_auto_close'),
				'change',
				BX.OpenLinesConfigEdit.actionAutoClose
			);
			BX.bind(
				BX('imol_kpi_first_answer_alert'),
				'change',
				BX.OpenLinesConfigEdit.toggleKpiFirstBlock
			);
			BX.bind(
				BX('imol_kpi_further_answer_alert'),
				'change',
				BX.OpenLinesConfigEdit.toggleKpiFurtherBlock
			);
			BX.bind(BX('imol_queue_type'),
				'change',
				function(e) {
					BX.OpenLinesConfigEdit.changeQueueTypeValue(this);
				}
			);
			BX.bind(
				BX('imol_limitation_max_chat'),
				'change',
				BX.OpenLinesConfigEdit.toggleQueueMaxChat
			);
			BX.bind(
				BX('imol_active_checkbox'),
				'change',
				function (e) {
					var configId = BX('imol_config_id').value;
					if (this.checked)
					{
						BX.ajax.runComponentAction('bitrix:imopenlines.lines.edit', 'checkCanActiveLine', {
							mode: 'ajax',
							data: {
								configId: configId
							}
						}).then(BX.proxy(function(data){
							if (data.data === false)
							{
								this.checked = false;
								alert(BX.message('IMOL_CONFIG_EDIT_POPUP_LIMITED_ACTIVE'));
							}
						}, this)).then(BX.proxy(function(data){
							this.checked = false;
						}, this));
					}
				}
			);
			BX.bind(
				BX('imol_quick_answer_manage'),
				'click',
				function (e) {
					e.preventDefault();
					BX.OpenLinesConfigEdit.openQuickAnswers(this);
				}
			);
			BX.bind(
				BX('imol_quick_answer_manage_not_can_use'),
				'click',
				function (e) {
					e.preventDefault();
					BX.imolTrialHandler.openPopup(BX.message('IMOL_CONFIG_EDIT_LIMIT_INFO_HELPER_QUICK_ANSWERS'));
				}
			);
			BX.bind(
				BX('imol_delete_openline'),
				'click',
				BX.proxy(this.showPopupDeleteConfirm, this)
			);
			BX.bind(
				BX('imol_form_welcome_new_form'),
				'click',
				BX.proxy(this.addNewForm, this)
			);
			BX.bind(
				BX('imol_form_welcome'),
				'change',
				BX.OpenLinesConfigEdit.toggleWelcomeFormBlock
			);
			BX.bind(
				BX('imol_form_welcome_delay'),
				'change',
				BX.OpenLinesConfigEdit.toggleWelcomeFormDelayHint
			);
			BX.bindDelegate(
				document.body,
				'click',
				{className: 'ui-sidepanel-menu-link'},
				BX.proxy(this.reloadHandler, this)
			);
		},
		onLoad: function ()
		{
			BX.OpenLinesConfigEdit.toggleNoAnswerRule();
			BX.OpenLinesConfigEdit.actionClose();
			BX.OpenLinesConfigEdit.actionAutoClose();
			BX.OpenLinesConfigEdit.toggleWorktimeDayoffRule();
		}
	};

	window.BX.imolTrialHandler = {
		openPopup : function(dialogId)
		{
			if (
				typeof(BX) != 'undefined' &&
				typeof(BX.UI) != 'undefined' &&
				typeof(BX.UI.InfoHelper) != 'undefined' &&
				dialogId
			)
			{
				BX.UI.InfoHelper.show(dialogId);
			}
			else
			{
				alert(BX.message('IMOL_CONFIG_EDIT_POPUP_LIMITED_TITLE_DEFAULT'));
			}
		},
		openPopupQueueAll : function ()
		{
			BX.imolTrialHandler.openPopup(BX.message('IMOL_CONFIG_EDIT_LIMIT_INFO_HELPER_MESSAGE_TO_ALL'));
		},

		openPopupQueueVote : function ()
		{
			BX.imolTrialHandler.openPopup(BX.message('IMOL_CONFIG_EDIT_LIMIT_INFO_HELPER_CUSTOMER_RATE'));
		},

		openPopupWorkTime : function ()
		{
			BX.imolTrialHandler.openPopup(BX.message('IMOL_CONFIG_EDIT_LIMIT_INFO_HELPER_WORKHOUR_SETTING'));
		},

		init : function ()
		{
			BX.bind(
				BX('imol_queue_all'),
				'click',
				BX.imolTrialHandler.openPopupQueueAll
			);
		}

	};

	window.BX.animationHandler = {
		animations: [],
		animate: function(params) //creates animation
		{
			params = params || {};
			var node = params.node || null;
			var p = new BX.Promise();
			params.transition = params.transition || BX.easing.transitions.linear;

			if(!BX.type.isElementNode(node))
			{
				p.reject();
				return p;
			}

			var duration = params.duration || 300;

			// add or get animation
			var anim = null;
			for(var k in BX.animationHandler.animations)
			{
				if(BX.animationHandler.animations[k].node == node)
				{
					anim = BX.animationHandler.animations[k];
					break;
				}
			}

			if(anim === null)
			{
				var easing = new BX.easing({
					duration : duration,
					start: params.start,
					finish: params.finish,
					transition: params.transition,
					step : params.step,
					complete: function()
					{
						// cleanup animation
						for(var k in BX.animationHandler.animations)
						{
							if(BX.animationHandler.animations[k].node == node)
							{
								BX.animationHandler.animations[k].easing = null;
								BX.animationHandler.animations[k].node = null;

								BX.animationHandler.animations.splice(k, 1);

								break;
							}
						}

						node = null;
						anim = null;

						params.complete.call(this);

						if(p)
						{
							p.fulfill();
						}
					}
				});
				anim = {node: node, easing: easing};
				anim.easing.animate();

				BX.animationHandler.animations.push(anim);
			}
			else
			{
				anim.easing.stop(true);
				params.duplicate.call(this);

				if(p)
				{
					p.reject();
				}
			}

			return p;
		},
		animateShowHide: function(params) //node toggle event handler method
		{
			params = params || {};
			var node = params.node || null;
			params.transition = params.transition || BX.easing.transitions.linear;

			if(!BX.type.isElementNode(node))
			{
				var p = new BX.Promise();
				p.reject();
				return p;
			}

			var invisible = BX.hasClass(node, 'invisible');
			var way = (typeof params.way == 'undefined' || params.way === null) ? invisible : !!params.way;

			if(invisible != way)
			{
				var p = new BX.Promise();
				p.resolve();
				return p;
			}

			var toShow = params.toShow || {};
			var toHide = params.toHide || {};

			return BX.animationHandler.animate({
				node: node,
				duration: params.duration,
				start: !way ? toShow : toHide,
				finish: way ? toShow : toHide,
				transition: params.transition,
				complete: function(){
					BX[!way ? 'addClass' : 'removeClass'](node, 'invisible');
					node.style.cssText = '';

					if(BX.type.isFunction(params.complete))
					{
						params.complete.call(this);
					}
				},
				duplicate: function(){
					BX[way ? 'addClass' : 'removeClass'](node, 'invisible');
					node.style.cssText = '';
					if(BX.type.isFunction(params.duplicate))
					{
						params.duplicate.call(this);
					}
				},
				step: function(state){

					if(typeof state.opacity != 'undefined')
					{
						node.style.opacity = state.opacity/100;
					}
					if(typeof state.height != 'undefined')
					{
						node.style.height = state.height+'px';
					}
					if(typeof state.width != 'undefined')
					{
						node.style.width = state.width+'px';
					}
				}
			});
		},
		fadeSlideToggleByClass: function(node, way, duration, onComplete) //node toggle event handler call with params
		{
			return BX.animationHandler.animateShowHide({
				node: node,
				duration: duration,
				toShow: {opacity: 100, height: BX.animationHandler.getInvisibleSize(node).height},
				toHide: {opacity: 0, height: 0},
				complete: onComplete,
				way: way //false - addClass, true - removeClass
			});
		},
		getInvisibleSize: function(node) //automatically calculates node height
		{
			var invisible = BX.hasClass(node, 'invisible');

			if(invisible)
			{
				BX.removeClass(node, 'invisible');
			}
			var p = BX.pos(node);
			if(invisible)
			{
				BX.addClass(node, 'invisible');
			}

			return p;
		},
		smoothScroll: function (node) {
			var posFrom = BX.GetWindowScrollPos().scrollTop,
				posTo = BX.pos(node).top - Math.round(BX.GetWindowInnerSize().innerHeight / 2),
				toBottom = posFrom < posTo,
				distance = Math.abs(posTo - posFrom),
				speed = Math.round(distance / 100) > 20 ? 20 : Math.round(distance / 100),
				step = 4 * speed,
				posCurrent = toBottom ? posFrom + step : posFrom - step,
				timer = 0;

			if (toBottom)
			{
				for (var i = posFrom; i < posTo; i += step)
				{
					setTimeout("window.scrollTo(0," + posCurrent +")", timer * speed);
					posCurrent += step;
					if (posCurrent > posTo)
					{
						posCurrent = posTo;
					}
					timer++;
				}
			}
			else
			{
				for (var i = posFrom; i > posTo; i -= step)
				{
					setTimeout("window.scrollTo(0," + posCurrent +")", timer * speed);
					posCurrent -= step;
					if (posCurrent < posTo)
					{
						posCurrent = posTo;
					}
					timer++;
				}
			}

		},
		smoothShowHide: function (node)
		{
			if (!node.classList.contains('imopenlines-page-show')) {
				setTimeout(function () {
					BX.removeClass(node, 'invisible');
				}, 100);
				BX.animationHandler.smoothShow(node);
			}
			else
			{
				BX.animationHandler.smoothHide(node);
				BX.addClass(node, 'invisible');
			}
		},
		smoothShow: function (node)
		{
			BX.removeClass(node, 'imopenlines-page-hide');
			BX.addClass(node, 'imopenlines-page-show');
		},
		smoothHide: function (node)
		{
			BX.removeClass(node, 'imopenlines-page-show');
			BX.addClass(node, 'imopenlines-page-hide');
		},
	};

	var Queue = function(params)
	{
		if(!params.queue)
		{
			params.queue = {};
		}

		this.id = params.queue['lineId'];
		this.nodes = {};
		this.defaultOperator = params.defaultOperator || {};
		this.nodesType = {
			destInput : '',
			userInput : '',
			userInputBox : '',
			userInputContainer : '',
			userInputButton : '',
			defaultUserInput: '',
		};

		this.popupDepartment = false;

		this.params = {
			'queueItems' : params.queue.queueItems,
			'readOnly' : params.queue.readOnly,
			'queueInputName' : params.queue.queueInputName,
			'queueUserInputName' : params.queue.queueUserInputName,
			'blockIdQueueInput' : params.queue.blockIdQueueInput,
			'queueUsers' : params.queue.queueUsers,
			'popupDepartment' : params.queue.popupDepartment,
			queueUsersFields: (!!params.queue['queueUsersFields'] ? BX.clone(params.queue['queueUsersFields']) : {}),
		};
	};
	Queue.prototype =
	{
		setInput : function(node)
		{
			node = BX(node);
			if (!!node && !node.hasAttribute("bx-destination-id"))
			{
				var locked = false;
				if(this.params.readOnly)
				{
					locked = true;
				}

				this.selector = new BX.UI.EntitySelector.TagSelector({
					id: node.id,
					readonly: locked,

					dialogOptions: {
						id: node.id,

						context: 'IMOL_QUEUE_USERS',
						selectedItems: this.params.queueItems,
						events: {
							'Item:onSelect': BX.proxy(function(event) {
								this.addItemQueue(event);
							}, this),
							'Item:onDeselect': BX.proxy(function(event) {
								this.deleteItemQueue(event);
							}, this),
							onShow: BX.proxy(function(event) {
								this.nodes[this.nodesType.userInputButton].style.visibility = 'hidden';
								event.target.tagSelector.hideAddButton();
							}, this),
							onHide: BX.proxy(function(event) {
								this.nodes[this.nodesType.userInputButton].style.visibility = 'visible';
								event.target.tagSelector.showAddButton();
							}, this),
						},
						entities: [
							{
								id: 'user',
								options: {
									inviteEmployeeLink: false,
									intranetUsersOnly: true,
								}
							},
							{
								id: 'department',
								options: {
									inviteEmployeeLink: false,
									selectMode: 'usersAndDepartments',
								}
							},
						],
					}
				});
				this.selector.renderTo(document.getElementById(node.id));
				this.reloadInputConfigQueue(this.selector.getDialog());
				BX.addCustomEvent("ItemUser:onDeselect", BX.delegate(function (id) {
					this.selector.getDialog().getItem({id: id, entityId: "user"}).deselect();
				}, this));

				this.nodes[node.id] = node;
				this.nodesType.destInput = node.id;
			}
		},
		setUserInput : function(node)
		{
			node = BX(node);
			if (!!node && !node.hasAttribute("bx-destination-id"))
			{
				var id = 'data-destination' + ('' + new Date().getTime()).substr(6), res;
				node.setAttribute('bx-destination-id', id);

				this.nodesType.userInput = id;
				this.nodesType.userInputBox = id + '-input-box';
				this.nodesType.userInputContainer = id + '-container';
				this.nodesType.userInputButton = id + '-add-button';

				node.appendChild(
					BX.create(
						"SPAN",
						{
							props : {
								id: this.nodesType.userInputContainer
							}
						}
					)
				);
				node.appendChild(
					BX.create(
						"SPAN",
						{
							props : {
								className : "bx-destination-input-box",
								id : this.nodesType.userInputBox
							},
							html : [
								'<input type="text" value="" class="bx-destination-input" id="' + id + '-input">'
							]
						}
					)
				);
				if(!this.params.readOnly)
				{
					node.appendChild(
						BX.create(
							"span",
							{
								props : {
									className : "ui-tag-selector-item ui-tag-selector-add-button"
								},
								children: [
									BX.create(
										"a",
										{
											props : {
												href: '#' + this.params.blockIdQueueInput,
												className : "ui-tag-selector-add-button-caption",
												id : this.nodesType.userInputButton
											},
											html : [
												BX.message("LM_ADD")
											]
										}
									)
								]
							}
						)
					);
				}

				this.nodes[this.nodesType.userInput] = node;
				this.nodes[this.nodesType.userInputBox] = BX(this.nodesType.userInputBox);
				this.nodes[this.nodesType.userInputContainer] = BX(this.nodesType.userInputContainer);
				this.nodes[this.nodesType.userInputButton] = BX(this.nodesType.userInputButton);

				if(typeof this.selector !== "undefined")
				{
					BX.bind(this.nodes[this.nodesType.userInputButton], 'click', BX.proxy(function(e){
						this.selector.dialog.show();
					}, this));
				}

				if(!this.params.readOnly)
				{
					BX.addCustomEvent(node, 'selectUserInput', BX.proxy(this.selectUserInput, this));
				}

				var previousNode;
				this.params.queueUsers.forEach(BX.proxy(function (entity)
				{
					var queueItem = (!!this.params.queueUsersFields[entity.entityId] ? this.params.queueUsersFields[entity.entityId] : {});
					previousNode = this.selectUserInputNodes(entity, queueItem, previousNode);
				}, this));
			}
		},

		selectUserInput : function (item, el)
		{
			if(!BX.findChild(this.nodes[this.nodesType.userInputContainer], { attr : { 'data-id' : item.entityId }}, false, false))
			{
				var elements = BX.findChildren(el, {className: 'imopenlines-form-settings-user-input'}, true);
				if (elements !== null)
				{
					var name;
					for (var j = 0; j < elements.length; j++)
					{
						name = this.params.queueUserInputName+''+ '[' + item.entityId + '][' + elements[j].name + ']';
						elements[j].setAttribute('name', name);
					}
				}

				var avatarNode = BX.findChild(el, { attr : { 'id' : 'button-avatar-user-' + item.entityId }}, true);
				var avatarInput = BX.findChild(el, { attr : { 'id' : 'input-avatar-user-' + item.entityId }}, true);
				var avatarFileIdInput = BX.findChild(el, { attr : { 'id' : 'input-avatar-file-id-user-' + item.entityId }}, true);

				if (!!avatarNode && !!avatarInput)
				{
					var avatarClickHandler = function() {
						this.currentAvatarNode = avatarNode;
						this.currentAvatarInputNode = avatarInput;
						this.currentAvatarFileIdInputNode = avatarFileIdInput;
						this.showAvatarPopup();
					};
					BX.bind(
						avatarNode,
						'click',
						BX.delegate(avatarClickHandler, this)
					);
				}

				this.nodes[this.nodesType.userInputContainer].appendChild(el);
			}
		},

		setDefaultUserInput : function(node)
		{
			node = BX(node);
			if (!!node)
			{
				var el = this.createDefaultUserInputNode(this.defaultOperator);
				var avatarNode = BX.findChild(el, { attr : { 'id' : 'button-avatar-user-default-user'}}, true);
				var avatarInput = BX.findChild(el, { attr : { 'id' : 'input-avatar-user-default-user' }}, true);
				var avatarFileIdInput = BX.findChild(el, { attr : { 'id' : 'input-avatar-file-id-user-default-user'}}, true);
				if (!!avatarNode && !!avatarInput)
				{
					var avatarClickHandler = function() {
						this.currentAvatarNode = avatarNode;
						this.currentAvatarInputNode = avatarInput;
						this.currentAvatarFileIdInputNode = avatarFileIdInput;
						this.showAvatarPopup();
					};
					BX.bind(
						avatarNode,
						'click',
						BX.delegate(avatarClickHandler, this)
					);
				}
				node.appendChild(el);
			}
		},
		addItemQueue : function(event)
		{
			this.showPopupDepartment(event.getData().item);
			this.reloadInputConfigQueue(event.getData().item.getDialog());
			this.reloadUserInputQueue(event.getData().item.getDialog());
		},
		deleteItemQueue : function(event)
		{
			this.reloadInputConfigQueue(event.getData().item.getDialog());
			this.reloadUserInputQueue(event.getData().item.getDialog());
		},
		reloadInputConfigQueue : function(dialog)
		{
			var items = dialog.getSelectedItems();
			BX.cleanNode(BX(this.params.blockIdQueueInput));
			items.forEach(BX.delegate(function (entity, index) {
					BX(this.params.blockIdQueueInput).appendChild(BX.create('input', {
						props: {
							name: this.params.queueInputName + '[' + index + '][id]',
							value: entity.id,
						},
						attrs: {
							type: 'hidden'
						}
					}));
					BX(this.params.blockIdQueueInput).appendChild(BX.create('input', {
						props: {
							name: this.params.queueInputName + '[' +index + '][type]',
							value: entity.entityId,
						},
						attrs: {
							type: 'hidden'
						}
					}));
				},
				this));
		},
		reloadUserInputQueue : function(dialog)
		{
			var items = dialog.getSelectedItems();

			var queue = [];
			items.forEach(function (entity) {
				queue.push({type : entity.entityId, id : entity.id})
			});

			if(queue.length>0)
			{
				this.sendActionRequest('getUsersQueue', {'queue': queue}, BX.proxy(this.setResultRequestQueueUsers, this));
			}
			else
			{
				this.setResultRequestQueueUsers({data : queue});
			}


		},
		showPopupDepartment : function (item)
		{
			var entity = {
				'id' : item.getId(),
				'entityId' : item.getEntityId()
			};
			if(
				this.params.popupDepartment.valueDisables !== true &&
				item.getEntityId() === 'department' &&
				!!this.selector
			)
			{
				if(!!this.popupDepartment)
				{
					this.popupDepartment.close();
					this.popupDepartment.destroy();
				}

				var container = this.selector.getTag(entity).getContainer();
				if(container)
				{
					var content = [
						BX.create('DIV',
							{
								html: BX.message('LM_HEAD_DEPARTMENT_EXCLUDED_QUEUE'),
								style: {margin: '10px 20px 10px 5px'}
							}
						),
						BX.create('DIV',
							{
								style: {margin: '10px 20px 10px 5px'},
								children: [
									BX.create("A",
										{
											props: {href: 'javascript:void(0)'},
											text: this.params.popupDepartment.titleOption,
											events: {'click': BX.proxy(function(){
													this.setDisablesPopupDepartment();
												}, this)
											}
										}
									)
								]
							}
						)
					];

					this.popupDepartment = BX.PopupWindowManager.create('popup-department', container, {
						content:  BX.create('DIV', {attrs: {className: 'imopenlines-hint-popup-contents'}, children: content}),
						zIndex: 100,
						closeIcon: {
							opacity: 1
						},
						closeByEsc: true,
						darkMode: false,
						autoHide: true,
						angle: true,
						offsetLeft: 20,
						offsetTop: 10,
						events: {
							onPopupClose: BX.proxy(function() {
								this.popupDepartment.destroy();
							}, this)
						}
					});

					this.popupDepartment.show();
				}
			}
		},
		setDisablesPopupDepartment : function ()
		{
			if(!!this.popupDepartment)
			{
				BX.userOptions.save(
					this.params.popupDepartment.nameOption.category,
					this.params.popupDepartment.nameOption.name,
					this.params.popupDepartment.nameOption.nameValue,
					'N',
					false
				);
				this.params.popupDepartment.valueDisables = true;
				this.popupDepartment.close();
				this.popupDepartment.destroy();
			}
		},
		setResultRequestQueueUsers : function(data)
		{
			this.params.queueUsers = data.data;
			var previousNode;
			var nodesChildUserInput = BX.findChild(this.nodes[this.nodesType.userInputContainer], {}, false, true);

			var nodesUserInput = {};
			if(nodesChildUserInput)
			{
				nodesChildUserInput.forEach(BX.proxy(function (child)
				{
					nodesUserInput[child.getAttribute('data-id')] = child;
				}, this));
			}

			this.params.queueUsers.forEach(BX.proxy(function (entity)
			{
				delete nodesUserInput[entity.entityId];

				var queueItem = (!!this.params.queueUsersFields[entity.entityId] ? this.params.queueUsersFields[entity.entityId] : {});
				previousNode = this.selectUserInputNodes(entity, queueItem, previousNode);
			}, this));

			if(nodesUserInput)
			{
				for (var userId in nodesUserInput)
				{
					delete this.params.queueUsersFields[userId];
					BX.Dom.remove(nodesUserInput[userId]);
				}
			}
		},

		showAvatarPopup : function ()
		{
			if (!this.avatarPopup)
			{
				var contentNode = BX('imol_user_data_avatar_upload');
				this.avatarEditor = new ImolAvatarEditor({
					'caller': this,
					'context': contentNode
				});
				BX.addCustomEvent(this.avatarEditor, 'onClose', BX.delegate(this.showAvatarPopup, this));
				BX.addCustomEvent(this.avatarEditor, 'onSelect', BX.delegate(this.onSelectAvatar, this));

				this.avatarPopup = BX.PopupWindowManager.create(
					'imol_user_edit_avatar',
					null,
					{
						autoHide: true,
						lightShadow: true,
						overlay: true,
						closeIcon: true,
						closeByEsc: true,
						angle: true,
						content: contentNode
					}
				);
			}

			this.avatarEditor.markCurrentAvatar(this.currentAvatarInputNode.value);
			this.avatarPopup.setBindElement(this.currentAvatarNode);
			this.avatarPopup.show();
		},
		onSelectAvatar: function (path, fileId)
		{
			if (path)
			{
				this.currentAvatarNode.style['background-image'] = "url('" + encodeURI(path) + "')";
				this.currentAvatarInputNode.value = path;

				this.currentAvatarFileIdInputNode.value = fileId;
			}

			this.avatarPopup.close();
		},
		createUserInputNode : function(params, queueItem)
		{
			params.entityId = BX.util.htmlspecialchars(params.entityId);
			params.department = BX.util.htmlspecialchars(params.department);
			params.name = BX.util.htmlspecialchars(params.name);
			params.avatar = BX.util.htmlspecialchars(params.avatar);
			queueItem.USER_AVATAR = BX.util.htmlspecialchars(queueItem.USER_AVATAR);
			queueItem.USER_NAME = BX.util.htmlspecialchars(queueItem.USER_NAME);
			queueItem.USER_WORK_POSITION = BX.util.htmlspecialchars(queueItem.USER_WORK_POSITION);
			queueItem.USER_AVATAR_ID = BX.util.htmlspecialchars(queueItem.USER_AVATAR_ID);
			var userName = (!!queueItem.USER_NAME ? queueItem.USER_NAME : params.name),
				userWorkPosition = (!!queueItem.USER_WORK_POSITION ? queueItem.USER_WORK_POSITION : ''),
				userAvatar = (!!queueItem.USER_AVATAR ? queueItem.USER_AVATAR : ''),
				userAvatarShow = (!!queueItem.USER_AVATAR ? queueItem.USER_AVATAR : params.avatar),
				userAvatarFileId = (!!queueItem.USER_AVATAR_ID ? queueItem.USER_AVATAR_ID : null);

			if(!!params.avatar)
			{
				params.avatar = ' style="background-image: url(' + encodeURI(params.avatar) + ')"';
			}
			if(!!userAvatarShow)
			{
				userAvatarShow = ' style="background-image: url(' + encodeURI(userAvatarShow) + ')"';
			}

			var userInputTemplate = BX('user_data_input_template').innerHTML;
			var innerHtml = userInputTemplate
				.replace(new RegExp('%data_id%', 'g'), params.entityId)
				.replace(new RegExp('%data_department%', 'g'), params.department)
				.replace(new RegExp('%user_name_default%', 'g'), params.name)
				.replace(new RegExp('%user_avatar_default%', 'g'), params.avatar)
				.replace(new RegExp('%user_name%', 'g'), userName)
				.replace(new RegExp('%user_work_position%', 'g'), userWorkPosition)
				.replace(new RegExp('%user_avatar%', 'g'), userAvatar)
				.replace(new RegExp('%user_avatar_show%', 'g'), userAvatarShow)
				.replace(new RegExp('%user_avatar_file_id%', 'g'), userAvatarFileId)
				.replace(new RegExp('%user_data_input_container_class%', 'g'), 'imopenlines-form-settings-user')
				.replace(new RegExp('background-image: url\\(\\)', 'g'), '');

			var el = BX.create('DIV');
			el.innerHTML = innerHtml;
			el = el.children[0];

			if(
				(
					!params.department ||
					params.department == 0
				)
				&& !this.params.readOnly
			)
			{
				el.appendChild(this.createBottomDeleteUser(params.entityId));
			}

			return el;
		},
		modificationUserInputNode : function(params, el)
		{
			params.entityId = BX.util.htmlspecialchars(params.entityId);
			params.department = BX.util.htmlspecialchars(params.department);
			el.setAttribute('data-department', params.department);

			var bottomDeleteUser = BX.findChildByClassName(el, 'imopenlines-form-settings-user-delete');

			if(
				(
					!params.department ||
					params.department == 0
				)
				&& !this.params.readOnly
			)
			{
				if(!bottomDeleteUser)
				{
					el.appendChild(this.createBottomDeleteUser(params.entityId));
				}
			}
			else
			{
				if(bottomDeleteUser)
				{
					BX.Dom.remove(bottomDeleteUser);
				}
			}

			return el;
		},
		createBottomDeleteUser : function(entityId)
		{
			var bottom = BX.create("span", {
				props : {
					'className' : "imopenlines-form-settings-user-delete"
				},
				events : {
					'click' : BX.delegate(
						function(e){
							BX.onCustomEvent('ItemUser:onDeselect', [entityId]);
						},
						this
					),
					'mouseover' : function(){
						BX.addClass(this.parentNode, 'bx-destination-hover');
					},
					'mouseout' : function(){
						BX.removeClass(this.parentNode, 'bx-destination-hover');
					}
				}
			});

			return bottom;
		},
		createDefaultUserInputNode : function(item)
		{
			item.AVATAR = BX.util.htmlspecialchars(item.AVATAR);
			var userName = (!!item.NAME ? BX.util.htmlspecialchars(item.NAME) : ''),
				userAvatar = (!!item.AVATAR ? item.AVATAR : ''),
				userAvatarShow = (!!item.AVATAR ? ' style="background-image: url(' + encodeURI(item.AVATAR) + ')' : ''),
				userAvatarFileId = (!!item.AVATAR_ID ? BX.util.htmlspecialchars(item.AVATAR_ID) : null);
			var userInputTemplate = BX('default_user_data_input_template').innerHTML;
			var innerHtml = userInputTemplate
				.replace(new RegExp('%user_name%', 'g'), userName)
				.replace(new RegExp('%user_avatar%', 'g'), userAvatar)
				.replace(new RegExp('%user_avatar_show%', 'g'), userAvatarShow)
				.replace(new RegExp('%user_avatar_file_id%', 'g'), userAvatarFileId)
				.replace(new RegExp('background-image: url\\(\\)', 'g'), '');
			var el = BX.create('DIV');
			el.innerHTML = innerHtml;
			el = el.children[0];

			return el;
		},
		selectUserInputNodes : function(params, queueItem, previousNode)
		{
			var currentNode = BX.findChild(this.nodes[this.nodesType.userInputContainer], { attr : { 'data-id' : params.entityId }}, false, false)
			if(!currentNode)
			{
				var el = this.createUserInputNode(params, queueItem);
				BX.onCustomEvent(this.nodes[this.nodesType.userInput], 'selectUserInput', [params, el]);
			}
			else
			{
				var el = this.modificationUserInputNode(params, currentNode);
			}

			if(!previousNode)
			{
				BX.prepend(el, BX(this.nodes[this.nodesType.userInputContainer]));
			}
			else
			{
				BX.insertAfter(el, previousNode)
			}

			return el;
		},
		sendActionRequest: function (action, sendData, callbackSuccess, callbackFailure)
		{
			callbackSuccess = callbackSuccess || null;
			callbackFailure = callbackFailure || null;/*BX.proxy(this.showErrorPopup, this)*/
			sendData = sendData || {};

			if (sendData instanceof FormData)
			{
				sendData.append('action', action);
				sendData.append('configId', this.id);
			}
			else
			{
				sendData.action = action;
				sendData.configId = this.id;
			}
			BX.ajax.runComponentAction('bitrix:imopenlines.lines.edit', action, {
				mode: 'ajax',
				data: sendData
			}).then(BX.proxy(function(data){

				data = data || {};
				if(data.error)
				{
					callbackFailure.apply(this, [data]);
				}
				else if(callbackSuccess)
				{
					callbackSuccess.apply(this, [data]);
				}
			}, this)).then(BX.proxy(function(data){
				var applyData = {'error': true, 'text': data.error};
				callbackFailure.apply(this, [applyData]);
			}, this));
		}
	};

	var ImolAvatarEditor = function(params)
	{
		this.caller = params.caller;
		this.context = params.context;

		this.init();
	};
	ImolAvatarEditor.prototype =
	{
		init: function()
		{
			this.editButtonNode = this.context.querySelector('[data-imopenlines-user-photo-edit]');
			BX.bind(this.editButtonNode, 'click', BX.delegate(this.show, this));
			this.avatarContainer = this.context.querySelector('[data-imopenlines-user-photo-edit-avatars]');
			this.attributeCarouselNext = 'dataimopenlines-user-photo-edit-avatar-next';
			this.attributeCarouselPrev = 'data-imopenlines-user-photo-edit-avatar-prev';
			this.attributeAvatar = 'data-imopenlines-user-photo-edit-avatar-item';
			this.attributeAvatarRemove = 'data-remove';
			this.attributeFileId = 'data-file-id';
			this.attributePath = 'data-path';
			this.attributeView = 'data-view';

			this.getAllAvatarNodes().forEach(this.initAvatar, this);
			//this.initCarousel();
		},

		createAvatarNode: function(fileId, path)
		{
			var avatarTemplate = BX('user_data_avatar_template');
			avatarTemplate = avatarTemplate.innerHTML;
			fileId = BX.util.htmlspecialchars(fileId);
			path = BX.util.htmlspecialchars(path);
			avatarTemplate = avatarTemplate
				.replace('%file_id%', fileId)
				.replace('%path%', path)
				.replace('%url_path%', encodeURI(path));

			var node = BX.create('DIV');
			node.innerHTML = avatarTemplate;
			node = node.children[0];

			return node;
		},

		initAvatar: function(node)
		{
			var nodeView = node.querySelector('[' + this.attributeView + ']');
			nodeView = nodeView || node;
			BX.bind(nodeView, 'click', BX.delegate(function () {
				this.selectAvatar(node);
			}, this));

			var fileId = node.getAttribute(this.attributeFileId);
			if (fileId)
			{
				var nodeRemove = node.querySelector('[' + this.attributeAvatarRemove + ']');
				BX.bind(nodeRemove, 'click', BX.delegate(function () {
					this.removeFile(fileId, node);
				}, this));
			}
		},

		selectAvatar: function (node)
		{
			var path = node.getAttribute(this.attributePath);
			var fileId = node.getAttribute(this.attributeFileId);
			BX.onCustomEvent(this, 'onSelect', [path, fileId]);
		},

		initCarousel: function ()
		{
			if (!this.carouselNextNode)
			{
				this.carouselNextNode = this.context.querySelector('[' + this.attributeCarouselNext + ']');
				this.carouselPrevNode = this.context.querySelector('[' + this.attributeCarouselPrev + ']');
				BX.bind(this.carouselNextNode, 'click', BX.delegate(function () {
					this.turnCarousel('next');
				}, this));
				BX.bind(this.carouselPrevNode, 'click', BX.delegate(function () {
					this.turnCarousel('prev');
				}, this));
			}

			var nodes = this.getUserAvatarNodes();
			var nodeWidth = Math.round(100 / nodes.length);
			nodes.forEach(function (node) {
				node.style.width = nodeWidth + '%';
			}, this);
			this.avatarContainer.style.width = 'calc(66px * ' + nodes.length + ')';
			this.turnCarousel('start');
		},

		turnCarousel: function (pos)
		{
			var nodes = this.getUserAvatarNodes();
			var val;
			var step = 66;
			var maxLeft = -step * (nodes.length - 3);
			var oldLeft = this.avatarContainer.style.left.toString();
			oldLeft = parseInt(oldLeft.replace('px', ''));
			if (isNaN(oldLeft)) oldLeft = 0;

			switch (pos)
			{
				case 'prev':
					val = oldLeft + step;
					break;
				case 'start':
					val = 0;
					break;
				case 'end':
					val = 100000;
					break;
				case 'next':
				default:
					val = oldLeft - step;
					break;
			}

			if (val >= 0)
			{
				val = 0;
			}
			else if (val < maxLeft)
			{
				val = maxLeft;
			}

			this.carouselPrevNode.style.display = (val == 0 || nodes.length <= 3) ? 'none' : '';
			this.carouselNextNode.style.display = (val == maxLeft || nodes.length <= 3) ? 'none' : '';

			this.avatarContainer.style.left = val + (val == 0 ? '' : 'px');
		},

		markCurrentAvatar: function (path)
		{
			var classCurrent = 'selected';
			this.getAllAvatarNodes().forEach(function (node) {
				var nodePath = node.getAttribute(this.attributePath);
				if (nodePath == path)
				{
					BX.addClass(node, classCurrent);
				}
				else
				{
					BX.removeClass(node, classCurrent);
				}
			}, this);

			this.getUserAvatarNodes().forEach(function (node, index) {
				if (index > 2 && BX.hasClass(node, classCurrent))
				{
					this.avatarContainer.insertBefore(
						node,
						this.avatarContainer.children[0]
					);
				}
			}, this);
		},

		getUserAvatarNodeByFileId: function (fileId)
		{
			return this.avatarContainer.querySelector('[' + this.attributeFileId + '="' + fileId + '"]');
		},

		getUserAvatarNodes: function ()
		{
			return BX.convert.nodeListToArray(
				this.avatarContainer.querySelectorAll('[' + this.attributeAvatar + ']')
			);
		},

		getAllAvatarNodes: function ()
		{
			return BX.convert.nodeListToArray(
				this.context.querySelectorAll('[' + this.attributeAvatar + ']')
			);
		},

		showLoader: function()
		{
			BX.addClass(this.editButtonNode, 'loader');
		},

		hideLoader: function()
		{
			BX.removeClass(this.editButtonNode, 'loader');
		},

		onFileAdded: function(data)
		{
			if (!data.error && !!data.data.path)
			{
				var node = this.createAvatarNode(data.data.fileId, data.data.path);
				if (this.avatarContainer.children.length > 0)
				{
					this.avatarContainer.insertBefore(node, this.avatarContainer.children[0]);
				}
				else
				{
					this.avatarContainer.appendChild(node);
				}

				this.initAvatar(node);
				this.selectAvatar(node);
				this.hideLoader();
			}
		},

		onFileRemoved: function(data)
		{
			if (data.data.fileId && data.data.fileId > 0)
			{
				var node = this.getUserAvatarNodeByFileId(data.data.fileId);
			}

			if (data.error)
			{
				//this.caller.showErrorPopup(data);
				if (node)
				{
					node.style.display = '';
				}
			}
			else if(node)
			{
				BX.remove(node);
			}

			this.hideLoader();
		},

		addFile: function(blob)
		{
			if (!blob || blob.size <= 0)
			{
				return;
			}

			this.showLoader();
			var fd = new FormData();
			fd.append('avatarFile', blob);
			this.caller.sendActionRequest(
				'addAvatarFile',
				fd,
				BX.proxy(this.onFileAdded, this),
				BX.proxy(function (data) {
					data = data || {error: true};
					this.onFileAdded(data);
				}, this)
			);

		},

		removeFile: function(fileId, node)
		{
			this.showLoader();
			node.style.display = 'none';
			this.caller.sendActionRequest('removeAvatarFile', {'fileId': fileId}, BX.proxy(this.onFileRemoved, this));
		},

		show: function()
		{
			if (!this.editor)
			{
				this.initEditor();
			}

			this.editor.show();
			BX.addCustomEvent(this.editor.popup, "onPopupClose",BX.proxy(this.onEditorPopupClose, this));
		},

		onEditorPopupClose: function()
		{
			BX.onCustomEvent(this, 'onClose', []);
			BX.removeCustomEvent(this.editor.popup, "onPopupClose",BX.proxy(this.onEditorPopupClose, this));
		},

		initEditor: function()
		{
			this.editor = new BX.AvatarEditor();
			BX.addCustomEvent(this.editor, "onApply", BX.delegate(function (blob) {
				if (!blob)
				{
					return;
				}

				this.addFile(blob);
			}, this));
		}
	};

	BX.ImolKpiTimeMenu = function(params)
	{
		this.element = params.element;
		this.bindElement = document.getElementById(params.bindElement);
		this.inputElement = document.getElementById(params.inputElement);
		this.items = this.prepareItems(params.items);
		this.fullBlockName = params.fullBlockName;
		this.customInputId = params.customInputId;

		this.init();
	};

	BX.ImolKpiTimeMenu.prototype =
	{
		init: function ()
		  {
			  var params = {
				  maxHeight: 600,
				  minWidth: this.bindElement.offsetWidth
			  };

			  this.menu = new BX.PopupMenuWindow(
				  this.element,
				  this.bindElement,
				  this.items,
				  params
			  );

			  BX.bind(this.bindElement, 'click', BX.delegate(this.show, this));
		  },

		show: function()
		  {
			  this.menu.show();
		  },

		close: function()
		   {
			   this.menu.close();
		   },

		prepareItems: function (items)
		  {
			  if (typeof items === "object")
			  {
				  items = Object.values(items)
			  }

			  var newItems = [];
			  var newItem;

			  for (var i = 0; i < items.length; i++)
			  {
				  newItem = this.prepareItem(items[i]);

				  if (newItem.delimiterBefore)
				  {
					  newItems.push({delimiter: true});
				  }

				  newItems.push(newItem);

				  if (newItem.delimiterAfter)
				  {
					  newItems.push({delimiter: true});
				  }
			  }

			  return newItems;
		  },

		prepareItem: function (item)
		 {
			 var newItem = {};
			 newItem.title = item.NAME;
			 newItem.text = item.TITLE || item.NAME;
			 newItem.delimiterAfter = item.DELIMITER_AFTER;
			 newItem.delimiterBefore = item.DELIMITER_BEFORE;

			 newItem.onclick = BX.delegate(
				 function (event) {
					 if (item.CUSTOM === 'Y')
					 {
						 var customParamWin = new BX.PopupWindow(null, event.target, {
							 offsetLeft: event.target.offsetWidth,
							 offsetTop: -event.target.offsetHeight,
							 className: 'imopenlines-lines-edit-popup',
							 autoHide: true,
							 buttons: [
								 new BX.PopupWindowButton({
									 text: BX.message('IMOL_CONFIG_EDIT_KPI_ANSWER_TIME_SET'),
									 className: 'ui-btn ui-btn-sm ui-btn-primary',
									 events: {
										 click: BX.proxy(function() {
										 	var value = BX(this.customInputId).value;
										 	 var innerHTML = value + ' ';
										 	 innerHTML += item.TYPE == 'further' ? BX.message('IMOL_CONFIG_EDIT_KPI_ANSWER_TIME_MINUTES') : BX.message('IMOL_CONFIG_EDIT_KPI_ANSWER_TIME_SECONDS');
											 this.bindElement.innerHTML = innerHTML;
										 	 this.inputElement.value = item.TYPE == 'further' ? 60 * value : value;
											 this.changeKpiBlock(value);
											 customParamWin.destroy();
											 this.close();
										 }, this)
									 }
								 })
							 ],
							 events: {
								 onPopupClose: function() { customParamWin.destroy(); }
							 },
							 contentNoPaddings: true,
							 contentColor: 'white',
							 content: BX.create('div', {
								 props: {
									 className: 'imopenlines-lines-edit-popup-content'
								 },
								 children: [
									 BX.create('div', {
										 props: {
											 className: 'ui-ctl ui-ctl-textbox ui-ctl-inline'
										 },
										 style: {
											 maxWidth: '45px'
										 },
										 children: [
											 BX.create('input', {
												 props: {
													 type: 'number',
													 className: 'ui-ctl-element imopenlines-lines-edit-popup-input-number',
													 id: this.customInputId
												 },
												 style: {
													 textAlign: 'center'
												 }
											 })
										 ]
									 }),
									 BX.create('span', {
										 props: {
											 className: 'imopenlines-lines-edit-popup-text'
										 },
										 text: item.TYPE == 'further' ? BX.message('IMOL_CONFIG_EDIT_KPI_ANSWER_TIME_MINUTES') : BX.message('IMOL_CONFIG_EDIT_KPI_ANSWER_TIME_SECONDS')
									 })
								 ]
							 })
						 });

						 customParamWin.show();
					 }
					 else
					 {
						 this.bindElement.innerHTML = item.NAME;
						 this.inputElement.value = item.VALUE;
						 this.changeKpiBlock(item.VALUE);
						 this.close();
					 }
				 },
				 this
			 );

			 return newItem;
		 },

		changeKpiBlock: function(value)
		{
			if (this.fullBlockName)
			{
				var way = value != 0;
				BX.animationHandler.fadeSlideToggleByClass(BX(this.fullBlockName), way);
			}
		},
	};

	var kpiSelector = function(params)
	{
		this.params = {
			id : params.id,
			inputName : params.inputName,
			inputId : params.inputId,
			list : params.list,
			readOnly : params.readOnly,
		};

		this.nodesType = {
			userInput : this.params.id,
			userInputContainer : this.params.inputId,
		};
		this.nodes = {};
		this.nodes[this.nodesType.userInput] = BX(this.params.id);
		this.nodes[this.nodesType.userInputContainer] = BX(this.params.inputId);
	};
	kpiSelector.prototype =
		{
			setInput: function ()
			{
				var locked = false;
				if(this.params.readOnly)
				{
					locked = true;
				}

				this.selector = new BX.UI.EntitySelector.TagSelector({
					id: this.nodesType.userInput,
					readonly: locked,

					dialogOptions: {
						id: this.nodesType.userInput,
						context: 'IMOL_KPI_USERS',

						preselectedItems: this.prepareItems(),

						events: {
							'Item:onSelect': BX.proxy(function() {
								this.reloadInputKpi();
							}, this),
							'Item:onDeselect': BX.proxy(function() {
								this.reloadInputKpi();
							}, this),
							//TODO: 280454 (b416c3108697)
							'onLoad': BX.proxy(function() {
								this.reloadInputKpi();
							}, this),
						},
						entities: [
							{
								id: 'user',
								options: {
									inviteEmployeeLink: false,
									intranetUsersOnly: true,
								}
							},
							{
								id: 'department',
								options: {
									inviteEmployeeLink: false,
									selectMode: 'usersOnly',
								}
							},
						]
					}
				});
				this.selector.renderTo(this.nodes[this.nodesType.userInput]);
			},
			prepareItems: function ()
			{
				var preselectedItems = [];

				this.params.list.forEach(function (item) {
					preselectedItems.push(['user', item])
				})

				return preselectedItems;
			},
			reloadInputKpi : function()
			{
				var items = this.selector.getDialog().getSelectedItems();
				BX.cleanNode(this.nodes[this.nodesType.userInputContainer]);
				items.forEach(BX.delegate(function (entity) {
						this.nodes[this.nodesType.userInputContainer].appendChild(BX.create('input', {
							props: {
								name: this.params.inputName,
								value: entity.id,
							},
							attrs: {
								type: 'hidden'
							}
						}));
					},
					this));
			},
		}
})(window);
