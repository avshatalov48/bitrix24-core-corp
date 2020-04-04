BX.ready(function()
{
	/**
	 * Event on app install.
	 */
	BX.addCustomEvent(
		window, 
		"Rest:AppLayout:ApplicationInstall", 
		function(installed)
		{
			if (installed)
			{
				//
			}
		}
	);

	/**
	 * For open app pages in slider.
	 */
	if (
		typeof BX.rest !== "undefined" &&
		typeof BX.rest.Marketplace !== "undefined"
	)
	{
		BX.rest.Marketplace.bindPageAnchors({});
	}

	/**
	 * On required links click.
	 */
	var onRequiredLinkClick = function(element)
	{
		var href = element.getAttribute("href");

		if (href.substr(0, 1) !== "#")
		{
			window.open(href, "_top");
		}

		var linkTpl = href.substr(1);
		var urlParams = {};
		var linkTplAnchor = '';

		if (linkTpl.indexOf('@') > 0)
		{
			linkTplAnchor = linkTpl.split('@')[1];
			linkTpl = linkTpl.split('@')[0];
		}
		linkTpl = linkTpl.toUpperCase();

		if (linkTpl === "PAGE_URL_CATALOG_EDIT")
		{
			linkTpl = "PAGE_URL_SITE_EDIT";
			urlParams.tpl = "catalog";
		}

		if (
			typeof landingParams[linkTpl] !== "undefined" &&
			typeof BX.SidePanel !== "undefined"
		)
		{
			BX.SidePanel.Instance.open(
				BX.util.add_url_param(
					landingParams[linkTpl],
					urlParams
				) +
				(linkTplAnchor ? '#' + linkTplAnchor : ''),
				{
					allowChangeHistory: false
				}
			);
		}
	};

	BX.addCustomEvent("BX.Landing.Block:init", function(event)
	{
		if (event.data.requiredUserActionIsShown)
		{
			BX.bind(event.data.button, "click", function()
			{
				onRequiredLinkClick(this);
			});
		}
	});

	var requiredLinks = [].slice.call(document.querySelectorAll(".landing-required-link"));
	requiredLinks.forEach(function(element, index)
	{
		BX.bind(element, "click", function()
		{
			onRequiredLinkClick(this);
		});
	});
});

var landingAlertMessage = function landingAlertMessage(errorText, payment)
{
	if (
		payment === true &&
		typeof BX.Landing.PaymentAlertShow !== 'undefined'
	)
	{
		BX.Landing.PaymentAlertShow({
			message: errorText
		});
	}
	else
	{
		var msg = BX.Landing.UI.Tool.ActionDialog.getInstance();
		msg.show({
			content: errorText,
			confirm: 'OK',
			contentColor: 'grey'
		});
	}
}