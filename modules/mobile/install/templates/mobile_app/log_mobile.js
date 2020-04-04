/* log Mobile */
(function() {

if (BX.MSL)
	return;

BX.MobileSonetLog = function () {
}

BX.MobileSonetLog.prototype.DBCheck = function(oCallback)
{
	if (app.db != undefined)
	{
		app.db.createTable({
			tableName: 'b_default',
			fields: [
				{
					name: "KEY", 
					unique: true
				},
				"VALUE"
			],
			success: function (res) { oCallback.success(); },
			fail: function (e) { oCallback.fail() }
		});
	}
	else
		return false;
};

BX.MobileSonetLog.prototype.DBDelete = function(sonetGroupID)
{
	if (parseInt(sonetGroupID) <= 0)
	{
		sonetGroupID = false;
	}

	if (app.db != undefined)
	{
		var r = false;

		this.DBCheck({
			success: function() {
				app.db.deleteRows({
					tableName: "b_default",
					filter: {
						KEY: 'post_unsent' + (sonetGroupID ? '_' + sonetGroupID : '')
					},
					success: function (res) {},
					fail: function (e) {}
				});
			},
			fail: function() {}
		});
	}
	else
		return false;
};

BX.MobileSonetLog.prototype.DBSave = function(oData, sonetGroupID)
{
	if (parseInt(sonetGroupID) <= 0)
	{
		sonetGroupID = false;
	}

	for (x in oData) {
		if (x === 'sessid')
		{
			delete oData[x];
			break;
		}
	}

	if (app.db != undefined)
	{
		this.DBCheck({
			success: function() {
				app.db.getRows({
					tableName: "b_default",
					filter: {
						KEY: 'post_unsent' + (sonetGroupID ? '_' + sonetGroupID : '')
					},
					success: function (res)
					{
						text = JSON.stringify(oData);
						if (res.items.length > 0)
						{
							app.db.updateRows({
								tableName: "b_default",
								updateFields: {
									VALUE: text
								},
								filter: {
									KEY: 'post_unsent' + (sonetGroupID ? '_' + sonetGroupID : '')
								},
								success: function (res) {},
								fail: function (e) {}
							});
						}
						else
						{
							app.db.addRow(
							{
								tableName: "b_default",
								insertFields: {
									KEY: 'post_unsent' + (sonetGroupID ? '_' + sonetGroupID : ''),
									VALUE: text
								},
								success: function (res) {},
								fail: function (e) {}
							});
						}
					},
					fail: function (e) {}
				});
			},
			fail: function() {}
		});
	}
	else
		return false;
};

BX.MobileSonetLog.prototype.DBLoad = function(oCallback, sonetGroupID)
{
	if (parseInt(sonetGroupID) <= 0)
	{
		sonetGroupID = false;
	}

	if (app.db != undefined)
	{
		this.DBCheck({
			success: function() {
				app.db.getRows({
					tableName: "b_default",
					filter: {
						KEY: 'post_unsent' + (sonetGroupID ? '_' + sonetGroupID : '')
					},
					success: function (res)
					{ 
						if (
							res.items.length > 0 
							&& res.items[0].VALUE.length > 0
						)
						{
							var obResult = JSON.parse(res.items[0].VALUE);
							if (typeof obResult == 'object')
								oCallback.onLoad(obResult);
							else
								oCallback.onEmpty();
						}
						else
							oCallback.onEmpty();
					},
					fail: function (e) { oCallback.onEmpty(); }
				});
			},
			fail: function() { oCallback.onEmpty(); }
		});
	}
	else
	{
		oCallback.onEmpty();
		return null;
	}
};

BX.MobileSonetLog.prototype.viewImageBind = function(div, isTarget)
{
	if (app.enableInVersion(6))
	{
		div = BX(div);
		if (!!div)
		{
			BX.bindDelegate(div, 'click', isTarget, function(e)
			{
				var imgNodeList = BX.findChildren(div, isTarget, true),
					imgList = [],
					currentImage = false,
					currentPreview = false;

				var arPhotos = [];
				for(var i=0; i<imgNodeList.length; i++)
				{
					currentImage = imgNodeList[i].getAttribute('data-bx-image');

					if (!BX.util.in_array(currentImage, imgList))
					{
						currentPreview = imgNodeList[i].getAttribute('data-bx-preview');
						imgList[imgList.length] = imgNodeList[i].getAttribute('data-bx-image');
						arPhotos[arPhotos.length] = {
							url: currentImage,
							preview: (typeof currentPreview != 'undefined' && currentPreview !== null && currentPreview.length > 0 ? currentPreview : ''),
							description: ''
						};
					}
				}

				var oParams = {
					photos: arPhotos
				};

				if (this.tagName.toUpperCase() == 'IMG')
				{
					currentImage = this.getAttribute('data-bx-image');
					if (
						typeof currentImage != 'undefined'
						&& currentImage.length > 0
					)
					{
						oParams.default_photo = currentImage;
					}

					currentPreview = this.getAttribute('data-bx-preview');
					if (
						typeof currentPreview != 'undefined'
						&& currentPreview !== null
						&& currentPreview.length > 0
					)
					{
						oParams.default_preview = currentPreview;
					}
				}

				BXMobileApp.UI.Photo.show(oParams);

				return BX.PreventDefault(e);
			});
		}
	}
};

BX.MSL = new BX.MobileSonetLog;
window.BX.MSL = BX.MSL;
})();