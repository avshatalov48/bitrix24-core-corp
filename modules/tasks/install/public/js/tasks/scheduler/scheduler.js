
BX.namespace("BX.Scheduler");

BX.Scheduler.View = (function() {

	var View = function(config) {
		this.config = config || {};

		this.timeline = new BX.Scheduler.Timeline(this.config);
		this.eventLayout = new BX.Scheduler.EventLayout(this.timeline);

		this.resourceStore = new BX.Scheduler.ResourceStore();
		this.eventStore = new BX.Scheduler.EventStore();

		this.resourceStore.setEventStore(this.eventStore);
		this.resourceStore.setView(this);

		this.eventStore.setResourceStore(this.resourceStore);
		this.eventStore.setView(this);

		this.scrollDate = null;

		BX.addCustomEvent(this.timeline, "onBeforeZoomChange", BX.proxy(this.onBeforeZoomChange, this));
		BX.addCustomEvent(this.timeline, "onZoomChange", BX.proxy(this.onZoomChange, this));
	};

	View.prototype = {

		clearAll: function() {
			this.resourceStore.clearAll();
			this.eventStore.clearAll();
		},

		/**
		 *
		 * @returns {BX.Scheduler.ResourceStore}
		 */
		getResourceStore: function() {
			return this.resourceStore;
		},

		/**
		 *
		 * @returns {BX.Scheduler.EventStore}
		 */
		getEventStore: function() {
			return this.eventStore;
		},

		render: function() {
			this.expandTimeline();

			var root = this.resourceStore.getRoot();
			var rows = this.renderRecursive(root.getChildNodes());

			this.timeline.clearItems();
			this.timeline.clearRows();

			this.timeline.appendRows(rows);

			var firstRender = !BX.type.isDomNode(this.timeline.layout.root.parentNode);
			this.timeline.draw();

			if (firstRender)
			{
				this.timeline.autoScroll();
			}
			//this.timeline.adjustChartContainer();
		},

		/**
		 *
		 * @param {BX.Scheduler.TreeNode[]} nodes
		 * @returns {BX.Scheduler.TimelineRow[]}
		 */
		renderRecursive: function(nodes) {
			var rows = [];
			for (var i = 0, l = nodes.length; i < l; i++)
			{
				var node = nodes[i];
				var resource = node.getData();
				var timelineRow = resource.renderTimelineRow();
				rows.push(timelineRow);

				var childNodes = node.getChildNodes();
				if (childNodes.length)
				{
					Array.prototype.push.apply(rows, this.renderRecursive(childNodes)); //merging two arrays
				}
			}

			return rows;
		},

		/**
		 * 
		 * @returns {BX.Scheduler.EventLayout}
		 */
		getEventLayout: function() {
			return this.eventLayout;
		},

		/**
		 *
		 * @returns {BX.Scheduler.Timeline}
		 */
		getTimeline: function() {
			return this.timeline;
		},

		expandTimeline: function() {
			var events = this.eventStore.getEvents();
			var minDate = this.timeline.getCurrentDate();
			var maxDate = this.timeline.getCurrentDate();

			for (var eventId in events)
			{
				var event = events[eventId];
				if (event.getStartDate() < minDate)
				{
					minDate = event.getStartDate();
				}

				if (event.getEndDate() > maxDate)
				{
					maxDate = event.getEndDate();
				}
			}

			this.timeline.autoExpandTimeline([minDate, maxDate]);
		},

		getCurrentScrollDate: function()
		{
			this.calculateScrollDate();
			return this.scrollDate;
		},

		calculateScrollDate: function()
		{
			var scrollLeft = this.timeline.layout.timeline.scrollLeft;

			this.scrollDate = BX.Tasks.Date.floorDate(
				this.timeline.getDateFromPixels(scrollLeft),
				BX.Tasks.Date.Unit.Day
			);
		},

		onBeforeZoomChange: function(zoomLevel) {
			this.calculateScrollDate();
		},

		onZoomChange: function(zoomLevel) {
			this.render();

			if (this.scrollDate)
			{
				this.timeline.scrollToDate(this.scrollDate);
			}

			BX.onCustomEvent(this, "onZoomChange", [zoomLevel]);
		}
	};

	return View;
})();


