function FaceidTimeManStartPromo()
{
	// open howto
	BX.bind(BX('faceid-tms-howto-open'), 'click', function ()
	{
		if (BX.hasClass(this, 'b24-time-button-blue'))
		{
			BX.removeClass(this, 'b24-time-button-blue');
			BX.addClass(this, 'b24-time-button-grey');

			BX.show(BX('faceid-tms-howto'));
			BX.show(BX('faceid-tms-index'));
		}
	});

	// start index
	BX.bind(BX('faceid-tms-index-button'), 'click', function ()
	{
		if (BX.hasClass(this, 'b24-time-button-blue'))
		{
			BX.removeClass(this, 'b24-time-button-blue');
			BX.addClass(this, 'b24-time-button-grey');

			BX.addClass(BX('faceid-tms-index'), 'active');

			BX.ajax({
				url: '/bitrix/components/bitrix/faceid.timeman.start/ajax.php',
				method: 'GET',
				dataType: 'html',
				processData: true,
				start: true,
				onsuccess: function (html)
				{
					BX('faceid-tms-stepper').innerHTML = html;
				}
			});
		}
	});

	// event on index is done
	BX.addCustomEvent(window, "onStepperHasBeenFinished", function(data)
	{
		var i, stepper;

		if (data.data)
		{
			for (i in data.data)
			{
				stepper = data.data[i];
				if (stepper.class == 'Bitrix\\Faceid\\ProfilePhotoIndex')
				{
					// stop index
					BX.addClass(BX('faceid-tms-index'), 'done');
				}
			}
		}
	});

	// upgrade plan
	BX.bind(BX('faceid-tms-plan-upgrade-button'), 'click', function ()
	{
		B24.licenseInfoPopup.show('faceid-b24time-plan-upgrade-popup', BX.message('FACEID_TMS_START_PLAN_UPGRADE_TITLE'), '<span>'+BX.message('FACEID_TMS_START_PLAN_UPGRADE_TEXT')+'</span>');
	});

	// enable timeman
	BX.bind(BX('faceid-tms-enable-timeman-button'), 'click', function ()
	{
		var popupId = 'faceid-tms-enable-timeman-popup';
		var popup;

		if (BX.PopupWindowManager.isPopupExists(popupId))
		{
			popup = BX(popupId);
		}
		else
		{
			popup = BX.PopupWindowManager.create(popupId, null, {
				titleBar: BX.message("FACEID_TMS_START_ENABLE_TM_TITLE"),
				content: BX.message("FACEID_TMS_START_ENABLE_TM_TEXT"),
				width: 500,
				overlay: true,
				closeIcon: true,
				buttons: [
					new BX.PopupWindowButton({
						text: BX.message("FACEID_TMS_START_ENABLE_TM_CLOSE"),
						events: {click: function(){
							this.popupWindow.close();
						}}
					})
				]
			});
		}

		popup.show();
	});
}
