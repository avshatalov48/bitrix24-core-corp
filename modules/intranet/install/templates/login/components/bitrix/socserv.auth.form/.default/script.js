BX.ready(function(){
	BX.bind(BX("socservMoreButton"), 'click', function(){
		var popup = BX.PopupWindowManager.create("socservPopup", this, {
			autoHide: true,
			offsetLeft: 28,
			offsetTop: 0,
			overlay : false,
			draggable: {restrict:true},
			closeByEsc: true,
			angle: {offset:40},
			content: BX("moreSocServPopup")
		});

		popup .show();
	});
});