BX.Scheduler.ResourceStore = (function() {
	var ResourceStore = function(config) {
		ResourceStore.superclass.constructor.apply(this, arguments);
		this.eventStore = null;

		/** @var {BX.Scheduler.View} */
		this.view = null;
	};

	BX.extend(ResourceStore, BX.Scheduler.Tree);

	ResourceStore.prototype.setEventStore = function(eventStore) {
		this.eventStore = eventStore;
	};

	/**
	 *
	 * @param {BX.Scheduler.View} view
	 */
	ResourceStore.prototype.setView = function(view) {
		if (view instanceof BX.Scheduler.View)
		{
			this.view = view;
		}
	};

	ResourceStore.prototype.getDefaultDataType = function() {
		return BX.Scheduler.Resource;
	};

	ResourceStore.prototype.onNodeAdded = function(node) {
		ResourceStore.superclass.onNodeAdded.apply(this, arguments);
		node.getData().view = this.view;
	};

	return ResourceStore;

})();

BX.Scheduler.Resource = (function() {

	var Resource = function(config) {
		this.config = config || {};
		this.id = config.id || Resource.getUniqueId();
		this.name = BX.type.isNotEmptyString(config.name) ? config.name : "";
		this.link = BX.type.isNotEmptyString(config.link) ? config.link : "";

		/** @var {BX.Scheduler.ResourceStore} */
		this.store = null;

		/** @var {BX.Scheduler.View} */
		this.view = null;

		this.timelineRow = new BX.Scheduler.TimelineRow();

		this.collapsed = true;

		this.layout = {
			resource: null,
			arrow: null,
			name: null
		};
	};

	var globalResourceId = 0;
	Resource.getUniqueId = function() {
		return "resource-" + (++globalResourceId);
	};

	Resource.prototype = {

		renderTimelineRow: function() {

			var resourceRow = this.timelineRow.getResourceRow();
			var eventRow = this.timelineRow.getEventRow();

			BX.cleanNode(resourceRow);
			BX.cleanNode(eventRow);

			resourceRow.appendChild(this.renderResource());
			eventRow.appendChild(this.renderEvents());

			return this.timelineRow;
		},

		createLayout: function() {
			this.layout.resource = BX.create("div", {
				props: {
					className: "scheduler-resource"
				},
				children: [
					(this.layout.arrow = BX.create("span", {
						props: {
							className: "scheduler-resource-arrow"
						},
						events: {
							click: BX.proxy(this.onArrowClick, this)
						}
					})),
					(this.layout.name = BX.create("a", {
						props: {
							className: "scheduler-resource-name",
							href: this.link,
							target: "_blank"
						},
						text : this.getName()
					}))
				]
			})
		},

		updatelayout: function() {

		},

		renderResource: function() {

			if (this.layout.resource === null)
			{
				this.createLayout();
			}

			this.updatelayout();

			return this.layout.resource;
		},

		/**
		 * 
		 * @returns {DocumentFragment}
		 */
		renderEvents: function() {
			var events = this.getEvents();

			var eventLayout = this.getView().getEventLayout();
			var nbrOfSubRows = eventLayout.layoutEvents(events);

			var fragment = document.createDocumentFragment();
			for (var i = 0; i < events.length; i++)
			{
				var event = events[i];
				fragment.appendChild(event.render());
			}

			if (!this.collapsed && nbrOfSubRows > 1)
			{
				BX.removeClass(this.timelineRow.getResourceRow(), "scheduler-row-resource-collapsed");
				BX.removeClass(this.timelineRow.getEventRow(), "scheduler-row-event-collapsed");
				this.timelineRow.setRowHeight(this.getTimeline().getRowHeight() * nbrOfSubRows);
			}
			else
			{
				BX.addClass(this.timelineRow.getResourceRow(), "scheduler-row-resource-collapsed");
				BX.addClass(this.timelineRow.getEventRow(), "scheduler-row-event-collapsed");
				this.timelineRow.setRowHeight(this.getTimeline().getRowHeight());
			}

			if (nbrOfSubRows > 1)
			{
				BX.addClass(this.layout.resource, "scheduler-resource-collapsable");
			}
			else
			{
				BX.removeClass(this.layout.resource, "scheduler-resource-collapsable");
			}

			return fragment;
		},

		/**
		 * 
		 * @returns {BX.Scheduler.Event[]}
		 */
		getEvents: function() {
			var eventStore = this.getEventStore();
			return eventStore.getResourceEvents(this.getId());
		},

		/**
		 * 
		 * @returns {BX.Scheduler.EventStore|null}
		 */
		getEventStore: function() {
			return this.store && this.store.eventStore;
		},

		/**
		 *
		 * @returns {BX.Scheduler.View}
		 */
		getView: function() {
			return this.view;
		},

		/**
		 *
		 * @returns {BX.Scheduler.Timeline}
		 */
		getTimeline: function() {
			return this.view.getTimeline();
		},

		getTimelineRow: function() {
			return this.timelineRow;
		},

		getId: function() {
			return this.id;
		},

		getName: function () {
			return this.name;
		},

		isCollapsed: function() {
			return this.collapsed;
		},

		onArrowClick: function(event) {
			this.collapsed = !this.collapsed;
			this.renderTimelineRow();
		}
	};

	return Resource;
})();


