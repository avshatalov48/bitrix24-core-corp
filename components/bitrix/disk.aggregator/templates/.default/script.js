BX.namespace("BX.Disk");
BX.Disk.AggregatorClass = (function ()
{
	var AggregatorClass = function (parameters)
	{
		this.ajaxUrl = '/bitrix/components/bitrix/disk.aggregator/ajax.php';
	};
	AggregatorClass.prototype.showHiddenContent = function (el)
	{
		el.style.display = (el.style.display == 'none') ? 'block' : 'none';
	};

	AggregatorClass.prototype.hide = function(el)
	{
		if (!el.getAttribute('displayOld'))
		{
			el.setAttribute("displayOld", el.style.display)
		}
		el.style.display = "none"
	};

	AggregatorClass.prototype.showNetworkDriveConnect = function (params)
	{
		params = params || {};
		var link = params.link,
			showHiddenContent = this.showHiddenContent,
			hide = this.hide;
		showHiddenContent(BX('bx-disk-network-drive-full'));

		BX.Disk.modalWindow({
			modalId: 'bx-disk-show-network-drive-connect',
			title: BX.message('DISK_AGGREGATOR_TITLE_NETWORK_DRIVE'),
			contentClassName: 'tac',
			contentStyle: {
			},
			events: {
				onAfterPopupShow: function () {
					var inputLink = BX('disk-get-network-drive-link');
					BX.focus(inputLink);
					inputLink.setSelectionRange(0, inputLink.value.length)
				},
				onPopupClose: function () {
					hide(BX('bx-disk-network-drive'));
					hide(BX('bx-disk-network-drive-full'));
					document.body.appendChild(BX('bx-disk-network-drive-full'));
					this.destroy();
				}
			},
			content: [
				BX.create('label', {
					text: BX.message('DISK_AGGREGATOR_TITLE_NETWORK_DRIVE_DESCR_MODAL') + ' :',
					props: {
						className: 'bx-disk-popup-label',
						"for": 'disk-get-network-drive-link'
					}
				}),
				BX.create('input', {
					style: {
						marginTop: '10px'
					},
					props: {
						id: 'disk-get-network-drive-link',
						className: 'bx-disk-popup-input',
						type: 'text',
						value: link
					}
				}),
				BX('bx-disk-network-drive-full')
			],
			buttons: [
				new BX.PopupWindowButton({
					text: BX.message('DISK_AGGREGATOR_BTN_CLOSE'),
					events: {
						click: function () {
							BX.PopupWindowManager.getCurrentPopup().close();
						}
					}
				})
			]
		});
		if(BX('bx-disk-network-drive-secure-label'))
		{
			hide(BX.findChildByClassName(BX('bx-disk-show-network-drive-connect'), 'bx-disk-popup-label'));
			hide(BX.findChildByClassName(BX('bx-disk-show-network-drive-connect'), 'bx-disk-popup-input'));
		}
	};

	AggregatorClass.prototype.getListStorage = function (action, proxyType)
	{
		var siteId = null, siteDir = null;
		if(BX('bx-disk-da-site-id'))
		{
			siteId = BX('bx-disk-da-site-id').value;
		}
		if(BX('bx-disk-da-site-dir'))
		{
			siteDir = BX('bx-disk-da-site-dir').value;
		}
		var showHiddenContent = this.showHiddenContent,
			idDiv = 'bx-disk-'+proxyType+'-div';
		if(!BX(idDiv).getElementsByTagName('ul').length)
		{
			BX.ajax({
				method: 'POST',
				dataType: 'json',
				url: BX.Disk.addToLinkParam(this.ajaxUrl, 'action', action),
				data: {
					siteId: siteId,
					siteDir: siteDir,
					proxyType: proxyType,
					sessid: BX.bitrix_sessid()
				},
				onsuccess: function (result) {
					if(result.status == 'success')
					{
						var html = '';
						html += '<h2 class="bx-disk-aggregator-storage-title">'+BX.util.htmlspecialchars(result.title)+'</h2>';
						html += '<ul>';
						for(var k in result.listStorage)
						{
							html += '<li class="bx-disk-aggregator-list">';
							html += '<img style="vertical-align:middle" src="'+result.listStorage[k]["ICON"]+'" class="bx-disk-aggregator-icon" />';
							html += '<a class="bx-disk-aggregator-a-link" href="'+result.listStorage[k]["URL"]+'">'+BX.util.htmlspecialchars(result.listStorage[k]["TITLE"])+'</a>';
							html += '</li>';
						}
						html += '</ul>';
						BX(idDiv).innerHTML = html;
						showHiddenContent(BX(idDiv));
					}
					else
					{
						result.errors = result.errors || [{}];
						BX.Disk.showModalWithStatusAction({
							status: 'error',
							message: result.errors.pop().message
						})
					}
				}
			});
		}
		else
		{
			showHiddenContent(BX(idDiv));
		}

	};

	return AggregatorClass;
})();
