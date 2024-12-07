/* eslint-disable */
this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,d3,main_kanban,ui_notification,main_popup,main_core) {
	'use strict';

	var _templateObject;
	function createStub() {
	  return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-st-kanban-stub\"></div>"])));
	}

	function isLine(value) {
	  return main_core.Type.isArray(value) && value.length === 2 && value.every(main_core.Type.isNumber);
	}

	function isOverlap(line1, line2) {
	  if (!isLine(line1) || !isLine(line2)) {
	    throw new Error('Invalid lines. Line should be Array<number>');
	  }
	  var a1 = Math.min.apply(Math, babelHelpers.toConsumableArray(line1));
	  var a2 = Math.max.apply(Math, babelHelpers.toConsumableArray(line1));
	  var b1 = Math.min.apply(Math, babelHelpers.toConsumableArray(line2));
	  var b2 = Math.max.apply(Math, babelHelpers.toConsumableArray(line2));
	  return a1 >= b1 && a1 <= b2 || a2 >= b1 && a2 <= b2 || b1 >= a1 && b1 <= a2 || b2 >= a1 && b2 <= a2;
	}

	var isValidRect = function isValidRect(rect) {
	  return main_core.Type.isNumber(rect.left) && main_core.Type.isNumber(rect.top) && main_core.Type.isNumber(rect.width) && main_core.Type.isNumber(rect.height);
	};
	function makeRelativeRect(rect1, rect2) {
	  if (!isValidRect(rect1) || !isValidRect(rect2)) {
	    throw new Error('Invalid rect. Rect should includes x, y, width and height props with a number value');
	  }
	  return {
	    left: rect2.left - rect1.left,
	    top: rect2.top - rect1.top,
	    right: rect2.left - rect1.left + rect2.width,
	    bottom: rect2.top - rect1.top + rect2.height,
	    width: rect2.width,
	    height: rect2.height
	  };
	}

	function isRect(value) {
	  return main_core.Type.isNumber(value.left) && main_core.Type.isNumber(value.top) && main_core.Type.isNumber(value.width) && main_core.Type.isNumber(value.height);
	}

	function getMiddlePoint(rect) {
	  if (!isRect(rect)) {
	    throw new Error('Invalid rect. Rect should includes x, y, width and height props with a number value');
	  }
	  return {
	    middleX: rect.left + rect.width / 2,
	    middleY: rect.top + rect.height / 2
	  };
	}

	var _templateObject$1, _templateObject2, _templateObject3, _templateObject4;
	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	/**
	 * Implements interface for works with marker
	 */
	var Marker = /*#__PURE__*/function (_Event$EventEmitter) {
	  babelHelpers.inherits(Marker, _Event$EventEmitter);
	  babelHelpers.createClass(Marker, null, [{
	    key: "getMarkerFromPoint",
	    value: function getMarkerFromPoint(point) {
	      return Marker.instances.find(function (marker) {
	        return marker.isReceiverIntersecting(point);
	      });
	    }
	  }, {
	    key: "emitReceiverDragOutForAll",
	    value: function emitReceiverDragOutForAll() {
	      var exclude = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
	      Marker.instances.forEach(function (marker) {
	        if (marker !== exclude) {
	          marker.onReceiverDragOut();
	        }
	      });
	    }
	  }, {
	    key: "getAllLinks",
	    value: function getAllLinks() {
	      return Marker.instances.reduce(function ( /* Set */acc, marker) {
	        return babelHelpers.toConsumableArray(marker.links).reduce(function (subAcc, link) {
	          return subAcc.add(link);
	        }, acc);
	      }, new Set());
	    }
	  }, {
	    key: "getAllStubLinks",
	    value: function getAllStubLinks() {
	      return Marker.instances.reduce(function ( /* Set */acc, marker) {
	        return babelHelpers.toConsumableArray(marker.stubLinks).reduce(function (subAcc, link) {
	          return subAcc.add(link);
	        }, acc);
	      }, new Set());
	    }
	  }, {
	    key: "highlightLink",
	    value: function highlightLink() {
	      for (var _len = arguments.length, targets = new Array(_len), _key = 0; _key < _len; _key++) {
	        targets[_key] = arguments[_key];
	      }
	      Marker.getAllLinks().forEach(function (link) {
	        if (!targets.includes(link)) {
	          if ([].concat(targets).every(function (item) {
	            return item.from !== link.from;
	          })) {
	            main_core.Dom.addClass(link.from.dispatcher, 'crm-st-fade');
	            main_core.Dom.addClass(link.from.receiver, 'crm-st-fade');
	            main_core.Dom.addClass(link.from.getTunnelButton(), 'crm-st-fade');
	          }
	          main_core.Dom.addClass(link.to.dispatcher, 'crm-st-fade');
	          main_core.Dom.addClass(link.to.receiver, 'crm-st-fade');
	          main_core.Dom.addClass(link.node.node(), 'crm-st-fade');
	          main_core.Dom.addClass(link.arrow.select('path').node(), 'crm-st-fade');
	        } else {
	          var node = link.node.node();
	          node.parentNode.appendChild(node);
	          var arrowMarker = link.arrow.node();
	          var defs = arrowMarker.closest('defs');
	          main_core.Dom.insertAfter(arrowMarker, defs.firstChild);
	        }
	      });
	    }
	  }, {
	    key: "unhighlightLinks",
	    value: function unhighlightLinks() {
	      Marker.getAllLinks().forEach(function (link) {
	        main_core.Dom.removeClass(link.from.dispatcher, 'crm-st-fade');
	        main_core.Dom.removeClass(link.from.receiver, 'crm-st-fade');
	        main_core.Dom.removeClass(link.from.getTunnelButton(), 'crm-st-fade');
	        main_core.Dom.removeClass(link.to.dispatcher, 'crm-st-fade');
	        main_core.Dom.removeClass(link.to.receiver, 'crm-st-fade');
	        main_core.Dom.removeClass(link.node.node(), 'crm-st-fade');
	        main_core.Dom.removeClass(link.arrow.select('path').node(), 'crm-st-fade');
	      });
	    }
	  }, {
	    key: "blurLinks",
	    value: function blurLinks(marker) {
	      Marker.getAllLinks().forEach(function (link) {
	        if (link.from === marker || link.to === marker) {
	          main_core.Dom.addClass(link.node.node(), 'crm-st-blur-link');
	          main_core.Dom.addClass(link.from.getTunnelButton(), 'crm-st-blur-link');
	        }
	      });
	    }
	  }, {
	    key: "unblurLinks",
	    value: function unblurLinks() {
	      Marker.getAllLinks().forEach(function (link) {
	        main_core.Dom.removeClass(link.node.node(), 'crm-st-blur-link');
	        main_core.Dom.removeClass(link.from.getTunnelButton(), 'crm-st-blur-link');
	        main_core.Dom.removeClass(link.to.getTunnelButton(), 'crm-st-blur-link');
	      });
	    }
	  }, {
	    key: "removeAllLinks",
	    value: function removeAllLinks() {
	      Marker.instances.forEach(function (marker) {
	        var preventSave = true;
	        marker.removeAllLinks(preventSave);
	      });
	      Marker.instances.forEach(function (marker) {
	        marker.removeAllStubLinks();
	      });
	    }
	  }, {
	    key: "restoreAllLinks",
	    value: function restoreAllLinks() {
	      var preventSave = true;
	      Marker.getAllLinks().forEach(function (link) {
	        link.from.links["delete"](link);
	        link.from.addLinkTo(link.to, link.robotAction, preventSave);
	      });
	    }
	  }, {
	    key: "adjustLinks",
	    value: function adjustLinks() {
	      Marker.getAllLinks().forEach(function (link, index) {
	        var path = link.from.getLinkPath(link.to);
	        link.node.style('transition', 'none');
	        d3.select(link.from.getTunnelButton()).style('transition', 'none');
	        link.from.showTunnelButton(path);
	        link.node.attr('d', d3.line()(path));
	        link.path = path;
	        clearTimeout(Marker.adjustLinksTimeoutIds[index]);
	        Marker.adjustLinksTimeoutIds[index] = setTimeout(function () {
	          link.node.style('transition', null);
	          d3.select(link.from.getTunnelButton()).style('transition', null);
	        }, 1000);
	      });
	    }
	  }]);
	  function Marker(options) {
	    var _this;
	    babelHelpers.classCallCheck(this, Marker);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Marker).call(this));
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "links", new Set());
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "stubLinks", new Set());
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "cache", new main_core.Cache.MemoryCache());
	    _this.dispatcher = options.dispatcher;
	    _this.receiver = options.receiver;
	    _this.point = options.point;
	    _this.container = options.container;
	    _this.intermediateXPoints = options.intermediateXPoints;
	    _this.name = options.name;
	    _this.data = options.data;
	    var linksRoot = _this.getLinksRoot();

	    // Add arrow marker
	    if (!linksRoot.select('defs').node()) {
	      linksRoot.append('svg:defs').append('filter').attr('id', 'crm-st-blur').append('feGaussianBlur').attr('stdDeviation', '2');
	      linksRoot.select('#crm-st-blur').append('feColorMatrix').attr('type', 'saturate').attr('values', '0');
	    }
	    _this.onDispatcherMouseDown = _this.onDispatcherMouseDown.bind(babelHelpers.assertThisInitialized(_this));
	    _this.onMarkerRootMouseUp = _this.onMarkerRootMouseUp.bind(babelHelpers.assertThisInitialized(_this));
	    _this.onMarkerRootMouseMove = _this.onMarkerRootMouseMove.bind(babelHelpers.assertThisInitialized(_this));
	    d3.select(_this.dispatcher).on('mousedown', _this.onDispatcherMouseDown);
	    Marker.instances.push(babelHelpers.assertThisInitialized(_this));
	    return _this;
	  }
	  babelHelpers.createClass(Marker, [{
	    key: "disable",
	    value: function disable() {
	      this.disabled = true;
	    }
	  }, {
	    key: "enable",
	    value: function enable() {
	      this.disabled = false;
	    }
	  }, {
	    key: "isEnabled",
	    value: function isEnabled() {
	      return !this.disabled;
	    }
	  }, {
	    key: "getMarkerRoot",
	    value: function getMarkerRoot() {
	      var _this2 = this;
	      return this.cache.remember('markerRoot', function () {
	        var markerRoot = d3.select(_this2.container).select('.crm-st-svg-root');
	        if (markerRoot.node()) {
	          return markerRoot;
	        }
	        return d3.select(_this2.container).append('svg').attr('class', 'crm-st-svg-root');
	      });
	    }
	  }, {
	    key: "getMarkerRootRect",
	    value: function getMarkerRootRect() {
	      return this.getMarkerRoot().node().getBoundingClientRect();
	    }
	  }, {
	    key: "getLinksRoot",
	    value: function getLinksRoot() {
	      var _this3 = this;
	      return this.cache.remember('linksRoot', function () {
	        var linksRoot = d3.select(_this3.container).select('.crm-st-svg-links-root');
	        if (linksRoot.node()) {
	          return linksRoot;
	        }
	        return d3.select(_this3.container).append('svg').attr('class', 'crm-st-svg-links-root');
	      });
	    }
	  }, {
	    key: "getMarkerLine",
	    value: function getMarkerLine() {
	      return this.cache.remember('markerLine', this.getMarkerRoot().append('line').attr('class', 'crm-st-svg-marker'));
	    }
	  }, {
	    key: "removeMarkerLine",
	    value: function removeMarkerLine() {
	      this.getMarkerLine().remove();
	      this.cache["delete"]('markerLine');
	    }
	  }, {
	    key: "getDispatcherRect",
	    value: function getDispatcherRect() {
	      var relativeRect = makeRelativeRect(this.getMarkerRootRect(), this.dispatcher.getBoundingClientRect());
	      return _objectSpread(_objectSpread({}, relativeRect), getMiddlePoint(relativeRect));
	    }
	  }, {
	    key: "getReceiverRect",
	    value: function getReceiverRect() {
	      var relativeRect = makeRelativeRect(this.getMarkerRootRect(), this.receiver.getBoundingClientRect());
	      return _objectSpread(_objectSpread({}, relativeRect), getMiddlePoint(relativeRect));
	    }
	  }, {
	    key: "getPointRect",
	    value: function getPointRect() {
	      var relativeRect = makeRelativeRect(this.getMarkerRootRect(), this.point.getBoundingClientRect());
	      return _objectSpread(_objectSpread({}, relativeRect), getMiddlePoint(relativeRect));
	    }
	  }, {
	    key: "getMarkerRootMousePosition",
	    value: function getMarkerRootMousePosition() {
	      if (main_core.Type.isFunction(d3.pointer)) {
	        var _d3$pointer = d3.pointer(this.getMarkerRootMouseMoveEvent(), this.getMarkerRoot().node()),
	          _d3$pointer2 = babelHelpers.slicedToArray(_d3$pointer, 2),
	          _x = _d3$pointer2[0],
	          _y = _d3$pointer2[1];
	        return {
	          x: _x,
	          y: _y
	        };
	      }
	      var _d3$mouse = d3.mouse(this.getMarkerRoot().node()),
	        _d3$mouse2 = babelHelpers.slicedToArray(_d3$mouse, 2),
	        x = _d3$mouse2[0],
	        y = _d3$mouse2[1];
	      return {
	        x: x,
	        y: y
	      };
	    } /** @private */
	  }, {
	    key: "onReceiverDragOver",
	    value: function onReceiverDragOver(from, to) {
	      if (!this.hovered) {
	        this.hovered = true;
	        this.emit('Marker:receiver:dragOver', {
	          from: from,
	          to: to
	        });
	      }
	    } /** @private */
	  }, {
	    key: "onReceiverDragOut",
	    value: function onReceiverDragOut() {
	      if (this.hovered) {
	        this.hovered = false;
	        this.emit('Marker:receiver:dragOut');
	      }
	    } /** @private */
	  }, {
	    key: "onDispatcherMouseDown",
	    value: function onDispatcherMouseDown() {
	      var _this$getDispatcherRe = this.getDispatcherRect(),
	        middleX = _this$getDispatcherRe.middleX,
	        middleY = _this$getDispatcherRe.middleY;
	      this.getMarkerLine().attr('x1', middleX).attr('y1', middleY).attr('x2', middleX).attr('y2', middleY);
	      this.getMarkerRoot().style('z-index', '222').on('mousemove', this.onMarkerRootMouseMove).on('mouseup', this.onMarkerRootMouseUp);
	      this.emit('Marker:dragStart');
	    }
	  }, {
	    key: "setMarkerRootMouseMoveEvent",
	    value: function setMarkerRootMouseMoveEvent(event) {
	      this.cache.set('markerRootMouseMoveEvent', event);
	    }
	  }, {
	    key: "getMarkerRootMouseMoveEvent",
	    value: function getMarkerRootMouseMoveEvent() {
	      return this.cache.get('markerRootMouseMoveEvent');
	    } /** @private */
	  }, {
	    key: "onMarkerRootMouseMove",
	    value: function onMarkerRootMouseMove(event) {
	      this.setMarkerRootMouseMoveEvent(event);
	      var _this$getMarkerRootMo = this.getMarkerRootMousePosition(),
	        x = _this$getMarkerRootMo.x,
	        y = _this$getMarkerRootMo.y;
	      this.getMarkerLine().attr('x2', x).attr('y2', y);
	      this.emit('Marker:drag');
	      var destinationMarker = this.getDestinationMarker();
	      if (destinationMarker && destinationMarker.isEnabled()) {
	        if (destinationMarker !== this) {
	          destinationMarker.onReceiverDragOver(this, destinationMarker);
	        }
	      }
	      Marker.emitReceiverDragOutForAll(destinationMarker);
	    } /** @private */
	  }, {
	    key: "onMarkerRootMouseUp",
	    value: function onMarkerRootMouseUp() {
	      this.getMarkerRoot().on('mousemove', null).on('mouseup', null).style('z-index', null);
	      this.removeMarkerLine();

	      // @todo refactoring
	      Marker.instances.forEach(function (marker) {
	        return marker.onReceiverDragOut();
	      });
	      var destinationMarker = this.getDestinationMarker();
	      var event = new main_core.Event.BaseEvent({
	        data: {
	          from: this,
	          to: destinationMarker
	        }
	      });
	      this.emit('Marker:dragEnd', event);
	      if (destinationMarker && destinationMarker.isEnabled()) {
	        if (destinationMarker && !event.isDefaultPrevented()) {
	          if (!this.data.column.data.isCategoryEditable) {
	            this.emit('Marker:error', {
	              message: main_core.Loc.getMessage('CRM_ST_TUNNEL_EDIT_ACCESS_DENIED')
	            });
	            return;
	          }
	          this.addLinkTo(destinationMarker, 'copy');
	        }
	      }
	    }
	  }, {
	    key: "getTunnelMenu",
	    value: function getTunnelMenu() {
	      var _this4 = this;
	      return this.cache.remember('tunnelMenu', function () {
	        return new main_popup.PopupMenuWindow({
	          bindElement: _this4.getTunnelButton(),
	          items: _this4.getTunnelMenuItems(babelHelpers.toConsumableArray(_this4.links)[0]),
	          events: {
	            onPopupClose: function onPopupClose() {
	              return _this4.deactivateTunnelButton();
	            },
	            onPopupShow: function onPopupShow() {
	              return _this4.activateTunnelButton();
	            }
	          }
	        });
	      });
	    }
	  }, {
	    key: "getTunnelMenuItems",
	    value: function getTunnelMenuItems(link) {
	      var self = this;
	      var onRobotActionChange = function onRobotActionChange(robotAction) {
	        if (!main_core.Type.isNil(link) && link.robotAction !== robotAction) {
	          self.changeRobotAction(link, robotAction);
	          link.robotAction = robotAction;
	        }
	        this.getParentMenuWindow().close();
	        this.getParentMenuWindow().getMenuItems()[0].setText(main_core.Loc.getMessage("CRM_ST_ROBOT_ACTION_".concat(robotAction.toUpperCase())));
	      };
	      var robotAction = main_core.Type.isNil(link) ? 'COPY' : link.robotAction.toUpperCase();
	      return [{
	        text: main_core.Loc.getMessage("CRM_ST_ROBOT_ACTION_".concat(robotAction)),
	        items: [{
	          text: main_core.Loc.getMessage('CRM_ST_ACTION_COPY'),
	          onclick: function onclick() {
	            onRobotActionChange.call(this, 'copy');
	          }
	        }, {
	          text: main_core.Loc.getMessage('CRM_ST_ACTION_MOVE'),
	          onclick: function onclick() {
	            onRobotActionChange.call(this, 'move');
	          }
	        }]
	      }, {
	        text: main_core.Loc.getMessage('CRM_ST_SETTINGS'),
	        onclick: function onclick() {
	          self.editLink(link);
	          this.close();
	        }
	      }, {
	        text: main_core.Loc.getMessage('CRM_ST_REMOVE'),
	        onclick: function onclick() {
	          self.removeLink(link);
	          var parentMenu = this.getParentMenuWindow();
	          if (parentMenu) {
	            parentMenu.removeMenuItem(this.getParentMenuItem().id);
	          }
	        }
	      }];
	    }
	  }, {
	    key: "changeRobotAction",
	    value: function changeRobotAction(link, action) {
	      var _this5 = this;
	      this.emit('Marker:changeRobotAction', {
	        link: link,
	        action: action,
	        onChangeRobotEnd: function onChangeRobotEnd() {
	          return _this5.emit('Marker:editLink', {
	            link: link
	          });
	        }
	      });
	    }
	  }, {
	    key: "editLink",
	    value: function editLink(link) {
	      this.emit('Marker:editLink', {
	        link: link
	      });
	    }
	  }, {
	    key: "addLinkTo",
	    value: function addLinkTo(destination, robotAction) {
	      var _this6 = this;
	      var preventSave = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
	      setTimeout(function () {
	        if (!babelHelpers.toConsumableArray(_this6.links).some(function (link) {
	          return link.to === destination;
	        })) {
	          var linksRoot = _this6.getLinksRoot();
	          var path = _this6.getLinkPath(destination);
	          var line = d3.line();
	          var fromId = _this6.data.column.getId().replace(':', '-');
	          var toId = destination.data.column.getId().replace(':', '-');
	          var arrowId = "".concat(fromId, "-").concat(toId);
	          var arrow = linksRoot.select('defs').append('svg:marker').attr('id', arrowId).attr('refX', 8).attr('refY', 6).attr('markerWidth', 30).attr('markerHeight', 30).attr('markerUnits', 'userSpaceOnUse').attr('orient', 'auto').append('path').attr('d', 'M 0 0 12 6 0 12 3 6').attr('class', 'crm-st-svg-link-arrow').select(function selectCallback() {
	            return this.parentNode;
	          });
	          var linkNode = linksRoot.append('path').attr('class', 'crm-st-svg-link').attr('marker-end', "url(#".concat(arrowId, ")")).attr('d', line(path));
	          _this6.showTunnelButton(path);
	          var link = {
	            from: _this6,
	            to: destination,
	            node: linkNode,
	            robotAction: robotAction,
	            arrow: arrow,
	            path: path
	          };
	          _this6.emit('Marker:linkFrom', {
	            link: link,
	            preventSave: preventSave
	          });
	          destination.emit('Marker:linkTo', {
	            link: link,
	            preventSave: preventSave
	          });
	          _this6.links.add(link);
	          var menu = _this6.getTunnelsListMenu();
	          var id = menu.getMenuItems().length;
	          menu.addMenuItem({
	            id: "#".concat(id),
	            text: main_core.Text.encode(destination.name),
	            events: {
	              onMouseEnter: function onMouseEnter() {
	                Marker.highlightLink(link);
	              },
	              onMouseLeave: function onMouseLeave() {
	                Marker.unhighlightLinks();
	              }
	            },
	            items: _this6.getTunnelMenuItems(link)
	          });
	        }
	        if (_this6.links.size > 1) {
	          _this6.setTunnelsCounterValue(_this6.links.size);
	        }
	      });
	    }
	  }, {
	    key: "addStubLinkTo",
	    value: function addStubLinkTo(destination) {
	      var _this7 = this;
	      setTimeout(function () {
	        if (!babelHelpers.toConsumableArray(_this7.stubLinks).some(function (link) {
	          return link.to === destination;
	        })) {
	          var linksRoot = _this7.getLinksRoot();
	          var path = _this7.getLinkPath(destination);
	          var line = d3.line();
	          var fromId = _this7.data.column.getId().replace(':', '-');
	          var toId = destination.data.column.getId().replace(':', '-');
	          var arrowId = "".concat(fromId, "-").concat(toId);
	          var arrow = linksRoot.select('defs').append('svg:marker').attr('id', arrowId).attr('refX', 8).attr('refY', 6).attr('markerWidth', 30).attr('markerHeight', 30).attr('markerUnits', 'userSpaceOnUse').attr('orient', 'auto').append('path').attr('d', 'M 0 0 12 6 0 12 3 6').attr('class', 'crm-st-svg-link-arrow crm-st-svg-link-arrow-stub').select(function selectCallback() {
	            return this.parentNode;
	          });
	          var linkNode = linksRoot.append('path').attr('class', 'crm-st-svg-link crm-st-svg-link-stub').attr('marker-end', "url(#".concat(arrowId, ")")).attr('d', line(path));
	          _this7.showTunnelStubButton(path);
	          var link = {
	            from: _this7,
	            to: destination,
	            node: linkNode,
	            arrow: arrow,
	            path: path
	          };
	          _this7.emit('Marker:stubLinkFrom', {
	            link: link,
	            preventSave: true
	          });
	          destination.emit('Marker:stubLinkTo', {
	            link: link,
	            preventSave: true
	          });
	          _this7.stubLinks.add(link);
	        }
	      });
	    }
	  }, {
	    key: "updateLink",
	    value: function updateLink(link, newTo) {
	      var preventSave = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
	      var path = this.getLinkPath(newTo);
	      var line = d3.line();
	      var oldTo = link.to;
	      link.node.attr('d', line(path));
	      link.path = path;
	      link.to = newTo;
	      this.emit('Marker:linkFrom', {
	        link: link,
	        preventSave: preventSave
	      });
	      newTo.emit('Marker:linkTo', {
	        link: link,
	        preventSave: preventSave
	      });
	      if (!oldTo.isLinked()) {
	        oldTo.emit('Marker:unlink');
	      }
	    }
	  }, {
	    key: "removeLink",
	    value: function removeLink(link) {
	      var preventSave = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
	      // @todo refactoring

	      link.hidden = true;
	      if (!preventSave) {
	        this.links["delete"](link);
	      }
	      link.node.remove();
	      link.arrow.remove();
	      if (!this.isLinkedFrom()) {
	        main_core.Dom.remove(link.from.getTunnelButton());
	        this.getTunnelMenu().destroy();
	        this.deactivateTunnelButton();
	        this.cache["delete"]('tunnelMenu');
	      }
	      this.setTunnelsCounterValue(this.links.size);
	      var visibleLinks = babelHelpers.toConsumableArray(this.links).filter(function (item) {
	        return !item.hidden;
	      });
	      if (visibleLinks.length <= 1) {
	        if (this.getTunnelsListMenu().getPopupWindow().isShown()) {
	          this.getTunnelMenu().destroy();
	          this.cache["delete"]('tunnelMenu');
	          this.getTunnelMenu().show();
	        }
	        this.getTunnelsListMenu().destroy();
	        this.deactivateTunnelButton();
	        this.cache["delete"]('tunnelsListMenu');
	      }
	      link.from.emit('Marker:removeLinkFrom', {
	        link: link,
	        preventSave: preventSave
	      });
	      if (!link.from.isLinked()) {
	        link.from.emit('Marker:unlink', {
	          preventSave: preventSave
	        });
	      }
	      link.to.emit('Marker:removeTo', {
	        link: link,
	        preventSave: preventSave
	      });
	      if (!link.to.isLinked()) {
	        link.to.emit('Marker:unlink', {
	          preventSave: preventSave
	        });
	      }
	    }
	  }, {
	    key: "removeAllLinks",
	    value: function removeAllLinks() {
	      var _this8 = this;
	      var preventSave = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
	      this.links.forEach(function (link) {
	        return _this8.removeLink(link, preventSave);
	      });
	    }
	  }, {
	    key: "removeStubLink",
	    value: function removeStubLink(link) {
	      this.stubLinks["delete"](link);
	      link.node.remove();
	      link.arrow.remove();
	      if (!this.isLinkedStub()) {
	        main_core.Dom.remove(link.from.getStubTunnelButton());
	      }
	      link.from.emit('Marker:removeStubLink', {
	        link: link
	      });
	      link.from.emit('Marker:removeStubLinkFrom', {
	        link: link
	      });
	      if (!link.from.isLinkedStub()) {
	        link.from.emit('Marker:unlinkStub');
	      }
	      link.to.emit('Marker:removeStubTo', {
	        link: link
	      });
	      if (!link.to.isLinkedStub()) {
	        link.to.emit('Marker:unlinkStub');
	      }
	    }
	  }, {
	    key: "removeAllStubLinks",
	    value: function removeAllStubLinks() {
	      var _this9 = this;
	      this.stubLinks.forEach(function (link) {
	        return _this9.removeStubLink(link);
	      });
	    }
	  }, {
	    key: "isLinked",
	    value: function isLinked() {
	      var _this10 = this;
	      return babelHelpers.toConsumableArray(Marker.getAllLinks()).some(function (item) {
	        return !item.hidden && (item.from === _this10 || item.to === _this10);
	      });
	    }
	  }, {
	    key: "isLinkedFrom",
	    value: function isLinkedFrom() {
	      var _this11 = this;
	      return babelHelpers.toConsumableArray(Marker.getAllLinks()).some(function (item) {
	        return !item.hidden && item.from === _this11;
	      });
	    }
	  }, {
	    key: "isLinkedTo",
	    value: function isLinkedTo() {
	      var _this12 = this;
	      return babelHelpers.toConsumableArray(Marker.getAllLinks()).some(function (item) {
	        return !item.hidden && item.to === _this12;
	      });
	    }
	  }, {
	    key: "isLinkedStub",
	    value: function isLinkedStub() {
	      var _this13 = this;
	      return babelHelpers.toConsumableArray(Marker.getAllLinks()).some(function (item) {
	        return !item.hidden && (item.from === _this13 || item.to === _this13);
	      });
	    }
	  }, {
	    key: "showTunnelButton",
	    value: function showTunnelButton(path) {
	      var button = this.getTunnelButton();
	      var category = this.getCategory();
	      var left = path[0][0];
	      main_core.Tag.style(button)(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\tbottom: 0px;\n\t\t\tleft: ", "px;\n\t\t\ttransform: translate3d(-50%, 50%, 0);\n\t\t"])), left);
	      if (!category.contains(button)) {
	        main_core.Dom.append(button, category);
	      }
	    }
	  }, {
	    key: "getStubTunnelButton",
	    value: function getStubTunnelButton() {
	      var _this14 = this;
	      return this.cache.remember('tunnelStubButton', function () {
	        var button = main_core.Runtime.clone(_this14.getTunnelButton());
	        main_core.Dom.addClass(button, 'crm-st-tunnel-button-stub');
	        return button;
	      });
	    }
	  }, {
	    key: "showTunnelStubButton",
	    value: function showTunnelStubButton(path) {
	      var button = this.getStubTunnelButton();
	      var category = this.getCategory();
	      var left = path[0][0];
	      main_core.Tag.style(button)(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\tbottom: 0px;\n\t\t\tleft: ", "px;\n\t\t\ttransform: translate3d(-50%, 50%, 0);\n\t\t"])), left);
	      if (!category.contains(button)) {
	        main_core.Dom.append(button, category);
	      }
	    }
	  }, {
	    key: "getTunnelButton",
	    value: function getTunnelButton() {
	      var _this15 = this;
	      var canEdit = this.data.column.data.isCategoryEditable;
	      return this.cache.remember('tunnelButton', function () {
	        return main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-st-tunnel-button\" \n\t\t\t\t\t onmouseenter=\"", "\"\n\t\t\t\t\t onmouseleave=\"", "\"\n\t\t\t\t\t onclick=\"", "\"\n\t\t\t\t\t title=\"", "\"\n\t\t\t\t\t style=\"", "\"\n\t\t\t\t>", "</div>\n\t\t\t"])), _this15.onTunnelButtonMouseEnter.bind(_this15), Marker.onTunnelButtonMouseLeave, _this15.onTunnelButtonClick.bind(_this15), main_core.Loc.getMessage('CRM_ST_TUNNEL_BUTTON_TITLE'), !canEdit ? 'pointer-events: none;' : '', main_core.Loc.getMessage('CRM_ST_TUNNEL_BUTTON_LABEL'));
	      });
	    } /** @private */
	  }, {
	    key: "onTunnelButtonMouseEnter",
	    value: function onTunnelButtonMouseEnter() {
	      Marker.highlightLink.apply(Marker, babelHelpers.toConsumableArray(this.links));
	    } /** @private */
	  }, {
	    key: "onTunnelButtonClick",
	    /** @private */value: function onTunnelButtonClick() {
	      if (BX.Crm.Restriction.Bitrix24.isRestricted('automation')) {
	        BX.Crm.Restriction.Bitrix24.getHandler('automation').call();
	      } else if (this.links.size > 1) {
	        if (this.isTunnelButtonActive()) {
	          this.getTunnelsListMenu().close();
	          return;
	        }
	        this.getTunnelsListMenu().show();
	      } else {
	        this.getTunnelMenu().show();
	      }
	    }
	  }, {
	    key: "getTunnelsListMenu",
	    value: function getTunnelsListMenu() {
	      var _this16 = this;
	      return this.cache.remember('tunnelsListMenu', new main_popup.PopupMenuWindow({
	        bindElement: this.getTunnelButton(),
	        items: [],
	        closeByEsc: true,
	        menuShowDelay: 0,
	        events: {
	          onPopupClose: function onPopupClose() {
	            return _this16.deactivateTunnelButton();
	          },
	          onPopupShow: function onPopupShow() {
	            return _this16.activateTunnelButton();
	          }
	        }
	      }));
	    }
	  }, {
	    key: "activateTunnelButton",
	    value: function activateTunnelButton() {
	      main_core.Dom.addClass(this.getTunnelButton(), 'crm-st-tunnel-button-active');
	    }
	  }, {
	    key: "deactivateTunnelButton",
	    value: function deactivateTunnelButton() {
	      main_core.Dom.removeClass(this.getTunnelButton(), 'crm-st-tunnel-button-active');
	    }
	  }, {
	    key: "isTunnelButtonActive",
	    value: function isTunnelButtonActive() {
	      return main_core.Dom.hasClass(this.getTunnelButton(), 'crm-st-tunnel-button-active');
	    }
	  }, {
	    key: "getTunnelsCounter",
	    value: function getTunnelsCounter() {
	      return this.cache.remember('tunnelsCounter', main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["<span class=\"crm-st-tunnel-button-counter\">0</span>"]))));
	    }
	  }, {
	    key: "setTunnelsCounterValue",
	    value: function setTunnelsCounterValue(value) {
	      var tunnelButton = this.getTunnelButton();
	      var tunnelsCounter = this.getTunnelsCounter();
	      if (value > 1) {
	        if (!tunnelButton.contains(tunnelsCounter)) {
	          main_core.Dom.append(tunnelsCounter, tunnelButton);
	        }
	        tunnelsCounter.innerText = value;
	      } else {
	        tunnelsCounter.innerText = 0;
	        main_core.Dom.remove(tunnelsCounter);
	      }
	    }
	  }, {
	    key: "getCategory",
	    value: function getCategory() {
	      return this.receiver.closest('.crm-st-category');
	    }
	  }, {
	    key: "getIntermediateXPoints",
	    value: function getIntermediateXPoints() {
	      if (main_core.Type.isArray(this.intermediateXPoints)) {
	        var markerRootRect = this.getMarkerRootRect();
	        return this.intermediateXPoints.map(function (value) {
	          return value - markerRootRect.left;
	        });
	      }
	      if (main_core.Type.isFunction(this.intermediateXPoints)) {
	        var _markerRootRect = this.getMarkerRootRect();
	        return this.intermediateXPoints().map(function (value) {
	          return value - _markerRootRect.left;
	        });
	      }
	      return [];
	    }
	  }, {
	    key: "getNearestIntermediateXPoint",
	    value: function getNearestIntermediateXPoint(x) {
	      return this.getIntermediateXPoints().reduce(function (prev, curr) {
	        return Math.abs(curr - x) < Math.abs(prev - x) ? curr : prev;
	      });
	    }
	  }, {
	    key: "getLinkPath",
	    value: function getLinkPath(target) {
	      var _this17 = this;
	      var targetPosition = target.getPointRect();
	      var currentPosition = this.getDispatcherRect();
	      var baseOffset = 80;
	      var markerMargin = 10;
	      var path = [];
	      path.push([currentPosition.middleX, currentPosition.middleY]);
	      path.push([currentPosition.middleX, currentPosition.middleY + baseOffset]);
	      if (currentPosition.middleY !== targetPosition.middleY) {
	        var intermediateX = this.getNearestIntermediateXPoint(targetPosition.middleX);
	        path.push([intermediateX, currentPosition.middleY + baseOffset]);
	        path.push([intermediateX, targetPosition.middleY + baseOffset / 3 - markerMargin / 3]);
	        path.push([targetPosition.middleX, targetPosition.middleY + baseOffset / 3 - markerMargin / 3]);
	      } else {
	        path.push([targetPosition.middleX, targetPosition.middleY + baseOffset]);
	      }
	      path.push([targetPosition.middleX, targetPosition.middleY + markerMargin]);
	      var lineOffset = 4;
	      return babelHelpers.toConsumableArray(Marker.getAllLinks()).reduce(function (acc, link) {
	        var from = link.from,
	          currentPath = link.path;
	        if (from !== _this17) {
	          /**
	           * Horizon lines
	           * 1x -> 2x
	           * 3x -> 4x
	           */

	          if (acc[1][1] === currentPath[1][1]) {
	            if (isOverlap([acc[1][0], acc[2][0]], [currentPath[1][0], currentPath[2][0]])) {
	              acc[1][1] += lineOffset;
	              acc[2][1] += lineOffset;
	            }
	          }
	          if (currentPath.length === 6) {
	            if (acc[1][1] === currentPath[3][1]) {
	              if (isOverlap([acc[1][0], acc[2][0]], [currentPath[3][0], currentPath[4][0]])) {
	                acc[1][1] += lineOffset;
	                acc[2][1] += lineOffset;
	              }
	            }
	          }
	          if (acc.length === 6) {
	            if (acc[3][1] === currentPath[1][1]) {
	              if (isOverlap([acc[3][0], acc[4][0]], [currentPath[1][0], currentPath[2][0]])) {
	                acc[3][1] += lineOffset;
	                acc[4][1] += lineOffset;
	              }
	            }
	            if (currentPath.length === 6) {
	              if (acc[3][1] === currentPath[3][1]) {
	                if (isOverlap([acc[3][0], acc[4][0]], [currentPath[3][0], currentPath[4][0]])) {
	                  acc[3][1] += lineOffset;
	                  acc[4][1] += lineOffset;
	                }
	              }
	            }
	          }

	          /**
	           * Vertical line
	           * 2y -> 3y
	           */

	          if (acc.length === 6) {
	            if (acc[2][0] === currentPath[2][0]) {
	              if (isOverlap([acc[2][1], acc[3][1]], [currentPath[2][1], currentPath[3][1]])) {
	                acc[2][0] += lineOffset;
	                acc[3][0] += lineOffset;
	              }
	            }
	          }
	        }
	        return acc;
	      }, [].concat(path));
	    }
	  }, {
	    key: "getDestinationMarker",
	    value: function getDestinationMarker() {
	      var mousePosition = this.getMarkerRootMousePosition();
	      var destinationMarker = Marker.getMarkerFromPoint(mousePosition);
	      if (destinationMarker && destinationMarker !== this) {
	        return destinationMarker;
	      }
	      return null;
	    }
	  }, {
	    key: "isReceiverIntersecting",
	    value: function isReceiverIntersecting(point) {
	      var receiverRect = this.getReceiverRect();
	      var heightOffset = 10;
	      return point.x > receiverRect.left && point.x < receiverRect.right && point.y > receiverRect.top && point.y < receiverRect.bottom + heightOffset;
	    }
	  }, {
	    key: "blurLinks",
	    value: function blurLinks() {
	      Marker.blurLinks(this);
	    }
	  }, {
	    key: "getData",
	    value: function getData() {
	      return this.data;
	    }
	  }], [{
	    key: "onTunnelButtonMouseLeave",
	    value: function onTunnelButtonMouseLeave() {
	      Marker.unhighlightLinks();
	    }
	  }]);
	  return Marker;
	}(main_core.Event.EventEmitter);
	babelHelpers.defineProperty(Marker, "instances", []);
	babelHelpers.defineProperty(Marker, "paths", []);
	babelHelpers.defineProperty(Marker, "adjustLinksTimeoutIds", {});

	function isLinkInSameCategory(event) {
	  var columnFrom = event.data.from.data.column;
	  var columnTo = event.data.to.data.column;
	  var dataFrom = columnFrom.getData();
	  var dataTo = columnTo.getData();
	  return String(dataFrom.category.id) === String(dataTo.category.id);
	}

	function isCycleLink(event) {
	  var columnFrom = event.data.from.data.column;
	  var columnTo = event.data.to.data.column;
	  return babelHelpers.toConsumableArray(Marker.getAllLinks()).some(function (item) {
	    return item.from === columnTo.marker && item.to === columnFrom.marker;
	  });
	}

	var _templateObject$2, _templateObject2$1, _templateObject3$1;
	if (BX.Kanban.Pagination) {
	  BX.Kanban.Pagination.prototype.adjust = function () {};
	}
	var Column = /*#__PURE__*/function (_Kanban$Column) {
	  babelHelpers.inherits(Column, _Kanban$Column);
	  function Column(options) {
	    var _this;
	    babelHelpers.classCallCheck(this, Column);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Column).call(this, options));
	    _this.currentName = _this.getName();
	    _this.marker = new Marker({
	      dispatcher: _this.getDot(),
	      receiver: _this.getHeader(),
	      point: _this.getDot(),
	      container: _this.getData().appContainer,
	      intermediateXPoints: function intermediateXPoints() {
	        return _this.getIntermediateXPoints();
	      },
	      name: "".concat(_this.getData().categoryName, " (").concat(_this.getName(), ")"),
	      data: {
	        column: babelHelpers.assertThisInitialized(_this)
	      }
	    });
	    _this.marker.subscribe('Marker:dragStart', _this.onMarkerDragStart.bind(babelHelpers.assertThisInitialized(_this))).subscribe('Marker:receiver:dragOver', _this.onMarkerDragOver.bind(babelHelpers.assertThisInitialized(_this))).subscribe('Marker:receiver:dragOut', _this.onMarkerDragOut.bind(babelHelpers.assertThisInitialized(_this))).subscribe('Marker:dragEnd', _this.onMarkerDragEnd.bind(babelHelpers.assertThisInitialized(_this))).subscribe('Marker:linkFrom', _this.onMarkerLinkFrom.bind(babelHelpers.assertThisInitialized(_this))).subscribe('Marker:stubLinkFrom', _this.onMarkerStubLinkFrom.bind(babelHelpers.assertThisInitialized(_this))).subscribe('Marker:linkTo', _this.onMarkerLinkTo.bind(babelHelpers.assertThisInitialized(_this))).subscribe('Marker:stubLinkTo', _this.onMarkerStubLinkTo.bind(babelHelpers.assertThisInitialized(_this))).subscribe('Marker:removeLinkFrom', _this.onRemoveLinkFrom.bind(babelHelpers.assertThisInitialized(_this))).subscribe('Marker:changeRobotAction', _this.onChangeRobotAction.bind(babelHelpers.assertThisInitialized(_this))).subscribe('Marker:editLink', _this.onEditLink.bind(babelHelpers.assertThisInitialized(_this))).subscribe('Marker:unlink', _this.onMarkerUnlink.bind(babelHelpers.assertThisInitialized(_this))).subscribe('Marker:unlinkStub', _this.onMarkerUnlinkStub.bind(babelHelpers.assertThisInitialized(_this))).subscribe('Marker:error', _this.onMarkerError.bind(babelHelpers.assertThisInitialized(_this)));
	    _this.onTransitionStart = _this.onTransitionStart.bind(babelHelpers.assertThisInitialized(_this));
	    _this.onTransitionEnd = _this.onTransitionEnd.bind(babelHelpers.assertThisInitialized(_this));
	    return _this;
	  }
	  babelHelpers.createClass(Column, [{
	    key: "isAllowedTransitionProperty",
	    value: function isAllowedTransitionProperty(propertyName) {
	      return ['width', 'min-width', 'max-width', 'transform'].includes(propertyName);
	    }
	  }, {
	    key: "onTransitionStart",
	    value: function onTransitionStart(event) {
	      if (event.srcElement === this.getContainer() && this.isAllowedTransitionProperty(event.propertyName)) {
	        clearInterval(this.intervalId);
	        this.intervalId = setInterval(Marker.adjustLinks, 16);
	      }
	    }
	  }, {
	    key: "onTransitionEnd",
	    value: function onTransitionEnd(event) {
	      if (event.srcElement === this.getContainer() && this.isAllowedTransitionProperty(event.propertyName)) {
	        clearInterval(this.intervalId);
	        this.intervalId = null;
	      }
	    }
	  }, {
	    key: "setOptions",
	    value: function setOptions(options) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Column.prototype), "setOptions", this).call(this, options);
	      if (main_core.Type.isFunction(options.data.onLink)) {
	        this.onLinkHandler = options.data.onLink;
	      }
	      if (main_core.Type.isFunction(options.data.onRemoveLinkFrom)) {
	        this.onRemoveLinkFromHandler = options.data.onRemoveLinkFrom;
	      }
	      if (main_core.Type.isFunction(options.data.onChangeRobotAction)) {
	        this.onChangeRobotAction = options.data.onChangeRobotAction;
	      }
	      if (main_core.Type.isFunction(options.data.onEditLink)) {
	        this.onEditLinkhandler = options.data.onEditLink;
	      }
	      if (main_core.Type.isFunction(options.data.onNameChange)) {
	        this.onNameChangeHandler = options.data.onNameChange;
	      }
	      if (main_core.Type.isFunction(options.data.onColorChange)) {
	        this.onColorChangeHandler = options.data.onColorChange;
	      }
	      if (main_core.Type.isFunction(options.data.onAddColumn)) {
	        this.onAddColumnHandler = options.data.onAddColumn;
	      }
	      if (main_core.Type.isFunction(options.data.onRemove)) {
	        this.onRemoveHandler = options.data.onRemove;
	      }
	      if (main_core.Type.isFunction(options.data.onChange)) {
	        this.onChangeHandler = options.data.onChange;
	      }
	      if (main_core.Type.isFunction(options.data.onError)) {
	        this.onErrorHandler = options.data.onError;
	      }
	      if (this.marker) {
	        this.marker.container = this.getData().appContainer;
	        if (main_core.Type.isFunction(this.marker.cache.clear)) {
	          this.marker.cache.clear();
	        }
	      }
	    }
	  }, {
	    key: "onMarkerError",
	    value: function onMarkerError(event) {
	      this.onErrorHandler(event.data);
	    }
	  }, {
	    key: "onEditLink",
	    value: function onEditLink(event) {
	      this.onEditLinkhandler(event.data);
	    }
	  }, {
	    key: "onMarkerLinkFrom",
	    value: function onMarkerLinkFrom(event) {
	      this.onLinkHandler(event.data);
	      this.activateDot();
	    }
	  }, {
	    key: "onMarkerStubLinkFrom",
	    value: function onMarkerStubLinkFrom() {
	      this.activateStubDot();
	    }
	  }, {
	    key: "onMarkerStubLinkTo",
	    value: function onMarkerStubLinkTo() {
	      this.activateStubDot();
	    }
	  }, {
	    key: "onRemoveLinkFrom",
	    value: function onRemoveLinkFrom(event) {
	      this.onRemoveLinkFromHandler(event.data);
	    }
	  }, {
	    key: "onMarkerLinkTo",
	    value: function onMarkerLinkTo() {
	      this.activateDot();
	    }
	  }, {
	    key: "getIntermediateXPoints",
	    value: function getIntermediateXPoints() {
	      var _this$getData$stagesG = this.getData().stagesGroups,
	        progressStagesGroup = _this$getData$stagesG.progressStagesGroup,
	        successStagesGroup = _this$getData$stagesG.successStagesGroup,
	        failStagesGroup = _this$getData$stagesG.failStagesGroup;
	      var progressRect = progressStagesGroup.getBoundingClientRect();
	      var successRect = successStagesGroup.getBoundingClientRect();
	      var failRect = failStagesGroup.getBoundingClientRect();
	      var offset = 15;
	      return [progressRect.left + offset, successRect.left - offset, successRect.left + offset, successRect.right - offset, successRect.right + offset, failRect.right - offset / 2];
	    }
	  }, {
	    key: "onMarkerDragStart",
	    value: function onMarkerDragStart() {
	      this.activateDot();
	    }
	  }, {
	    key: "onMarkerDragOver",
	    value: function onMarkerDragOver(event) {
	      if (isLinkInSameCategory(event) || isCycleLink(event)) {
	        event.preventDefault();
	        this.disallowDot();
	        return;
	      }
	      this.allowDot();
	      this.highlightDot();
	    }
	  }, {
	    key: "onMarkerDragOut",
	    value: function onMarkerDragOut() {
	      this.allowDot();
	      this.unhighlightDot();
	    }
	  }, {
	    key: "onMarkerDragEnd",
	    value: function onMarkerDragEnd(event) {
	      if (!this.marker.isLinked()) {
	        this.deactivateDot();
	      }
	      if (event.data.from && event.data.to) {
	        if (isLinkInSameCategory(event) || isCycleLink(event)) {
	          event.preventDefault();
	        }
	      }
	    }
	  }, {
	    key: "onMarkerUnlink",
	    value: function onMarkerUnlink() {
	      this.deactivateDot();
	      this.deactivateStubDot();
	    }
	  }, {
	    key: "onMarkerUnlinkStub",
	    value: function onMarkerUnlinkStub() {
	      this.deactivateStubDot();
	    }
	  }, {
	    key: "activateDot",
	    value: function activateDot() {
	      main_core.Dom.addClass(this.getDot(), 'crm-st-kanban-column-dot-active');
	    }
	  }, {
	    key: "deactivateDot",
	    value: function deactivateDot() {
	      main_core.Dom.removeClass(this.getDot(), 'crm-st-kanban-column-dot-active');
	    }
	  }, {
	    key: "activateStubDot",
	    value: function activateStubDot() {
	      main_core.Dom.addClass(this.getDot(), 'crm-st-kanban-column-dot-active-stub');
	    }
	  }, {
	    key: "deactivateStubDot",
	    value: function deactivateStubDot() {
	      main_core.Dom.removeClass(this.getDot(), 'crm-st-kanban-column-dot-active-stub');
	    }
	  }, {
	    key: "highlightDot",
	    value: function highlightDot() {
	      main_core.Dom.addClass(this.getDot(), 'crm-st-kanban-column-dot-highlight');
	    }
	  }, {
	    key: "unhighlightDot",
	    value: function unhighlightDot() {
	      main_core.Dom.removeClass(this.getDot(), 'crm-st-kanban-column-dot-highlight');
	    }
	  }, {
	    key: "allowDot",
	    value: function allowDot() {
	      main_core.Dom.removeClass(this.getDot(), 'crm-st-kanban-column-dot-disallow');
	    }
	  }, {
	    key: "disallowDot",
	    value: function disallowDot() {
	      main_core.Dom.addClass(this.getDot(), 'crm-st-kanban-column-dot-disallow');
	    }
	  }, {
	    key: "getBody",
	    value: function getBody() {
	      return createStub();
	    }
	  }, {
	    key: "getDot",
	    value: function getDot() {
	      if (!main_core.Type.isDomNode(this.dot)) {
	        var title = main_core.Loc.getMessage('CRM_ST_DOT_TITLE');
	        this.dot = main_core.Tag.render(_templateObject$2 || (_templateObject$2 = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-st-kanban-column-dot\" title=\"", "\">\n\t\t\t\t<span class=\"crm-st-kanban-column-dot-disallow-icon\"> </span>\n\t\t\t\t<span class=\"crm-st-kanban-column-dot-pulse\"> </span>\n\t\t\t</div>"])), title);
	      }
	      return this.dot;
	    }
	  }, {
	    key: "getHeader",
	    value: function getHeader() {
	      var header = babelHelpers.get(babelHelpers.getPrototypeOf(Column.prototype), "getHeader", this).call(this);
	      if (!this.headerDotted) {
	        this.headerDotted = true;
	        var dot = this.getDot();
	        main_core.Event.bind(dot, 'mousedown', function (event) {
	          return event.stopPropagation();
	        });
	        main_core.Event.bind(dot, 'mouseup', function (event) {
	          return event.stopPropagation();
	        });
	        main_core.Event.bind(dot, 'mousemove', function (event) {
	          return event.stopPropagation();
	        });
	        main_core.Dom.append(dot, header);
	      }
	      main_core.Tag.attrs(header)(_templateObject2$1 || (_templateObject2$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\ttitle: ", ";\n\t\t"])), this.getName());
	      return header;
	    }
	  }, {
	    key: "getSubTitle",
	    value: function getSubTitle() {
	      return createStub();
	    }
	  }, {
	    key: "getContainer",
	    value: function getContainer() {
	      var container = babelHelpers.get(babelHelpers.getPrototypeOf(Column.prototype), "getContainer", this).call(this);
	      main_core.Dom.addClass(container, 'crm-st-kanban-column');
	      main_core.Event.bind(container, 'transitionstart', this.onTransitionStart);
	      main_core.Event.bind(container, 'transitionend', this.onTransitionEnd);
	      return container;
	    }
	  }, {
	    key: "getTotalItem",
	    value: function getTotalItem() {
	      this.layout.total = createStub();
	      return this.layout.total;
	    }
	  }, {
	    key: "handleTextBoxBlur",
	    value: function handleTextBoxBlur(event) {
	      var _this2 = this;
	      babelHelpers.get(babelHelpers.getPrototypeOf(Column.prototype), "handleTextBoxBlur", this).call(this, event);
	      setTimeout(function () {
	        if (_this2.currentName !== _this2.getName()) {
	          _this2.onNameChangeHandler(_this2);
	          _this2.currentName = _this2.getName();
	          main_core.Tag.attrs(_this2.getHeader())(_templateObject3$1 || (_templateObject3$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\ttitle: ", ";\n\t\t\t\t"])), _this2.getName());
	        }
	      }, 500);
	    }
	  }, {
	    key: "onColorSelected",
	    value: function onColorSelected(color) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Column.prototype), "onColorSelected", this).call(this, color);
	      this.onColorChangeHandler(this);
	    }
	  }, {
	    key: "handleAddColumnButtonClick",
	    value: function handleAddColumnButtonClick(event) {
	      this.onAddColumnHandler(this);
	    }
	  }, {
	    key: "handleRemoveButtonClick",
	    value: function handleRemoveButtonClick(event) {
	      this.getConfirmDialog().setContent(main_core.Loc.getMessage('CRM_ST_CONFIRM_STAGE_REMOVE_TEXT'));
	      babelHelpers.get(babelHelpers.getPrototypeOf(Column.prototype), "handleRemoveButtonClick", this).call(this, event);
	    }
	  }, {
	    key: "handleConfirmButtonClick",
	    value: function handleConfirmButtonClick() {
	      var _this3 = this;
	      // @todo refactoring

	      var event = new main_core.Event.BaseEvent({
	        data: {
	          column: this,
	          onConfirm: function onConfirm() {
	            Marker.getAllLinks().forEach(function (link) {
	              if (String(link.to.data.column.id) === String(_this3.id)) {
	                link.from.removeLink(link);
	              }
	              if (String(link.from.data.column.id) === String(_this3.id)) {
	                link.from.removeLink(link);
	              }
	            });
	            babelHelpers.get(babelHelpers.getPrototypeOf(Column.prototype), "handleConfirmButtonClick", _this3).call(_this3);
	            setTimeout(function () {
	              Marker.removeAllLinks();
	              Marker.restoreAllLinks();
	            });
	          },
	          onCancel: function onCancel() {
	            _this3.getConfirmDialog().close();
	          }
	        }
	      });
	      this.onRemoveHandler(event);
	    }
	  }, {
	    key: "switchToEditMode",
	    value: function switchToEditMode() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Column.prototype), "switchToEditMode", this).call(this);
	    }
	  }, {
	    key: "applyEditMode",
	    value: function applyEditMode() {
	      var title = BX.util.trim(this.getTitleTextBox().value);
	      var colorChanged = this.colorChanged;
	      var titleChanged = false;
	      if (title.length > 0 && this.getName() !== title) {
	        titleChanged = true;
	      }
	      babelHelpers.get(babelHelpers.getPrototypeOf(Column.prototype), "applyEditMode", this).call(this);
	      if (titleChanged || colorChanged) {
	        this.onChangeHandler(this);
	      }
	      Marker.adjustLinks();
	    }
	  }, {
	    key: "onColumnDrag",
	    value: function onColumnDrag(x, y) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Column.prototype), "onColumnDrag", this).call(this, x, y);
	      Marker.adjustLinks();
	    }
	  }, {
	    key: "resetRectArea",
	    value: function resetRectArea() {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Column.prototype), "resetRectArea", this).call(this);
	      clearTimeout(this.resetRectAreaTimeoutId);
	      this.resetRectAreaTimeoutId = setTimeout(function () {
	        Marker.adjustLinks();
	      }, 200);
	    }
	  }]);
	  return Column;
	}(main_kanban.Kanban.Column);

	var Grid = /*#__PURE__*/function (_Kanban$Grid) {
	  babelHelpers.inherits(Grid, _Kanban$Grid);
	  function Grid() {
	    var _babelHelpers$getProt;
	    var _this;
	    babelHelpers.classCallCheck(this, Grid);
	    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
	      args[_key] = arguments[_key];
	    }
	    _this = babelHelpers.possibleConstructorReturn(this, (_babelHelpers$getProt = babelHelpers.getPrototypeOf(Grid)).call.apply(_babelHelpers$getProt, [this].concat(args)));
	    babelHelpers.defineProperty(babelHelpers.assertThisInitialized(_this), "emitter", new main_core.Event.EventEmitter());
	    return _this;
	  }
	  babelHelpers.createClass(Grid, [{
	    key: "adjustLayout",
	    value: function adjustLayout() {}
	  }, {
	    key: "adjustHeight",
	    value: function adjustHeight() {}
	  }, {
	    key: "getEmptyStub",
	    value: function getEmptyStub() {
	      return createStub();
	    }
	  }, {
	    key: "getDropZoneArea",
	    value: function getDropZoneArea() {
	      var area = babelHelpers.get(babelHelpers.getPrototypeOf(Grid.prototype), "getDropZoneArea", this).call(this);
	      main_core.Dom.addClass(area.getContainer(), 'crm-st-kanban-stub');
	      return area;
	    }
	  }, {
	    key: "getGridContainer",
	    value: function getGridContainer() {
	      var container = babelHelpers.get(babelHelpers.getPrototypeOf(Grid.prototype), "getGridContainer", this).call(this);
	      main_core.Dom.addClass(container, 'crm-st-kanban-grid');
	      return container;
	    }
	  }, {
	    key: "getInnerContainer",
	    value: function getInnerContainer() {
	      var container = babelHelpers.get(babelHelpers.getPrototypeOf(Grid.prototype), "getInnerContainer", this).call(this);
	      main_core.Dom.addClass(container, 'crm-st-kanban-inner');
	      return container;
	    }
	  }, {
	    key: "getOuterContainer",
	    value: function getOuterContainer() {
	      var container = babelHelpers.get(babelHelpers.getPrototypeOf(Grid.prototype), "getOuterContainer", this).call(this);
	      main_core.Dom.addClass(container, 'crm-st-kanban');
	      return container;
	    }
	  }, {
	    key: "getLeftEar",
	    value: function getLeftEar() {
	      return createStub();
	    }
	  }, {
	    key: "getRightEar",
	    value: function getRightEar() {
	      return createStub();
	    }
	  }, {
	    key: "onColumnDragStart",
	    value: function onColumnDragStart(column) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Grid.prototype), "onColumnDragStart", this).call(this, column);
	      Marker.adjustLinks();
	    }
	  }, {
	    key: "onColumnDragStop",
	    value: function onColumnDragStop(column) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Grid.prototype), "onColumnDragStop", this).call(this, column);
	      this.emitter.emit('Kanban.Grid:columns:sort');
	      setTimeout(function () {
	        Marker.adjustLinks();
	      });
	      this.getColumns().forEach(function (column) {
	        clearInterval(column.intervalId);
	      });
	    }
	  }, {
	    key: "getColumns",
	    value: function getColumns() {
	      this.columnsOrder.sort(function (a, b) {
	        if (a.getContainer().parentNode) {
	          var aIndex = babelHelpers.toConsumableArray(a.getContainer().parentNode.children).indexOf(a.getContainer());
	          var bIndex = babelHelpers.toConsumableArray(b.getContainer().parentNode.children).indexOf(b.getContainer());
	          return aIndex > bIndex ? 1 : -1;
	        }
	      });
	      return this.columnsOrder;
	    }
	  }]);
	  return Grid;
	}(main_kanban.Kanban.Grid);

	var _templateObject$3, _templateObject2$2, _templateObject3$2, _templateObject4$1, _templateObject5, _templateObject6, _templateObject7, _templateObject8, _templateObject9, _templateObject10, _templateObject11, _templateObject12, _templateObject13, _templateObject14, _templateObject15, _templateObject16, _templateObject17, _templateObject18, _templateObject19, _templateObject20, _templateObject21, _templateObject22, _templateObject23, _templateObject24, _templateObject25, _templateObject26, _templateObject27, _templateObject28, _templateObject29, _templateObject30, _templateObject31;
	var Category = /*#__PURE__*/function (_Event$EventEmitter) {
	  babelHelpers.inherits(Category, _Event$EventEmitter);
	  babelHelpers.createClass(Category, null, [{
	    key: "createGrid",
	    value: function createGrid(options) {
	      return new Grid({
	        renderTo: options.renderTo,
	        canEditColumn: options.editable === true,
	        canRemoveColumn: options.editable === true,
	        canAddColumn: options.editable === true,
	        canSortColumn: options.editable === true,
	        columnType: options.columnType || 'BX.Crm.SalesTunnels.Kanban.Column',
	        dropzoneType: 'BX.Crm.SalesTunnels.Kanban.DropZone',
	        columns: options.columns
	      });
	    }
	  }]);
	  function Category(options) {
	    var _this;
	    babelHelpers.classCallCheck(this, Category);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Category).call(this));
	    Category.instances.push(babelHelpers.assertThisInitialized(_this));
	    _this.renderTo = options.renderTo;
	    _this.appContainer = options.appContainer;
	    _this.id = options.id;
	    _this.name = options.name;
	    _this.access = options.access;
	    _this.sort = Number.parseInt(options.sort);
	    _this["default"] = Boolean(options["default"]);
	    _this.generatorsCount = Number(options.generatorsCount);
	    _this.generatorsListUrl = options.generatorsListUrl;
	    _this.stages = options.stages;
	    _this.robotsSettingsLink = options.robotsSettingsLink.replace('{category}', _this.id);
	    _this.generatorSettingsLink = options.generatorSettingsLink;
	    _this.permissionEditLink = options.permissionEditLink.replace('{category}', _this.id);
	    _this.cache = new main_core.Cache.MemoryCache();
	    _this.drawed = false;
	    _this.allowWrite = Boolean(options.allowWrite);
	    _this.isCategoryEditable = Boolean(options.isCategoryEditable);
	    _this.areStagesEditable = Boolean(options.areStagesEditable);
	    _this.isAvailableGenerator = options.isAvailableGenerator;
	    _this.isAutomationEnabled = options.isAutomationEnabled;
	    _this.isStagesEnabled = options.isStagesEnabled;
	    _this.entityTypeId = options.entityTypeId;
	    if (!options.lazy) {
	      _this.draw();
	    }
	    if (_this.generatorsCount > 0) {
	      _this.showGeneratorLinkIcon();
	    }
	    var dragButton = _this.getDragButton();
	    dragButton.onbxdragstart = _this.onDragStart.bind(babelHelpers.assertThisInitialized(_this));
	    dragButton.onbxdrag = _this.onDrag.bind(babelHelpers.assertThisInitialized(_this));
	    dragButton.onbxdragstop = _this.onDragStop.bind(babelHelpers.assertThisInitialized(_this));
	    jsDD.registerObject(dragButton, 40);
	    _this.adjustRobotsLinkIcon();
	    _this.getProgressKanban().emitter.subscribe('Kanban.Grid:removeColumn', function (event) {
	      _this.emit('Category:removeStage', event);
	    }).subscribe('Kanban.Grid:columns:sort', function () {
	      setTimeout(function () {
	        _this.emit('Column:sort', {
	          columns: _this.getAllColumns()
	        });
	      }, 500);
	    });
	    _this.getSuccessKanban().emitter.subscribe('Kanban.Grid:removeColumn', function (event) {
	      _this.emit('Category:removeStage', event);
	    }).subscribe('Kanban.Grid:columns:sort', function () {
	      setTimeout(function () {
	        _this.emit('Column:sort', {
	          columns: _this.getAllColumns()
	        });
	      }, 500);
	    });
	    _this.getFailKanban().emitter.subscribe('Kanban.Grid:removeColumn', function (event) {
	      _this.emit('Category:removeStage', event);
	    }).subscribe('Kanban.Grid:columns:sort', function () {
	      setTimeout(function () {
	        _this.emit('Column:sort', {
	          columns: _this.getAllColumns()
	        });
	      }, 500);
	    });
	    if (!_this.isCategoryEditable) {
	      main_core.Dom.addClass(_this.getContainer(), 'crm-st-category-editing-disabled');
	    }
	    if (!_this.isAutomationEnabled) {
	      main_core.Dom.addClass(_this.getContainer(), 'crm-st-category-automation-disabled');
	      _this.getAllColumns().forEach(function (column) {
	        column.marker.disable();
	      });
	    }
	    if (!_this.isStagesEnabled) {
	      main_core.Dom.addClass(_this.getContainer(), 'crm-st-category-stages-stub');
	    }
	    if (!_this.isAvailableGenerator) {
	      main_core.Dom.addClass(_this.getContainer(), 'crm-st-category-generator-disabled');
	    }
	    return _this;
	  }
	  babelHelpers.createClass(Category, [{
	    key: "hasTunnels",
	    value: function hasTunnels() {
	      if (!this.isAutomationEnabled) {
	        return false;
	      }
	      return this.getAllColumns().some(function (column) {
	        return column.marker.links.size > 0;
	      });
	    }
	  }, {
	    key: "getRectArea",
	    value: function getRectArea() {
	      var _this2 = this;
	      return this.cache.remember('rectArea', function () {
	        var rectArea = main_core.pos(_this2.getContainer());
	        rectArea.middle = rectArea.top + rectArea.height / 2;
	        return rectArea;
	      });
	    }
	  }, {
	    key: "getIndex",
	    value: function getIndex() {
	      var _this3 = this;
	      return babelHelpers.toConsumableArray(this.getContainer().parentNode.querySelectorAll('.crm-st-category')).findIndex(function (item) {
	        return item === _this3.getContainer();
	      });
	    }
	  }, {
	    key: "getNextCategorySibling",
	    value: function getNextCategorySibling() {
	      var _this4 = this;
	      return Category.instances.find(function (category, index) {
	        return index > _this4.getIndex();
	      }) || null;
	    } /** @private */
	  }, {
	    key: "onDragStart",
	    value: function onDragStart() {
	      main_core.Dom.addClass(this.getContainer(), 'crm-st-category-drag');
	      Marker.removeAllLinks();

	      // eslint-disable-next-line
	      this.dragOffset = jsDD.start_y - this.getRectArea().top;
	      this.dragIndex = this.getIndex();
	      this.dragTargetCategory = this.dragTargetCategory || this;
	    } /** @private */
	  }, {
	    key: "onDrag",
	    value: function onDrag(x, y) {
	      var _this5 = this;
	      main_core.Tag.style(this.getContainer())(_templateObject$3 || (_templateObject$3 = babelHelpers.taggedTemplateLiteral(["\n\t\t\ttransform: translate3d(0px, ", "px, 0px);\n\t\t"])), y - this.dragOffset - this.getRectArea().top);
	      var categoryHeight = this.getRectArea().height;
	      Category.instances.forEach(function (category, curIndex) {
	        if (category === _this5 || main_core.Dom.hasClass(category.getContainer(), 'crm-st-category-stub')) {
	          return;
	        }
	        var categoryContainer = category.getContainer();
	        var categoryRectArea = category.getRectArea();
	        var categoryMiddle = categoryRectArea.middle;
	        if (y > categoryMiddle && curIndex > _this5.dragIndex && categoryContainer.style.transform !== "translate3d(0px, ".concat(-categoryHeight, "px, 0px)")) {
	          main_core.Tag.style(categoryContainer)(_templateObject2$2 || (_templateObject2$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\ttransition: 200ms;\n\t\t\t\t\ttransform: translate3d(0px, ", "px, 0px);\n\t\t\t\t"])), -categoryHeight);
	          _this5.dragTargetCategory = category.getNextCategorySibling();
	          category.cache["delete"]('rectArea');
	        }
	        if (y < categoryMiddle && curIndex < _this5.dragIndex && categoryContainer.style.transform !== "translate3d(0px, ".concat(categoryHeight, "px, 0px)")) {
	          main_core.Tag.style(categoryContainer)(_templateObject3$2 || (_templateObject3$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\ttransition: 200ms;\n\t\t\t\t\ttransform: translate3d(0px, ", "px, 0px);\n\t\t\t\t"])), categoryHeight);
	          _this5.dragTargetCategory = category;
	          category.cache["delete"]('rectArea');
	        }
	        var moveBackTop = y < categoryMiddle && curIndex > _this5.dragIndex && categoryContainer.style.transform !== '' && categoryContainer.style.transform !== 'translate3d(0, 0, 0)';
	        var moveBackBottom = y > categoryMiddle && curIndex < _this5.dragIndex && categoryContainer.style.transform !== '' && categoryContainer.style.transform !== 'translate3d(0, 0, 0)';
	        if (moveBackBottom || moveBackTop) {
	          main_core.Tag.style(categoryContainer)(_templateObject4$1 || (_templateObject4$1 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\ttransition: 200ms;\n\t\t\t\t\ttransform: translate3d(0px, 0px, 0px);\n\t\t\t\t"])));
	          _this5.dragTargetCategory = category;
	          if (!moveBackTop && main_core.Dom.hasClass(category.getNextCategorySibling(), 'crm-st-category-stub')) {
	            _this5.dragTargetCategory = category.getNextCategorySibling();
	          }
	          category.cache["delete"]('rectArea');
	        }
	      });
	    } /** @private */
	  }, {
	    key: "onDragStop",
	    value: function onDragStop() {
	      main_core.Dom.removeClass(this.getContainer(), 'crm-st-category-drag');
	      requestAnimationFrame(function () {
	        Marker.restoreAllLinks();
	      });
	      Category.instances.forEach(function (category) {
	        main_core.Tag.style(category.getContainer())(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\ttransform: null;\n\t\t\t\ttransition: null;\n\t\t\t"])));
	        category.cache["delete"]('rectArea');
	      });
	      if (this.dragTargetCategory) {
	        main_core.Dom.insertBefore(this.getContainer(), this.dragTargetCategory.getContainer());
	      } else {
	        main_core.Dom.append(this.getContainer(), this.getContainer().parentElement);
	      }
	      var before = Category.instances.map(function (item) {
	        return item.getIndex();
	      });
	      Category.instances.sort(function (a, b) {
	        return a.getIndex() > b.getIndex() ? 1 : -1;
	      });
	      var after = Category.instances.map(function (item) {
	        return item.getIndex();
	      });
	      if (JSON.stringify(before) !== JSON.stringify(after)) {
	        this.emit('Category:sort');
	      }
	    }
	  }, {
	    key: "getContainer",
	    value: function getContainer() {
	      var _this6 = this;
	      return this.cache.remember('container', function () {
	        return main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-st-category\" data-id=\"", "\">\n\t\t\t\t\t<div class=\"crm-st-category-action\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"crm-st-category-info\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t<div class=\"crm-st-category-info-links\">\n\t\t\t\t\t\t\t<div class=\"crm-st-category-info-links-item\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"crm-st-category-info-links-item\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"crm-st-category-stages\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"])), _this6.id, _this6.getDragButton(), _this6.getTitleContainer(), _this6.getRobotsLink(), _this6.getRobotsHelpLink(), _this6.getGeneratorLink(), _this6.getGeneratorHelpLink(), _this6.getProgressContainer(), _this6.getSuccessContainer(), _this6.getFailContainer());
	      });
	    }
	  }, {
	    key: "getRobotsHelpLink",
	    value: function getRobotsHelpLink() {
	      return this.cache.remember('robotsHelpLink', function () {
	        var onClick = function onClick() {
	          if (window.top.BX.Helper) {
	            window.top.BX.Helper.show('redirect=detail&code=6908975');
	          }
	        };
	        return main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<span \n\t\t\t\t\tclass=\"crm-st-category-info-links-help crm-st-automation\" \n\t\t\t\t\tonclick=\"", "\"\n\t\t\t\t\ttitle=\"", "\"\n\t\t\t\t\t> </span>\n\t\t\t"])), onClick, main_core.Text.encode(main_core.Loc.getMessage('CRM_ST_ROBOTS_HELP_BUTTON')));
	      });
	    }
	  }, {
	    key: "getGeneratorHelpLink",
	    value: function getGeneratorHelpLink() {
	      return this.cache.remember('generatorHelpLink', function () {
	        var onClick = function onClick() {
	          if (window.top.BX.Helper) {
	            window.top.BX.Helper.show('redirect=detail&code=7530721');
	          }
	        };
	        return main_core.Tag.render(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<span \n\t\t\t\t\tclass=\"crm-st-category-info-links-help crm-st-generator\" \n\t\t\t\t\tonclick=\"", "\"\n\t\t\t\t\ttitle=\"", "\"\n\t\t\t\t\t> </span>\n\t\t\t"])), onClick, main_core.Text.encode(main_core.Loc.getMessage('CRM_ST_GENERATOR_HELP_BUTTON')));
	      });
	    }
	  }, {
	    key: "getProgressContainer",
	    value: function getProgressContainer() {
	      var _this7 = this;
	      return this.cache.remember('progressContainer', function () {
	        return main_core.Tag.render(_templateObject9 || (_templateObject9 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-st-category-stages-group crm-st-category-stages-group-in-progress\">\n\t\t\t\t\t<div class=\"crm-st-category-stages-group-header\">\n\t\t\t\t\t\t<span class=\"crm-st-category-stages-group-header-text\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</span>\n\t\t\t\t\t</div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), main_core.Loc.getMessage(_this7.isStagesEnabled ? 'CRM_ST_STAGES_GROUP_IN_PROGRESS' : 'CRM_ST_STAGES_DISABLED'), _this7.getProgressStagesContainer());
	      });
	    }
	  }, {
	    key: "getProgressStagesContainer",
	    value: function getProgressStagesContainer() {
	      return this.cache.remember('progressStagesContainer', function () {
	        return main_core.Tag.render(_templateObject10 || (_templateObject10 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-st-category-stages-list\"></div>\n\t\t\t"])));
	      });
	    }
	  }, {
	    key: "getProgressKanban",
	    value: function getProgressKanban() {
	      var _this8 = this;
	      return this.cache.remember('progressKanban', function () {
	        return Category.createGrid({
	          renderTo: _this8.getProgressStagesContainer(),
	          editable: _this8.areStagesEditable,
	          columns: _this8.stages.P.map(function (stage) {
	            return new Column({
	              id: stage.STATUS_ID,
	              name: stage.NAME,
	              color: stage.COLOR.replace('#', ''),
	              data: _this8.getColumnData(stage)
	            });
	          })
	        });
	      });
	    }
	  }, {
	    key: "getSuccessContainer",
	    value: function getSuccessContainer() {
	      var _this9 = this;
	      return this.cache.remember('successContainer', function () {
	        return main_core.Tag.render(_templateObject11 || (_templateObject11 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-st-category-stages-group crm-st-category-stages-group-success\">\n\t\t\t\t\t<div class=\"crm-st-category-stages-group-header\">\n\t\t\t\t\t\t<span class=\"crm-st-category-stages-group-in-success\"> </span> \n\t\t\t\t\t\t<span class=\"crm-st-category-stages-group-header-text\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</span>\n\t\t\t\t\t</div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), _this9.isStagesEnabled ? main_core.Loc.getMessage('CRM_ST_STAGES_GROUP_SUCCESS') : '', _this9.getSuccessStagesContainer());
	      });
	    }
	  }, {
	    key: "getSuccessStagesContainer",
	    value: function getSuccessStagesContainer() {
	      return this.cache.remember('successStagesContainer', function () {
	        return main_core.Tag.render(_templateObject12 || (_templateObject12 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-st-category-stages-list\"></div>\n\t\t\t"])));
	      });
	    }
	  }, {
	    key: "getSuccessKanban",
	    value: function getSuccessKanban() {
	      var _this10 = this;
	      return this.cache.remember('successKanban', function () {
	        return Category.createGrid({
	          renderTo: _this10.getSuccessStagesContainer(),
	          editable: _this10.areStagesEditable,
	          columns: _this10.stages.S.map(function (stage) {
	            return new Column({
	              id: stage.STATUS_ID,
	              name: stage.NAME,
	              color: stage.COLOR.replace('#', ''),
	              data: _this10.getColumnData(stage),
	              canRemove: false,
	              canSort: false
	            });
	          })
	        });
	      });
	    }
	  }, {
	    key: "getFailContainer",
	    value: function getFailContainer() {
	      var _this11 = this;
	      return this.cache.remember('failContainer', function () {
	        return main_core.Tag.render(_templateObject13 || (_templateObject13 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-st-category-stages-group crm-st-category-stages-group-fail\">\n\t\t\t\t\t<div class=\"crm-st-category-stages-group-header\">\n\t\t\t\t\t\t<span class=\"crm-st-category-stages-group-in-fail\"> </span> \n\t\t\t\t\t\t<span class=\"crm-st-category-stages-group-header-text\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</span>\n\t\t\t\t\t</div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), _this11.isStagesEnabled ? main_core.Loc.getMessage('CRM_ST_STAGES_GROUP_FAIL') : '', _this11.getFailStagesContainer());
	      });
	    }
	  }, {
	    key: "getFailStagesContainer",
	    value: function getFailStagesContainer() {
	      return this.cache.remember('failStagesContainer', function () {
	        return main_core.Tag.render(_templateObject14 || (_templateObject14 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-st-category-stages-list\"></div>\n\t\t\t"])));
	      });
	    }
	  }, {
	    key: "getFailKanban",
	    value: function getFailKanban() {
	      var _this12 = this;
	      return this.cache.remember('failKanban', function () {
	        return Category.createGrid({
	          renderTo: _this12.getFailStagesContainer(),
	          editable: _this12.areStagesEditable,
	          columns: _this12.stages.F.map(function (stage) {
	            return new Column({
	              id: stage.STATUS_ID,
	              name: stage.NAME,
	              color: stage.COLOR.replace('#', ''),
	              data: _this12.getColumnData(stage)
	            });
	          })
	        });
	      });
	    }
	  }, {
	    key: "getColumnData",
	    value: function getColumnData(stage) {
	      var _this13 = this;
	      return {
	        stageId: stage.ID,
	        entityId: stage.ENTITY_ID,
	        stage: stage,
	        onLink: function onLink(link) {
	          _this13.emit('Column:link', link);
	          _this13.adjustRobotsLinkIcon();
	        },
	        onRemoveLinkFrom: function onRemoveLinkFrom(link) {
	          _this13.emit('Column:removeLinkFrom', link);
	          _this13.adjustRobotsLinkIcon();
	        },
	        onChangeRobotAction: function onChangeRobotAction(event) {
	          _this13.emit('Column:changeRobotAction', event);
	        },
	        onEditLink: function onEditLink(link) {
	          _this13.emit('Column:editLink', link);
	          _this13.adjustRobotsLinkIcon();
	        },
	        onNameChange: function onNameChange(column) {
	          _this13.emit('Column:nameChange', {
	            column: column
	          });
	        },
	        onColorChange: function onColorChange(column) {
	          _this13.emit('Column:colorChange', {
	            column: column
	          });
	        },
	        onAddColumn: function onAddColumn(column) {
	          _this13.emit('Column:addColumn', {
	            column: column
	          });
	        },
	        onRemove: function onRemove(event) {
	          _this13.emit('Column:remove', event);
	        },
	        onChange: function onChange(column) {
	          _this13.emit('Column:change', {
	            column: column
	          });
	        },
	        onSort: function onSort() {
	          _this13.emit('Column:sort', {
	            columns: _this13.getAllColumns()
	          });
	        },
	        onError: function onError(event) {
	          _this13.emit('Column:error', event);
	        },
	        category: this,
	        appContainer: this.appContainer,
	        categoryContainer: this.getFailContainer(),
	        stagesGroups: {
	          progressStagesGroup: this.getProgressContainer(),
	          successStagesGroup: this.getSuccessContainer(),
	          failStagesGroup: this.getFailContainer()
	        },
	        currentStageGroup: this.getFailContainer(),
	        categoryName: this.getTitle().textContent,
	        isCategoryEditable: this.isCategoryEditable,
	        areStagesEditable: this.areStagesEditable
	      };
	    } /** @private */
	  }, {
	    key: "onRightsLinkClick",
	    value: function onRightsLinkClick(event) {
	      var _this14 = this;
	      event.preventDefault();

	      // eslint-disable-next-line
	      BX.SidePanel.Instance.open(this.robotsSettingsLink, {
	        cacheable: false,
	        events: {
	          onClose: function onClose() {
	            _this14.emit('Category:slider:close');
	            _this14.emit('Category:slider:robots:close');
	          }
	        }
	      });
	    }
	  }, {
	    key: "getRobotsLink",
	    value: function getRobotsLink() {
	      var _this15 = this;
	      return this.cache.remember('robotsLink', function () {
	        if (!_this15.isAutomationEnabled) {
	          return '<span></span>';
	        }
	        var isRestricted = BX.Crm.Restriction.Bitrix24.isRestricted('automation');
	        var onClick = isRestricted ? BX.Crm.Restriction.Bitrix24.getHandler('automation') : _this15.onRobotsLinkClick.bind(_this15);
	        return main_core.Tag.render(_templateObject15 || (_templateObject15 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t", "\n\t\t\t\t<span class=\"crm-st-category-info-links-link crm-st-robots-link crm-st-automation\" onclick=\"", "\">\n\t\t\t\t\t", "\n\t\t\t\t</span>\n\t\t\t"])), isRestricted ? '<span class="tariff-lock"></span>' : '', onClick, main_core.Loc.getMessage('CRM_ST_ROBOT_SETTINGS_LINK_LABEL'));
	      });
	    } /** @private */
	  }, {
	    key: "onRobotsLinkClick",
	    value: function onRobotsLinkClick(event) {
	      var _this16 = this;
	      event.preventDefault();
	      // eslint-disable-next-line
	      BX.SidePanel.Instance.open(this.robotsSettingsLink, {
	        cacheable: false,
	        events: {
	          onClose: function onClose() {
	            _this16.emit('Category:slider:close');
	            _this16.emit('Category:slider:robots:close');
	          }
	        }
	      });
	    }
	  }, {
	    key: "getGeneratorLink",
	    value: function getGeneratorLink() {
	      var _this17 = this;
	      return this.cache.remember('generatorLink', function () {
	        if (!_this17.isAvailableGenerator) {
	          return '<span></span>';
	        }
	        var isRestricted = BX.Crm.Restriction.Bitrix24.isRestricted('generator');
	        var onClick = isRestricted ? BX.Crm.Restriction.Bitrix24.getHandler('generator') : _this17.onGeneratorLinkClick.bind(_this17);
	        return main_core.Tag.render(_templateObject16 || (_templateObject16 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t", "\n\t\t\t\t<span class=\"crm-st-category-info-links-link crm-st-generator-link crm-st-generator\" onclick=\"", "\">\n\t\t\t\t\t", "\n\t\t\t\t</span>\n\t\t\t"])), isRestricted ? '<span class="tariff-lock"></span>' : '', onClick, main_core.Loc.getMessage('CRM_ST_GENERATOR_SETTINGS_LINK_LABEL'));
	      });
	    } /** @private */
	  }, {
	    key: "onGeneratorLinkClick",
	    value: function onGeneratorLinkClick(event) {
	      var _this18 = this;
	      event.preventDefault();

	      // eslint-disable-next-line
	      BX.SidePanel.Instance.open(this.generatorSettingsLink, {
	        cacheable: false,
	        events: {
	          onClose: function onClose() {
	            _this18.emit('Category:slider:close');
	            _this18.emit('Category:slider:generator:close', {
	              category: _this18
	            });
	          }
	        }
	      });
	    }
	  }, {
	    key: "getEditButton",
	    value: function getEditButton() {
	      var _this19 = this;
	      return this.cache.remember('editButton', function () {
	        return main_core.Tag.render(_templateObject17 || (_templateObject17 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<span \n\t\t\t\t\tclass=\"crm-st-edit-button\" \n\t\t\t\t\tonmousedown=\"", "\"\n\t\t\t\t\ttitle=\"", "\"\n\t\t\t\t\t> </span>\n\t\t\t"])), _this19.onEditButtonClick.bind(_this19), main_core.Loc.getMessage('CRM_ST_EDIT_CATEGORY_TITLE'));
	      });
	    }
	  }, {
	    key: "activateEditButton",
	    value: function activateEditButton() {
	      main_core.Dom.addClass(this.getEditButton(), 'crm-st-edit-button-active');
	    }
	  }, {
	    key: "deactivateEditButton",
	    value: function deactivateEditButton() {
	      main_core.Dom.removeClass(this.getEditButton(), 'crm-st-edit-button-active');
	    }
	  }, {
	    key: "onEditButtonClick",
	    value: function onEditButtonClick(event) {
	      if (event) {
	        event.preventDefault();
	      }
	      if (this.isTitleEditEnabled()) {
	        this.disableTitleEdit();
	        this.saveTitle();
	        return;
	      }
	      this.enableTitleEdit();
	    }
	  }, {
	    key: "showTitleEditor",
	    value: function showTitleEditor() {
	      var value = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
	      var titleEditor = this.getTitleEditor();
	      var _this$getTitle = this.getTitle(),
	        textContent = _this$getTitle.textContent;
	      titleEditor.value = main_core.Type.isString(value) ? value : main_core.Text.decode(textContent);
	      main_core.Tag.style(titleEditor)(_templateObject18 || (_templateObject18 = babelHelpers.taggedTemplateLiteral(["\n\t\t\tdisplay: block;\n\t\t"])));
	    }
	  }, {
	    key: "hideTitleEditor",
	    value: function hideTitleEditor() {
	      var titleEditor = this.getTitleEditor();
	      main_core.Tag.style(titleEditor)(_templateObject19 || (_templateObject19 = babelHelpers.taggedTemplateLiteral(["\n\t\t\tdisplay: null;\n\t\t"])));
	    }
	  }, {
	    key: "focusOnTitleEditor",
	    value: function focusOnTitleEditor() {
	      var titleEditor = this.getTitleEditor();
	      titleEditor.focus();
	      var title = this.getTitle();
	      titleEditor.setSelectionRange(titleLength, titleLength);
	      var titleLength = title.textContent.length;
	    }
	  }, {
	    key: "showTitle",
	    value: function showTitle() {
	      main_core.Tag.style(this.getTitle())(_templateObject20 || (_templateObject20 = babelHelpers.taggedTemplateLiteral(["\n\t\t\tdisplay: null;\n\t\t"])));
	    }
	  }, {
	    key: "hideTitle",
	    value: function hideTitle() {
	      main_core.Tag.style(this.getTitle())(_templateObject21 || (_templateObject21 = babelHelpers.taggedTemplateLiteral(["\n\t\t\tdisplay: none;\n\t\t"])));
	    }
	  }, {
	    key: "saveTitle",
	    value: function saveTitle() {
	      var title = this.getTitle();
	      var titleEditor = this.getTitleEditor();
	      var value = titleEditor.value;
	      var newTitle = value.trim() || main_core.Loc.getMessage('CRM_ST_TITLE_EDITOR_PLACEHOLDER2');
	      if (title.textContent !== newTitle) {
	        title.textContent = newTitle;
	        main_core.Dom.attr(title, 'title', newTitle);
	        this.name = newTitle;
	        this.emit('Category:title:save', {
	          categoryId: this.id,
	          value: newTitle
	        });
	      }
	    }
	  }, {
	    key: "enableTitleEdit",
	    value: function enableTitleEdit() {
	      var value = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
	      this.hideTitle();
	      this.showTitleEditor(value);
	      this.activateEditButton();
	      this.focusOnTitleEditor();
	    }
	  }, {
	    key: "disableTitleEdit",
	    value: function disableTitleEdit() {
	      this.showTitle();
	      this.hideTitleEditor();
	      this.deactivateEditButton();
	    }
	  }, {
	    key: "isTitleEditEnabled",
	    value: function isTitleEditEnabled() {
	      return main_core.Dom.hasClass(this.getEditButton(), 'crm-st-edit-button-active');
	    }
	  }, {
	    key: "getOptionButton",
	    value: function getOptionButton() {
	      var _this20 = this;
	      return this.cache.remember('optionButton', function () {
	        return main_core.Tag.render(_templateObject22 || (_templateObject22 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<span \n\t\t\t\t\tclass=\"crm-st-option-button\" \n\t\t\t\t\tonclick=\"", "\" \n\t\t\t\t\ttitle=\"", "\"\n\t\t\t\t\t> </span>\n\t\t\t"])), _this20.onOptionButtonClick.bind(_this20), main_core.Loc.getMessage('CRM_ST_EDIT_RIGHTS_CATEGORY'));
	      });
	    }
	  }, {
	    key: "onOptionButtonClick",
	    value: function onOptionButtonClick() {
	      var _this21 = this;
	      var onMenuItemClick = function onMenuItemClick(event, item) {
	        _this21.emit('Category:access', {
	          categoryId: _this21.id,
	          access: item.dataset.access,
	          onConfirm: function onConfirm() {},
	          onCancel: function onCancel() {}
	        });
	        main_core.Dom.addClass(item.getContainer(), 'menu-popup-item-accept');
	        main_core.Dom.removeClass(item.getContainer(), 'menu-popup-no-icon');
	        _this21.access = item.dataset.access;
	        _this21.menuWindow.getMenuItems().forEach(function (itemOther) {
	          if (itemOther === item) {
	            return;
	          }
	          main_core.Dom.removeClass(itemOther.getContainer(), 'menu-popup-item-accept');
	          main_core.Dom.addClass(itemOther.getContainer(), 'menu-popup-no-icon');
	        });
	        _this21.menuWindow.close();
	      };
	      var onSubMenuItemClick = function onSubMenuItemClick(event, item) {
	        _this21.emit('Category:access:copy', {
	          categoryId: _this21.id,
	          donorCategoryId: item.dataset.categoryId,
	          onConfirm: function onConfirm() {},
	          onCancel: function onCancel() {}
	        });
	        _this21.access = item.dataset.access;
	        _this21.menuWindow.getMenuItems().forEach(function (itemOther) {
	          if (itemOther.dataset === null) {
	            return;
	          }
	          if (_this21.access === itemOther.dataset.access) {
	            main_core.Dom.addClass(itemOther.getContainer(), 'menu-popup-item-accept');
	            main_core.Dom.removeClass(itemOther.getContainer(), 'menu-popup-no-icon');
	          } else {
	            main_core.Dom.removeClass(itemOther.getContainer(), 'menu-popup-item-accept');
	            main_core.Dom.addClass(itemOther.getContainer(), 'menu-popup-no-icon');
	          }
	        });
	        _this21.menuWindow.close();
	      };
	      var items = Category.instances.filter(function (category) {
	        return _this21.id !== category.id && category.id !== 'stub';
	      }).map(function (category) {
	        return {
	          text: main_core.Text.encode(category.name),
	          dataset: {
	            categoryId: category.id,
	            access: category.access
	          },
	          onclick: onSubMenuItemClick
	        };
	      });
	      var myItemsText = this.entityTypeId === BX.CrmEntityType.enumeration.deal ? main_core.Loc.getMessage('CRM_MENU_RIGHTS_CATEGORY_OWN_FOR_ALL_MSGVER_1') : main_core.Loc.getMessage('CRM_MENU_RIGHTS_CATEGORY_OWN_FOR_ELEMENT_MSGVER_1');
	      this.menuWindow = new main_popup.Menu({
	        id: "crm-tunnels-menu-".concat(main_core.Text.getRandom().toLowerCase()),
	        bindElement: this.getOptionButton(),
	        items: [{
	          text: main_core.Loc.getMessage('CRM_MENU_RIGHTS_CATEGORY_ALL_FOR_ALL_MSGVER_1'),
	          dataset: {
	            access: 'X'
	          }
	        }, {
	          text: main_core.Loc.getMessage('CRM_MENU_RIGHTS_CATEGORY_NONE_FOR_ALL_MSGVER_1'),
	          dataset: {
	            access: ''
	          }
	        }, {
	          text: myItemsText,
	          dataset: {
	            access: 'A'
	          }
	        }, items.length > 0 ? {
	          text: main_core.Loc.getMessage('CRM_MENU_RIGHTS_CATEGORY_COPY_FROM_TUNNELS2'),
	          items: items
	        } : null, {
	          delimiter: true
	        }, {
	          text: main_core.Loc.getMessage('CRM_MENU_RIGHTS_CATEGORY_CUSTOM'),
	          dataset: {
	            access: false
	          },
	          className: this.access !== 'A' && this.access !== 'X' && this.access !== '' ? 'menu-popup-item-accept' : '',
	          href: this.permissionEditLink,
	          target: '_blank',
	          onclick: function onclick(event, item) {
	            item.getMenuWindow().close();
	          }
	        }].filter(function (preItem) {
	          return preItem !== null;
	        }).map(function (preItem) {
	          if (preItem.dataset) {
	            if (_this21.access === preItem.dataset.access) {
	              preItem.className = 'menu-popup-item-accept';
	            }
	            if (!preItem.onclick) {
	              preItem.onclick = onMenuItemClick;
	            }
	          }
	          return preItem;
	        }),
	        events: {
	          onClose: function () {
	            main_core.Dom.removeClass(this.getOptionButton(), 'crm-st-option-button-active');
	            main_core.Dom.removeClass(this.getActionsButtons(), 'crm-st-category-action-buttons-active');
	            setTimeout(this.removeBlur.bind(this), 200);
	          }.bind(this)
	        },
	        angle: true,
	        offsetLeft: 9
	      });
	      main_core.Dom.addClass(this.getActionsButtons(), 'crm-st-category-action-buttons-active');
	      main_core.Dom.addClass(this.getOptionButton(), 'crm-st-option-button-active');
	      this.menuWindow.show();
	      this.addBlur();
	    }
	  }, {
	    key: "getRemoveButton",
	    value: function getRemoveButton() {
	      var _this22 = this;
	      return this.cache.remember('removeButton', function () {
	        var button = main_core.Tag.render(_templateObject23 || (_templateObject23 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<span \n\t\t\t\t\tclass=\"crm-st-remove-button\" \n\t\t\t\t\tonclick=\"", "\" \n\t\t\t\t\ttitle=\"", "\"\n\t\t\t\t\t> </span>\n\t\t\t"])), _this22.onRemoveButtonClick.bind(_this22), main_core.Loc.getMessage('CRM_ST_REMOVE_CATEGORY2'));
	        if (_this22["default"]) {
	          main_core.Tag.style(button)(_templateObject24 || (_templateObject24 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\tdisplay: none;\n\t\t\t\t"])));
	        }
	        return button;
	      });
	    }
	  }, {
	    key: "onRemoveButtonClick",
	    value: function onRemoveButtonClick() {
	      var _this23 = this;
	      this.showConfirmRemovePopup().then(function (_ref) {
	        var confirm = _ref.confirm;
	        if (confirm) {
	          _this23.emit('Category:remove', {
	            categoryId: _this23.id,
	            onConfirm: function onConfirm() {
	              _this23.remove();
	            },
	            onCancel: function onCancel() {
	              _this23.removeBlur();
	            }
	          });
	          return;
	        }
	        _this23.removeBlur();
	      });
	      this.addBlur();
	    }
	  }, {
	    key: "getAllColumns",
	    value: function getAllColumns() {
	      var progressColumns = this.getProgressKanban().getColumns().sort(function (a, b) {
	        return a.getIndex() > b.getIndex() ? 1 : -1;
	      });
	      var successColumn = this.getSuccessKanban().getColumns().sort(function (a, b) {
	        return a.getIndex() > b.getIndex() ? 1 : -1;
	      });
	      var failColumns = this.getFailKanban().getColumns().sort(function (a, b) {
	        return a.getIndex() > b.getIndex() ? 1 : -1;
	      });
	      return [].concat(babelHelpers.toConsumableArray(progressColumns), babelHelpers.toConsumableArray(successColumn), babelHelpers.toConsumableArray(failColumns));
	    }
	  }, {
	    key: "addBlur",
	    value: function addBlur() {
	      main_core.Dom.addClass(this.getContainer(), 'crm-st-blur');
	      this.getAllColumns().forEach(function (column) {
	        column.marker.blurLinks();
	      });
	    }
	  }, {
	    key: "removeBlur",
	    value: function removeBlur() {
	      main_core.Dom.removeClass(this.getContainer(), 'crm-st-blur');
	      Marker.unblurLinks();
	    }
	  }, {
	    key: "remove",
	    value: function remove() {
	      var _this24 = this;
	      main_core.Dom.remove(this.getContainer());
	      Marker.getAllLinks().forEach(function (link) {
	        var columnFrom = link.from.data.column;
	        var categoryFrom = columnFrom.getData().category;
	        var columnTo = link.to.data.column;
	        var categoryTo = columnTo.getData().category;
	        if (String(categoryFrom.id) === String(_this24.id)) {
	          link.from.removeLink(link);
	        }
	        if (String(categoryTo.id) === String(_this24.id)) {
	          link.to.removeLink(link);
	        }
	      });
	      Marker.getAllStubLinks().forEach(function (link) {
	        var columnFrom = link.from.data.column;
	        var categoryFrom = columnFrom.getData().category;
	        var columnTo = link.to.data.column;
	        var categoryTo = columnTo.getData().category;
	        if (String(categoryFrom.id) === String(_this24.id)) {
	          link.from.removeStubLink(link);
	        }
	        if (String(categoryTo.id) === String(_this24.id)) {
	          link.to.removeStubLink(link);
	        }
	      });
	      Category.instances = Category.instances.filter(function (item) {
	        return item !== _this24;
	      });
	    }
	  }, {
	    key: "getTitle",
	    value: function getTitle() {
	      var safeTitle = main_core.Text.encode(this.name);
	      return this.cache.remember('title', function () {
	        return main_core.Tag.render(_templateObject25 || (_templateObject25 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<h3 class=\"crm-st-category-info-title\" title=\"", "\">", "</h3>\n\t\t\t"])), safeTitle, safeTitle);
	      });
	    }
	  }, {
	    key: "getTitleEditor",
	    value: function getTitleEditor() {
	      var _this25 = this;
	      return this.cache.remember('titleEditor', function () {
	        var onKeyDown = _this25.onTitleEditorKeyDown.bind(_this25);
	        var onBlur = _this25.onTitleEditorBlur.bind(_this25);
	        return main_core.Tag.render(_templateObject26 || (_templateObject26 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<input class=\"crm-st-category-info-title-editor\" \n\t\t\t\t\t onkeydown=\"", "\"\n\t\t\t\t\t onblur=\"", "\"\n\t\t\t\t\t value=\"", "\"\n\t\t\t\t\t placeholder=\"", "\"\n\t\t\t\t >\n\t\t\t"])), onKeyDown, onBlur, main_core.Text.encode(_this25.name), main_core.Loc.getMessage('CRM_ST_TITLE_EDITOR_PLACEHOLDER2'));
	      });
	    }
	  }, {
	    key: "onTitleEditorKeyDown",
	    value: function onTitleEditorKeyDown(event) {
	      event.stopPropagation();
	      if (this.isTitleEditEnabled()) {
	        if (event.key.startsWith('Enter')) {
	          this.onEditButtonClick();
	        }
	        if (event.key.startsWith('Esc')) {
	          this.disableTitleEdit();
	        }
	      }
	    }
	  }, {
	    key: "onTitleEditorBlur",
	    value: function onTitleEditorBlur() {
	      if (this.isTitleEditEnabled()) {
	        this.onEditButtonClick();
	      }
	    }
	  }, {
	    key: "getActionsButtons",
	    value: function getActionsButtons() {
	      var _this26 = this;
	      return this.cache.remember('getActionsButtons', function () {
	        return main_core.Tag.render(_templateObject27 || (_templateObject27 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-st-category-action-buttons\">\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), _this26.isCategoryEditable ? _this26.getEditButton() : '', _this26.isCategoryEditable ? _this26.getOptionButton() : '', _this26.isCategoryEditable ? _this26.getRemoveButton() : '');
	      });
	    }
	  }, {
	    key: "getTitleContainer",
	    value: function getTitleContainer() {
	      var _this27 = this;
	      return this.cache.remember('titleContainer', function () {
	        return main_core.Tag.render(_templateObject28 || (_templateObject28 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-st-category-info-title-container\">\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), _this27.getTitle(), _this27.getTitleEditor(), _this27.getActionsButtons());
	      });
	    }
	  }, {
	    key: "getDragButton",
	    value: function getDragButton() {
	      return this.cache.remember('dragButton', function () {
	        return main_core.Tag.render(_templateObject29 || (_templateObject29 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<span \n\t\t\t\t\tclass=\"crm-st-category-action-drag\"\n\t\t\t\t\ttitle=\"", "\"\n\t\t\t\t\t>&nbsp;</span>\n\t\t\t"])), main_core.Loc.getMessage('CRM_ST_CATEGORY_DRAG_BUTTON2'));
	      });
	    }
	  }, {
	    key: "isDrawed",
	    value: function isDrawed() {
	      return this.drawed;
	    }
	  }, {
	    key: "draw",
	    value: function draw() {
	      if (!this.isDrawed()) {
	        this.drawed = true;
	        main_core.Dom.append(this.getContainer(), this.renderTo);
	        this.getProgressKanban().draw();
	        this.getSuccessKanban().draw();
	        this.getFailKanban().draw();
	      }
	    }
	  }, {
	    key: "getKanbanColumn",
	    value: function getKanbanColumn(columnId) {
	      var columns = [].concat(babelHelpers.toConsumableArray(this.getProgressKanban().getColumns()), babelHelpers.toConsumableArray(this.getSuccessKanban().getColumns()), babelHelpers.toConsumableArray(this.getFailKanban().getColumns()));
	      return columns.find(function (column) {
	        return columnId === column.getId() || columnId === column.getData().statusId;
	      });
	    }
	  }, {
	    key: "showConfirmRemovePopup",
	    value: function showConfirmRemovePopup() {
	      var _this28 = this;
	      return new Promise(function (resolve) {
	        void new main_popup.PopupWindow({
	          width: 400,
	          overlay: {
	            opacity: 30
	          },
	          titleBar: main_core.Loc.getMessage('CRM_ST_REMOVE_CATEGORY_CONFIRM_POPUP_TITLE2').replace('#name#', _this28.getTitle().textContent),
	          content: main_core.Loc.getMessage('CRM_ST_REMOVE_CATEGORY_CONFIRM_POPUP_DESCRIPTION2'),
	          buttons: [new main_popup.PopupWindowButton({
	            text: main_core.Loc.getMessage('CRM_ST_REMOVE_CATEGORY_CONFIRM_REMOVE_BUTTON_LABEL2'),
	            className: 'popup-window-button-decline',
	            events: {
	              click: function click() {
	                resolve({
	                  confirm: true
	                });
	                this.popupWindow.destroy();
	              }
	            }
	          }), new main_popup.PopupWindowButtonLink({
	            text: main_core.Loc.getMessage('CRM_ST_REMOVE_CATEGORY_CONFIRM_CANCEL_BUTTON_LABEL'),
	            events: {
	              click: function click() {
	                resolve({
	                  confirm: false
	                });
	                this.popupWindow.destroy();
	              }
	            }
	          })]
	        }).show();
	      });
	    }
	  }, {
	    key: "getRobotsLinkIcon",
	    value: function getRobotsLinkIcon() {
	      return this.cache.remember('robotsLinkIcon', function () {
	        return main_core.Tag.render(_templateObject30 || (_templateObject30 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<span class=\"crm-st-robots-link-icon\"> </span>\n\t\t\t"])));
	      });
	    }
	  }, {
	    key: "showRobotsLinkIcon",
	    value: function showRobotsLinkIcon() {
	      main_core.Dom.insertAfter(this.getRobotsLinkIcon(), this.getRobotsLink());
	    }
	  }, {
	    key: "hideRobotsLinkIcon",
	    value: function hideRobotsLinkIcon() {
	      main_core.Dom.remove(this.getRobotsLinkIcon());
	    }
	  }, {
	    key: "adjustRobotsLinkIcon",
	    value: function adjustRobotsLinkIcon() {
	      var _this29 = this;
	      setTimeout(function () {
	        if (_this29.hasTunnels()) {
	          _this29.showRobotsLinkIcon();
	          return;
	        }
	        _this29.hideRobotsLinkIcon();
	      });
	    }
	  }, {
	    key: "getGeneratorLinkIcon",
	    value: function getGeneratorLinkIcon() {
	      var _this30 = this;
	      return this.cache.remember('generatorLinkIcon', function () {
	        var onClick = function onClick() {
	          return BX.SidePanel.Instance.open(_this30.generatorsListUrl);
	        };
	        return main_core.Tag.render(_templateObject31 || (_templateObject31 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<span class=\"crm-st-generator-link-icon\" onclick=\"", "\">", "</span>\n\t\t\t"])), onClick, _this30.generatorsCount);
	      });
	    }
	  }, {
	    key: "showGeneratorLinkIcon",
	    value: function showGeneratorLinkIcon() {
	      main_core.Dom.insertAfter(this.getGeneratorLinkIcon(), this.getGeneratorLink());
	    }
	  }]);
	  return Category;
	}(main_core.Event.EventEmitter);
	babelHelpers.defineProperty(Category, "instances", []);

	var Backend = /*#__PURE__*/function () {
	  function Backend() {
	    babelHelpers.classCallCheck(this, Backend);
	  }
	  babelHelpers.createClass(Backend, null, [{
	    key: "request",
	    value: function request(_ref) {
	      var action = _ref.action,
	        data = _ref.data,
	        analyticsLabel = _ref.analyticsLabel;
	      return new Promise(function (resolve, reject) {
	        main_core.ajax.runComponentAction(Backend.component, action, {
	          mode: 'class',
	          data: {
	            data: data,
	            entityTypeId: Backend.entityTypeId
	          },
	          analyticsLabel: analyticsLabel
	        }).then(resolve, reject);
	      });
	    }
	  }, {
	    key: "createCategory",
	    value: function createCategory(data) {
	      return Backend.request({
	        action: 'createCategory',
	        analyticsLabel: {
	          component: Backend.component,
	          action: 'create.new.category'
	        },
	        data: data
	      });
	    }
	  }, {
	    key: "getCategory",
	    value: function getCategory(data) {
	      return Backend.request({
	        action: 'getCategory',
	        analyticsLabel: {
	          component: Backend.component,
	          action: 'get.category'
	        },
	        data: data
	      });
	    }
	  }, {
	    key: "updateCategory",
	    value: function updateCategory(data) {
	      return Backend.request({
	        action: 'updateCategory',
	        analyticsLabel: {
	          component: Backend.component,
	          action: 'update.category'
	        },
	        data: data
	      });
	    }
	  }, {
	    key: "removeCategory",
	    value: function removeCategory(data) {
	      return Backend.request({
	        action: 'removeCategory',
	        analyticsLabel: {
	          component: Backend.component,
	          action: 'remove.category'
	        },
	        data: data
	      });
	    }
	  }, {
	    key: "accessCategory",
	    value: function accessCategory(data) {
	      return Backend.request({
	        action: 'accessCategory',
	        analyticsLabel: {
	          component: Backend.component,
	          action: 'access.category'
	        },
	        data: data
	      });
	    }
	  }, {
	    key: "copyAccessCategory",
	    value: function copyAccessCategory(data) {
	      return Backend.request({
	        action: 'copyAccessCategory',
	        analyticsLabel: {
	          component: Backend.component,
	          action: 'access.category'
	        },
	        data: data
	      });
	    }
	  }, {
	    key: "createRobot",
	    value: function createRobot(data) {
	      return Backend.request({
	        action: 'createRobot',
	        analyticsLabel: {
	          component: Backend.component,
	          action: 'create.robot'
	        },
	        data: data
	      });
	    }
	  }, {
	    key: "removeRobot",
	    value: function removeRobot(data) {
	      return Backend.request({
	        action: 'removeRobot',
	        analyticsLabel: {
	          component: Backend.component,
	          action: 'remove.robot'
	        },
	        data: data
	      });
	    }
	  }, {
	    key: "getRobotSettingsDialog",
	    value: function getRobotSettingsDialog(data) {
	      return Backend.request({
	        action: 'getRobotSettingsDialog',
	        analyticsLabel: {
	          component: Backend.component,
	          action: 'settings.robot'
	        },
	        data: data
	      });
	    }
	  }, {
	    key: "addStage",
	    value: function addStage(data) {
	      return Backend.request({
	        action: 'addStage',
	        analyticsLabel: {
	          component: Backend.component,
	          action: 'add.stage'
	        },
	        data: data
	      });
	    }
	  }, {
	    key: "removeStage",
	    value: function removeStage(data) {
	      return Backend.request({
	        action: 'removeStage',
	        analyticsLabel: {
	          component: Backend.component,
	          action: 'remove.stage'
	        },
	        data: data
	      });
	    }
	  }, {
	    key: "updateStage",
	    value: function updateStage(data) {
	      return Backend.request({
	        action: 'updateStage',
	        analyticsLabel: {
	          component: Backend.component,
	          action: 'update.stage'
	        },
	        data: data
	      });
	    }
	  }, {
	    key: "updateStages",
	    value: function updateStages(data) {
	      return Backend.request({
	        action: 'updateStages',
	        analyticsLabel: {
	          component: Backend.component,
	          action: 'update.stages'
	        },
	        data: data
	      });
	    }
	  }, {
	    key: "getCategories",
	    value: function getCategories() {
	      return Backend.request({
	        action: 'getCategories',
	        analyticsLabel: {
	          component: Backend.component,
	          action: 'get.categories'
	        }
	      });
	    }
	  }]);
	  return Backend;
	}();
	babelHelpers.defineProperty(Backend, "component", 'bitrix:crm.sales.tunnels');
	babelHelpers.defineProperty(Backend, "entityTypeId", 2);

	var CategoryStub = /*#__PURE__*/function (_Category) {
	  babelHelpers.inherits(CategoryStub, _Category);
	  function CategoryStub(options) {
	    var _this;
	    babelHelpers.classCallCheck(this, CategoryStub);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(CategoryStub).call(this, options));
	    main_core.Dom.addClass(_this.getContainer(), 'crm-st-category-stub');
	    main_core.Dom.removeClass(_this.getContainer(), 'crm-st-category-automation-disabled');
	    _this.getAllColumns().forEach(function (column) {
	      column.marker.disable();
	    });
	    return _this;
	  }
	  return CategoryStub;
	}(Category);

	function createStageStubs(count) {
	  return Array.from({
	    length: count
	  }).map(function (item, index) {
	    return {
	      STATUS_ID: "stub_".concat(index),
	      COLOR: 'F1F5F7',
	      NAME: 'category stub'
	    };
	  });
	}

	function makeErrorMessageFromResponse(response) {
	  if (response.data && response.data.errors && main_core.Type.isArray(response.data.errors) && response.data.errors.length > 0) {
	    return response.data.errors.reduce(function (acc, errorText) {
	      return "".concat(acc).concat(main_core.Text.encode(errorText), "<br>");
	    }, '');
	  }
	  if (response.errors && main_core.Type.isArray(response.errors) && response.errors.length > 0) {
	    return response.errors.reduce(function (result, error) {
	      return "".concat(result).concat(main_core.Text.encode(error.message ? error.message : error), "<br>");
	    }, '');
	  }
	  return main_core.Loc.getMessage('CRM_ST_SAVE_ERROR2');
	}

	var Manager = /*#__PURE__*/function () {
	  function Manager(options) {
	    var _this = this;
	    babelHelpers.classCallCheck(this, Manager);
	    this.container = options.container;
	    this.entityTypeId = options.entityTypeId;
	    this.documentType = options.documentType;
	    this.addCategoryButtonTop = options.addCategoryButtonTop;
	    this.helpButton = options.helpButton;
	    this.categoriesOptions = options.categories;
	    this.robotsUrl = options.robotsUrl;
	    this.generatorUrl = options.generatorUrl;
	    this.permissionEditUrl = options.permissionEditUrl;
	    this.tunnelScheme = options.tunnelScheme;
	    this.isCategoryEditable = Boolean(options.isCategoryEditable);
	    this.isCategoryCreatable = Boolean(options.isCategoryCreatable);
	    this.areStagesEditable = Boolean(options.areStagesEditable);
	    this.isAvailableGenerator = options.isAvailableGenerator;
	    this.isStagesEnabled = options.isStagesEnabled;
	    this.isAutomationEnabled = options.isAutomationEnabled && this.isStagesEnabled;
	    this.categories = new Map();
	    this.cache = new main_core.Cache.MemoryCache();
	    this.isChanged = false;
	    this.initCategories();
	    this.initTunnels();
	    Backend.entityTypeId = this.entityTypeId;
	    if (this.isCategoryCreatable) {
	      setTimeout(function () {
	        if (!_this.hasTunnels()) {
	          _this.showCategoryStub();
	        }
	      });
	    }
	    main_core.Event.bind(this.getAddCategoryButton(), 'click', this.onAddCategoryClick.bind(this));
	    main_core.Event.bind(this.addCategoryButtonTop, 'click', this.onAddCategoryTopClick.bind(this));
	    main_core.Event.bind(this.helpButton, 'click', this.onHelpButtonClick.bind(this));
	    var toolbarComponent = main_core.Reflection.getClass('top.BX.Crm.ToolbarComponent') ? main_core.Reflection.getClass('top.BX.Crm.ToolbarComponent').Instance : null;
	    var slider = this.getSlider();
	    if (slider && toolbarComponent) {
	      main_core.Event.EventEmitter.subscribe('SidePanel.Slider:onClose', function () {
	        if (_this.isChanged) {
	          toolbarComponent.emitCategoriesUpdatedEvent();
	        }
	      });
	    }
	    this.constructor.lastInstance = this;
	  }
	  babelHelpers.createClass(Manager, [{
	    key: "hasTunnels",
	    value: function hasTunnels() {
	      return this.getTunnels().length > 0;
	    }
	  }, {
	    key: "getContainer",
	    value: function getContainer() {
	      return this.cache.remember('container', function () {
	        return document.querySelector('.crm-st');
	      });
	    }
	  }, {
	    key: "getAppContainer",
	    value: function getAppContainer() {
	      var _this2 = this;
	      return this.cache.remember('appContainer', function () {
	        return _this2.getContainer().querySelector('.crm-st-container');
	      });
	    }
	  }, {
	    key: "getCategoriesContainer",
	    value: function getCategoriesContainer() {
	      var _this3 = this;
	      return this.cache.remember('categoriesContainer', function () {
	        return _this3.getAppContainer().querySelector('.crm-st-categories');
	      });
	    }
	  }, {
	    key: "getAddCategoryButton",
	    value: function getAddCategoryButton() {
	      var _this4 = this;
	      return this.cache.remember('addCategoryButton', function () {
	        return _this4.getContainer().querySelector('.crm-st-add-category-btn');
	      });
	    }
	  }, {
	    key: "getMaxSort",
	    value: function getMaxSort() {
	      return babelHelpers.toConsumableArray(this.categories.values()).reduce(function (acc, curr) {
	        return acc > curr.sort ? acc : curr.sort;
	      }, 0);
	    }
	  }, {
	    key: "onAddCategoryClick",
	    value: function onAddCategoryClick(event) {
	      var _this5 = this;
	      event.preventDefault();
	      if (!this.isCategoryCreatable) {
	        return Promise.resolve(false);
	      }
	      if (BX.Crm.Restriction.Bitrix24.isRestricted('dealCategory')) {
	        var restrictionData = BX.Crm.Restriction.Bitrix24.getData('dealCategory');
	        if (restrictionData && restrictionData.quantityLimit <= this.categories.size) {
	          BX.Crm.Restriction.Bitrix24.getHandler('dealCategory').call();
	          return Promise.resolve(false);
	        }
	      }
	      return Backend.createCategory({
	        name: main_core.Loc.getMessage('CRM_ST_TITLE_EDITOR_PLACEHOLDER2'),
	        sort: this.getMaxSort() + 10
	      }).then(function (response) {
	        _this5.addCategoryFromOptions(response.data);
	        var allStages = _this5.getStages();
	        var newStages = [].concat(babelHelpers.toConsumableArray(response.data.STAGES.P), babelHelpers.toConsumableArray(response.data.STAGES.S), babelHelpers.toConsumableArray(response.data.STAGES.F));
	        newStages.forEach(function (item) {
	          return allStages.push(item);
	        });
	        var category = _this5.getCategory(response.data.ID);
	        category.enableTitleEdit('');
	        category.getAllColumns().forEach(function (column) {
	          _this5.tunnelScheme.stages.push({
	            categoryId: column.getData().category.id,
	            stageId: column.getId(),
	            locked: false,
	            tunnels: []
	          });
	        });
	        if (_this5.isShownCategoryStub()) {
	          _this5.hideCategoryStub();
	        }
	      })["catch"](function (response) {
	        _this5.showErrorPopup(makeErrorMessageFromResponse(response));
	      });
	    }
	  }, {
	    key: "onAddCategoryTopClick",
	    value: function onAddCategoryTopClick(event) {
	      this.onAddCategoryClick(event).then(function (success) {
	        if (success) {
	          window.scrollTo(0, document.body.scrollHeight);
	        }
	      });
	    }
	  }, {
	    key: "onHelpButtonClick",
	    value: function onHelpButtonClick(event) {
	      event.preventDefault();
	      if (top.BX.Helper) {
	        top.BX.Helper.show('redirect=detail&code=9474707');
	      }
	    }
	  }, {
	    key: "getCategoryStub",
	    value: function getCategoryStub() {
	      var _this6 = this;
	      return this.cache.remember('categoryStub', function () {
	        return new CategoryStub({
	          renderTo: _this6.getCategoriesContainer(),
	          appContainer: _this6.getAppContainer(),
	          id: 'stub',
	          name: 'stub',
	          "default": false,
	          stages: {
	            P: createStageStubs(5),
	            S: createStageStubs(1),
	            F: createStageStubs(2)
	          },
	          sort: 0,
	          robotsSettingsLink: _this6.robotsUrl,
	          generatorSettingsLink: _this6.generatorUrl,
	          permissionEditLink: _this6.permissionEditUrl,
	          lazy: true,
	          isAvailableGenerator: true,
	          isStagesEnabled: _this6.isStagesEnabled,
	          isAutomationEnabled: true
	        });
	      });
	    }
	  }, {
	    key: "showCategoryStub",
	    value: function showCategoryStub() {
	      this.shownCategoryStub = true;
	      var categoryStub = this.getCategoryStub();
	      categoryStub.draw();
	      var firstCategory = babelHelpers.toConsumableArray(this.categories.values())[0];
	      var _firstCategory$getSuc = firstCategory.getSuccessKanban().getColumns(),
	        _firstCategory$getSuc2 = babelHelpers.slicedToArray(_firstCategory$getSuc, 1),
	        columnFrom = _firstCategory$getSuc2[0];
	      var _categoryStub$getProg = categoryStub.getProgressKanban().getColumns(),
	        _categoryStub$getProg2 = babelHelpers.slicedToArray(_categoryStub$getProg, 1),
	        columnTo = _categoryStub$getProg2[0];
	      if (this.isAutomationEnabled) {
	        columnFrom.marker.addStubLinkTo(columnTo.marker, true);
	      }
	    }
	  }, {
	    key: "hideCategoryStub",
	    value: function hideCategoryStub() {
	      this.shownCategoryStub = false;
	      this.getCategoryStub().remove();
	      this.cache["delete"]('categoryStub');
	    }
	  }, {
	    key: "isShownCategoryStub",
	    value: function isShownCategoryStub() {
	      return this.shownCategoryStub;
	    }
	  }, {
	    key: "adjustCategoryStub",
	    value: function adjustCategoryStub() {
	      if (!this.hasTunnels()) {
	        this.showCategoryStub();
	        return;
	      }
	      this.hideCategoryStub();
	    }
	  }, {
	    key: "addCategoryFromOptions",
	    value: function addCategoryFromOptions(options) {
	      var _this7 = this;
	      var stages = options.STAGES;
	      if (!this.isStagesEnabled) {
	        stages = {
	          P: createStageStubs(5),
	          S: createStageStubs(1),
	          F: createStageStubs(2)
	        };
	      }
	      var category = new Category({
	        renderTo: this.getCategoriesContainer(),
	        appContainer: this.getAppContainer(),
	        id: options.ID,
	        name: options.NAME,
	        "default": options.IS_DEFAULT,
	        stages: stages,
	        sort: options.SORT,
	        access: options.ACCESS,
	        robotsSettingsLink: this.robotsUrl,
	        generatorSettingsLink: this.generatorUrl,
	        permissionEditLink: this.permissionEditUrl,
	        generatorsCount: options.RC_COUNT,
	        generatorsListUrl: options.RC_LIST_URL,
	        isCategoryEditable: this.isCategoryEditable,
	        areStagesEditable: this.areStagesEditable,
	        isAvailableGenerator: this.isAvailableGenerator,
	        isAutomationEnabled: this.isAutomationEnabled,
	        isStagesEnabled: this.isStagesEnabled,
	        entityTypeId: this.entityTypeId
	      });
	      category.subscribe('Category:title:save', function (event) {
	        var _event$data = event.data,
	          categoryId = _event$data.categoryId,
	          value = _event$data.value;
	        Backend.updateCategory({
	          id: categoryId,
	          fields: {
	            NAME: value
	          }
	        }).then(function () {
	          ui_notification.UI.Notification.Center.notify({
	            content: main_core.Loc.getMessage('CRM_ST_NOTIFICATION_CHANGES_SAVED'),
	            autoHideDelay: 1500,
	            category: 'save'
	          });
	          _this7.isChanged = true;
	        })["catch"](function (response) {
	          _this7.showErrorPopup(makeErrorMessageFromResponse(response));
	        });
	      }).subscribe('Category:access', function (event) {
	        var _event$data2 = event.data,
	          categoryId = _event$data2.categoryId,
	          access = _event$data2.access;
	        Backend.accessCategory({
	          id: categoryId,
	          access: access
	        }).then(function () {
	          ui_notification.UI.Notification.Center.notify({
	            content: main_core.Loc.getMessage('CRM_ST_NOTIFICATION_CHANGES_SAVED'),
	            autoHideDelay: 1500,
	            category: 'save'
	          });
	        })["catch"](function (response) {
	          _this7.showErrorPopup(makeErrorMessageFromResponse(response));
	        });
	      }).subscribe('Category:access:copy', function (event) {
	        var _event$data3 = event.data,
	          categoryId = _event$data3.categoryId,
	          donorCategoryId = _event$data3.donorCategoryId;
	        Backend.copyAccessCategory({
	          id: categoryId,
	          donorId: donorCategoryId
	        }).then(function () {
	          ui_notification.UI.Notification.Center.notify({
	            content: main_core.Loc.getMessage('CRM_ST_NOTIFICATION_CHANGES_SAVED'),
	            autoHideDelay: 1500,
	            category: 'save'
	          });
	        })["catch"](function (response) {
	          _this7.showErrorPopup(makeErrorMessageFromResponse(response));
	        });
	      }).subscribe('Category:remove', function (event) {
	        Backend.removeCategory({
	          id: event.data.categoryId
	        }).then(function () {
	          event.data.onConfirm();
	          ui_notification.UI.Notification.Center.notify({
	            content: main_core.Loc.getMessage('CRM_ST_NOTIFICATION_CHANGES_SAVED'),
	            autoHideDelay: 1500,
	            category: 'save'
	          });
	          setTimeout(function () {
	            if (_this7.isShownCategoryStub()) {
	              _this7.hideCategoryStub();
	              _this7.showCategoryStub();
	              return;
	            }
	            _this7.adjustCategoryStub();
	            Marker.adjustLinks();
	          });
	          _this7.isChanged = true;
	        })["catch"](function (response) {
	          event.data.onCancel();
	          _this7.showErrorPopup(makeErrorMessageFromResponse(response));
	        });
	      }).subscribe('Column:link', function (event) {
	        if (!_this7.isAutomationEnabled) {
	          return;
	        }
	        if (!event.data.preventSave) {
	          if (BX.Crm.Restriction.Bitrix24.isRestricted('automation')) {
	            return BX.Crm.Restriction.Bitrix24.getHandler('automation').call();
	          }
	          var from = {
	            category: event.data.link.from.getData().column.getData().category.id,
	            stage: event.data.link.from.getData().column.data.stage.STATUS_ID
	          };
	          var to = {
	            category: event.data.link.to.getData().column.getData().category.id,
	            stage: event.data.link.to.getData().column.data.stage.STATUS_ID
	          };
	          var robotAction = event.data.link.robotAction;
	          Backend.createRobot({
	            from: from,
	            to: to,
	            robotAction: robotAction
	          }).then(function (response) {
	            ui_notification.UI.Notification.Center.notify({
	              content: main_core.Loc.getMessage('CRM_ST_NOTIFICATION_CHANGES_SAVED'),
	              autoHideDelay: 1500,
	              category: 'save'
	            });
	            var stage = _this7.getStages().find(function (item) {
	              return String(item.CATEGORY_ID) === String(response.data.tunnel.srcCategory) && String(item.STATUS_ID) === String(response.data.tunnel.srcStage);
	            });
	            stage.TUNNELS.push(response.data.tunnel);
	          })["catch"](function (response) {
	            var link = event.data.link;
	            link.from.removeLink(link);
	            _this7.showErrorPopup(makeErrorMessageFromResponse(response));
	          });
	        }
	        _this7.hideCategoryStub();
	      }).subscribe('Column:removeLinkFrom', function (event) {
	        if (!_this7.isAutomationEnabled) {
	          return;
	        }
	        if (!event.data.preventSave) {
	          var columnFrom = event.data.link.from.getData().column;
	          var columnTo = event.data.link.to.getData().column;
	          var srcCategory = columnFrom.getData().category.id;
	          var srcStage = columnFrom.getId();
	          var dstCategory = columnTo.getData().category.id;
	          var dstStage = columnTo.getId();
	          var tunnel = _this7.getTunnelByLink(event.data.link);
	          if (tunnel) {
	            if (BX.Crm.Restriction.Bitrix24.isRestricted('automation')) {
	              return BX.Crm.Restriction.Bitrix24.getHandler('automation').call();
	            }
	            var requestOptions = {
	              srcCategory: srcCategory,
	              srcStage: srcStage,
	              dstCategory: dstCategory,
	              dstStage: dstStage,
	              robot: tunnel.robot
	            };
	            Backend.removeRobot(requestOptions).then(function () {
	              ui_notification.UI.Notification.Center.notify({
	                content: main_core.Loc.getMessage('CRM_ST_NOTIFICATION_CHANGES_SAVED'),
	                autoHideDelay: 1500,
	                category: 'save'
	              });
	            })["catch"](function (response) {
	              _this7.showErrorPopup(makeErrorMessageFromResponse(response));
	            });
	            var stage = _this7.getStageDataById(srcStage);
	            stage.TUNNELS = stage.TUNNELS.filter(function (item) {
	              return !(String(item.srcStage) === String(srcStage) && String(item.srcCategory) === String(srcCategory) && String(item.dstStage) === String(dstStage) && String(item.dstCategory) === String(dstCategory));
	            });
	            _this7.adjustCategoryStub();
	          }
	        }
	      }).subscribe('Column:changeRobotAction', function (event) {
	        if (!_this7.isAutomationEnabled || event.data.preventSave) {
	          return;
	        }
	        if (BX.Crm.Restriction.Bitrix24.isRestricted('automation')) {
	          return BX.Crm.Restriction.Bitrix24.getHandler('automation').call();
	        }
	        var columnFrom = event.data.link.from.getData().column;
	        var columnTo = event.data.link.to.getData().column;
	        var srcCategory = columnFrom.getData().category.id;
	        var srcStage = columnFrom.getId();
	        var dstCategory = columnTo.getData().category.id;
	        var dstStage = columnTo.getId();
	        var tunnel = _this7.getTunnelByLink(event.data.link);
	        if (tunnel) {
	          var from = {
	            category: srcCategory,
	            stage: srcStage
	          };
	          var to = {
	            category: dstCategory,
	            stage: dstStage
	          };
	          Backend.removeRobot(tunnel).then(function () {
	            Backend.createRobot({
	              from: from,
	              to: to,
	              robotAction: event.data.link.robotAction
	            }).then(function (response) {
	              ui_notification.UI.Notification.Center.notify({
	                content: main_core.Loc.getMessage('CRM_ST_NOTIFICATION_CHANGES_SAVED'),
	                autoHideDelay: 1500,
	                category: 'save'
	              });
	              var stage = _this7.getStageDataById(srcStage);
	              var index = stage.TUNNELS.findIndex(function (item) {
	                return String(item.srcStage) === String(srcStage) && String(item.srcCategory) === String(srcCategory) && String(item.dstStage) === String(dstStage) && String(item.dstCategory) === String(dstCategory);
	              });
	              if (index >= 0) {
	                stage.TUNNELS[index] = response.data.tunnel;
	              }
	              event.data.onChangeRobotEnd();
	            })["catch"](function (response) {
	              return _this7.showErrorPopup(makeErrorMessageFromResponse(response));
	            });
	          })["catch"](function (response) {
	            return _this7.showErrorPopup(makeErrorMessageFromResponse(response));
	          });
	        }
	      }).subscribe('Column:editLink', function (event) {
	        if (!_this7.isAutomationEnabled) {
	          return;
	        }
	        var tunnel = _this7.getTunnelByLink(event.data.link);

	        // eslint-disable-next-line
	        BX.Bizproc.Automation.API.showRobotSettings(tunnel.robot, _this7.documentType, tunnel.srcStage, function (robot) {
	          tunnel.robot = robot.serialize();
	          Backend.request({
	            action: 'updateRobot',
	            analyticsLabel: {
	              component: Backend.component,
	              action: 'update.robot'
	            },
	            data: tunnel
	          }).then(function () {
	            ui_notification.UI.Notification.Center.notify({
	              content: main_core.Loc.getMessage('CRM_ST_NOTIFICATION_CHANGES_SAVED'),
	              autoHideDelay: 1500,
	              category: 'save'
	            });
	            tunnel.dstCategory = robot.getProperty('CategoryId');
	            tunnel.dstStage = robot.getProperty('StageId');
	            var category = _this7.getCategory(tunnel.dstCategory);
	            var column = category.getKanbanColumn(tunnel.dstStage);
	            event.data.link.from.updateLink(event.data.link, column.marker, true);
	            _this7.adjustCategoryStub();
	          })["catch"](function (response) {
	            _this7.showErrorPopup(makeErrorMessageFromResponse(response));
	          });
	        });
	      }).subscribe('Category:sort', function () {
	        var results = Category.instances.filter(function (category) {
	          return category.id !== 'stub';
	        }).map(function (category, index) {
	          return Backend.updateCategory({
	            id: category.id,
	            fields: {
	              SORT: (index + 1) * 100
	            }
	          });
	        });
	        Promise.all(results).then(function () {
	          ui_notification.UI.Notification.Center.notify({
	            content: main_core.Loc.getMessage('CRM_ST_NOTIFICATION_CHANGES_SAVED'),
	            autoHideDelay: 1500,
	            category: 'save'
	          });
	          _this7.isChanged = true;
	        });
	      }).subscribe('Column:remove', function (event) {
	        if (!main_core.Type.isNil(event.data.column.data.stageId)) {
	          var hasTunnels = _this7.isAutomationEnabled ? babelHelpers.toConsumableArray(Marker.getAllLinks()).some(function (item) {
	            return event.data.column.marker === item.from || event.data.column.marker === item.to;
	          }) : false;
	          Backend.removeStage({
	            statusId: event.data.column.getId(),
	            stageId: event.data.column.data.stageId,
	            entityId: event.data.column.data.entityId
	          }).then(function () {
	            event.data.onConfirm();
	            if (!hasTunnels) {
	              ui_notification.UI.Notification.Center.notify({
	                content: main_core.Loc.getMessage('CRM_ST_NOTIFICATION_CHANGES_SAVED'),
	                autoHideDelay: 1500,
	                category: 'save'
	              });
	              _this7.isChanged = true;
	            }
	          })["catch"](function (response) {
	            event.data.onCancel();
	            _this7.showErrorPopup(makeErrorMessageFromResponse(response));
	          });
	        }
	      }).subscribe('Column:change', function (event) {
	        Backend.updateStage({
	          statusId: event.data.column.getId(),
	          stageId: event.data.column.data.stageId,
	          entityId: event.data.column.data.entityId,
	          name: event.data.column.getName(),
	          sort: event.data.column.data.stage.SORT,
	          color: event.data.column.getColor()
	        }).then(function (_ref) {
	          var data = _ref.data;
	          if (data.success) {
	            ui_notification.UI.Notification.Center.notify({
	              content: main_core.Loc.getMessage('CRM_ST_NOTIFICATION_CHANGES_SAVED'),
	              autoHideDelay: 1500,
	              category: 'save'
	            });
	            _this7.isChanged = true;
	          } else {
	            _this7.showErrorPopup(makeErrorMessageFromResponse({
	              data: data
	            }));
	          }
	        });
	      }).subscribe('Column:addColumn', function (event) {
	        Backend.addStage({
	          name: event.data.column.getGrid().getMessage('COLUMN_TITLE_PLACEHOLDER'),
	          sort: function () {
	            var column = event.data.column;
	            return Number(column.data.stage.SORT) + 1;
	          }(),
	          entityId: function () {
	            var column = event.data.column;
	            return column.data.stage.ENTITY_ID;
	          }(),
	          color: BX.Kanban.Column.DEFAULT_COLOR,
	          semantics: function () {
	            var column = event.data.column;
	            return column.data.stage.SEMANTICS;
	          }(),
	          categoryId: function () {
	            var column = event.data.column;
	            return column.data.category.id;
	          }()
	        }).then(function (_ref2) {
	          var data = _ref2.data;
	          ui_notification.UI.Notification.Center.notify({
	            content: main_core.Loc.getMessage('CRM_ST_NOTIFICATION_CHANGES_SAVED'),
	            autoHideDelay: 1500,
	            category: 'save'
	          });
	          _this7.isChanged = true;
	          var stage = data.stage;
	          var prevColumn = event.data.column;
	          var grid = prevColumn.getGrid();
	          var category = _this7.getCategory(prevColumn.data.category.id);
	          stage.TUNNELS = [];
	          _this7.getStages().push(stage);
	          var targetId = grid.getNextColumnSibling(prevColumn);
	          // column.getGrid().removeColumn(column);
	          var column = grid.addColumn({
	            id: stage.STATUS_ID,
	            name: stage.NAME,
	            color: stage.COLOR.replace('#', ''),
	            data: category.getColumnData(stage),
	            targetId: targetId
	          });
	          column.switchToEditMode();
	          Marker.adjustLinks();
	        })["catch"](function (response) {
	          _this7.showErrorPopup(makeErrorMessageFromResponse(response));
	        });
	      }).subscribe('Column:sort', function (event) {
	        var sortData = event.data.columns.map(function (column, index) {
	          var newSorting = (index + 1) * 100;
	          var columnData = {
	            statusId: column.getId(),
	            stageId: column.data.stageId,
	            entityId: column.data.entityId,
	            name: column.getName(),
	            sort: newSorting,
	            color: column.getColor()
	          };
	          column.data.stage.SORT = newSorting;
	          return columnData;
	        });
	        Backend.updateStages(sortData).then(function (_ref3) {
	          var data = _ref3.data;
	          var success = data.every(function (item) {
	            return item.success;
	          });
	          if (success) {
	            ui_notification.UI.Notification.Center.notify({
	              content: main_core.Loc.getMessage('CRM_ST_NOTIFICATION_CHANGES_SAVED'),
	              autoHideDelay: 1500,
	              category: 'save'
	            });
	            _this7.isChanged = true;
	          } else {
	            _this7.showErrorPopup(makeErrorMessageFromResponse({
	              data: data
	            }));
	          }
	        });
	      }).subscribe('Category:slider:close', function () {
	        _this7.reload();
	      }).subscribe('Column:error', function (event) {
	        _this7.showErrorPopup(makeErrorMessageFromResponse({
	          data: {
	            errors: [event.data.message]
	          }
	        }));
	      });
	      this.categories.set(String(options.ID), category);
	    }
	  }, {
	    key: "showErrorPopup",
	    value: function showErrorPopup(message) {
	      if (!this.errorPopup) {
	        this.errorPopup = new main_popup.PopupWindow({
	          titleBar: main_core.Loc.getMessage('CRM_ST_ERROR_POPUP_TITLE'),
	          width: 350,
	          closeIcon: true,
	          buttons: [new main_popup.PopupWindowButtonLink({
	            id: 'close',
	            text: main_core.Loc.getMessage('CRM_ST_ERROR_POPUP_CLOSE_BUTTON_LABEL'),
	            events: {
	              click: function click() {
	                this.popupWindow.close();
	              }
	            }
	          })]
	        });
	      }
	      this.errorPopup.setContent(message);
	      this.errorPopup.show();
	    }
	  }, {
	    key: "getSlider",
	    value: function getSlider() {
	      // eslint-disable-next-line
	      return BX.SidePanel.Instance.getSlider(window.location.pathname);
	    }
	  }, {
	    key: "reload",
	    value: function reload() {
	      var slider = this.getSlider();
	      if (slider) {
	        slider.reload();
	      }
	    }
	  }, {
	    key: "getStageDataById",
	    value: function getStageDataById(id) {
	      return this.getStages().find(function (stage) {
	        return String(stage.STATUS_ID) === String(id);
	      });
	    }
	  }, {
	    key: "getTunnelByLink",
	    value: function getTunnelByLink(link) {
	      var columnFrom = link.from.getData().column;
	      var columnTo = link.to.getData().column;
	      var srcCategory = columnFrom.getData().category.id;
	      var srcStage = columnFrom.getId();
	      var dstCategory = columnTo.getData().category.id;
	      var dstStage = columnTo.getId();
	      var stageFrom = this.getStageDataById(srcStage);
	      if (stageFrom) {
	        return stageFrom.TUNNELS.find(function (item) {
	          return String(item.srcCategory) === String(srcCategory) && String(item.srcStage) === String(srcStage) && String(item.dstCategory) === String(dstCategory) && String(item.dstStage) === String(dstStage);
	        });
	      }
	      return null;
	    }
	  }, {
	    key: "getCategory",
	    value: function getCategory(id) {
	      return this.categories.get(String(id));
	    }
	  }, {
	    key: "getStages",
	    value: function getStages() {
	      var _this8 = this;
	      return this.cache.remember('allStages', function () {
	        return _this8.categoriesOptions.reduce(function (acc, category) {
	          return [].concat(babelHelpers.toConsumableArray(acc), babelHelpers.toConsumableArray(category.STAGES.P), babelHelpers.toConsumableArray(category.STAGES.S), babelHelpers.toConsumableArray(category.STAGES.F));
	        }, []);
	      });
	    }
	  }, {
	    key: "getTunnels",
	    value: function getTunnels() {
	      return this.getStages().reduce(function (acc, stage) {
	        return [].concat(babelHelpers.toConsumableArray(acc), babelHelpers.toConsumableArray(stage.TUNNELS || []));
	      }, []);
	    }
	  }, {
	    key: "initCategories",
	    value: function initCategories() {
	      var _this9 = this;
	      this.categoriesOptions.map(function (categoryOptions) {
	        _this9.addCategoryFromOptions(categoryOptions);
	      });
	    }
	  }, {
	    key: "initTunnels",
	    value: function initTunnels() {
	      var _this10 = this;
	      if (!this.isAutomationEnabled) {
	        return;
	      }
	      this.getStages().filter(function (stage) {
	        return main_core.Type.isArray(stage.TUNNELS) && stage.TUNNELS.length;
	      }).forEach(function (stage) {
	        stage.TUNNELS.forEach(function (tunnel) {
	          var categoryFrom = _this10.getCategory(tunnel.srcCategory);
	          var categoryTo = _this10.getCategory(tunnel.dstCategory);
	          if (categoryFrom && categoryTo) {
	            var columnFrom = categoryFrom.getKanbanColumn(tunnel.srcStage);
	            var columnTo = categoryTo.getKanbanColumn(tunnel.dstStage);
	            if (columnFrom && columnTo) {
	              var preventEvent = true;
	              columnFrom.marker.addLinkTo(columnTo.marker, tunnel.robotAction, preventEvent);
	            }
	          }
	        });
	      });
	    }
	  }], [{
	    key: "getLastInstance",
	    value: function getLastInstance() {
	      return this.lastInstance;
	    }
	  }]);
	  return Manager;
	}();
	babelHelpers.defineProperty(Manager, "lastInstance", null);

	var Kanban = {
	  Column: Column,
	  Grid: Grid
	};

	exports.Kanban = Kanban;
	exports.Manager = Manager;

}((this.BX.Crm.SalesTunnels = this.BX.Crm.SalesTunnels || {}),BX.Main,BX,BX,BX.Main,BX));
//# sourceMappingURL=script.js.map
