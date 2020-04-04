;(function() {
	var BX = window.BX, currentList;
	BX.namespace("BX.Mobile.Tasks");

	BX.Mobile.Tasks.go = function(node) {
		window.BXMobileApp.PageManager.loadPageUnique({url : BX.message("TASK_PATH_TO_USER_PROFILE").replace("#USER_ID#", node.getAttribute("bx-user_id")), bx24ModernStyle: true});
	};

}());