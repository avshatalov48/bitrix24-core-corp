BX.ready(
	function() {

		var wrap = document.getElementById('fl-wrapper');
		if (wrap)
			wrap.addEventListener(/*'touchstart'*/'click', function(event) {
				event.preventDefault();
				if(event.target.getAttribute('data-removable-icon') == 'true') 
				{
					var childrenList = event.target.parentNode.parentNode.childNodes;
					BX.toggleClass(event.target, "fl-delete-column");
					for(var i=0; i<childrenList.length; i++) 
					{
						if(childrenList[i].nodeType != 3)
						{
							if(childrenList[i].getAttribute('data-removable-btn')) 
							{
								BX.toggleClass(childrenList[i], "fl-delete-btn-open");
								var button = childrenList[i];
								button.addEventListener('click', function()
								{
									if (
										button.parentNode.id.length > 0
									)
									{
										if (button.parentNode.id.indexOf('mfl_item_') === 0)
										{
											file_id = parseInt(button.parentNode.id.substr(9));
											if (file_id > 0)
												__MFLDeleteFile(file_id);
										}
										else if (button.parentNode.id.indexOf('mfl_element_') === 0)
										{
											element_id = parseInt(button.parentNode.id.substr(12));
											if (element_id > 0)
												__MFLDeleteElement(element_id);
										}										
									}
								});
							}
						}
					}
				}

			}, false);
	}
);

__MFLDeleteFile = function(file_id) {

	if (parseInt(file_id) > 0)
	{
		app.showPopupLoader({text:""});

		var BMAjaxWrapper = new MobileAjaxWrapper;
		BMAjaxWrapper.Wrap({
			'type': 'json',
			'method': 'POST',
			'url': BX.message('MFUSiteDir') + 'mobile/ajax.php',
			'data': {
				'file_id': file_id,
				'post_id': BX.message('MFUPostID'),
				'action': 'delete',
				'mobile_action': 'delete_file',
				'sessid' : BX.message('MFUSessID')
			},
			'callback': function(data) {
				app.hidePopupLoader();
				if (BX('mfl_item_' + file_id))
				{
					BX.toggleClass(BX('mfl_item_' + file_id), 'fl-block-close');
				}

				if (
					data["SUCCESS"] !== undefined
					&& data["SUCCESS"] == "Y"
					&& parseInt(data["FILE_ID"]) > 0
				)
				{
					app.onCustomEvent('onAfterMFLDeleteFile', data["FILE_ID"]);
				}
			},
			'callback_failure': function() {
				app.hidePopupLoader();
			}
		});
	}
}

__MFLDeleteElement = function(element_id) {

	if (parseInt(element_id) > 0)
	{
		app.showPopupLoader({text:""});

		var BMAjaxWrapper = new MobileAjaxWrapper;
		BMAjaxWrapper.Wrap({
			'type': 'json',
			'method': 'POST',
			'url': BX.message('MFUSiteDir') + 'mobile/ajax.php',
			'data':
			{
				'element_id': element_id,
				'post_id': BX.message('MFUPostID'),
				'action': 'delete_element',
				'mobile_action': 'delete_file_element',
				'sessid' : BX.message('MFUSessID')
			},
			'callback': function(data)
			{
				app.hidePopupLoader();
				if (BX('mfl_element_' + element_id))
				{
					BX.toggleClass(BX('mfl_element_' + element_id), 'fl-block-close');
				}

				if (
					data["SUCCESS"] !== undefined
					&& data["SUCCESS"] == "Y"
					&& parseInt(data["ELEMENT_ID"]) > 0
				)
				{
					app.onCustomEvent('onAfterMFLDeleteElement', data["ELEMENT_ID"]);
				}
			},
			'callback_failure': function() {
				app.hidePopupLoader();
			}
		});
	}
}