BX.namespace('Crm.Component');

// (function() {
if(typeof BX.Crm.Component.FormRecurring === 'undefined')
{

	BX.Crm.Component.FormRecurring = function()
	{
		this.prevPeriod = null;
		this.selectorMail = null;
		this.context = null;
		this.templateUrl = null;
		this.typeId = null;
		this.existUserMail = null;
		this.ajaxUrl = null;
		this.lastExecution = null;
		this.constants = {
			C_CRM_OWNER_TYPE_INVOICE: 5,
			MANAGER_SINGLE_EXECUTION: 1,
			MANAGER_MULTIPLY_EXECUTION: 2,
			PERIOD_DAILY: 1,
			PERIOD_WEEKLY: 2,
			PERIOD_MONTHLY: 3,
			PERIOD_YEARLY: 4,

			REPEAT_TILL_ENDLESS: 'N',
			REPEAT_TILL_TIMES: 'T',
			REPEAT_TILL_DATE: 'D',

			MONDAY: 1, //monday starting from 0
			TUESDAY: 2,
			THURSDAY: 4,
			SUNDAY: 7
		};

		this.construct = function (params)
		{
			if (BX.type.isElementNode(BX('datepicker-display-start')))
			{
				BX.CrmDateLinkField.create(
					BX('datepicker-display-start'), null, {showTime: false, setFocusOnShow: false}
				);
				BX('datepicker-display-start').setAttribute('readonly', 'readonly');
			}

			if (BX.type.isElementNode(BX('datepicker-display-end')))
			{
				BX.CrmDateLinkField.create(
					BX('datepicker-display-end'), null, {showTime: false, setFocusOnShow: false}
				);
				BX('datepicker-display-end').setAttribute('readonly', 'readonly');
			}

			if (BX.type.isElementNode(BX('deal-datepicker-before')))
			{
				BX.CrmDateLinkField.create(
					BX('deal-datepicker-before'), null, {showTime: false, setFocusOnShow: false}
				);
				BX('deal-datepicker-before').setAttribute('readonly', 'readonly');
			}
			this.ajaxUrl = params.AJAX_URL || "";
			this.existUserMail = (params.ALLOW_SEND_BILL == 'Y');
			this.context = params.CONTEXT || "";
			this.templateUrl = params.TEMPLATE_URL || "";
			this.entityTypeId = params.ENTITY_TYPE_ID || this.constants.C_CRM_OWNER_TYPE_INVOICE;
			this.entityTypeName = params.ENTITY_TYPE_NAME || "Invoice";
			this.lastExecution = params.LAST_EXECUTION || "";

			if (params.EMAILS[0] !== undefined)
			{
				this.createEmailSelection(params.EMAILS)
			}

			this.prevPeriod = this.getValueString('period');
			this.onUpdateHint();
			this.bindEvents();
			this.onPeriodChange();
		};

		this.createEmailSelection = function(list)
		{
			var switcher = BX('crm-recur-email-change');

			this.selectorMail = BX.CmrSelectorMenu.create(
				'selector_recurring_email',
				{
					container: switcher,
					items: list
				}
			);

			this.selectorMail.addOnSelectListener(BX.delegate(this.onTypeSelect, this));

			BX.bind(switcher, 'click', BX.delegate(
				function ()
				{
					this.selectorMail.open(switcher);
				}, this)
			);
		};

		this.toggleMailSender = function()
		{
			if (!this.existUserMail)
			{
				BX('crm-recurring-email').checked = "";
				BX('crm-recurring-email').disabled = "disabled";
				BX('crm-recurring-email').classList.add("crm-recur-checkbox-blocked");
				BX('crm-recurring-empty-owner-email').classList.add('crm-recurring-empty-owner-email');
			}
			else
			{
				BX('crm-recurring-email').disabled = "";
				BX('crm-recurring-email').classList.remove("crm-recur-checkbox-blocked");
				BX('crm-recurring-empty-owner-email').innerHTML = "";
				BX('crm-recurring-empty-owner-email').classList.remove('crm-recurring-empty-owner-email');
			}
		};

		this.changeClientData = function(sender, data)
		{
			var mailList = [];
			var mailData = [];

			if (sender.getId() === 'UF_MYCOMPANY_ID')
			{
				mailData = data.primaryEntityInfo.getMultiFieldsByType("EMAIL");

				this.existUserMail = (data.primaryEntityInfo && mailData[0] !== undefined);
				this.toggleMailSender();
			}
			else if (sender.getId() === 'CLIENT')
			{
				if (data.primaryEntityInfo)
				{
					var primaryEntityInfoData = data.primaryEntityInfo.getMultiFieldsByType("EMAIL");
					if (primaryEntityInfoData[0] !== undefined)
					{
						mailData = mailData.concat(primaryEntityInfoData);
					}

				}
				if (data.entityInfos instanceof Array)
				{
					data.entityInfos.forEach(
						function(entity)
						{
							var entityInfoData = entity.getMultiFieldsByType("EMAIL");
							if (entityInfoData[0] !== undefined)
							{
								mailData = mailData.concat(entityInfoData);
							}
						}
					)
				}

				if (mailData.length > 0)
				{
					var mailListSort = [];
					for (var mailKey in mailData)
					{
						if (
							BX.type.isPlainObject(mailData[mailKey])
							&& BX.type.isNotEmptyString(mailData[mailKey].VALUE)
							&& mailListSort.indexOf(mailData[mailKey].VALUE) < 0
						)
						{
							var id = mailData[mailKey].ENTITY_ID ? mailData[mailKey].ENTITY_ID : mailData[mailKey].ID;
							mailList.push({
								text: mailData[mailKey].VALUE,
								value: id
							});
							mailListSort.push(mailData[mailKey].VALUE);
						}
					}
				}

				if (mailList.length > 0)
				{
					this.createEmailSelection(mailList);

					if (mailList[0] !== undefined)
					{
						BX.removeClass(BX('crm-recur-email-block'),'crm-recur-invisible');
						BX('crm-recur-client-email-value').innerHTML = mailList[0].text;
						BX('crm-recur-client-email-input').value = mailList[0].value;
					}
					else
					{
						BX.addClass(BX('crm-recur-email-block'),'crm-recur-invisible');
						BX('crm-recurring-email').checked = "";
					}
				}
				else
				{
					BX.addClass(BX('crm-recur-email-block'),'crm-recur-invisible');
					BX('crm-recurring-email').checked = "";
				}
			}
		};

		this.onTypeSelect = function(sender, selectedItem)
		{
			BX('crm-recur-client-email-value').innerHTML = selectedItem.getText();
			BX('crm-recur-client-email-input').value = selectedItem.getValue();
		};

		this.bindInstantChange = function(node, cb, ctx)
		{
			if(!BX.type.isElementNode(node))
			{
				return BX.DoNothing;
			}

			ctx = ctx || node;

			var value = node.value;

			var f = BX.debounce(function(e)
			{

				if(node.value.toString() != value.toString())
				{
					cb.apply(ctx, arguments);

					value = node.value;
				}
			}, 3, ctx);

			BX.bind(node, 'input', f);
			BX.bind(node, 'keyup', f);
			BX.bind(node, 'change', f);
		};

		this.bindEvents = function()
		{
			var handler = BX.delegate(this.onUpdateHint, this);
			var validateDate = BX.delegate(this.onChangeNumDay, this);
			var moreNull = BX.delegate(this.moreNullValue, this);

			if (this.entityTypeId == this.constants.C_CRM_OWNER_TYPE_INVOICE)
			{
				this.bindInvoiceEvents();
			}
			else
			{
				this.bindDealEvents();
			}


			BX.bind(BX('crm-recurring-flag'), 'click', BX.delegate(
				function (event)
				{
					if (event.target.checked)
					{
						BX.removeClass(BX('crm-recur-edit-recurring-panel'),'crm-recur-invisible');
					}
					else
					{
						BX.addClass(BX('crm-recur-edit-recurring-panel'),'crm-recur-invisible');
					}
				}, this)
			);

			// update hint on all selectboxes, checkboxes and radiobuttons
			BX.bindDelegate(BX('crm-recur-edit-replication-block'), 'change', {tag: 'select'}, handler);
			BX.bindDelegate(BX('crm-recur-edit-replication-block'), 'change', {tag: 'input', attr: {type: 'checkbox'}}, handler);
			BX.bindDelegate(BX('crm-recur-edit-replication-block'), 'change', {tag: 'input', attr: {type: 'radio'}}, handler);

			// update hint instantly on all texts
			this.bindInstantChange(BX('daily-interval-day'), handler);
			this.bindInstantChange(BX('daily-interval-month'), handler);
			this.bindInstantChange(BX('weekly-interval-week'), handler);
			BX.bind(BX('monthly-day-num'), 'change', validateDate);
			this.bindInstantChange(BX('monthly-day-num'), handler);
			this.bindInstantChange(BX('monthly-month-num-1'), handler);
			this.bindInstantChange(BX('monthly-month-num-2'), handler);
			this.bindInstantChange(BX('yearly-day-num'), handler);
			BX.bind(BX('yearly-interval-day'), 'change', validateDate);
			BX.bind(BX('yearly-month-1'), 'change', validateDate);
			this.bindInstantChange(BX('yearly-interval-day'), handler);
			this.bindInstantChange(BX('deal-count-before'), handler);
			this.bindInstantChange(BX('deal-datepicker-before'), handler);

			BX.bind(BX('daily-interval-day'),'change',  BX.delegate(moreNull ,this));
			BX.bind(BX('monthly-month-num-1'),'change',  BX.delegate(moreNull ,this));
			BX.bind(BX('monthly-month-num-2'),'change',  BX.delegate(moreNull ,this));
			BX.bind(BX('weekly-interval-week'),'change',  BX.delegate(moreNull ,this));

			// update hint on repeat constraints change
			this.bindInstantChange(BX('end-times'), handler);
			this.bindInstantChange(BX('datepicker-display-start'), handler);
			this.bindInstantChange(BX('datepicker-display-end'), handler);
		};

		this.bindInvoiceEvents = function()
		{
			BX.addCustomEvent("RecurringInvoiceClientDataList", BX.delegate(function(sender, data)
				{
					this.changeClientData(sender, data);
				},this)
			);

			BX.bind(BX('crm-recur-create-mail-template-link'), 'click', BX.delegate(
				function(){
					this.onMailTemplateCreateClick()
				}, this)
			);

			BX.bind(BX('crm-recurring-email').parentNode, 'click', BX.delegate(
				function ()
				{
					if (!this.existUserMail)
					{
						BX('crm-recurring-empty-owner-email').innerHTML = BX.message('CRM_RECURRING_EMPTY_OWNER_EMAIL1');
					}
					else if (BX('crm-recurring-empty-owner-email').innerHTML != "")
					{
						BX('crm-recurring-empty-owner-email').innerHTML = "";
					}
				}, this)
			);

			BX.bindDelegate(
				BX('period-type-selector'), 'click',  { 'className': 'period-type-option' }, BX.proxy(
					function(event)
					{
						this.onSetPeriodValue(event.target);
					}, this
				)
			);

			this.toggleMailSender();
		};

		this.bindDealEvents = function()
		{
		};

		this.onMailTemplateCreateClick = function()
		{
			var url = this.templateUrl;

			var context = (this.context + "_" + BX.util.getRandomString(6)).toLowerCase();
			if(url === "" || context === "")
			{
				return;
			}

			context = (context + "_" + BX.util.getRandomString(6)).toLowerCase();
			var urlParams = {
				ENTITY_TYPE_ID: this.entityTypeId,
				external_context: context
			};
			url = BX.util.add_url_param(url, urlParams);
			if(!this._externalRequestData)
			{
				this._externalRequestData = {};
			}
			this._externalRequestData[context] = { context: context };

			BX.SidePanel.Instance.open(url, {
				cacheable: false,
				width: 1080
			});

			if(!this._externalEventHandler)
			{
				this._externalEventHandler = BX.delegate(this.onExternalEvent, this);
				BX.addCustomEvent(window, "onLocalStorageSet", this._externalEventHandler);
			}
		};

		this.onExternalEvent = function(params)
		{
			if(this._readOnly)
			{
				return;
			}

			var key = BX.type.isNotEmptyString(params["key"]) ? params["key"] : "";
			var value = BX.type.isPlainObject(params["value"]) ? params["value"] : {};
			var context = BX.type.isNotEmptyString(value["context"]) ? value["context"] : "";

			if(
				key === "onCrmMailTemplateCreate"
				&& this._externalRequestData
				&& BX.type.isPlainObject(this._externalRequestData[context]))
			{
				if (value.templateId > 0 && (value.entityType > 0 ? value.entityType == this.entityTypeId : true))
				{
					var newMailTemplate = BX.create(
						"option",
						{
							props: {
								value: value.templateId,
								text: value.templateTitle
							}
						}
					);
					BX('email_template').appendChild(newMailTemplate);
					BX('email_template').selectedIndex = BX('email_template').options.length-1;
					if (BX('email_template').disabled)
					{
						BX('email_template').removeAttribute('disabled');
						BX('email_template').parentNode.classList.remove('disabled');
					}
				}

				delete this._externalRequestData[context];
			}
		};

		this.getCurrentPeriod = function()
		{
			return this.getValueString('period');
		};

		this.moreNullValue = function(e)
		{
			e.target.value = e.target.value > 0 ? parseInt(e.target.value) : 1;
		};

		this.onSetPeriodValue = function(node)
		{
			var type = BX.data(node, 'type');
			if (BX.util.in_array(type, [this.constants.PERIOD_DAILY, this.constants.PERIOD_WEEKLY, this.constants.PERIOD_MONTHLY, this.constants.PERIOD_YEARLY]))
			{
				var oldActive = BX.findChildrenByClassName(node.parentNode, 'active-recur');
				if (oldActive[0])
				{
					BX.removeClass(oldActive[0], 'active-recur');
				}
				BX.addClass(node, 'active-recur');
				BX('period').value = type;
				this.onPeriodChange();
			}
		};

		this.getDays = function(month)
		{
			return month === 2 ? 28 : 30 + (month > 7 ? month + 1 : month) % 2;
		};

		this.onChangeNumDay = function()
		{
			var period = this.getCurrentPeriod();
			if (period == 3)
			{
				topValue = 31;
				target = BX('monthly-day-num');
			}
			else if (period == 4)
			{
				month = this.getValueInt('yearly-month-1');
				var topValue = this.getDays(month);
				target = BX('yearly-interval-day');
			}

			var intValue = parseInt(target.value);
			if (intValue <= 0 || isNaN(intValue))
			{
				target.value = 1
			}
			else if (intValue > topValue)
			{
				target.value = topValue;
			}
			else
			{
				target.value = intValue;
			}
		};

		this.setConstraintPanelHeight = function(period)
		{
			var nodeToShow = BX('panel-' + period);
			if (nodeToShow)
			{
				var height = BX.pos(nodeToShow).height;
				BX('panel').style.height = height + 'px';
			}
		};

		this.onPeriodChange = function()
		{
			var period = this.getCurrentPeriod();
			if (this.prevPeriod != period)
			{
				var nodeToHide = BX('panel-period-' + this.prevPeriod);
				var nodeToShow = BX('panel-period-' + period);
				if (nodeToHide && nodeToShow)
				{
					this.setConstraintPanelHeight(this.prevPeriod);

					// hide previous
					BX.addClass(nodeToHide, 'nodisplay');
					BX.removeClass(nodeToShow, 'nodisplay');

					this.setConstraintPanelHeight(period);

					this.prevPeriod = period;
				}
			}

			this.onUpdateHint();
		};

		this.onUpdateHint = function()
		{
			if (this.entityTypeId == this.constants.C_CRM_OWNER_TYPE_INVOICE)
			{
				var params = {
					'PERIOD': this.getValueString('period'),
					'DAILY_INTERVAL_DAY': this.getValueInt('daily-interval-day'),
					'DAILY_WORKDAY_ONLY': this.getSelectedControlValues('workday-only-select')[0],
					'DAILY_MONTH_INTERVAL': this.getValueInt('daily-interval-month'),
					'WEEKLY_INTERVAL_WEEK': this.getValueInt('weekly-interval-week'),
					'WEEKLY_WEEK_DAYS': this.getWeekDays(), // in this field, values start from 1
					'MONTHLY_INTERVAL_DAY': this.getValueInt('monthly-day-num'),
					'MONTHLY_MONTH_NUM_1': this.getValueInt('monthly-month-num-1'),
					'MONTHLY_WORKDAY_ONLY': this.getSelectedControlValues('monthly-only-select')[0],
					'MONTHLY_TYPE': this.getSelectedControlValues('monthly-type')[0],
					'MONTHLY_WEEKDAY_NUM': this.getValueInt('monthly-week-day-num'),
					'MONTHLY_WEEK_DAY': this.getValueInt('monthly-week-day'),
					'MONTHLY_MONTH_NUM_2': this.getValueInt('monthly-month-num-2'),
					'YEARLY_TYPE': this.getSelectedControlValues('yearly-type')[0],
					'YEARLY_INTERVAL_DAY': this.getValueInt('yearly-interval-day'),
					'YEARLY_MONTH_NUM_1': this.getValueInt('yearly-month-1'),
					'YEARLY_WEEK_DAY_NUM': this.getValueInt('yearly-week-day-num'),
					'YEARLY_WORKDAY_ONLY': this.getSelectedControlValues('yearly-only-select')[0],
					'YEARLY_WEEK_DAY': this.getValueInt('yearly-week-day'),
					'YEARLY_MONTH_NUM_2': this.getValueInt('yearly-month-2'),
					'START_DATE': this.getValueString('datepicker-display-start'),
					'END_DATE': this.getValueString('datepicker-display-end'),
					'TIMES': this.getValueInt('end-times'),
					'REPEAT_TILL': this.getRepeatTill()
				};
			}
			else
			{
				var params = {
					'EXECUTION_TYPE': this.getSelectedControlValues('selected-deal-type')[0],
					'PERIOD_DEAL': this.getSelectedControlValues('period-deal-select')[0],
					'DEAL_COUNT_BEFORE': this.getValueInt('deal-count-before'),
					'DEAL_DATEPICKER_BEFORE': this.getValueString('deal-datepicker-before'),
					'DEAL_TYPE_BEFORE': this.getSelectedControlValues('deal-type-before-select')[0],
					'START_DATE': this.getValueString('datepicker-display-start'),
					'END_DATE': this.getValueString('datepicker-display-end'),
					'TIMES': this.getValueInt('end-times'),
					'REPEAT_TILL': this.getRepeatTill()
				};
			}
			var hint = BX('hint');
			if (hint)
			{
				hint.innerHTML = this.makeHintText(params);
			}

			this.updateExecutionHint(params);
		};

		this.updateExecutionHint = function(params)
		{			
			BX.ajax(
			{
				url: this.ajaxUrl,
				method: 'POST',
				dataType: 'json',
				data:
				{
					START_DATE :  this.getValueString('datepicker-display-start'),
					PARAMS: params,
					ENTITY_TYPE: this.entityTypeId,
					LAST_EXECUTION: this.lastExecution
				},
				onsuccess: BX.delegate(this.setExecutionHTML, this)
			});
		};

		this.setExecutionHTML = function(data)
		{
			if (data.RESULT.NEXT_DATE !== undefined)
			{
				BX('next-data-hint').innerHTML = BX.message('NEXT_EXECUTION_'+this.entityTypeName+'_HINT').replace('#DATE_EXECUTION#', (data.RESULT.NEXT_DATE));
			}
		};

		this.makeInvoiceHintMessage = function (params)
		{
			var number = null;
			var messageElement = "";
			var langId = BX.message('LANGUAGE_ID');
			var weekDayName = "";
			var weekDayGender = "";

			if(params.PERIOD == this.constants.PERIOD_DAILY)
			{
				var dayNumber = params.DAILY_INTERVAL_DAY > 1 ? params.DAILY_INTERVAL_DAY +' ' : '';

				if(params.DAILY_WORKDAY_ONLY == 'Y')
				{
					messageElement = BX.message('CRM_RECURRING_HINT_ELEMENT_DAY_MASK_WORK').replace('#DAY_NUMBER#', dayNumber);
				}
				else
				{
					messageElement = BX.message('CRM_RECURRING_HINT_ELEMENT_DAY_MASK').replace('#DAY_NUMBER#', dayNumber);
				}
			}
			else if(params.PERIOD == this.constants.PERIOD_WEEKLY)
			{
				number = params.WEEKLY_INTERVAL_WEEK > 1 ? params.WEEKLY_INTERVAL_WEEK +' ' : '';

				if (params.WEEKLY_WEEK_DAYS.length == 7)
				{
					weekdays = BX.message('CRM_RECURRING_HINT_WEEKDAY_EVERY_DAY');
				}
				else
				{
					var weekList = [];
					for (var k = 0; k < params.WEEKLY_WEEK_DAYS.length; k++)
					{
						weekList.push(BX.message('CRM_RECURRING_HINT_WEEKDAY_WD_' + params.WEEKLY_WEEK_DAYS[k]));
					}
					weekdays = weekList.join(', ');
				}

				messageElement = BX.message('CRM_RECURRING_HINT_ELEMENT_WEEKDAY_MASK').replace('#WEEK_NUMBER#', number).replace('#LIST_WEEKDAY_NAMES#', weekdays);
			}
			else if(params.PERIOD == this.constants.PERIOD_MONTHLY)
			{
				var monthNumber = null;
				var each = "";
				if (params.MONTHLY_TYPE == 1)
				{
					number = parseInt(params.MONTHLY_INTERVAL_DAY) > 0 ? parseInt(params.MONTHLY_INTERVAL_DAY) : 1;
					monthNumber = params.MONTHLY_MONTH_NUM_1;

					if (params.MONTHLY_WORKDAY_ONLY == 'Y')
						messageElement = BX.message('CRM_RECURRING_HINT_ELEMENT_MONTH_MASK_1_WORK').replace('#DAY_NUMBER#', number).replace('#MONTH_NUMBER#', (monthNumber > 1 ? monthNumber+' ' : ''));
					else
						messageElement = BX.message('CRM_RECURRING_HINT_ELEMENT_MONTH_MASK_1').replace('#DAY_NUMBER#', number).replace('#MONTH_NUMBER#', (monthNumber > 1 ? monthNumber+' ' : ''));
				}
				else
				{
					if (langId == 'ru' || langId == 'ua')
					{
						weekDayGender = this.getWeekDayGender(params.MONTHLY_WEEK_DAY);
					}

					weekDayName = this.getWeekDayName(params.MONTHLY_WEEK_DAY);

					number = BX.message('CRM_RECURRING_HINT_WEEKDAY_NUMBER_' + params.MONTHLY_WEEKDAY_NUM + weekDayGender);
					each = BX.message('CRM_RECURRING_HINT_EACH' + weekDayGender);
					monthNumber = params.MONTHLY_MONTH_NUM_2;
					messageElement = BX.message('CRM_RECURRING_HINT_MONTHLY_EXT_TYPE_2')
						.replace('#EACH#', each)
						.replace('#WEEKDAY_NUMBER#', number)
						.replace('#WEEKDAY_NAME#', weekDayName)
						.replace('#MONTH_NUMBER#', (monthNumber > 1 ? monthNumber+" " : ''));
				}
			}
			else
			{
				var monthName = "";
				if (params.YEARLY_TYPE == 1)
				{
					number = parseInt(params.YEARLY_INTERVAL_DAY) > 0 ? parseInt(params.YEARLY_INTERVAL_DAY) : 1;
					monthName = BX.message('CRM_RECURRING_HINT_MONTH_' + params.YEARLY_MONTH_NUM_1);
					if (params.YEARLY_WORKDAY_ONLY == 'Y')
						messageElement = BX.message('CRM_RECURRING_HINT_YEARLY_EXT_TYPE_1_WORKDAY').replace('#DAY_NUMBER#', number).replace('#MONTH_NAME#', monthName);
					else
						messageElement = BX.message('CRM_RECURRING_HINT_YEARLY_EXT_TYPE_1').replace('#DAY_NUMBER#', number).replace('#MONTH_NAME#', monthName);
				}
				else
				{
					if (langId == 'ru' || langId == 'ua')
					{
						weekDayGender = this.getWeekDayGender(params.YEARLY_WEEK_DAY);
					}

					weekDayName = this.getWeekDayName(params.YEARLY_WEEK_DAY);

					number = BX.message('CRM_RECURRING_HINT_WEEKDAY_NUMBER_' + params.YEARLY_WEEK_DAY_NUM + weekDayGender);
					each = BX.message('CRM_RECURRING_HINT_EACH' + weekDayGender);
					monthName = BX.message('CRM_RECURRING_HINT_MONTH_' + params.YEARLY_MONTH_NUM_2);
					messageElement = BX.message('CRM_RECURRING_HINT_YEARLY_EXT_TYPE_2')
						.replace('#EACH#', each)
						.replace('#WEEKDAY_NUMBER#', number)
						.replace('#WEEKDAY_NAME#', weekDayName)
						.replace('#MONTH_NAME#', monthName);
				}
			}

			return messageElement;
		};


		this.makeDealHintMessage = function (params)
		{
			var messageElement = "";
			if (params.EXECUTION_TYPE == this.constants.MANAGER_SINGLE_EXECUTION)
			{
				var countElement = parseInt(params.DEAL_COUNT_BEFORE);
				if (countElement === 0)
				{
					messageElement = params.DEAL_DATEPICKER_BEFORE || BX.message('CRM_RECURRING_HINT_TODAY');
				}
				else if (params.DEAL_DATEPICKER_BEFORE == "")
				{
					messageElement = BX.message('CRM_RECURRING_HINT_TODAY');
				}
				else
				{
					var type = params.DEAL_TYPE_BEFORE;
					var typeElement = this.getMessagePlural(countElement, "CRM_RECURRING_HINT_" + type);
					var date = params.DEAL_DATEPICKER_BEFORE || "";
					if (date.length > 0)
					{
						dateMessage = BX.message('CRM_RECURRING_HINT_BEFORE_DATE').replace('#DATE#', params.DEAL_DATEPICKER_BEFORE || "");
					}
					else
					{
						dateMessage = "";
					}
					messageElement = BX.message('CRM_RECURRING_HINT_A_FEW_DAYS_BEFORE_DATE')
						.replace('#COUNT_ELEMENT#', countElement)
						.replace('#TYPE_ELEMENT#', typeElement)
						.replace('#BEFORE_DATE#', dateMessage);
				}
			}
			else
			{
				messageElement = BX.message('CRM_RECURRING_HINT_EVERY_DEAL_' +  parseInt(params.PERIOD_DEAL));
			}

			return messageElement;
		};

		this.makeHintText = function (params)
		{
			var messageElement = "";
			var constraint = "";
			var startText = "";

			if (this.entityTypeId == this.constants.C_CRM_OWNER_TYPE_INVOICE)
			{
				messageElement = this.makeInvoiceHintMessage(params);
			}
			else
			{
				messageElement = this.makeDealHintMessage(params);
			}

			var repeatTimes = params.TIMES;

			if (params.EXECUTION_TYPE == this.constants.MANAGER_SINGLE_EXECUTION)
			{
				startText = "";
			}
			else if (params.START_DATE != '')
			{
				// start date to short format
				var short = BX.date.format(BX.date.convertBitrixFormat(BX.message('FORMAT_DATE')), BX.parseDate(params.START_DATE, true), false, true);

				startText = BX.message('CRM_RECURRING_HINT_START_DATE').replace('#DATETIME#', short);
			}
			else
			{
				startText = BX.message('CRM_RECURRING_HINT_START_EMPTY');
			}

			var till = this.getRepeatTill();

			if (params.EXECUTION_TYPE == this.constants.MANAGER_SINGLE_EXECUTION)
			{
				constraint = "";
			}
			else if (params.END_DATE != '' && till == this.constants.REPEAT_TILL_DATE)
			{
				constraint = BX.message('CRM_RECURRING_HINT_END').replace('#DATETIME#', params.END_DATE);
			}
			else if (repeatTimes > 0 && till == this.constants.REPEAT_TILL_TIMES)
			{
				constraint = BX.message('CRM_RECURRING_HINT_END_TIMES').replace('#TIMES#', repeatTimes).replace('#TIMES_PLURAL#', this.getMessagePlural(repeatTimes, 'CRM_RECURRING_HINT_END_CONSTRAINT_TIMES'));

			}
			else
			{
				constraint = BX.message('CRM_RECURRING_HINT_END_NONE');
			}

			return BX.message('CRM_RECURRING_'+this.entityTypeName+'_HINT_BASE').replace('#ELEMENT#', messageElement).replace('#START#', startText).replace('#END#', constraint);
		};

		this.getWeekDayName = function (num)
		{
			var weekDayGender = "";
			if (BX.message('LANGUAGE_ID') == 'ru' || BX.message('LANGUAGE_ID')  == 'ua')
			{
				weekDayGender = this.getWeekDayGender(num);
			}

			return BX.message('CRM_RECURRING_HINT_WEEKDAY_WD_' + num + (weekDayGender == '_F' ? '_ALT' : ''));
		};

		this.getValueString = function (controlName)
		{
			var control = BX(controlName);
			if (BX.type.isElementNode(control))
			{
				return BX.util.htmlspecialchars(control.value.toString());
			}
			return '';
		};

		this.getValueInt = function (controlName)
		{
			var control = BX(controlName);
			if (BX.type.isElementNode(control))
			{
				var val = parseInt(control.value.toString());
				if (isNaN(val))
				{
					return 0;
				}

				return val;
			}
			return 0;
		};

		this.getMessagePlural = function(n, msgId)
		{
			var pluralForm, langId;
	
			langId = BX.message('LANGUAGE_ID');
			n = parseInt(n);
	
			if (n < 0)
			{
				n = (-1) * n;
			}
	
			if (langId)
			{
				switch (langId)
				{
					case 'de':
					case 'en':
						pluralForm = ((n !== 1) ? 1 : 0);
						break;
	
					case 'ru':
					case 'ua':
						pluralForm = ( ((n%10 === 1) && (n%100 !== 11)) ? 0 : (((n%10 >= 2) && (n%10 <= 4) && ((n%100 < 10) || (n%100 >= 20))) ? 1 : 2) );
						break;
	
					default:
						pluralForm = 1;
						break;
				}
			}
			else
			{
				pluralForm = 1;
			}
	
			if(BX.type.isArray(msgId))
			{
				return msgId[pluralForm];
			}
	
			return (BX.message(msgId + '_PLURAL_' + pluralForm));
		};
		
		this.getSelectedControlValues = function (selector)
		{
			var result = [];
			var nodes = document.getElementsByClassName(selector);
			for (var k in nodes)
			{
				if (nodes[k].checked || nodes[k].selected)
				{
					if (result.indexOf(nodes[k].value) === -1)
						result.push(nodes[k].value);
				}
			}

			return result;
		};

		this.getRepeatTill = function ()
		{
			var repeat = this.getSelectedControlValues('selected-end');
			if (typeof repeat[0] == 'undefined')
			{
				return this.constants.REPEAT_TILL_ENDLESS;
			}

			return repeat[0];
		};

		this.getWeekDays = function ()
		{
			var wd = this.getSelectedControlValues('weekly-week-days');
			if (wd.length == 0)
			{
				wd.push(this.constants.MONDAY);
				document.getElementsByClassName('weekly-week-days')[0].checked = true;
			}
			else
			{
				for (var k = 0; k < wd.length; k++)
				{
					wd[k] = parseInt(wd[k]);
				}
			}

			return wd;
		};

		this.getWeekDayGender = function (num)
		{
			if (num == this.constants.MONDAY || num == this.constants.TUESDAY || num == this.constants.THURSDAY)
			{
				return '_M';
			}
			if (num == this.constants.SUNDAY)
			{
				return '';
			}
			return '_F';
		}
	};
}
