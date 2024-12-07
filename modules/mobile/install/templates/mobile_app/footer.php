<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
} ?>

<script>
	<?if ($APPLICATION->GetPageProperty("LAZY_AUTOLOAD", true) === true):?>
	document.addEventListener("deviceready", function ()
	{
		if(typeof window.BitrixMobile !== "undefined")
			BitrixMobile.LazyLoad.showImages();
	}, false);
	<?endif?>

	<?if ($APPLICATION->GetPageProperty("LAZY_AUTOSCROLL", true) === true):?>
	document.addEventListener("DOMContentLoaded", function ()
	{
		if(typeof window.BitrixMobile !== "undefined")
		{
			window.addEventListener("scroll", BitrixMobile.LazyLoad.onScroll, { passive: true });
		}
	}, false);
	<?endif?>


	document.addEventListener('DOMContentLoaded', function ()
	{
		BX.bindDelegate(document.body, 'click', {tagName: 'A'}, function (e)
		{
			if (this.hostname == document.location.hostname)
			{
				let analyticsLabel = {};
				try
				{
					analyticsLabel = JSON.parse(this.attributes['data-analytics'].value);
				}
				catch
				{}

				var func = BX.MobileTools.resolveOpenFunction(this.href, { analyticsLabel });

				if (func)
				{
					func();
					return BX.PreventDefault(e);
				}
			}
		});
	}, false);


</script>
</body>
</html>