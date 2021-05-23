;(function ()
{
	'use strict';

	var namespace = BX.namespace('BX.Salescenter.DeliveryInstallation');
	if (namespace.Wizard)
	{
		return;
	}

	/**
	 * Wizard.
	 *
	 */
	function Wizard()
	{
	}

	Wizard.prototype.init = function (params)
	{
		this.id = params.id;
		this.code = params.code;
		this.form = BX(params.formId);
		this.saveButton = BX(params.saveButtonId);
		this.confirmDeleteMessage = params.confirmDeleteMessage;
		this.errorMessageNode = BX(params.errorMessageId);

		this.buttonWaitClass = 'ui-btn-wait';

		BX.bind(this.saveButton, 'click', this.onSave.bind(this));
		BX.bind(this.form, 'submit', this.onSubmit.bind(this));
	};
	Wizard.prototype.onSave = function ()
	{
		if (BX.hasClass(this.saveButton, this.buttonWaitClass))
		{
			BX.removeClass(this.saveButton, this.buttonWaitClass);
		}
	};
	Wizard.prototype.delete = function (event)
	{
		event.preventDefault();

		let deleteButton = event.target;

		if(this.id > 0 && confirm(this.confirmDeleteMessage))
		{
			BX.ajax.runComponentAction(
				'bitrix:salescenter.delivery.wizard',
				'delete',
				{
					data: {id: this.id, code: this.code},
				}
			).then(
				function() {
					deleteButton.classList.remove('ui-btn-wait');
					BX.SidePanel.Instance.close();
				}.bind(this),
				function (result) {
					deleteButton.classList.remove('ui-btn-wait');

					this.showError(result.errors);
				}.bind(this)
			);
		}
		else
		{
			setTimeout(() => BX.removeClass(deleteButton, 'ui-btn-wait'), 100);
		}
	};
	Wizard.prototype.onSubmit = function (event)
	{
		let settings = {};
		let formData = new FormData(this.form);
		for(let pair of formData.entries())
		{
			settings[pair[0]] = pair[1];
		}

		this.saveButton.classList.add('ui-btn-wait');

		let finallyCallback = () => {this.saveButton.classList.remove('ui-btn-wait');};

		let action = formData.has('id') ? 'update' : 'install';

		BX.ajax.runComponentAction(
			'bitrix:salescenter.delivery.wizard',
			action,
			{
				json: settings
			}
		).then((response) =>
		{
			finallyCallback();
			BX.SidePanel.Instance.close();
		}).catch((result) => {
			finallyCallback();
			this.showError(result.errors);
		});

		event.preventDefault();
	};
	Wizard.prototype.showError = function (errors)
	{
		let text = '';

		errors.forEach(function (error) {
			text += error.message + '<br>';
		});

		if(this.errorMessageNode && text)
		{
			this.errorMessageNode.parentNode.style.display = 'block';
			this.errorMessageNode.innerHTML = text;
		}
	}

	namespace.Wizard = new Wizard();
})();