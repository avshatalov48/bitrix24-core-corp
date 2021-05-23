(function () {

	var WebForm = BX.namespace('BX.Crm.WebForm');

	function Design()
	{

	}
	Design.prototype = {
		init: function (options)
		{
			this.context = BX(options.containerId);
			this.themes = options.themes;

			this.picker = new BX.ColorPicker({
				'popupOptions': {
					'offsetLeft': 15,
					'offsetTop': 5
				}
			});

			this.themeControl = this.context.querySelector('[name="design-virtual-theme"]');
			this.modeControls = BX.convert.nodeListToArray(
				this.context.querySelectorAll('[name="design-virtual-mode"]')
			);
			BX.bind(this.themeControl, 'change', this.applyTheme.bind(this));
			this.modeControls.forEach(function (modeControl) {
				BX.bind(modeControl, 'change', this.applyTheme.bind(this));
			}, this);

			this.colorControls = [];
			this.controls = BX.convert.nodeListToArray(
				this.context.querySelectorAll('[name^="DESIGN"]')
			);
			this.controls.forEach(function (element) {
				if (element.type === 'hidden')
				{
					if (element.dataset.color)
					{
						this.colorControls.push(new ColorControl({
							node: element,
							picker: this.picker,
							handler: this.onChange.bind(this),
							defaultColor: function () {
								return this.getTheme().color[element.dataset.color]
							}.bind(this)
						}));
					}
				}
				else
				{
					BX.bind(element, 'change', this.onChange.bind(this))
				}
			}, this);

			var moreFields = BX('design-more-fields');
			var moreFieldsBtn = BX('design-more-fields-btn');
			BX.bind(moreFieldsBtn, 'click', function () {
				var visible = moreFields.style.display !== 'none';
				moreFields.style.display = visible ? 'none' : '';
				//moreFieldsBtn.style.display = !visible ? 'none' : '';
			});

			this.modifyB24Form();
		},
		modifyB24Form: function ()
		{
			if (window.b24form && window.b24form.App)
			{
				if (window.b24form.App.list()[0])
				{
					window.b24form.App.list()[0].provider.submit = function (form) {
						form.loading = false;
						return new Promise(function () {

						});
					};
					return;
				}
			}

			setTimeout(this.modifyB24Form.bind(this), 50);
		},
		getMode: function ()
		{
			return this.context.querySelector('[name="design-virtual-mode"]:checked').value;
		},
		getThemeCode: function ()
		{
			return this.themeControl.value + '-' + (this.getMode() === 'Y' ? 'dark' : 'light');
		},
		getTheme: function ()
		{
			return this.themes[this.getThemeCode()];
		},
		applyTheme: function ()
		{
			this.context.querySelector('[name="DESIGN[theme]"]').value = this.getThemeCode();
			this.applyThemeOptions(this.getTheme());
			this.colorControls.forEach(function (colorControl) {
				colorControl.reload();
			});
			this.onChange();
		},
		applyThemeOptions: function (options, section)
		{
			for (var key in options)
			{
				if (!options.hasOwnProperty(key))
				{
					continue;
				}

				var value = options[key];
				if (typeof value === 'object')
				{
					this.applyThemeOptions(value, key);
				}
				else
				{
					var name = 'DESIGN[' + (section ? section + '][' : '') + key + ']';
					this.controls.filter(function (control) {
						return control.name === name;
					}, this).forEach(function (control) {
						if (control.type === 'radio' || control.type === 'checkbox')
						{
							control.checked = control.value === value;
						}
						else
						{
							control.value = value;
						}
					}, this);
				}
			}
		},
		onChange: function ()
		{
			var design = this.controls.reduce(this.accumulateElementValue.bind(this), {});
			console.log('design', design);
			b24form.App.list()[0].adjust({design: design});
		},
		accumulateElementValue: function (design, element)
		{
			var value = element.value;
			if (element.type === 'radio' || element.type === 'checkbox')
			{
				if (element.value === 'Y')
				{
					value = element.checked ? 'Y' : 'N';
				}
				else if (!element.checked)
				{
					return design;
				}
			}

			var name = element.name
				.replace('DESIGN', '')
				.replace('][', '|')
				.replace(/[\]\[]/g, '')
				.split('|');
			var group = name[1];
			name = name[0];

			value = value === 'Y'
				? true
				: (value === 'N' ? false : value);

			if (group)
			{
				if (name === 'color' && !value)
				{
					return design;
				}
				design[name] = design[name] ? design[name] : {};
				design[name][group] = value;
			}
			else
			{
				design[name] = value;
			}

			return design;
		}
	};

	function ColorControl (options)
	{
		this.node = options.node;
		this.picker = options.picker;
		this.handler = options.handler;
		this.defaultColor = options.defaultColor;

		this.circleNode = this.node.parentElement.querySelector('[data-color-circle]');
		this.opacityNode = this.node.parentElement.querySelector('[data-color-opacity]');


		BX.bind(this.circleNode, 'click', this.showPicker.bind(this));

		if (this.opacityNode)
		{
			BX.bind(this.opacityNode, 'change', function(){
				this.applyColor();
				this.handler();
			}.bind(this));

			if (this.opacityNode.children.length === 0)
			{
				for (var i = 0; i <= 100; i++)
				{
					var selectOption = document.createElement('option');
					selectOption.value = i / 100;
					selectOption.textContent = i + '%';
					this.opacityNode.appendChild(selectOption);
				}
			}
		}

		this.reload();
	}
	ColorControl.prototype = {
		showPicker: function ()
		{
			this.picker.close();
			this.picker.open({
				defaultColor: this.defaultColor
					? this.defaultColor()
					: '',
				allowCustomColor: true,
				bindElement: this.circleNode,
				onColorSelected: this.onColorSelected.bind(this)
			});
		},
		onColorSelected: function (color)
		{
			this.applyColor(color);
			this.handler();
		},
		reload: function ()
		{
			this.initialized = false;
			this.applyColor();
			this.initialized = true;
		},
		applyColor: function (color)
		{
			var element = this.node;
			var circleNode = this.circleNode;
			var opacityNode = this.opacityNode;

			var parts = this.parseHex(color || element.value);
			if (opacityNode && this.initialized)
			{
				parts[3] = opacityNode.value;
			}

			if (opacityNode)
			{
				opacityNode.value = parts[3];
			}

			circleNode.style.background = this.toRgba(parts);
			element.value = this.toHex(parts);
		},
		parseHex: function  (hex)
		{
			hex = this.fillHex(hex);
			var parts = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})?$/i.exec(hex);
			if (!parts)
			{
				parts = [0,0,0,1];
			}
			else
			{
				parts = [
					parseInt(parts[1], 16),
					parseInt(parts[2], 16),
					parseInt(parts[3], 16),
					parseInt(100 * (parseInt(parts[4] || 'ff', 16) / 255)) / 100,
				];
			}

			return parts;
		},
		toRgba: function  (r,g,b,a)
		{
			var args = arguments.length === 1
				? arguments[0]
				: [].slice.call(arguments);

			return 'rgba(' + args.join(', ') + ')';
		},
		toHex: function  (r,g,b,a)
		{
			var args = arguments.length === 1
				? arguments[0]
				: [].slice.call(arguments);

			args[3] = typeof args[3] === 'undefined' ? 1 : args[3];
			args[3] = parseInt(255 * args[3]);

			return '#' + args.map(function (part) {
				part = part.toString(16);
				return part.length === 1 ? '0' + part : part;
			}).join('');
		},
		hexToRgba: function  (hex)
		{
			return 'rgba(' + this.parseHex(hex).join(', ') + ')';
		},
		fillHex: function  (hex, fillAlpha)
		{
			if (hex.length === 4 || (fillAlpha && hex.length === 5))
			{
				hex = hex.replace(/([a-f0-9])/gi, "$1$1");
			}

			if (fillAlpha && hex.length === 7)
			{
				hex += 'ff';
			}

			return hex;
		},
	};

	WebForm.Design = new Design();

})();