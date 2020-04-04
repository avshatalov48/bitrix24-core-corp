BX.namespace("BX.Scheduler");

BX.Scheduler.Util = {
	getClass: function(fullClassName) {
		if (!BX.type.isNotEmptyString(fullClassName))
		{
			return null;
		}

		var classFn = null;
		var currentNamespace = window;
		var namespaces = fullClassName.split(".");
		for (var i = 0; i < namespaces.length; i++)
		{
			var namespace = namespaces[i];
			if (!currentNamespace[namespace])
			{
				return null;
			}

			currentNamespace = currentNamespace[namespace];
			classFn = currentNamespace;
		}

		return classFn;
	},

	startDrag: function(domElement, events, cursor) {
		if (!domElement)
		{
			return;
		}

		if (events)
		{
			for (var eventId in events)
			{
				BX.bind(document, eventId, events[eventId]);
			}
		}

		this.denySelection(domElement);
		domElement.style.cursor = BX.type.isString(cursor) ? cursor : "ew-resize";
	},

	stopDrag: function(domElement, events, cursor) {
		if (!domElement)
		{
			return;
		}

		if (events)
		{
			for (var eventId in events)
			{
				BX.unbind(document, eventId, events[eventId]);
			}
		}

		this.allowSelection(domElement);
		domElement.style.cursor = BX.type.isString(cursor) ? cursor : "default";
	},

	denySelection: function(domElement) {
		if (BX.type.isDomNode(domElement))
		{
			domElement.onselectstart = BX.False;
			domElement.ondragstart = BX.False;
			domElement.style.MozUserSelect = "none";
		}
	},

	allowSelection: function(domElement) {
		if (BX.type.isDomNode(domElement))
		{
			domElement.onselectstart = null;
			domElement.ondragstart = null;
			domElement.style.MozUserSelect = "";
		}
	},

	isMSBrowser: function() {
		return window.navigator.userAgent.match(/(Trident\/|MSIE|Edge\/)/) !== null;
	}
};