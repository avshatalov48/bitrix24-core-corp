(function(window){
	window.listDrop = function(element)
	{
		this.element = element;
		var elementAttr = element.getAttribute('for');
		var dropList = document.getElementById(elementAttr).offsetHeight;
		var nextElHeight = element.nextElementSibling.offsetHeight;
		if (nextElHeight < 10) {
			element.nextElementSibling.style.height = dropList + "px";
			element.parentNode.className = "crm-doc-drop crm-doc-drop-open";
		} else {
			element.nextElementSibling.style.height = 0 + "px";
			element.parentNode.className = "crm-doc-drop";
		}
	};
})(window);