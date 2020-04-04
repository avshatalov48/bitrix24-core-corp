BX.namespace('Tasks.Util');

BX.Tasks.Util.DatePicker = BX.Tasks.Util.Widget.extend({
	sys: {
		code: 'datepicker'
	},
	options: {
		defaultTime: {H: 9, M: 0, S:0},
		displayFormat: 'system-long',
		valueFormat: 'system-long'
	},
	methods: {
		construct: function()
		{
			this.callConstruct(BX.Tasks.Util.Widget);

			var format = BX.message('FORMAT_DATETIME').replace(':SS', '').replace('/SS', '');
			if(this.option('displayFormat') == 'system-short')
			{
				format = BX.message('FORMAT_DATE');
			}
			this.vars.formatDisplay = BX.date.convertBitrixFormat(format);

			format = BX.message('FORMAT_DATETIME');
			if(this.option('valueFormat') == 'system-short')
			{
				format = BX.message('FORMAT_DATE');
			}
			this.vars.formatValue = BX.date.convertBitrixFormat(format);

			this.vars.fireChangeEvent = true;

			this.bindEvents();

			// ensure display is set
			this.ensureDisplaySet();
		},

		bindEvents: function()
		{
			this.bindDelegateControl('display', 'click', this.passCtx(this.onInputClicked));
			this.bindDelegateControl('clear', 'click', BX.delegate(this.clear, this));
		},

		enableChangeEvent: function()
		{
			this.vars.fireChangeEvent = true;
		},

		disableChangeEvent: function()
		{
			this.vars.fireChangeEvent = false;
		},

        // set value by string representation of a date IN SITE FORMAT
        setValue: function(value)
        {
            if(BX.type.isNotEmptyString(value))
            {
                var stamp = this.dateStringToStamp(value);
                this.setDates(
                    this.dateStampToString(stamp, this.vars.formatValue),
                    this.dateStampToString(stamp, this.vars.formatDisplay)
                );
            }
            else
            {
                this.clear();
            }
        },

        // set value by a utc timestamp
        setTimeStamp: function(stamp)
        {
            stamp = parseInt(stamp);

            var d = new Date(stamp * 1000); // localtime

            this.setDates(BX.date.format(this.vars.formatValue, d, false, true), BX.date.format(this.vars.formatDisplay, d, false, true));
        },

        // reset value
        clear: function()
        {
            this.setDates('', '');
        },

        // get value as string representation of a date IN SITE FORMAT
        getValue: function()
        {
            return this.control('value').value;
        },

        // get value as an utc timestamp
        getTimeStamp: function()
        {
            var value = this.getValue();
            if(value.toString().length > 0)
            {
                var d = BX.parseDate(value, true);
                if(d === null)
                {
                    return null;
                }
                return this.convertToSeconds(d.getTime());
            }

            return null;
        },

		onInputClicked: function(node)
		{
            // open calendar
			this.instances.calendar = BX.calendar({
				node: this.scope(), 
				form: 'task-edit-form', 
				field: node.name,
				bTime: this.option('displayFormat') == 'system-long',
				value: this.getInitialValue(),
				bHideTime: false,
				callback_after: BX.delegate(this.onTimeSelected, this)
			});

            this.fireEvent('open');
		},

        closeCalendar: function()
        {
            if(typeof this.instances.calendar != 'undefined')
            {
                this.instances.calendar.Close();
            }
        },

		onTimeSelected: function(value) // value is localtime!
		{
			var display = '';
			var vValue = '';
			if(value.toString().length > 0)
			{
				display = BX.date.format(this.vars.formatDisplay, value);
				vValue = BX.date.format(this.vars.formatValue, value);
			}

			this.setDates(vValue, display);
		},

		setDates: function(value, display)
		{
			this.control('display').value = display;
			this.control('value').value = value;

            var ts = this.getTimeStamp();

            this.setEmpty(ts === null);

			if (this.vars.fireChangeEvent)
			{
				this.fireEvent('change', [ts, value, display]);
			}
		},

		getInitialValue: function()
		{
			var node = this.control('value');

			if(!BX.type.isElementNode(node))
			{
				return '';
			}

			if(BX.type.isNotEmptyString(node.value))
			{
				return node.value;
			}
			else
			{
				var d = new Date();
				var dt = this.option('defaultTime');
				var currentTime = new Date(
					d.getFullYear(),
					d.getMonth(),
					d.getDate(),
					dt.H || 0,
					dt.M || 0,
					dt.S || 0
				);

				return BX.date.convertToUTC(currentTime);
			}
		},

		convertToSeconds: function(value)
		{
			return Math.floor((parseInt(value) / 1000));
		},

		ensureDisplaySet: function()
		{
			var value = this.control('value').value;
			var display = this.control('display').value;

            var ts = null;
			if(value.toString().length > 0 && display.toString().length == 0)
			{
				ts = this.getTimeStamp();
				if(ts !== null)
				{
                    this.setTimeStamp(ts);
				}
			}

            this.setEmpty(ts === null);
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

        setEmpty: function(way)
        {
            BX[way ? 'addClass' : 'removeClass'](this.scope(), 't-empty');
        }
	}
});