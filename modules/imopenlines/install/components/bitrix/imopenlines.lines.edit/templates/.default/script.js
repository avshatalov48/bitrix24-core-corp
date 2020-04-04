;(function(window){
	if (!!window.BX.OpenLinesConfigEdit)
		return;

	var destinationInstance = null;

	window.BX.OpenLinesConfigEdit = {
		init : function()
		{
			BX.imolTrialHandler.init();
			BX.OpenLinesConfigEdit.addEventForTooltip();
			BX.OpenLinesConfigEdit.bindEvents();
			BX.OpenLinesConfigEdit.onLoad();
		},
		initDestination : function(nodes, inputs, params)
		{
			if (destinationInstance === null)
				destinationInstance = new Destination(params);
			destinationInstance.setInput(BX(nodes.destInputNode), inputs.destInputName);
			destinationInstance.setUserDataInput(BX(nodes.userDataInputNode), inputs.userDataInputName);
			destinationInstance.setDefaultUserDataInput(BX(nodes.defaultUserDataInputNode));
		},
		sendUsersData : function()
		{
			if (destinationInstance !== null)
			{
				var selectedUsers = destinationInstance.params.selectedForMessage;
				BX.SidePanel.Instance.postMessage(window, 'ImOpenlines:reloadUsersList', selectedUsers);
			}
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

			this.changeHistory(params);
		},
		changeRatingRequest: function(ratingRequest)
		{
			var params = {
				'rating-request': ratingRequest
			};

			this.changeHistory(params);
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
		changeNoAnswerValue: function(selector)
		{
			var noAnswerInput = BX('imol_no_answer_rule_hidden');
			if (!!noAnswerInput)
			{
				if (noAnswerInput.value == 'queue' && selector.options[selector.selectedIndex].value != 'evenly')
				{
					noAnswerInput.value = 'text';
				}
			}

			if (selector.options[selector.selectedIndex].value == 'all')
			{
				BX.animationHandler.fadeSlideToggleByClass(BX('imol_limitation_max_chat_block'), false);
				BX('imol_queue_time_title').innerHTML = BX.message('IMOL_CONFIG_EDIT_NA_TIME_NEW');
			}
			else if (BX('imol_queue_time_title').innerHTML != BX.message('IMOL_CONFIG_EDIT_QUEUE_TIME'))
			{
				BX.animationHandler.fadeSlideToggleByClass(BX('imol_limitation_max_chat_block'), true);
				BX('imol_queue_time_title').innerHTML = BX.message('IMOL_CONFIG_EDIT_QUEUE_TIME');
			}
		},
		toggleCrmBlock: function()
		{
			BX.animationHandler.fadeSlideToggleByClass(BX('imol_crm_block'))
		},
		toggleCheckOnlineBlock: function()
		{
			BX.animationHandler.fadeSlideToggleByClass(BX('imol_check_online_block'))
		},
		toggleCrmSourceRule: function()
		{
			var selector = BX('imol_crm_create');
			if (selector.options[selector.selectedIndex].value != 'none')
			{
				BX.animationHandler.fadeSlideToggleByClass(BX('imol_crm_source_rule'), true);
			}
			else
			{
				BX.animationHandler.fadeSlideToggleByClass(BX('imol_crm_source_rule'), false);
			}
		},
		toggleQueueSettingsBlock: function(e)
		{
			e.preventDefault();
			BX.animationHandler.fadeSlideToggleByClass(BX('imol_queue_settings_block'));
			//BX.OpenLinesConfigEdit.toggleBoolInputValue(BX('imol_queue_settings_input'));
		},
		toggleAutoMessageBlock: function()
		{
			BX.animationHandler.fadeSlideToggleByClass(BX('imol_action_welcome'))
		},
		toggleAgreementBlock: function()
		{
			BX.animationHandler.fadeSlideToggleByClass(BX('imol_agreement_message_block'))
		},
		toggleVoteBlock: function()
		{
			BX.animationHandler.fadeSlideToggleByClass(BX('imol_vote_message_block'));

			var ratingRequest = BX(this).checked === true ? 'Y' : 'N';
			BX.OpenLinesConfigEdit.changeRatingRequest(ratingRequest);
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
		toggleWorktimeBlock: function()
		{
			BX.animationHandler.fadeSlideToggleByClass(BX('imol_worktime_block'))
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
				BX('imol_config_edit_form'),
				'submit',
				BX.OpenLinesConfigEdit.sendUsersData
			);
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
				BX('imol_check_online'),
				'change',
				BX.OpenLinesConfigEdit.toggleCheckOnlineBlock
			);
			BX.bind(
				BX('imol_welcome_message'),
				'change',
				BX.OpenLinesConfigEdit.toggleAutoMessageBlock
			);
			BX.bind(
				BX('imol_worktime_checkbox'),
				'change',
				BX.OpenLinesConfigEdit.toggleWorktimeBlock
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
			BX.bind(BX('imol_queue_type'),
				'change',
				function(e) {
					BX.OpenLinesConfigEdit.changeNoAnswerValue(this);
				}
			);
			BX.bind(
				BX('imol_limitation_max_chat'),
				'change',
				BX.OpenLinesConfigEdit.toggleQueueMaxChat
			);
			BX.bind(
				BX('imol_quick_answer_manage'),
				'click',
				function (e) {
					e.preventDefault();
					BX.OpenLinesConfigEdit.openQuickAnswers(this);
				}
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
		openPopup : function(dialogId, text)
		{
			if (typeof(B24) != 'undefined' && typeof(B24.licenseInfoPopup) != 'undefined')
			{
				B24.licenseInfoPopup.show(dialogId, BX.message('IMOL_CONFIG_EDIT_POPUP_LIMITED_TITLE'), text);
			}
			else
			{
				alert(text);
			}
		},

		openPopupQueueAll : function ()
		{
			BX.imolTrialHandler.openPopup('imol_queue_all', BX.message('IMOL_CONFIG_EDIT_POPUP_LIMITED_QUEUE_ALL_NEW'));
		},

		openPopupQueueVote : function ()
		{
			BX.imolTrialHandler.openPopup('imol_vote', BX.message('IMOL_CONFIG_EDIT_POPUP_LIMITED_VOTE'));
		},

		init : function ()
		{
			BX.bind(
				BX('imol_queue_all'),
				'click',
				BX.imolTrialHandler.openPopupQueueAll
			);
			BX.bind(
				BX('imol_vote'),
				'click',
				BX.imolTrialHandler.openPopupQueueVote
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

	var Destination = function(params, type)
	{
		this.p = (!!params.queue ? params.queue : {});
		if (!!params.queue["SELECTED"])
		{
			var res = {}, tp, j;
			for (tp in params.queue["SELECTED"])
			{
				if (params.queue["SELECTED"].hasOwnProperty(tp) && typeof params.queue["SELECTED"][tp] == "object")
				{
					for (j in params.queue["SELECTED"][tp])
					{
						if (params.queue["SELECTED"][tp].hasOwnProperty(j))
						{
							if (tp == 'USERS')
								res['U' + params.queue["SELECTED"][tp][j]] = 'users';
							else if (tp == 'SG')
								res['SG' + params.queue["SELECTED"][tp][j]] = 'sonetgroups';
							else if (tp == 'DR')
								res['DR' + params.queue["SELECTED"][tp][j]] = 'department';
						}
					}
				}
			}
			this.p["SELECTED"] = res;
		}

		this.id = this.p['LINE_ID'];
		this.nodes = {};
		this.defaultOperator = params.defaultOperator || {};
		this.nodesType = {
			destInput : [],
			userDataInput : [],
			defaultUserDataInput: ''
		};
		this.ajaxUrl = '/bitrix/components/bitrix/imopenlines.lines.edit/ajax.php';

		var makeDepartmentTree = function(id, relation)
		{
			var arRelations = {}, relId, arItems, x;
			if (relation[id])
			{
				for (x in relation[id])
				{
					if (relation[id].hasOwnProperty(x))
					{
						relId = relation[id][x];
						arItems = [];
						if (relation[relId] && relation[relId].length > 0)
							arItems = makeDepartmentTree(relId, relation);
						arRelations[relId] = {
							id: relId,
							type: 'category',
							items: arItems
						};
					}
				}
			}
			return arRelations;
		},
		buildDepartmentRelation = function(department)
		{
			var relation = {}, p;
			for(var iid in department)
			{
				if (department.hasOwnProperty(iid))
				{
					p = department[iid]['parent'];
					if (!relation[p])
						relation[p] = [];
					relation[p][relation[p].length] = iid;
				}
			}
			return makeDepartmentTree('DR0', relation);
		};
		if (true || type == 'users')
		{
			this.params = {
				'name' : null,
				'searchInput' : null,
				'extranetUser' :  (this.p['EXTRANET_USER'] == "Y"),
				'bindMainPopup' : { node : null, 'offsetTop' : '5px', 'offsetLeft': '15px'},
				'bindSearchPopup' : { node : null, 'offsetTop' : '5px', 'offsetLeft': '15px'},
				departmentSelectDisable : true,
				'callback' : {
					'select' : BX.delegate(this.select, this),
					'unSelect' : BX.delegate(this.unSelect, this),
					'openDialog' : BX.delegate(this.openDialog, this),
					'closeDialog' : BX.delegate(this.closeDialog, this),
					'openSearch' : BX.delegate(this.openDialog, this),
					'closeSearch' : BX.delegate(this.closeSearch, this)
				},
				items : {
					users : (!!this.p['USERS'] ? this.p['USERS'] : {}),
					groups : {},
					sonetgroups : {},
					department : (!!this.p['DEPARTMENT'] ? this.p['DEPARTMENT'] : {}),
					departmentRelation : (!!this.p['DEPARTMENT'] ? buildDepartmentRelation(this.p['DEPARTMENT']) : {}),
					contacts : {},
					companies : {},
					leads : {},
					deals : {}
				},
				itemsLast : {
					users : (!!this.p['LAST'] && !!this.p['LAST']['USERS'] ? this.p['LAST']['USERS'] : {}),
					sonetgroups : {},
					department : {},
					groups : {},
					contacts : {},
					companies : {},
					leads : {},
					deals : {},
					crm : []
				},
				queueItems: (!!this.p['QUEUE_USERS_FIELDS'] ? BX.clone(this.p['QUEUE_USERS_FIELDS']) : {}),
				itemsSelected : (!!this.p['SELECTED'] ? BX.clone(this.p['SELECTED']) : {}),
				isCrmFeed : false,
				destSort : (!!this.p['DEST_SORT'] ? BX.clone(this.p['DEST_SORT']) : {})
			};

			this.params.selectedForMessage = BX.clone(this.params.itemsSelected);
		}
	};
	Destination.prototype =
	{
		setInput : function(node, inputName)
		{
			node = BX(node);
			if (!!node && !node.hasAttribute("bx-destination-id"))
			{
				var id = 'destination' + ('' + new Date().getTime()).substr(6), res;
				node.setAttribute('bx-destination-id', id);
				res = new DestInput(id, node, inputName);
				this.nodes[id] = node;
				this.nodesType.destInput.push(id);
				BX.defer_proxy(function(){
					params = this.params;
					params.name = res.id;
					params.searchInput = res.nodes.input;
					params.bindMainPopup.node = res.nodes.container;
					params.bindSearchPopup.node = res.nodes.container;

					BX.SocNetLogDestination.init(params);
				}, this)();
			}
		},
		setUserDataInput : function(node, inputName)
		{
			node = BX(node);
			if (!!node && !node.hasAttribute("bx-destination-id"))
			{
				var id = 'data-destination' + ('' + new Date().getTime()).substr(6), res;
				node.setAttribute('bx-destination-id', id);
				res = new UserDataInput(id, node, inputName);
				this.nodes[id] = node;
				this.nodesType.userDataInput.push(id);
				BX.defer_proxy(function(){
					params = this.params;
					params.name = res.id;
					params.searchInput = res.nodes.input;
					params.bindMainPopup.node = res.nodes.container;
					params.bindSearchPopup.node = res.nodes.container;

					BX.SocNetLogDestination.init(params);
				}, this)();
			}
		},
		setDefaultUserDataInput : function(node)
		{
			node = BX(node);
			if (!!node)
			{
				var el = this.createDefaultUserDataInputNode(this.defaultOperator);
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
				this.currentAvatarNode.style['background-image'] = "url(" + path + ")";
				this.currentAvatarInputNode.value = path;

				this.currentAvatarFileIdInputNode.value = fileId;
			}

			this.avatarPopup.close();
		},
		select : function(item, type, search, bUndeleted, id)
		{
			var type1 = type, prefix = 'S';

			if (type == 'groups')
			{
				type1 = 'all-users';
			}
			else if (BX.util.in_array(type, ['contacts', 'companies', 'leads', 'deals']))
			{
				type1 = 'crm';
			}

			if (type == 'sonetgroups')
			{
				prefix = 'SG';
			}
			else if (type == 'groups')
			{
				prefix = 'UA';
			}
			else if (type == 'users')
			{
				prefix = 'U';
			}
			else if (type == 'department')
			{
				prefix = 'DR';
			}
			else if (type == 'contacts')
			{
				prefix = 'CRMCONTACT';
			}
			else if (type == 'companies')
			{
				prefix = 'CRMCOMPANY';
			}
			else if (type == 'leads')
			{
				prefix = 'CRMLEAD';
			}
			else if (type == 'deals')
			{
				prefix = 'CRMDEAL';
			}

			var stl = (bUndeleted ? ' bx-destination-undelete' : '');
			stl += (type == 'sonetgroups' && typeof window['arExtranetGroupID'] != 'undefined' && BX.util.in_array(item.entityId, window['arExtranetGroupID']) ? ' bx-destination-extranet' : '');
			var classPostfix = type1 + stl;
			var elementParams = {
				item: item,
				type: type,
				bUndeleted: bUndeleted,
				classPostfix: classPostfix,
				prefix: prefix
			};
			var queueItem = (!!this.params.queueItems[elementParams.item.id] ? this.params.queueItems[elementParams.item.id] : {});
			this.params.selectedForMessage[elementParams.item.id] = elementParams.item;

			this.selectDestInputNodes(elementParams);
			this.selectUserDataInputNodes(elementParams, queueItem);
		},
		createDestInputNode : function(params)
		{
			var destInputTemplate = BX('dest_input_template').innerHTML;
			var innerHtml = destInputTemplate
				.replace(new RegExp('%data_id%', 'g'), params.item.id)
				.replace(new RegExp('%user_name_default%', 'g'), params.item.name)
				.replace(new RegExp('%dest_input_container_class%', 'g'), 'bx-destination bx-destination-' + params.classPostfix);
			var el = BX.create('DIV');
			el.innerHTML = innerHtml;
			el = el.children[0];

			if(!params.bUndeleted)
			{
				el.appendChild(BX.create("span", {
					props : {
						'className' : "imopenlines-remove-btn"
					},
					events : {
						'click' : BX.delegate(
							function(e){
								this.deleteItem(params.item.id, params.type);
								BX.PreventDefault(e)
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
				}));
			}

			return el;
		},
		createUserDataInputNode : function(params, queueItem)
		{
			var userName = (!!queueItem.USER_NAME ? BX.util.htmlspecialchars(queueItem.USER_NAME) : params.item.name),
				userWorkPosition = (!!queueItem.USER_WORK_POSITION ? BX.util.htmlspecialchars(queueItem.USER_WORK_POSITION) : ''),
				userAvatar = (!!queueItem.USER_AVATAR ? BX.util.htmlspecialchars(queueItem.USER_AVATAR) : params.item.avatar),
				userAvatarFileId = (!!queueItem.USER_AVATAR_ID ? BX.util.htmlspecialchars(queueItem.USER_AVATAR_ID) : null);
			var userDataInputTemplate = BX('user_data_input_template').innerHTML;
			var innerHtml = userDataInputTemplate
				.replace(new RegExp('%data_id%', 'g'), params.item.id)
				.replace(new RegExp('%user_name_default%', 'g'), params.item.name)
				.replace(new RegExp('%user_avatar_default%', 'g'), params.item.avatar.replace(/ /, '%20'))
				.replace(new RegExp('%user_name%', 'g'), userName)
				.replace(new RegExp('%user_work_position%', 'g'), userWorkPosition)
				.replace(new RegExp('%user_avatar%', 'g'), userAvatar)
				.replace(new RegExp('%user_avatar_show%', 'g'), userAvatar.replace(/ /, '%20'))
				.replace(new RegExp('%user_avatar_file_id%', 'g'), userAvatarFileId)
				.replace(new RegExp('%user_data_input_container_class%', 'g'), 'imopenlines-form-settings-user')
				.replace(new RegExp('background-image: url\\(\\)', 'g'), '');
			var el = BX.create('DIV');
			el.innerHTML = innerHtml;
			el = el.children[0];

			if(!params.bUndeleted)
			{
				el.appendChild(BX.create("span", {
					props : {
						'className' : "imopenlines-form-settings-user-delete"
					},
					events : {
						'click' : BX.delegate(
							function(e){
								this.deleteItem(params.item.id, params.type);
								BX.PreventDefault(e)
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
				}));
			}

			return el;
		},
		createDefaultUserDataInputNode : function(item)
		{
			var userName = (!!item.NAME ? BX.util.htmlspecialchars(item.NAME) : ''),
				userAvatar = (!!item.AVATAR ? BX.util.htmlspecialchars(item.AVATAR) : ''),
				userAvatarFileId = (!!item.AVATAR_ID ? BX.util.htmlspecialchars(item.AVATAR_ID) : null);
			var userDataInputTemplate = BX('default_user_data_input_template').innerHTML;
			var innerHtml = userDataInputTemplate
				.replace(new RegExp('%user_name%', 'g'), userName)
				.replace(new RegExp('%user_avatar%', 'g'), userAvatar)
				.replace(new RegExp('%user_avatar_show%', 'g'), userAvatar.replace(/ /, '%20'))
				.replace(new RegExp('%user_avatar_file_id%', 'g'), userAvatarFileId)
				.replace(new RegExp('background-image: url\\(\\)', 'g'), '');
			var el = BX.create('DIV');
			el.innerHTML = innerHtml;
			el = el.children[0];

			return el;
		},
		selectDestInputNodes : function(params)
		{
			this.nodesType.destInput.forEach(
				BX.delegate(
					function (id) {
						var el = this.createDestInputNode(params);
						BX.onCustomEvent(this.nodes[id], 'select', [params.item, el, params.prefix])
					},
					this
				)
			);
		},
		selectUserDataInputNodes : function(params, queueItem)
		{
			this.nodesType.userDataInput.forEach(
				BX.delegate(
					function (id) {
						var el = this.createUserDataInputNode(params, queueItem);
						BX.onCustomEvent(this.nodes[id], 'select', [params.item, el, params.prefix])
					},
					this
				)
			);
		},
		unSelect : function(item, type, search, id)
		{
			delete this.params.selectedForMessage[item.id];
			this.nodesEvent('unSelect', [item]);
		},
		deleteItem : function(id, type)
		{
			for (var key in this.nodes)
			{
				BX.SocNetLogDestination.deleteItem(id, type, key);
			}
		},
		openDialog : function(id)
		{
			BX.onCustomEvent(this.nodes[id], 'openDialog', []);
		},
		closeDialog : function(id)
		{
			if (!BX.SocNetLogDestination.isOpenSearch())
			{
				BX.onCustomEvent(this.nodes[id], 'closeDialog', []);
				this.disableBackspace();
			}
		},
		closeSearch : function(id)
		{
			if (!BX.SocNetLogDestination.isOpenSearch())
			{
				BX.onCustomEvent(this.nodes[id], 'closeSearch', []);
				this.disableBackspace();
			}
		},
		disableBackspace : function()
		{
			if (BX.SocNetLogDestination.backspaceDisable || BX.SocNetLogDestination.backspaceDisable !== null)
				BX.unbind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable);

			BX.bind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable = function(event){
				if (event.keyCode == 8)
				{
					BX.PreventDefault(event);
					return false;
				}
				return true;
			});
			setTimeout(function(){
				BX.unbind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable);
				BX.SocNetLogDestination.backspaceDisable = null;
			}, 5000);
		},
		nodesEvent : function (event, params)
		{
			for (var key in this.nodes)
			{
				BX.onCustomEvent(this.nodes[key], event, params)
			}
		},
		sendActionRequest: function (action, sendData, callbackSuccess, callbackFailure)
		{
			callbackSuccess = callbackSuccess || null;
			callbackFailure = callbackFailure || BX.proxy(this.showErrorPopup, this);
			sendData = sendData || {};

			if (sendData instanceof FormData)
			{
				sendData.append('action', action);
				sendData.append('configId', this.id);
				sendData.append('sessid', BX.bitrix_sessid());
			}
			else
			{
				sendData.action = action;
				sendData.configId = this.id;
				sendData.sessid = BX.bitrix_sessid();
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

	var DestInput = function(id, node, inputName)
	{
		this.node = node;
		this.id = id;
		this.inputName = inputName;
		this.node.appendChild(BX.create('SPAN', {
			props : { className : "bx-destination-wrap" },
			html : [
				'<span id="', this.id, '-container"><span class="bx-destination-wrap-item"></span></span>',
				'<span class="bx-destination-input-box" id="', this.id, '-input-box">',
					'<input type="text" value="" class="bx-destination-input" id="', this.id, '-input">',
				'</span>',
				'<a href="#" class="bx-destination-add" id="', this.id, '-add-button"></a>'
			].join('')}));
		BX.defer_proxy(this.bind, this)();
	};
	DestInput.prototype =
	{
		bind : function()
		{
			this.nodes = {
				inputBox : BX(this.id + '-input-box'),
				input : BX(this.id + '-input'),
				container : BX(this.id + '-container'),
				button : BX(this.id + '-add-button')
			};
			BX.bind(this.nodes.input, 'keyup', BX.proxy(this.search, this));
			BX.bind(this.nodes.input, 'keydown', BX.proxy(this.searchBefore, this));
			BX.bind(this.nodes.button, 'click', BX.proxy(function(e){BX.SocNetLogDestination.openDialog(this.id,  {bindNode: this.nodes.inputBox}); BX.PreventDefault(e); }, this));
			BX.bind(this.nodes.container, 'click', BX.proxy(function(e){BX.SocNetLogDestination.openDialog(this.id,  {bindNode: this.nodes.inputBox}); BX.PreventDefault(e); }, this));
			this.onChangeDestination();
			BX.addCustomEvent(this.node, 'select', BX.proxy(this.select, this));
			BX.addCustomEvent(this.node, 'unSelect', BX.proxy(this.unSelect, this));
			BX.addCustomEvent(this.node, 'delete', BX.proxy(this.delete, this));
			BX.addCustomEvent(this.node, 'openDialog', BX.proxy(this.openDialog, this));
			BX.addCustomEvent(this.node, 'closeDialog', BX.proxy(this.closeDialog, this));
			BX.addCustomEvent(this.node, 'closeSearch', BX.proxy(this.closeSearch, this));
		},
		select : function(item, el, prefix)
		{
			if (BX.message('LM_BUSINESS_USERS_ON') == 'Y' && BX.message('LM_BUSINESS_USERS').split(',').indexOf(item.id) == -1)
			{
				BX.SocNetLogDestination.closeDialog(this.id);
				BX.imolTrialHandler.openPopup('imol_queue', BX.message('LM_BUSINESS_USERS_TEXT'));
				return false;
			}
			if(!BX.findChild(this.nodes.container, { attr : { 'data-id' : item.id }}, false, false))
			{
				el.appendChild(BX.create("INPUT", { props : {
						type : "hidden",
						name : ('CONFIG['+this.inputName+']'+ '[' + prefix + '][]'),
						value : item.id
					}
				}));
				this.nodes.container.appendChild(el);
			}
			this.onChangeDestination();
		},
		unSelect : function(item)
		{
			var elements = BX.findChildren(this.nodes.container, {attribute: {'data-id': ''+item.id+''}}, true);
			if (elements !== null)
			{
				for (var j = 0; j < elements.length; j++)
					BX.remove(elements[j]);
			}
			this.onChangeDestination();
		},
		onChangeDestination : function()
		{
			var selectedId = [];
			var nodesButton = BX.findChildrenByClassName(this.nodes.container, "bx-destination", false);
			for (var i = 0; i < nodesButton.length; i++)
			{
				selectedId.push({
					'id' : nodesButton[i].getAttribute('data-id').substr(1),
					'name' : nodesButton[i].innerText
				});
			}
			BX.onCustomEvent('onChangeDestination', [selectedId]);

			this.nodes.input.innerHTML = '';
			this.nodes.button.innerHTML = (BX.SocNetLogDestination.getSelectedCount(this.id) <= 0 ? BX.message("LM_ADD1") : BX.message("LM_ADD2"));
		},
		openDialog : function()
		{
			BX.style(this.nodes.inputBox, 'display', 'inline-block');
			BX.style(this.nodes.button, 'display', 'none');
			BX.focus(this.nodes.input);
		},
		closeDialog : function()
		{
			if (this.nodes.input.value.length <= 0)
			{
				BX.style(this.nodes.inputBox, 'display', 'none');
				BX.style(this.nodes.button, 'display', 'inline-block');
				this.nodes.input.value = '';
			}
		},
		closeSearch : function()
		{
			if (this.nodes.input.value.length > 0)
			{
				BX.style(this.nodes.inputBox, 'display', 'none');
				BX.style(this.nodes.button, 'display', 'inline-block');
				this.nodes.input.value = '';
			}
		},
		searchBefore : function(event)
		{
			if (event.keyCode == 8 && this.nodes.input.value.length <= 0)
			{
				BX.SocNetLogDestination.sendEvent = false;
				BX.SocNetLogDestination.deleteLastItem(this.id);
			}
			return true;
		},
		search : function(event)
		{
			if (event.keyCode == 16 || event.keyCode == 17 || event.keyCode == 18 || event.keyCode == 20 || event.keyCode == 244 || event.keyCode == 224 || event.keyCode == 91)
				return false;

			if (event.keyCode == 13)
			{
				BX.SocNetLogDestination.selectFirstSearchItem(this.id);
				return true;
			}
			if (event.keyCode == 27)
			{
				this.nodes.input.value = '';
				BX.style(this.nodes.button, 'display', 'inline');
			}
			else
			{
				BX.SocNetLogDestination.search(this.nodes.input.value, true, this.id, '', {bindNode: this.nodes.inputBox});
			}

			if (!BX.SocNetLogDestination.isOpenDialog() && this.nodes.input.value.length <= 0)
			{
				BX.SocNetLogDestination.openDialog(this.id, {bindNode: this.nodes.inputBox});
			}
			else if (BX.SocNetLogDestination.sendEvent && BX.SocNetLogDestination.isOpenDialog())
			{
				BX.SocNetLogDestination.closeDialog();
			}
			if (event.keyCode == 8)
			{
				BX.SocNetLogDestination.sendEvent = true;
			}
			return true;
		}
	};

	var UserDataInput = function(id, node, inputName)
	{
		this.node = node;
		this.id = id;
		this.inputName = inputName;
		this.caller = destinationInstance;
		this.node.appendChild(
			BX.create(
				"SPAN",
				{
					props : {
						id: this.id + "-container"
					}
				}
			)
		);
		this.node.appendChild(
			BX.create(
				"SPAN",
				{
					props : {
						className : "bx-destination-input-box",
						id : this.id + "-input-box"
					},
					html : [
						'<input type="text" value="" class="bx-destination-input" id="' + this.id + '-input">'
					]
				}
			)
		);
		this.node.appendChild(
			BX.create(
				"A",
				{
					props : {
						href: "#",
						className : "imopenlines-form-settings-user-link",
						id : this.id + "-add-button"
					}
				}
			)
		);

		BX.defer_proxy(this.bind, this)();
	};
	UserDataInput.prototype =
	{
		bind : function()
		{
			this.nodes = {
				inputBox : BX(this.id + '-input-box'),
				input : BX(this.id + '-input'),
				container : BX(this.id + '-container'),
				button : BX(this.id + '-add-button')
			};
			BX.bind(this.nodes.input, 'keyup', BX.proxy(this.search, this));
			BX.bind(this.nodes.input, 'keydown', BX.proxy(this.searchBefore, this));
			BX.bind(this.nodes.button, 'click', BX.proxy(function(e){BX.SocNetLogDestination.openDialog(this.id, {bindNode: this.nodes.button}); BX.PreventDefault(e); }, this));
			this.onChangeDestination();
			BX.addCustomEvent(this.node, 'select', BX.proxy(this.select, this));
			BX.addCustomEvent(this.node, 'unSelect', BX.proxy(this.unSelect, this));
			BX.addCustomEvent(this.node, 'delete', BX.proxy(this.delete, this));
			BX.addCustomEvent(this.node, 'openDialog', BX.proxy(this.openDialog, this));
			BX.addCustomEvent(this.node, 'closeDialog', BX.proxy(this.closeDialog, this));
			BX.addCustomEvent(this.node, 'closeSearch', BX.proxy(this.closeSearch, this));
		},
		select : function (item, el, prefix)
		{
			if (BX.message('LM_BUSINESS_USERS_ON') == 'Y' && BX.message('LM_BUSINESS_USERS').split(',').indexOf(item.id) == -1)
			{
				BX.SocNetLogDestination.closeDialog(this.id);
				BX.imolTrialHandler.openPopup('imol_queue', BX.message('LM_BUSINESS_USERS_TEXT'));
				return false;
			}
			if(!BX.findChild(this.nodes.container, { attr : { 'data-id' : item.id }}, false, false))
			{
				var elements = BX.findChildren(el, {className: 'imopenlines-form-settings-user-input'}, true);
				if (elements !== null)
				{
					var name;
					for (var j = 0; j < elements.length; j++)
					{
						name = 'CONFIG['+this.inputName+']'+ '[' + prefix + '][' + item.id + '][' + elements[j].name + ']';
						elements[j].setAttribute('name', name);
					}
				}

				var avatarNode = BX.findChild(el, { attr : { 'id' : 'button-avatar-user-' + item.id }}, true);
				var avatarInput = BX.findChild(el, { attr : { 'id' : 'input-avatar-user-' + item.id }}, true);
				var avatarFileIdInput = BX.findChild(el, { attr : { 'id' : 'input-avatar-file-id-user-' + item.id }}, true);
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

				this.nodes.container.appendChild(el);
			}
			this.onChangeDestination();
		},
		unSelect : function (item)
		{
			var elements = BX.findChildren(this.nodes.container, {attribute: {'data-id': ''+item.id+''}}, true);
			if (elements !== null)
			{
				for (var j = 0; j < elements.length; j++)
					BX.remove(elements[j]);
			}
			this.onChangeDestination();
		},
		onChangeDestination : function()
		{
			var selectedId = [];
			var nodesButton = BX.findChildrenByClassName(this.nodes.container, "imopenlines-form-settings-user", false);
			for (var i = 0; i < nodesButton.length; i++)
			{
				selectedId.push({
					'id' : nodesButton[i].getAttribute('data-id').substr(1),
					'name' : nodesButton[i].innerText
				});
			}
			BX.onCustomEvent('onChangeDestination', [selectedId]);

			this.nodes.input.innerHTML = '';
			this.nodes.button.innerHTML = (BX.SocNetLogDestination.getSelectedCount(this.id) <= 0 ? BX.message("LM_ADD1") : BX.message("LM_ADD2"));
		},
		showAvatarPopup : function ()
		{
			if (!this.avatarPopup)
			{
				var contentNode = BX('imol_user_data_avatar_upload');
				this.avatarEditor = new ImolAvatarEditor({
					'caller': this.caller,
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
				this.currentAvatarNode.style['background-image'] = "url(" + path + ")";
				this.currentAvatarInputNode.value = path;

				this.currentAvatarFileIdInputNode.value = fileId;
			}

			this.avatarPopup.close();
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
				.replace('%path%', path);

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
				//this.initCarousel();
				this.selectAvatar(node);
				this.hideLoader();
			}
		},

		onFileRemoved: function(data)
		{
			if (data.fileId && data.fileId > 0)
			{
				var node = this.getUserAvatarNodeByFileId(data.fileId);
			}

			if (data.error)
			{
				this.caller.showErrorPopup(data);
				if (node)
				{
					node.style.display = '';
				}
			}
			else
			{
				BX.remove(node);
			}

			this.initCarousel();
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

	/*var ImolAjax = function(signedParameters, componentName)
	{
		this.signedParameters = signedParameters;
		this.componentName = componentName;
		this.loader = new ImolLoader();

		this.init();
	};

	ImolAjax.prototype =
	{
		init: function ()
		{
			BX.bindDelegate(
				document.body,
				'click',
				{className: 'ui-sidepanel-menu-link'},
				BX.proxy(this.reloadHandler, this)
			);
		},
		reloadHandler: function (e)
		{
			e.preventDefault();
			var context = BX.proxy_context;
			var pageParams = this.getPageParam(context.getAttribute('href').toString());
			this.reload(pageParams);

		},
		reload: function (pageParams)
		{
			this.loader.show();
			var page = pageParams.PAGE || 'queue-crm';
			var id = pageParams.ID || 0;

			BX.ajax.runComponentAction(this.componentName, 'loadPage', {
				mode: 'class',
				signedParameters: this.signedParameters,
				data: {
					ID: id,
					PAGE: page
				}
			}).then(
				BX.delegate(
					function(response) {
						var elem = BX.create('div');
						elem.innerHTML = response.data.html;
						BX('imol_config_edit_page').innerHTML = elem.querySelector('#imol_config_edit_page').innerHTML;
						BX.remove(elem);
						this.pageReloadActions(page);
						this.loader.hide();
					},
					this
				),
				BX.delegate(
					function(response) {
						this.loader.hide();
					},
					this
				)
			);
		},
		pageReloadActions: function (page)
		{
			BX.OpenLinesConfigEdit.unbindAll();
			BX.OpenLinesConfigEdit.init(page, this.signedParameters, this.componentName);
			var arr = BX('imol_config_edit_page').getElementsByTagName('script');
			for (var n = 0; n < arr.length; n++)
			{
				if (arr[n].getAttribute('type') !== 'text/html')
				{
					eval(arr[n].innerHTML);
				}
			}
			this.loader.setSizeDelegated();
		},
		getPageParam: function (url)
		{
			var result = {};
			if (url.charAt(0) === '/')
			{
				url = window.location.protocol + '//' + window.location.host + url;
			}

			var objUrl = new URL(url);
			result.ID = objUrl.searchParams.get('ID');
			result.PAGE = objUrl.searchParams.get('PAGE');

			return result;
		}
	};

	var ImolLoader = function()
	{
		this.parentNode = document.querySelector("#imopenlines-field-container");
		this.blockNode = document.querySelector("#imol_config_edit_form");
		this.body = BX.create("div", {
			props: {
				className: "imopenlines-side-panel-overlay"
			},
			children: [
				BX.create("div", {
					props: {
						className: "side-panel-default-loader-container"
					},
					html:
						'<svg class="side-panel-default-loader-circular" viewBox="25 25 50 50">' +
						'<circle ' +
						'class="side-panel-default-loader-path" ' +
						'cx="50" cy="50" r="20" fill="none" stroke-miterlimit="10"' +
						'/>' +
						'</svg>'
				})
			]
		});

		this.setSizeDelegated();
		this.bindResize();
	};

	ImolLoader.prototype =
	{
		show: function ()
		{
			this.parentNode.insertBefore(this.body, this.blockNode);
		},
		hide: function ()
		{
			this.parentNode.removeChild(this.body);
		},
		setSize: function () {
			this.body.style.width = this.blockNode.clientWidth + "px";
			this.body.style.height = this.blockNode.clientHeight + "px";
		},
		setSizeDelegated: function () {
			setTimeout(
				BX.delegate(function(event) {
					this.setSize();
				}, this),
				100
			)
		},
		bindResize: function ()
		{
			BX.bind(window, 'resize', this.setSizeDelegated.bind(this));
		}
	};*/
})(window);