BX.namespace("BX.Disk");
BX.Disk.CommentsAttachedObjectClass = (function ()
{
	var CommentsAttachedObjectClass = function (parameters)
	{
		this.containerId = parameters.containerId;
		this.menuButtonsByFile = parameters.menuButtonsByFile || {};
		this.selectorToFindMoreLinks = parameters.selectorToFindMoreLinks;
		this.container = BX(this.containerId);

		this.setEvents();
	};

	CommentsAttachedObjectClass.prototype.setEvents = function ()
	{
		if(!BX.Disk.isEmptyObject(this.selectorToFindMoreLinks))
		{
			BX.bindDelegate(this.container, "click", this.selectorToFindMoreLinks, BX.delegate(this.openMenu, this));
		}
	};

	var counterMenu = 0;
	CommentsAttachedObjectClass.prototype.openMenu = function (event)
	{
		var target = event.srcElement || event.target;
		var fileId = target.getAttribute('bx-attach-id');
		if(!fileId)
		{
			return;
		}
		counterMenu++;

		BX.PopupMenu.show(
			'bx-viewer-wd-popup' + fileId + '_' + counterMenu,
			BX(target),
			this.menuButtonsByFile[fileId],
			{
				angle:
				{
					position: 'top',
					offset: 25
				},
				autoHide: true
			}
		);

		return BX.PreventDefault(event);
	};

	return CommentsAttachedObjectClass;
})();