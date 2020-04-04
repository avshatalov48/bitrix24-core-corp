(function(){
	if (!BX.Tasks)
		BX.Tasks = {};

	if (BX.Tasks.ListControlsNS)
		return;

	BX.Tasks.ListControlsNS = {
		ready : false,
		init  : function() {
			this.ready = true;
		},
		menu : {
			menus : {},
			create : function(menuId){
				this.menus[menuId] = {
					items : []
				}
			},
			show : function(menuId, anchor)
			{
				if ( ! self.ready )
					return;

				if ( ! this.menus[menuId] )
					return;

				if ( ! this.menus[menuId].items.length )
					return;

				var anchorPos = BX.pos(anchor);

				BX.PopupMenu.show(
					'task-top-panel-menu' + menuId,
					anchor,
					this.menus[menuId].items,
					{
						autoHide : true,
						//"offsetLeft": -1 * anchorPos["width"],
						"offsetTop": 4,
						"events":
						{
							"onPopupClose" : function(ind){
							}
						}
					}
				);
			},
			addItem : function (menuId, title, className, href)
			{
				this.menus[menuId].items.push({
					text      : title,
					className : className,
					href      : href
				});
			},
			addDelimiter : function(menuId)
			{
				this.menus[menuId].items.push({
					delimiter : true
				});
			}
		}
	}

	var self = BX.Tasks.ListControlsNS;
})();