BX.Scheduler.EventLayout = function(timeline) {
	this.timeline = timeline;
};

BX.Scheduler.EventLayout.prototype = {

	layoutEvents: function(events) {

		var index = 0;
		if (!events.length)
		{
			return index;
		}

		events = events.slice(); //clone
		events.sort(this.sortEvents);

		while (events.length)
		{
			var event = events[0];
			while (event)
			{
				event.index = index;

				//Remove current event from the array
				var eventIndex = BX.util.array_search(event, events);
				events.splice(eventIndex, 1);

				event = this.getClosestEvent(event, events);
			}

			index++;
		}

		return index;
	},

	sortEvents: function (eventA, eventB) {
		var startA = eventA.getStartDate();
		var endA = eventA.getEndDate();

		var startB = eventB.getStartDate();
		var endB = eventB.getEndDate();

		if ((startA - startB) === 0)
		{
			return endA > endB ? -1 : 1;
		}
		else
		{
			return startA < startB ? -1 : 1;
		}
	},

	/**
	 *
	 * @param {BX.Scheduler.Event} event
	 * @param {BX.Scheduler.Event[]} events
	 * @returns {BX.Scheduler.Event}
	 */
	getClosestEvent: function (event, events) {
		var minGap = this.getMinGap();
		var maxGap = Infinity;
		var closest = null;
		var eventEnd = event.getEndDate();

		for (var i = 0, l = events.length; i < l; i++)
		{
			var curEvent = events[i];
			var gap = curEvent.getStartDate() - eventEnd;
			if (gap >= minGap && gap < maxGap)
			{
				closest = curEvent;
				maxGap = gap;
			}
		}

		return closest;
	},

	getMinGap: function() {
		return BX.Tasks.Date.getUnitRatio(BX.Tasks.Date.Unit.Milli, this.timeline.snapUnit);
	}
};

/**
 * @extends {BX.Scheduler.Resource}
 * @constructor
 */
BX.Scheduler.ResourceGroup = (function() {

	/**
	 * @extends {BX.Scheduler.Resource}
	 * @param config
	 * @constructor
	 */
	var ResourceGroup = function(config) {
		ResourceGroup.superclass.constructor.apply(this, arguments);
	};

	BX.extend(ResourceGroup, BX.Scheduler.Resource);

	ResourceGroup.prototype.renderResource = function() {
		return BX.create("div", {
			props: {
				className: "scheduler-resource scheduler-resource-group"
			},
			text : this.getName()
		});
	};

	return ResourceGroup;
})();

BX.Scheduler.EventStore = (function() {

	var EventStore = function(config) {
		this.config = config || {};
		this.resourceStore = null;
		this.events = {};
		this.byIdMap = {};

		/** @var {BX.Scheduler.View} */
		this.view = null;

		this.load(this.config.data);
	};

	EventStore.prototype = {

		clearAll: function() {
			this.events = {};
			this.byIdMap = {};
		},

		setResourceStore: function(resourceStore) {
			this.resourceStore = resourceStore;
		},

		/**
		 *
		 * @param {BX.Scheduler.View} view
		 */
		setView: function(view) {
			if (view instanceof BX.Scheduler.View)
			{
				this.view = view;
			}
		},

		/**
		 *
		 * @param id
		 * @returns {BX.Scheduler.Event|null}
		 */
		getById: function (id) {
			return this.byIdMap[id] ? this.byIdMap[id] : null;
		},

		/**
		 *
		 * @returns {{ String: {BX.Scheduler.Event} }}
		 */
		getEvents: function() {
			return this.byIdMap;
		},

		/**
		 *
		 * @returns {BX.Scheduler.View}
		 */
		getView: function() {
			return this.view;
		},

		/**
		 *
		 * @param {BX.Scheduler.Event} event
		 */
		add: function(event) {
			if (event instanceof BX.Scheduler.Event && !this.getById(event.getId()))
			{
				var resourceId = event.getResourceId();
				if (resourceId)
				{
					if (!this.events[resourceId])
					{
						this.events[resourceId] = [];
					}

					this.events[resourceId].push(event);
					this.byIdMap[event.getId()] = event;
					event.join(this);
				}
			}
		},

		remove: function(eventId) {

		},

		removeByResourceId: function(resourceId) {

		},

		load: function(data) {
			if (!BX.type.isArray(data))
			{
				return;
			}

			for (var i = 0; i < data.length; i++)
			{
				try {
					var event = new BX.Scheduler.Event(data[i]);
					this.add(event);
				}
				catch (exception) {
					BX.debug(exception);
				}

			}
		},

		/**
		 *
		 * @param resourceId
		 * @return {BX.Scheduler.Event[]}
		 */
		getResourceEvents: function(resourceId) {
			return this.events[resourceId] || [];
		}
	};

	return EventStore;
})();

