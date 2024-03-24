(function ()
	{
		var rules = rewriteRules;
		document.addEventListener('DOMContentLoaded', function ()
		{
			BX.bindDelegate(document.body, 'click', {tagName: 'A'}, function (e)
			{
				/**
				 * Handle url without domain
				 */
				let url = new URL(this.href);
				if (url.host === "" && url.protocol === "file:")
				{
					this.href = currentDomain + url.pathname + url.search + url.hash;
				}

				if (typeof BX.MobileTools !== 'undefined')
				{
					const openWidget = BX.MobileTools.resolveOpenFunction(this.href);
					if (openWidget)
					{
						openWidget();

						e.preventDefault();

						return true;
					}
				}

				/**
				 * Find mobile url
				 */
				let currentLocation = new URL(currentDomain);
				if (this.hostname == currentLocation.hostname)
				{
					let originalUrl = this.href;
					let rule = rules.find(function (rule)
					{
						var mobileLink = originalUrl.replace(rule.exp, rule.replace);
						if (mobileLink != originalUrl)
						{
							BXMobileApp.PageManager.loadPageBlank({
								url: mobileLink,
								bx24ModernStyle: rule.useNewStyle
							});

							return true;
						}
					});

					if(rule)
					{
						e.preventDefault();
					}


				}
			});
		}, false);
	}

)();




