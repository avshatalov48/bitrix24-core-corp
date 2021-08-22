
BX.crmInvoicePaymentClient = (function()
{
	var classDescription = function(params)
	{
		this.nameValue = params.nameValue || "buyMoney";
		this.ajaxUrl = params.url + "?hash=" + params.hash;
		this.hash = params.hash || {};
		this.returnUrl = params.returnUrl || "";
		this.wrapperId = params.wrapperId || "";
		this.templateBill = params.templateBill || "";
		this.templateFolder = params.templateFolder;
		this.accountNumber = params.accountNumber;
		this.pdfButton = document.getElementsByClassName('crm-invoice-payment-button-download')[0];

		this.wrapper = document.getElementById('crm-invoice-payment-client-wrapper');
		this.templateBlock = this.wrapper.getElementsByClassName('crm-invoice-payment-client-template')[0];
		this.paySystemsContainer = this.wrapper.getElementsByClassName('crm-invoice-payment-system-array')[0];
		this.paySystemsTemplate = this.wrapper.getElementsByClassName('crm-invoice-payment-system-template')[0];
		this.switcher = this.wrapper.getElementsByClassName('crm-invoice-payment-system-return-list')[0];
		this.frame = document.getElementById('crm-invoice-payment-template-frame');
		this.useFrame = params.useFrame === 'Y';

		if (this.useFrame)
		{
			var base = document.createElement('base');
			base.href = window.location.href;
			this.frame.contentDocument.open();
			this.frame.contentDocument.close();
			this.frame.contentDocument.head.appendChild(base);
			this.frame.contentDocument.body.innerHTML = this.templateBill;
			BX.html(this.frame.contentDocument.body, this.templateBill).then(BX.delegate(this.resizeForm, this));
			this.frame.contentDocument.body.style.overflow = 'hidden';
		}

		BX.ready(BX.proxy(this.init, this));
	};

	classDescription.prototype.init = function()
	{
		var paySystemNames = this.wrapper.getElementsByClassName('crm-invoice-payment-system-name');
		if (paySystemNames[0] !== undefined)
		{
			Array.prototype.forEach.call(paySystemNames, function(current)
			{
				if (current.innerText.length > 28)
				{
					current.innerHTML = current.innerText.substr(0,25) + "...";
				}
			});
		}
		BX.bindDelegate(this.paySystemsContainer, 'click', { 'className': 'crm-invoice-payment-system-image-block'}, BX.proxy(
			function(event)
			{
				var targetInput = event.target.getElementsByTagName('input')[0];
				if (targetInput === undefined)
				{
					targetInput = event.target.parentNode.getElementsByTagName('input')[0];
				}
				BX.ajax(
					{
						method: 'POST',
						dataType: 'html',
						url: this.ajaxUrl,
						data:
						{
							sessid: BX.bitrix_sessid(),
							paySystemId: targetInput.value,
							accountNumber: this.accountNumber,
							hash: this.hash,
							returnUrl: this.returnUrl,
						},
						onsuccess: BX.proxy(function(result)
						{
							this.paySystemsTemplate.innerHTML = result;
							this.paySystemsTemplate.style.display = 'block';
							this.switcher.style.display = 'block';
							this.paySystemsContainer.style.display = 'none';
						},this),
						onfailure: BX.proxy(function()
						{
							return this;
						}, this)
					}, this
				);

				return this;
			}, this)
		);

		if (this.useFrame)
		{
			window.parent.setTimeout(BX.delegate(function()
			{
				this.resizeForm();
			},this), 1000);
		}

		BX.bind(this.pdfButton,'click', BX.proxy(
			function(event)
			{
				var url = this.pdfButton.href;
				BX.ajax({
					'method': 'GET',
					'url': url,
					'onsuccess': function(result)
					{
						if (result)
						{
							jsUtils.Redirect(null, url);
						}
						else
						{
							window.location.href = "";
						}
					}
				});

				event.preventDefault();
			}
			, this)
		);

		BX.bind(this.switcher,'click', BX.proxy(
			function()
			{
				this.paySystemsContainer.style.display = 'block';
				this.switcher.style.display = 'none';
				this.paySystemsTemplate.style.display = 'none';
				this.paySystemsTemplate.innerHTML = null;
			}, this)
		);

		return this;
	};

	classDescription.prototype.resizeForm = function()
	{
		this.frame.height = this.frame.contentDocument.documentElement.scrollHeight;
		this.frame.width = this.frame.contentDocument.documentElement.scrollWidth;
		this.frame.contentDocument.body.style.margin = 0;
		this.frame.contentDocument.body.style.padding = 0;
	};

	return classDescription;
})();
