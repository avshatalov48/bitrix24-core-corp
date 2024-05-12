;(function () {

	if (!window.BX)
	{
		window.BX = {};
	}
	if (!BX.ready)
	{
		BX.ready = function (f) {
			f();
		};
	}

	var l = document.createElement('link');
	l.href = 'http://24.solj.bx/bitrix/js/crm/site/form/layout/form/main.css';
	document.head.appendChild(l);

	var getTemplate = function ()
	{
		var colors = '' + '<option value="">[default]</option>'+
		'<option value="#add8e6">lightblue</option>'+
		'<option value="#90ee90">lightgreen</option>'+
		'<option value="#ffff00">yellow</option>'+
		'<option value="#000">black</option>'+
		'<option value="#fff">white</option>'+
		'<option value="#ff0000">red</option>'+
		'<option value="#0000ff">blue</option>'+
			'';

		return '' +
'<div class="dev-grid">' +
	'<div class="dev-item">' +
		'<div class="dev-settings">'+
		'<h2>Actions</h2>'+
			'<a href="?b24form_user=123456">Autofill link</a>'+
			'<br><br>'+

			'<select id="window_pos">'+
			'<option value="center">Center</option>'+
			'<option value="left">Left</option>'+
			'<option value="right">Right</option>'+
			'</select>'+

			'<select id="window_vert">'+
			'<option value="bottom">Bottom</option>'+
			'<option value="top">Top</option>'+
			'</select>'+

			'<select id="window_type">'+
			'<option value="panel">Panel</option>'+
			'<option value="widget">Widget</option>'+
			'<option value="popup">Popup</option>'+
			'</select>'+

			'<button type="button" id="window_btn">show!</button>'+

		'<h2>Form</h2>'+

			'<input type="checkbox" id="paging"><label for="paging">enable pages</label>'+
			'<br>'+

			'<input type="checkbox" id="required" checked><label for="required">enable required</label>'+
			'<br>'+

			'<input type="checkbox" id="big-pic" checked><label for="big-pic">big picture</label>'+
			'<br>'+

			'Currency format '+
			'<select id="currency">'+
				'<option value="$#">USD</option>'+
				'<option value="# руб.">RUB</option>'+
				'<option value="&#8364;#">EUR</option>'+
			'</select>'+
			'<br><br>'+

			'Date format <br>'+
			'<select id="date-format">'+
				'<option value="YYYY, MM/DD">1999, 3/14</option>'+
				'<option value="YYYY, MM/DD">1999, 03/14</option>'+
				'<option value="DD.MM.YYYY">14.03.1999</option>'+
				'<option value="MM/DD/YYYY">03/14/1999</option>'+
				'<option value="YYYY-MM-DD">1999-03-14</option>'+
			'</select>'+
			'<select id="time-format">'+
				'<option value="G:MI:SS T">4:33:00 pm</option>'+
				'<option value="H:MI:SS TT">04:33:00 PM</option>'+
				'<option value="GG:MI:SS">4:33:00</option>'+
				'<option value="HH:MI:SS">04:33:00</option>'+
			'</select>'+
			'<br>'+

			'<input type="checkbox" id="date-sunday-firstly" checked><label for="date-sunday-firstly">Sunday firstly</label>'+
			'<br><br>'+

			'Show fields by type '+
			'<select id="field-type">'+
				'<option value="all">All</option>'+
				'<option value="product">Products</option>'+
				'<option value="string">Strings</option>'+
				'<option value="text">Text areas</option>'+
				'<option value="list">Lists</option>'+
				'<option value="checkbox">Checkboxes</option>'+
				'<option value="file">Files</option>'+
			'</select>'+
			'<br><br>'+

		'<h2>Design</h2>'+

			'Theme '+
			'<select id="designTheme">'+
			'<option value="">[none]</option>'+
			'<option value="modern-light">Modern light</option>'+
			'<option value="modern-dark">Modern dark</option>'+
			'<option value="classic-light">Classic light</option>'+
			'<option value="classic-dark">Classic dark</option>'+
			'<option value="fun-light">Fun light</option>'+
			'<option value="fun-dark">Fun dark</option>'+
			'<option value="pixel">Pixel</option>'+
			'<option value="old">Old russian</option>'+
			'<option value="writing">Writing</option>'+
			'</select>'+
			'<br><br>'+

			'Style '+
			'<select id="designStyle">'+
			'<option value="">[none]</option>'+
			'<option value="modern">Modern</option>'+
			'</select>'+
			'<br><br>'+

			'Dark mode '+
			'<select id="designDark">'+
			'<option value="">auto</option>'+
			'<option value="y">enabled</option>'+
			'<option value="n">disabled</option>'+
			'</select>'+
			'<br><br>'+

			'<b>Bright</b>: '+
			'Bg '+
			'<select id="colorPrimary">' + colors + '</select>' +
			'   Text'+
			'<select id="colorPrimaryText">' + colors + '</select>' +
			'<br><br>'+

			'<b>Form</b>: '+
			'Bg '+
			'<select id="colorBackground">' + colors + '</select>' +
			'   Text '+
			'<select id="colorText">' + colors + '</select>' +
			'<br><br>'+

			'<b>Fields</b>: '+
			'Bg '+
			'<select id="colorFieldBackground">' + colors + '</select>' +
			'   Border '+
			'<select id="colorFieldBorder">' + colors + '</select>' +
			'<br><br>'+

			'Font '+
			'<input id="designFontFamily" placeholder="Family. Ex.: Comic Sans MS">'+
			'<input id="designFontUri" placeholder="Link. Ex.: https://example...">'+
			'<button type="button" id="designFont">apply</button>'+

		'</div>'+
	'</div>'+

	'<div class="dev-item" style="width: 100%;">'+
		'<div class="dev-form">'+
			'<div id="form1"></div>'+
		'</div>'+
	'</div>'+
'</div>';
	};

	BX.ready(function () {

		window.b24form = window.b24form || {};
		window.b24formDev = window.b24formDev || {};

		document.getElementById('dev_form').innerHTML = getTemplate();

		var devUi = {
			pagingBtn: document.getElementById('paging'),
			required: document.getElementById('required'),
			fieldType: document.getElementById('field-type'),
			currency: document.getElementById('currency'),
			dateFormat: document.getElementById('date-format'),
			timeFormat: document.getElementById('time-format'),
			dateSundayFirstly: document.getElementById('date-sunday-firstly'),
			bigPic: document.getElementById('big-pic'),
			design: {
				theme: document.getElementById('designTheme'),
				style: document.getElementById('designStyle'),
				dark: document.getElementById('designDark'),
				font: {
					btn: document.getElementById('designFont'),
					uri: document.getElementById('designFontUri'),
					family: document.getElementById('designFontFamily'),
				},
				colorPrimary: document.getElementById('colorPrimary'),
				colorPrimaryText: document.getElementById('colorPrimaryText'),
				colorText: document.getElementById('colorText'),
				colorBackground: document.getElementById('colorBackground'),
				colorFieldBorder: document.getElementById('colorFieldBorder'),
				colorFieldBackground: document.getElementById('colorFieldBackground'),
			},
			window: {
				btn: document.getElementById('window_btn'),
				pos: document.getElementById('window_type'),
				pos: document.getElementById('window_pos'),
				ver: document.getElementById('window_ver')
			}
		};

		var getOptions = function ()
		{
			return b24formDev.config.main;
		};
		var OptionsFieldsFilter = function (excludePages, fieldCount)
		{
			var bigPic = devUi.bigPic.checked;
			excludePages = excludePages || !devUi.pagingBtn.checked;
			return getOptions().fields
				.map(function (field){
					if (!devUi.required.checked)
					{
						field.required = false;
					}
					field.bigPic = bigPic;
					return field;
				})
				.filter(function (field) {
					if (excludePages && field.type === 'page')
					{
						return false;
					}

					var fType = devUi.fieldType.value;
					switch (devUi.fieldType.value)
					{
						case 'string':
							if (!['string', 'email', 'phone', 'integer'].includes(field.type))
							{
								return false;
							}
							break;
						case 'text':
						case 'file':
						case 'product':
							if (field.type !== fType)
							{
								return false;
							}
							break;
						case 'list':
							if (!['select'].includes(field.type))
							{
								return false;
							}
							break;
						case 'checkbox':
							if (!['radio', 'checkbox', 'bool'].includes(field.type))
							{
								return false;
							}
							break;
					}

					return true;
				}
			).slice(0, fieldCount || undefined);
		};

		var OptionsCurrency = function () {
			return {
				format: devUi.currency.value
			};
		};
		var OptionsDate = function () {
			var dateFormat = devUi.dateFormat.value;
			var timeFormat = devUi.timeFormat.value;
			var dateSundayFirstly = devUi.dateSundayFirstly.checked;
			return {
				sundayFirstly: dateSundayFirstly,
				dateFormat: dateFormat,
				dateTimeFormat: dateFormat + ' ' + timeFormat,
			};
		};
		var OptionsDesign = function (t) {
			var r = {color: {}};
			if (!t || t === devUi.design.theme)
			{
				r.theme = devUi.design.theme.value;
			}
			if (!t || t === devUi.design.style)
			{
				r.style = devUi.design.style.value;
			}
			if (!t || t === devUi.design.dark)
			{
				var v = devUi.design.dark.value;
				v = v === 'y' ? true : (v === 'n' ? false : null);
				r.dark = v;
			}
			if (!t || t === devUi.design.font.btn)
			{
				r.font = {
					uri: devUi.design.font.uri.value,
					family: devUi.design.font.family.value
				};
			}
			if (!t || t === devUi.design.colorPrimary)
			{
				r.color.primary = devUi.design.colorPrimary.value;
			}
			if (!t || t === devUi.design.colorPrimaryText)
			{
				r.color.primaryText = devUi.design.colorPrimaryText.value;
			}
			if (!t || t === devUi.design.colorText)
			{
				r.color.text = devUi.design.colorText.value;
			}
			if (!t || t === devUi.design.colorBackground)
			{
				r.color.background = devUi.design.colorBackground.value;
			}
			if (!t || t === devUi.design.colorFieldBorder)
			{
				r.color.fieldBorder = devUi.design.colorFieldBorder.value;
			}
			if (!t || t === devUi.design.colorFieldBackground)
			{
				r.color.fieldBackground = devUi.design.colorFieldBackground.value;
			}

			return r;
		};

		var createForm = function (view, node)
		{
			var excludePages = false;
			var fieldCount = null;
			var internalOptions = {visible: false};

			if (view)
			{
				if (view.type === 'widget')
				{
					excludePages = true;
					fieldCount = 1;
				}
				internalOptions.view = view;
			}

			if (node)
			{
				internalOptions.node = node;
			}
			internalOptions.fields = OptionsFieldsFilter(excludePages, fieldCount);
			internalOptions.currency = OptionsCurrency();
			internalOptions.date = OptionsDate();
			internalOptions.design = OptionsDesign();

			var form = b24form.App.createForm24(
				{
					id: 1,
					sec: 'xxxxxx',
					address: window.location.origin
				},
				Object.assign(
					{},
					getOptions(),
					internalOptions
				)
			);

			var handler = function () {
				form.adjust({
					date: OptionsDate(),
					currency: OptionsCurrency(),
					fields: OptionsFieldsFilter(excludePages, fieldCount),
				});
			};
			var handlerDesign = function (e) {
				form.adjust({
					design: OptionsDesign(e.target),
				});
			};
			devUi.pagingBtn.addEventListener('change', handler);
			devUi.required.addEventListener('change', handler);
			devUi.fieldType.addEventListener('change', handler);
			devUi.bigPic.addEventListener('change', handler);
			devUi.currency.addEventListener('change', handler);
			devUi.dateFormat.addEventListener('change', handler);
			devUi.timeFormat.addEventListener('change', handler);
			devUi.dateSundayFirstly.addEventListener('change', handler);

			devUi.design.theme.addEventListener('change', handlerDesign);
			devUi.design.style.addEventListener('change', handlerDesign);
			devUi.design.dark.addEventListener('change', handlerDesign);
			devUi.design.font.btn.addEventListener('click', handlerDesign);
			devUi.design.colorText.addEventListener('change', handlerDesign);
			devUi.design.colorBackground.addEventListener('change', handlerDesign);
			devUi.design.colorPrimary.addEventListener('change', handlerDesign);
			devUi.design.colorPrimaryText.addEventListener('change', handlerDesign);
			devUi.design.colorFieldBorder.addEventListener('change', handlerDesign);
			devUi.design.colorFieldBackground.addEventListener('change', handlerDesign);

			return form;
		};


		// INLINE
		createForm(null, document.getElementById('form1')).show();

		// POPUP
		var windows = {};
		devUi.window.btn.addEventListener('click', function() {
			var view = {
				'type': document.getElementById('window_type').value,
				'position': document.getElementById('window_pos').value,
				'vertical': document.getElementById('window_vert').value
			};

			if (windows[view.type])
			{
				windows[view.type].adjust({view: view});
			}
			else
			{
				windows[view.type] = createForm(view);
			}

			windows[view.type].show();
		});

	});
})();