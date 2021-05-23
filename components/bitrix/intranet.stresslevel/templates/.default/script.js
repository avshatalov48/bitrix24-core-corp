;(function () {
	'use strict';

	var namespace = BX.namespace('BX.Intranet.StressLevel');
	if (namespace.Manager)
	{
		return;
	}

	namespace.EmptyManager = function(params)
	{
		this.init(params);
	};

	namespace.EmptyManager.prototype = {
		init: function(params)
		{
			this.sendAppButton = BX('intranet-stresslevel-send-app-button');
			this.sendAppPhoneInput = BX('intranet-stresslevel-instruction-input');
			this.sendAppHintNode = BX('intranet-stresslevel-instruction-apps-link');
			this.sendAppInputNode = BX('intranet-stresslevel-instruction-input');

			this.classes = {
				sendAppInputShow: 'intranet-stresslevel-instruction-apps-input-wrapper-show'
			};

			if (
				BX.type.isDomNode(this.sendAppButton)
				&& BX.type.isDomNode(this.sendAppPhoneInput)
			)
			{
				BX.bind(this.sendAppButton, 'click', function () {

					if (BX.type.isNotEmptyString(this.sendAppPhoneInput.value))
					{
						BX.addClass(this.sendAppButton, "ui-btn-wait");

						BX.ajax.runAction('intranet.controller.sms.sendsmsforapp', {
							data: {
								phone: this.sendAppPhoneInput.value
							}
						}).then(function (response) {
							BX.removeClass(this.sendAppButton, "ui-btn-wait");
							this.sendAppPhoneInput.value = '';
						}.bind(this), function (response) {
							BX.removeClass(this.sendAppButton, "ui-btn-wait");
						}.bind(this));

					}
				}.bind(this));
			}

			if (
				this.sendAppHintNode
				&& this.sendAppInputNode
			)
			{
				BX.bind(this.sendAppHintNode, 'click', function() {
					BX(this.sendAppHintNode.getAttribute('for')).classList.add(this.classes.sendAppInputShow);
					this.sendAppInputNode.focus();
				}.bind(this));
			}
		},

	}

})();