if (!BX.VoxImplant)
	BX.VoxImplant = function() {};

if (!BX.VoxImplant.sipPayments)
	BX.VoxImplant.sipPayments = function() {};

BX.VoxImplant.sipPayments.init = function()
{
	BX.VoxImplant.sipPayments.notifyButton = BX('vi_sip_notify_button');
	BX.VoxImplant.sipPayments.notifyBlock = BX('vi_sip_notify_block');

	BX.bind(BX.VoxImplant.sipPayments.notifyButton, 'click', BX.VoxImplant.sipPayments.hideNotify);
};

BX.VoxImplant.sipPayments.hideNotify = function()
{
	BX.remove(BX.VoxImplant.sipPayments.notifyBlock);

	BX.ajax({
		url: '/bitrix/components/bitrix/voximplant.sip_payments/ajax.php',
		method: 'POST',
		dataType: 'json',
		timeout: 60,
		data: {'VI_NOTICE_HIDE': 'Y', 'sessid': BX.bitrix_sessid()}
	});
};
