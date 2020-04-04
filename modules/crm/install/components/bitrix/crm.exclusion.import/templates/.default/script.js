;(function ()
{

	BX.namespace('BX.Crm.Exclusion');
	if (BX.Crm.Exclusion.Import)
	{
		return;
	}

	/**
	 * Importer.
	 *
	 */
	function Importer()
	{

	}
	Importer.prototype.init = function (params)
	{
		this.context = BX(params.containerId);
		this.listData = null;

		this.limit = params.limit || 500;
		this.pathToList = params.pathToList;

		this.componentName = params.componentName;
		this.signedParameters = params.signedParameters;

		this.textarea = this.getNode('text-list', this.context);
		this.process = this.getNode('process', this.context);
		this.loader = this.getNode('loader', this.context);
		this.indicator = this.getNode('indicator', this.context);

		this.initButtons();
	};
	Importer.prototype.initButtons = function ()
	{
		var buttonSave = this.getNode('panel-button-save', this.context);
		var buttonCancel = this.getNode('panel-button-cancel', this.context);

		if (buttonSave)
		{
			BX.bind(buttonSave, 'click', this.run.bind(this));
			BX.bind(buttonSave, 'click', function () {
				BX.addClass(buttonSave, 'ui-btn-wait');
				setTimeout(function () {
					buttonSave.disabled = true;
				}, 100);
			});
		}

		if (buttonCancel && BX.SidePanel.Instance.getTopSlider())
		{
			BX.bind(buttonCancel, 'click', function (e) {
				BX.SidePanel.Instance.close();
				e.preventDefault();
				e.stopPropagation();
			});
		}
	};
	Importer.prototype.getNode = function (role, context)
	{
		return context.querySelector('[data-role="' + role + '"]');
	};
	Importer.prototype.exit = function ()
	{
		if (BX.SidePanel.Instance.getTopSlider())
		{
			if (this.listData)
			{
				top.BX.onCustomEvent(
					top,
					'BX.Crm.Exclusion.Import::loaded',
					[this.listData]
				);
			}
			BX.SidePanel.Instance.close();
		}
		else
		{
			window.location.href = this.pathToList;
		}
	};
	Importer.prototype.run = function ()
	{
		var loader = this.loader;
		loader.style.display = '';
		setTimeout(function () {
			loader.style.opacity = 1;
		}, 50);

		this.updateProcess();
		var list = this.getTextPortion();
		if (list.length === 0)
		{
			setTimeout(this.exit.bind(this), 500);
			return;
		}

		var self = this;
		BX.ajax.runComponentAction(this.componentName, 'importList', {
			mode: 'class',
			signedParameters: this.signedParameters,
			data: {
				'list': list
			}
		}).then(function (response) {
			self.listData = response.data || {};
			self.run();
		});
	};
	Importer.prototype.updateProcess = function ()
	{
		if (!this.initialValue)
		{
			this.initialValue = this.getTextLength();
		}

		var value = 100;
		if (this.initialValue)
		{
			value = (this.initialValue - this.getTextLength()) / this.initialValue;
			value = Math.round(value * 100);
		}

		this.process.textContent = value;
		this.indicator.style.width = value + '%';
	};
	Importer.prototype.getTextLength = function ()
	{
		var matches = this.textarea.value.match(/\n/g);
		return matches ? matches.length : 0;
	};
	Importer.prototype.getTextPortion = function ()
	{
		var list = [];

		var regexp = /\n/g;
		var text = this.textarea.value.trim();
		var startIndex = regexp.lastIndex;
		do
		{
			var result = regexp.exec(text);
			var lastIndex = result ? regexp.lastIndex : text.length;
			var code = text.substring(startIndex, lastIndex).trim();

			startIndex = lastIndex;
			if (code.length === 0 || code.length > 255)
			{
				if (!result)
				{
					break;
				}
				continue;
			}

			list.push(code);
			if (list.length >= this.limit)
			{
				break;
			}
		} while (result);

		this.textarea.value = !code ? '' : this.textarea.value.trim().substring(lastIndex);

		return list;
	};


	BX.Crm.Exclusion.Import = new Importer();

})(window);