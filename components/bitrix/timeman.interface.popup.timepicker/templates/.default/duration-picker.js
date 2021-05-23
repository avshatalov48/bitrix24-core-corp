;(function ()
{
	BX.namespace('BX.Timeman.Component.Popup');
	/**
	 * @param options
	 * @extends BX.Timeman.Component.BaseComponent
	 * @constructor
	 */
	BX.Timeman.Component.Popup.DurationPicker = function (options)
	{
		BX.Timeman.Component.BaseComponent.apply(this, arguments);
		this.durationPopupToggle = options.durationPopupToggle ? options.durationPopupToggle : options.selectOneByRole(options.durationPopupToggleSelector);
		this.durationInput = options.durationInput ? options.durationInput :
			(options.durationInputSelector ? options.selectOneByRole(options.durationInputSelector) : this.durationPopupToggle);
		this.addEventHandlers();
	};
	BX.Timeman.Component.Popup.DurationPicker.prototype = {
		__proto__: BX.Timeman.Component.BaseComponent.prototype,
		constructor: BX.Timeman.Component.Popup.DurationPicker,
		addEventHandlers: function ()
		{
			BX.bind(this.durationPopupToggle, 'click', BX.delegate(this.onDurationPopupToggleClick, this));
		},
		onDurationPopupToggleClick: function (event)
		{
			this.durationPopup = this.buildBreakTimePopup(event);
			var breakTimeContent = BX.create('span', {
				props: {
					className: 'timeman-schedule-form-title-break-duration-parts-wrapper'
				},
				children: [
					this.buildDurationPopupInput(BX.message('TIMEMAN_SHIFT_EDIT_POPUP_FORMAT_HOUR'), 'hour'),
					this.buildDurationPopupInput(BX.message('TIMEMAN_SHIFT_EDIT_POPUP_FORMAT_MINUTE'), 'min')
				]
			});
			this.durationPopup.setContent(breakTimeContent);

			this.durationPopup.show();
		},
		buildDurationPopupInput: function (text, type)
		{
			var initValue = '';
			var search = /([0-9].):([0-9].)/;
			if (type === 'hour')
			{
				initValue = this.durationInput.value.search(search) !== -1 ? this.durationInput.value.match(search)[1] : '00';
			}
			else
			{
				initValue = this.durationInput.value.search(search) !== -1 ? this.durationInput.value.match(search)[2] : '00';
			}
			return BX.create('span', {
				props: {
					className: ''
				},
				children: [
					BX.create('input', {
						attrs: {
							value: initValue
						},
						props: {
							className: 'timeman-schedule-form-title-input' +
								' timeman-schedule-form-title-input-break-duration-part',
							type: 'number'
						},
						dataset: {type: type},
						events: {
							input: BX.proxy(this.onDurationUpdate, this),
							change: BX.proxy(this.onDurationUpdate, this)
						}
					}),
					BX.create('span', {
						text: text
					})
				]
			});
		},
		onDurationUpdate: function (e)
		{
			var maxValue = e.currentTarget.dataset.type === 'hour' ? 24 : 59;
			if (!e.currentTarget.value ||
				(parseInt(e.currentTarget.value) < 0 || parseInt(e.currentTarget.value) > maxValue)
			)
			{
				e.currentTarget.value = '00';
			}
			if (e.currentTarget.value.length > 2)
			{
				if (e.currentTarget.value.length === 3)
				{
					e.currentTarget.value = e.currentTarget.value.slice(1);
				}
				else
				{
					e.currentTarget.value = '00';
				}
			}
			e.currentTarget.value = e.currentTarget.value.padStart(2, '0');
			var search = /([0-9-].):([0-9-].)/g;
			var newVal = e.currentTarget.value.padStart(2, '0');
			if (e.currentTarget.dataset.type === 'hour')
			{
				newVal = newVal + ":$2";
			}
			else
			{
				newVal = "$1:" + newVal;
			}
			this.durationInput.value = this.durationInput.value.replace(search, newVal);
			this.durationInput.value = this.durationInput.value.replace(/--/, '00');
			if (this.durationPopupToggle && this.durationPopupToggle.textContent)
			{
				this.durationPopupToggle.textContent = this.durationInput.value;
			}

			var event = new Event('updateValue');
			this.durationInput.dispatchEvent(event);
		},
		buildBreakTimePopup: function (event)
		{
			return new BX.PopupWindow('timeman-break-time-' + Math.random(), event.currentTarget, {
				titleBar: BX.message('TIMEMAN_SHIFT_EDIT_POPUP_PICK_TIME_TITLE'),
				autoHide: true,
				bindElement: event.currentTarget,
				closeByEsc: true,
				angle: true,
				contentColor: 'white',
				contentNoPaddings: true,
				buttons: [
					new BX.PopupWindowButton({
						text: BX.message('TIMEMAN_SHIFT_EDIT_BTN_SET_TITLE'),
						className: 'popup-window-button-blue',
						events: {
							click: BX.delegate(function ()
							{
								this.durationPopup.close();
							}, this)
						}
					})
				]
			});
		}
	};
})();