BX.namespace('Tasks.Component');

(function(){

	if(typeof BX.Tasks.Component.TaskDetailPartsReminder != 'undefined')
	{
		return;
	}

	BX.Tasks.Component.TaskDetailPartsReminder = BX.Tasks.Util.ItemSet.extend({
		sys: {
			code: 'reminder'
		},
        constants: {
            UNIT_DAY: 'D',
            UNIT_HOUR: 'H'
        },
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Util.ItemSet);

                this.vars.editItemValue = 0;

                this.instances.windows = {};

                this.instances.form = new Form({
                    scope: this.control('form'),
                    id: this.id()+'-form',
                    parent: this
                });

                this.setTaskId(this.option('taskId'));
                this.setTaskDeadLine(this.option('taskDeadline'));

                this.instances.form.bindEvent('save', BX.delegate(this.onItemApply, this));
                this.bindEvent('setTaskDeadLine', BX.delegate(this.setTaskDeadLine, this));

                // behaviour on some global eventing
                BX.addCustomEvent(window, 'tasksTaskEventChangeDeadline', BX.delegate(this.onTaskDeadLineChange, this));
                BX.addCustomEvent(window, 'tasksTaskEventAddReminder', BX.delegate(this.openAddForm, this));
			},

			extractItemValue: function()
			{
				return this.vars.idOffset++; // no primary key for this entity :(
			},

			extractItemDisplay: function()
			{
				return '1'; // whatever, display is unused here
			},

            openUpdateForm: function(id)
            {
                var item = this.vars.items[id];
                this.vars.editItemValue = id;

                this.instances.form.open(item.scope(), item.vars.data);
            },

			createItem: function(data)
			{
				data = Item.prepareData(data);

				// prepare scope
				var scope = this.getNodeByTemplate('item', data)[0];
                // todo: when items are massively added, buffierize this and then append for all items at once
				BX.append(scope, this.control('items'));

				// make widget-like item
				var item = new Item({
					scope: scope,
					data: data,
					auxData: this.option('auxData'),
                    parent: this
				});

				return item;
			},

            setTaskId: function(taskId)
            {
                this.vars.taskId = taskId ? taskId : 0;
            },

            setTaskDeadLine: function(dateString)
            {
                var stamp = 0;
                if(BX.type.isNotEmptyString(dateString))
                {
                    stamp = this.dateStringToStamp(dateString);
                }

                this.instances.form.setDeadLineStamp(stamp);
            },

			openAddForm: function(node)
			{
                this.vars.editItemValue = 0;
                this.instances.form.open(node);
			},

            onTaskDeadLineChange: function(taskId, dateString)
            {
                if(taskId == this.vars.taskId)
                {
                    this.setTaskDeadLine(dateString);
                }
            },

            onItemApply: function(data)
            {
                if(this.vars.editItemValue != 0)
                {
                    this.vars.items[this.vars.editItemValue].update(data);
                    this.syncAllIfCan(); // "item update" is not "change" in terms of ItemSet class, so have to call sync manually
                }
                else
                {
                    this.addItem(data);
                }
            },

            // this fires on each add and delete
			fireChangeEvent: function(parameters)
			{
				this.callMethod(BX.Tasks.Util.ItemSet, 'fireChangeEvent', arguments);

				if(!parameters.load) // dont sync when loading
				{
					this.syncAllIfCan();
				}
			},

			/*
			fireChangeDeferredEvent: function(items, parameters)
            {
	            this.callMethod(BX.Tasks.Util.ItemSet, 'fireChangeDeferredEvent');
                this.syncAllIfCan();
            },
			*/

            getDateDiff: function(from, to)
            {
                if(!BX.type.isNumber(from))
                {
                    from = this.dateStringToStamp(from);
                }
                if(!BX.type.isNumber(to))
                {
                    to = this.dateStringToStamp(to);
                }

                var diff = to - from;
                var unit = this.UNIT_HOUR;
                if(diff % 86400 == 0)
                {
                    unit = this.UNIT_DAY;
                    diff = Math.floor(diff / 86400);
                }
                else
                {
                    diff = Math.floor(diff / 3600);
                }

                return {diff: diff, unit: unit};
            },

            dateStampToString: function(stamp, format)
            {
                var d = new Date(stamp * 1000); // localtime

                return BX.date.format(format, d, false, true);
            },

            dateStringToStamp: function(string)
            {
                var parsed = BX.parseDate(string, true);
                if(parsed != null)
                {
                    return Math.floor((parseInt(parsed.getTime()) / 1000));
                }

                return 0;
            },

            syncAll: function()
            {
                var q = this.getQuery();
                if(q && parseInt(this.option('taskId')))
                {
                    var arg = [];
                    for(var k in this.vars.items)
                    {
                        arg.push(this.vars.items[k].data());
                    }

                    q.add('task.update', {
                        id: parseInt(this.option('taskId')),
                        data: {
                            SE_REMINDER: arg
                        }
                    },{
                        code: 'update_reminder'
                    });
                }
            }
		}
	});

	var Item = BX.Tasks.Util.ItemSet.Item.extend({
		sys: {
			code: 'item'
		},
		methods: {
			construct: function()
			{
				this.callConstruct(BX.Tasks.Util.ItemSet.Item);

				this.vars.data = this.option('data');
				this.vars.randomTag = Math.round(Math.random() * 100000);

				this.bindEvents();
			},

			bindEvents: function()
			{
				this.bindDelegateControl('edit', 'click', BX.delegate(this.onEditClick, this));
			},

			data: function()
			{
				return {
					TRANSPORT: this.vars.data.TRANSPORT,
					TYPE: this.vars.data.TYPE,
					REMIND_DATE: this.vars.data.REMIND_DATE,
					RECEPIENT_TYPE: this.vars.data.RECEPIENT_TYPE
				};
			},

			value: function()
			{
				return this.vars.data.VALUE;
			},
			display: function()
			{
				return this.vars.data.DISPLAY;
			},

			update: function(data)
			{
				// TYPE: "D", RECEPIENT_TYPE: "R", REMIND_DATE: "31.01.2016 11:00:00", TRANSPORT: "J"
				data = Item.prepareData(data);

				this.control('transport').value = data.TRANSPORT;
				this.control('type').value = data.TYPE;
				this.control('remind-date').value = data.REMIND_DATE;
				this.control('recipient-type').value = data.RECEPIENT_TYPE;
				this.control('edit').innerHTML = data.REMINDER_TEXT;

				BX[data.TRANSPORT == 'J' ? 'addClass' : 'removeClass'](this.control('info'), 'transport-j');

				this.vars.data = BX.mergeEx(this.vars.data, data);
			},

			delete: function()
			{
				var value = this.value();

				BX.remove(this.sys.scope);
				this.sys.scope = null;
				this.ctrls = null;
				this.vars.data = null;

				return value;
			},

			onEditClick: function(e)
			{
				this.parent().openUpdateForm(this.value());
				BX.PreventDefault(e);
			},

			onDeleteClick: function(e)
			{
				// itemSet will catch the event and then delete item by itself, if there are correct permissions
				this.parent().deleteItem(this.value());
				BX.PreventDefault(e);
			}
		}
	});
	Item.prepareData = function(data)
	{
		// "template logic" emulation :(

		var text = '';

		if(data.RECEPIENT_TYPE == 'R' || data.RECEPIENT_TYPE == 'O' || data.RECEPIENT_TYPE == 'S')
		{
			text = BX.message('TASKS_TTDP_TEMPLATE_REMINDER_RECEPIENT_TYPE_'+data.RECEPIENT_TYPE);
		}

		data.REMINDER_TEXT = data.REMIND_DATE+' '+text;
		data.TRANSPORT_CLASS = data.TRANSPORT == 'J' ? 'transport-j' : '';

		return data;
	};

	var Form = BX.Tasks.Util.Widget.extend({
		sys: {
			code: 'form'
		},
		constants: {
			REMINDER_TYPE_DEADLINE: 'D',
			REMINDER_TYPE_COMMON: 'A'
		},
		methods: {

			construct: function()
			{
				this.callConstruct(BX.Tasks.Util.Widget);

				// dont call reset() here, as we dont want to pre-init controls
				this.vars.data = {
					TYPE: 'A',
					RECEPIENT_TYPE: 'R',
					REMIND_DATE: '',
					TRANSPORT: 'J'
				};
				this.vars.deadLine = 0;
				this.vars.formatValue = BX.date.convertBitrixFormat(BX.message('FORMAT_DATETIME'));
			},

			load: function(data)
			{
				if(!BX.type.isNotEmptyString(data.REMIND_DATE))
				{
					return;
				}

				this.setType(data.TYPE);
				this.setRecipientType(data.RECEPIENT_TYPE);
				this.setTransportType(data.TRANSPORT);

				if(data.TYPE == this.REMINDER_TYPE_DEADLINE)
				{
					// need to calc time unit and count here...
					var diff = this.parent().getDateDiff(data.REMIND_DATE, this.vars.deadLine);
					this.setUnit(diff.unit);
					this.setMultiplier(diff.diff);
				}
				else
				{
					this.setDate(data.REMIND_DATE, true);
				}
			},

			reset: function()
			{
				this.setType('A');
				this.setRecipientType('R');
				this.setDate('', true);
				this.setTransportType('J');

				this.setMultiplier(1);
				this.setUnit(this.parent().UNIT_DAY);
			},

			open: function(node, data)
			{
				var win = this.getWindow(node); // some init here, dont relocate to end

				if(typeof data != 'undefined')
				{
					this.load(data);
				}
				else
				{
					this.reset();
				}
				this.changeCSSFlag('mode-update', typeof data != 'undefined');
				win.show();
			},
			close: function()
			{
				this.getWindow().close();
				this.reset();
			},

			getWindow: function(node)
			{
				if(typeof this.instances.window == 'undefined')
				{
					this.instances.window = new BX.PopupWindow(this.id(), node, {
						autoHide: true, // close on outer click
						closeByEsc: true,
						content: this.scope(),
						overlay: false,
						lightShadow: true, // rounded corners = off
						closeIcon: true,
						draggable: false,
						titleBar: false,
						angle: {position: 'top'},
						offsetTop: 12,
						offsetLeft: 45,
						events: {
							onPopupClose: BX.delegate(this.onFormClose, this)
							//onPopupShow: BX.delegate(this.onPopupOpen, this)
						}
					});

					this.bindControl('change-type', 'click', this.passCtx(this.onChangeType));
					this.bindControl('change-recipient', 'change', this.passCtx(this.onChangeRecipientType));
					this.bindDelegateControl('change-transport', 'click', this.passCtx(this.onChangeTransportType));
					this.bindControl('submit', 'click', BX.delegate(this.onSubmit, this));
				}

				if(typeof this.instances.datepicker == 'undefined')
				{
					var scope = this.control('date');
					if(BX.type.isElementNode(scope))
					{
						var dp = new BX.Tasks.Util.DatePicker({
							scope: scope,
							defaultTime: this.optionP('auxData').COMPANY_WORKTIME.HOURS.START
						});
						dp.bindEvent('change', BX.delegate(this.onChangeRemindDate, this));
						dp.bindEvent('open', BX.delegate(this.closeTypeSubWindow, this));

						this.instances.datepicker = dp;
					}
				}

				if(BX.type.isElementNode(node))
				{
					this.instances.window.setBindElement(node);
				}

				return this.instances.window;
			},

			setDeadLineStamp: function(stamp)
			{
				this.vars.deadLine = stamp;
				this.toggleDeadLineMode();
			},

			onChangeRemindDate: function(stamp, string)
			{
				this.setDate(string, false);
			},

			setMultiplier: function(value)
			{
				this.control('time-multiplier').value = value;
			},

			setUnit: function(value)
			{
				this.control('time-unit').value = value;
			},

			setDate: function(value, setPicker)
			{
				if(typeof value == 'undefined')
				{
					return;
				}

				this.vars.data.REMIND_DATE = value;
				if(setPicker)
				{
					this.instances.datepicker.setValue(value);
				}
			},

			setType: function(type)
			{
				if(!type)
				{
					return;
				}

				this.vars.data.TYPE = type;
				this.setCSSMode('type', type.toLowerCase());
			},

			setRecipientType: function(type)
			{
				if(!type)
				{
					return;
				}

				this.vars.data.RECEPIENT_TYPE = type;
				this.control('change-recipient').value = type;
			},

			setTransportType: function(type)
			{
				if(!type)
				{
					return;
				}

				this.vars.data.TRANSPORT = type;
				this.setCSSMode('transport', type.toLowerCase());
			},

			toggleDeadLineMode: function()
			{
				var noDeadline = this.vars.deadLine <= 0;

				if (noDeadline)
				{
					// switch to other mode
					if(this.vars.data.TYPE == this.REMINDER_TYPE_DEADLINE)
					{
						this.setType(this.REMINDER_TYPE_COMMON);
						this.setDate(0, '');
					}
				}

				this.changeCSSFlag('no-deadline', noDeadline);
			},

			getDeadLineOffset: function()
			{
				var mp = parseInt(this.control('time-multiplier').value);
				if(isNaN(mp))
				{
					mp = 0;
				}
				var seconds = this.control('time-unit').value == 'D' ? 86400 : 3600;

				return this.parent().dateStampToString(this.vars.deadLine - mp*seconds, this.vars.formatValue);
			},

			onSubmit: function()
			{
				if(this.vars.data.TYPE == this.REMINDER_TYPE_DEADLINE)
				{
					// calc deadline now
					this.vars.data.REMIND_DATE = this.getDeadLineOffset();
				}

				if(this.vars.data.REMIND_DATE.length == '')
				{
					return;
				}

				this.fireEvent('save', [BX.clone(this.vars.data)]);
				this.close();
			},

			onFormClose: function()
			{
				// close child windows
				this.closeTypeSubWindow();
				this.closeCalendarSubWindow();
			},

			closeTypeSubWindow: function()
			{
				if(typeof this.instances.typeWindow != 'undefined' && this.instances.typeWindow != null)
				{
					this.instances.typeWindow.popupWindow.close();
				}
			},

			closeCalendarSubWindow: function()
			{
				if(typeof this.instances.datepicker != 'undefined' && this.instances.datepicker != null)
				{
					this.instances.datepicker.closeCalendar();
				}
			},

			onChangeTransportType: function(node)
			{
				var type = BX.data(node, 'transport');
				if(typeof type != 'undefined' && type != null)
				{
					this.setTransportType(type);
				}
			},

			onChangeRecipientType: function(node)
			{
				this.setRecipientType(node.value);
			},

			onChangeType: function(node)
			{
				var self = this;

				var menu = [
					{
						text: BX.message("TASKS_TTDP_TEMPLATE_REMINDER_TYPE_A"),
						title: BX.message("TASKS_TTDP_TEMPLATE_REMINDER_TYPE_A_EX"),
						className: "menu-popup-no-icon",
						onclick: function() {
							self.setType('A');
							this.popupWindow.close();
						}
					},
					{
						text: BX.message("TASKS_TTDP_TEMPLATE_REMINDER_TYPE_D"),
						title: BX.message("TASKS_TTDP_TEMPLATE_REMINDER_TYPE_D_EX"),
						className: "menu-popup-no-icon",
						onclick: function() {
							self.setType('D');
							this.popupWindow.close();
						}
					}
				];

				var menuId = this.id()+'-form-transport';
				BX.PopupMenu.show(menuId, node, menu, {offsetTop : 0, events: {
					onPopupShow: BX.delegate(this.closeCalendarSubWindow, this)
				}});
				this.instances.typeWindow = BX.PopupMenu.getMenuById(menuId);
			}
		}
	});

})();