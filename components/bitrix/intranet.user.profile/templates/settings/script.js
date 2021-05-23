;(function ()
{
	var namespace = BX.namespace('BX.Intranet.UserProfile');
	if (namespace.Settings)
	{
		return;
	}

	BX.Intranet.UserProfile.Settings = {
		init: function (params)
		{
			this.signedParameters = params.signedParameters;
			this.componentName = params.componentName;

			var closeBtn = document.querySelector("[data-role='fieldsCloseBtn']");
			if (BX.type.isDomNode(closeBtn))
			{
				BX.bind(closeBtn, "click", function () {
					this.closeSlider();
				}.bind(this));
			}

			var saveBtn = document.querySelector("[data-role='fieldsSaveBtn']");
			if (BX.type.isDomNode(saveBtn))
			{
				BX.bind(saveBtn, "click", function () {
					this.saveSettings(saveBtn);
				}.bind(this));
			}
		},

		saveSettings: function (button)
		{
			BX.addClass(button, "ui-btn-wait");

			var form = document.forms["profileFieldsSettingsForm"];
			var fieldsView = [];
			var fieldsEdit = [];
			if (form)
			{
				var fieldsViewNode = form.querySelector("[data-name='fieldsView']");
				if (BX.type.isDomNode(fieldsViewNode))
				{
					fieldsView = JSON.parse(fieldsViewNode.getAttribute("data-value"));
				}

				var fieldsEditNode = form.querySelector("[data-name='fieldsEdit']");
				if (BX.type.isDomNode(fieldsEditNode))
				{
					fieldsEdit = JSON.parse(fieldsEditNode.getAttribute("data-value"));
				}
			}

			BX.ajax.runComponentAction(this.componentName, "fieldsSettings", {
				signedParameters: this.signedParameters,
				mode: 'ajax',
				data: {
					fieldsView: fieldsView,
					fieldsEdit: fieldsEdit
				}
			}).then(function (response) {
				if (response.data === true)
				{
					location.reload();
				}
				else
				{
					BX.removeClass(button, "ui-btn-wait");
					this.closeSlider();
				}

			}.bind(this), function (response) {
				BX.removeClass(button, "ui-btn-wait");
				this.closeSlider();
			}.bind(this));
		},

		closeSlider: function ()
		{
			BX.SidePanel.Instance.close();
		}
	};
})();