BX.Scheduler.Event = (function() {

	var Event = function(config) {
		this.config = config || {};

		if (!BX.type.isDate(config.startDate))
		{
			throw new Error("You must set starDate parameter.");
		}

		if (!BX.type.isDate(config.endDate))
		{
			throw new Error("You must set endDate parameter.");
		}

		this.startDate = BX.Tasks.Date.convertToUTC(config.startDate);
		this.endDate = BX.Tasks.Date.convertToUTC(config.endDate);
		this.resourceId = config.resourceId || null;
		this.name = config.name || "";
		this.className = BX.type.isNotEmptyString(config.className) ? config.className : "";
		this.id = config.id || Event.getUniqueId();
		this.index = 0;
		this.pathToTask = config.pathToTask || null;

		/** @var {BX.Scheduler.EventStore} */
		this.store = null;
	};

	var globalEventId = 0;
	Event.getUniqueId = function() {
		return "event-" + (++globalEventId);
	};

	Event.prototype = {

		/**
		 *
		 * @returns {BX.Scheduler.Resource}
		 */
		getResource: function() {
			var resourceStore = this.getResourceStore();
			if (!resourceStore)
			{
				return null;
			}

			var node = resourceStore.getById(this.getResourceId());
			return node && node.getData();
		},

		/**
		 *
		 * @returns {BX.Scheduler.EventStore}
		 */
		getResourceStore: function() {
			return this.store && this.store.resourceStore;
		},

		getResourceId: function() {
			return this.resourceId;
		},

		/**
		 *
		 * @returns {Date}
		 */
		getStartDate: function() {
			return this.startDate;
		},

		/**
		 *
		 * @returns {Date}
		 */
		getEndDate: function() {
			return this.endDate;
		},

		getClassName: function() {
			return this.className;
		},

		join: function(store) {
			this.store = store;
		},

		unjoin: function() {
			delete this.store;
		},

		getName: function() {
			return this.name;
		},

		getId: function() {
			return this.id;
		},

		/**
		 *
		 * @returns {Element}
		 */
		render: function() {

			const resource = this.getResource();
			var timeline = resource.getTimeline();

			var left = timeline.getPixelsFromDate(this.getStartDate());
			var right = timeline.getPixelsFromDate(this.getEndDate());

			var minBarWidth = 2;
			var width = right - left;
			width = Math.max(width, minBarWidth);

			const top = resource.isCollapsed() ? 0 : this.index * timeline.getRowHeight();
			let events = null;
			if (this.pathToTask !== null)
			{
				events = {
					click: BX.proxy(this.openTask, this)
				};
			}

			let className = 'scheduler-event';
			if (this.pathToTask !== null)
			{
				className += " scheduler-event-clickable";
			}

			if (this.getClassName() !== "")
			{
				className += " " + this.getClassName();
			}

			return BX.create("div", {
				attrs: {
					title: this.getName()
				},
				props: {
					className: className
				},
				style: {
					left: left + "px",
					top: top + "px",
					width: width + "px"
				},
				events: events,
				text : width > 30 ? this.getName() : ""
			});
		},

		openTask: function()
		{
			if (BX.SidePanel)
			{
				BX.SidePanel.Instance.open(this.pathToTask);
			}
			else
			{
				window.top.location.href = this.pathToTask;
			}
		},
	};

	return Event;

})();