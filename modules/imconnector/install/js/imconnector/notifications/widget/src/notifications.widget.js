;(function()
{
	if(!BX)
	{
		window.BX = {};
	}
	BX.NotificationsWidget = function(options)
	{
		this.url = options.url;
		this.onclick = options.onclick;

		this.messages = options.messages;
		this.disclaimerUrl = options.disclaimerUrl;
		this.qrCode = null;

		this.elements = {
			overlay: null,
			root: null,
			qrCode: null,
		}
	}

	BX.NotificationsWidget.prototype = {
		show: function ()
		{
			if (isMobile())
			{
				return;
			}

			this.elements.overlay = this.render();
			document.body.appendChild(this.elements.overlay);
		},

		close: function()
		{
			if (this.elements.overlay && this.elements.overlay.parentElement)
			{
				this.elements.overlay.parentElement.removeChild(this.elements.overlay);
			}
		},

		render: function()
		{
			return el('div', {
				className: 'notifications-widget-overlay',
				events: {
					click: function(event) {
						if (event.target === this.elements.overlay)
						{
							this.close();
						}
					}.bind(this)
				},
				children: [
					this.elements.root = this.renderFirstScreen()
				]
			})
		},

		renderFirstScreen: function()
		{
			return el('div', {
				className: 'notifications-widget-root',
				children: [
					el('div', {
						className: 'notifications-widget-title',
						text: this.messages['IMCONNECTOR_NOTIFICATIONS_WIDGET_SELECT_COMMUNICATION_WAY']
					}),
					el('div', {
						className: 'notifications-widget-goto-block',
						children: [
							el('span', {
								className: 'notifications-widget-goto-label',
								text: this.messages['IMCONNECTOR_NOTIFICATIONS_WIDGET_OPEN_HERE']
							}),
							el('a', {
								attrs: {
									href: this.url,
									onclick: this.onclick,
									target: '_blank',
								},
								children: [
									el('button', {
										className: 'notifications-widget-goto-button',
										text: this.messages['IMCONNECTOR_NOTIFICATIONS_WIDGET_GOTO'],
									})
								]
							})
						]
					}),
					el('div', {
						className: 'notifications-widget-goto-block',
						children: [
							el('span', {
								className: 'notifications-widget-goto-label',
								text: this.messages['IMCONNECTOR_NOTIFICATIONS_WIDGET_OPEN_MOBILE']
							}),
							el('button', {
								className: 'notifications-widget-goto-button',
								text: this.messages['IMCONNECTOR_NOTIFICATIONS_WIDGET_GOTO'],
								events: {
									click: this.showSecondPage.bind(this),
								}
							})
						]
					}),
				]
			})
		},

		renderQrCode: function()
		{
			var result = el('div', {
				className: 'notifications-widget-root',
				children: [
					el('div', {
						className: 'notifications-widget-title',
						text: this.messages['IMCONNECTOR_NOTIFICATIONS_WIDGET_SCAN_QR_CODE']
					}),
					this.elements.qrCode = el('div', {
						className: 'notifications-widget-qr-code',
					}),
					el('button', {
						className: 'notifications-widget-button-close',
						text: this.messages['IMCONNECTOR_NOTIFICATIONS_WIDGET_CLOSE'],
						events: {
							click: this.close.bind(this)
						}
					})
				]
			});

			this.qrCode = new QRCode(this.elements.qrCode, {
				text: this.url
			});

			return result;
		},

		showSecondPage: function()
		{
			this.elements.overlay.removeChild(this.elements.root);
			this.elements.root = this.renderQrCode();
			this.elements.overlay.appendChild(this.elements.root);
		},
	}
	function el(tagName, options)
	{
		var el = document.createElement(tagName);
		if (!options)
		{
			return el;
		}
		if (options.className)
		{
			el.className = options.className;
		}
		if (options.text)
		{
			el.innerText = options.text;
		}
		if (options.html)
		{
			el.innerHTML = options.html;
		}
		if (options.children)
		{
			for (var i = 0; i < options.children.length; i++)
			{
				el.appendChild(options.children[i]);
			}
		}
		if (options.events)
		{
			for (var eventName in options.events)
			{
				el.addEventListener(eventName, options.events[eventName])
			}
		}
		if (options.attrs)
		{
			for (var key in options.attrs)
			{
				el.setAttribute(key, options.attrs[key]);
			}
		}

		return el;
	}
	function isMobile()
	{
		var UA = navigator.userAgent.toLowerCase();
		return UA.includes('android')
			|| UA.includes('iphone;')
			|| UA.includes('ipad;')
			|| (UA.includes('macintosh') && (('ontouchstart' in window) || navigator.maxTouchPoints > 0 || navigator.msMaxTouchPoints > 0));
	}
})();

