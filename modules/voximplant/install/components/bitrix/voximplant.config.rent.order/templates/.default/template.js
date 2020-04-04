if (!BX.VoxImplant)
	BX.VoxImplant = function() {};

BX.VoxImplant.rentPhoneOrder = function() {};

BX.VoxImplant.rentPhoneOrder.init = function()
{
	BX.VoxImplant.rentPhoneOrder.blockAjax = false;

	BX.VoxImplant.rentPhoneOrder.inputName = BX('vi_rent_order_name');
	BX.VoxImplant.rentPhoneOrder.inputContact = BX('vi_rent_order_contact');
	BX.VoxImplant.rentPhoneOrder.inputRegCode = BX('vi_rent_order_reg_code');
	BX.VoxImplant.rentPhoneOrder.inputPhone = BX('vi_rent_order_phone');
	BX.VoxImplant.rentPhoneOrder.inputEmail = BX('vi_rent_order_email');

	BX.ready(function(){
		BX.bind(BX('vi_rent_order'), 'click', function(e){
			BX('vi_rent_order_div').style.display = 'block';
			BX.remove(BX('vi_rent_order'));
		});

		BX.bind(BX('tel-order-form-button'), 'click', function(e)
		{
			BX.VoxImplant.rentPhoneOrder.sendForm();
			BX.PreventDefault(e);
		});
	});
};

BX.VoxImplant.rentPhoneOrder.showConfig = function(configId)
{
	BX.SidePanel.Instance.open("/telephony/edit.php?ID=" + configId, {cacheable: false});
};

BX.VoxImplant.rentPhoneOrder.sendForm = function()
{
	if (BX.VoxImplant.rentPhoneOrder.blockAjax)
		return true;

	if (
		BX.VoxImplant.rentPhoneOrder.inputName.value.length <= 0 ||
		BX.VoxImplant.rentPhoneOrder.inputContact.value.length <= 0 ||
		BX.VoxImplant.rentPhoneOrder.inputRegCode.value.length <= 0 ||
		BX.VoxImplant.rentPhoneOrder.inputPhone.value.length <= 0 ||
		BX.VoxImplant.rentPhoneOrder.inputEmail.value.length <= 0
	)
	{
		BX.Voximplant.alert(BX.message('VI_CONFIG_RENT_ORDER_ERROR'), BX.message('VI_CONFIG_RENT_ORDER_ALL_FIELD_REQUIRED'));
		return false;
	}

	BX.removeClass(BX('tel-order-form-button'), 'webform-button-create');

	BX.showWait();

	BX.VoxImplant.rentPhoneOrder.blockAjax = true;
	BX.ajax({
		url: '/bitrix/components/bitrix/voximplant.config.rent.order/ajax.php',
		method: 'POST',
		dataType: 'json',
		timeout: 60,
		data: {
			'VI_PHONE_ORDER': 'Y',
			'FORM_NAME': BX.VoxImplant.rentPhoneOrder.inputName.value,
			'FORM_CONTACT': BX.VoxImplant.rentPhoneOrder.inputContact.value,
			'FORM_REG_CODE': BX.VoxImplant.rentPhoneOrder.inputRegCode.value,
			'FORM_PHONE': BX.VoxImplant.rentPhoneOrder.inputPhone.value,
			'FORM_EMAIL': BX.VoxImplant.rentPhoneOrder.inputEmail.value,
			'VI_AJAX_CALL' : 'Y',
			'sessid': BX.bitrix_sessid()
		},
		onsuccess: BX.delegate(function(data)
		{
			BX.closeWait();
			BX.VoxImplant.rentPhoneOrder.blockAjax = false;
			if (data.ERROR == '')
			{
				BX('tel-order-form-button').style.display = 'none';
				BX('tel-order-form-success').style.display = 'inline-block';

				BX.VoxImplant.rentPhoneOrder.inputName.setAttribute('disabled', 'true');
				BX.VoxImplant.rentPhoneOrder.inputContact.setAttribute('disabled', 'true');
				BX.VoxImplant.rentPhoneOrder.inputRegCode.setAttribute('disabled', 'true');
				BX.VoxImplant.rentPhoneOrder.inputPhone.setAttribute('disabled', 'true');
				BX.VoxImplant.rentPhoneOrder.inputEmail.setAttribute('disabled', 'true');

			}
			BX.addClass(BX('tel-order-form-button'), 'webform-button-create');
		}, this),
		onfailure: function(){
			BX.closeWait();
			BX.VoxImplant.rentPhoneOrder.blockAjax = false;
		}
	});
};


BX.VoxImplant.rentPhoneOrderExtra = function() {};

BX.VoxImplant.rentPhoneOrderExtra.init = function()
{
	BX.VoxImplant.rentPhoneOrderExtra.blockAjax = false;

	BX.VoxImplant.rentPhoneOrderExtra.selectType = BX('vi_rent_order_extra_type');

	BX.ready(function(){
		BX.bind(BX('vi_rent_order_extra'), 'click', function(e){

			if (BX('vi_rent_order_extra_div').style.display == 'none')
			{
				BX.removeClass(BX(this), 'webform-button-create');
				BX('vi_rent_order_extra_div').style.display = 'block';
			}
			else
			{
				BX.addClass(BX(this), 'webform-button-create');
				BX('vi_rent_order_extra_div').style.display = 'none';
			}
			BX.PreventDefault(e);
		});

		BX.bind(BX('tel-order-extra-form-button'), 'click', function(e)
		{
			BX.VoxImplant.rentPhoneOrderExtra.sendForm();
			BX.PreventDefault(e);
		});
	});
};

BX.VoxImplant.rentPhoneOrderExtra.sendForm = function()
{
	if (BX.VoxImplant.rentPhoneOrderExtra.blockAjax)
		return true;

	BX.removeClass(BX('tel-order-extra-form-button'), 'webform-button-create');

	BX.showWait();

	BX.VoxImplant.rentPhoneOrderExtra.blockAjax = true;
	BX.ajax({
		url: '/bitrix/components/bitrix/voximplant.config.rent.order/ajax.php',
		method: 'POST',
		dataType: 'json',
		timeout: 60,
		data: {
			'VI_PHONE_ORDER_EXTRA': 'Y',
			'FORM_TYPE': BX.VoxImplant.rentPhoneOrderExtra.selectType.value,
			'VI_AJAX_CALL' : 'Y',
			'sessid': BX.bitrix_sessid()
		},
		onsuccess: BX.delegate(function(data)
		{
			BX.closeWait();
			BX.VoxImplant.rentPhoneOrderExtra.blockAjax = false;
			if (data.ERROR == '')
			{
				BX('tel-order-extra-form-button').style.display = 'none';
				BX('tel-order-extra-form-success').style.display = 'inline-block';

				BX.VoxImplant.rentPhoneOrder.rentPhoneOrderExtra.setAttribute('disabled', 'true');
				BX.VoxImplant.rentPhoneOrder.rentPhoneOrderExtra.setAttribute('disabled', 'true');
				BX.VoxImplant.rentPhoneOrder.rentPhoneOrderExtra.setAttribute('disabled', 'true');
				BX.VoxImplant.rentPhoneOrder.rentPhoneOrderExtra.setAttribute('disabled', 'true');
				BX.VoxImplant.rentPhoneOrder.rentPhoneOrderExtra.setAttribute('disabled', 'true');

			}
			BX.addClass(BX('tel-order-extra-form-button'), 'webform-button-create');
		}, this),
		onfailure: function(){
			BX.closeWait();
			BX.VoxImplant.rentPhoneOrder.blockAjax = false;
		}
	});
};