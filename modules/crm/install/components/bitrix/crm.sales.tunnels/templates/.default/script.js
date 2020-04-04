this.BX = this.BX || {};
this.BX.Crm = this.BX.Crm || {};
(function (exports,d3,main_kanban,ui_notification,main_popup,main_core) {
	'use strict';

	function _templateObject() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-st-kanban-stub\"></div>"]);

	  _templateObject = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	function createStub() {
	  return main_core.Tag.render(_templateObject());
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

	function _templateObject4() {
	  var data = babelHelpers.taggedTemplateLiteral(["<span class=\"crm-st-tunnel-button-counter\">0</span>"]);

	  _templateObject4 = function _templateObject4() {
	    return data;
	  };

	  return data;
	}

	function _templateObject3() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-st-tunnel-button\" \n\t\t\t\t\t onmouseenter=\"", "\"\n\t\t\t\t\t onmouseleave=\"", "\"\n\t\t\t\t\t onclick=\"", "\"\n\t\t\t\t\t title=\"", "\"\n\t\t\t\t\t style=\"", "\"\n\t\t\t\t>", "</div>\n\t\t\t"]);

	  _templateObject3 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\tbottom: 0px;\n\t\t\tleft: ", "px;\n\t\t\ttransform: translate3d(-50%, 50%, 0);\n\t\t"]);

	  _templateObject2 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\tbottom: 0px;\n\t\t\tleft: ", "px;\n\t\t\ttransform: translate3d(-50%, 50%, 0);\n\t\t"]);

	  _templateObject$1 = function _templateObject() {
	    return data;
	  };

	  return data;
	}

	/**
	 * Implements interface for works with marker
	 */
	var Marker =
	/*#__PURE__*/
	function (_Event$EventEmitter) {
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
	      return Marker.instances.reduce(function (
	      /* Set */
	      acc, marker) {
	        return babelHelpers.toConsumableArray(marker.links).reduce(function (subAcc, link) {
	          return subAcc.add(link);
	        }, acc);
	      }, new Set());
	    }
	  }, {
	    key: "getAllStubLinks",
	    value: function getAllStubLinks() {
	      return Marker.instances.reduce(function (
	      /* Set */
	      acc, marker) {
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
	        link.from.links.delete(link);
	        link.from.addLinkTo(link.to, preventSave);
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

	    var linksRoot = _this.getLinksRoot(); // Add arrow marker


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
	      this.cache.delete('markerLine');
	    }
	  }, {
	    key: "getDispatcherRect",
	    value: function getDispatcherRect() {
	      var relativeRect = makeRelativeRect(this.getMarkerRootRect(), this.dispatcher.getBoundingClientRect());
	      return babelHelpers.objectSpread({}, relativeRect, getMiddlePoint(relativeRect));
	    }
	  }, {
	    key: "getReceiverRect",
	    value: function getReceiverRect() {
	      var relativeRect = makeRelativeRect(this.getMarkerRootRect(), this.receiver.getBoundingClientRect());
	      return babelHelpers.objectSpread({}, relativeRect, getMiddlePoint(relativeRect));
	    }
	  }, {
	    key: "getPointRect",
	    value: function getPointRect() {
	      var relativeRect = makeRelativeRect(this.getMarkerRootRect(), this.point.getBoundingClientRect());
	      return babelHelpers.objectSpread({}, relativeRect, getMiddlePoint(relativeRect));
	    }
	  }, {
	    key: "getMarkerRootMousePosition",
	    value: function getMarkerRootMousePosition() {
	      var _d3$mouse = d3.mouse(this.getMarkerRoot().node()),
	          _d3$mouse2 = babelHelpers.slicedToArray(_d3$mouse, 2),
	          x = _d3$mouse2[0],
	          y = _d3$mouse2[1];

	      return {
	        x: x,
	        y: y
	      };
	    }
	    /** @private */

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
	    }
	    /** @private */

	  }, {
	    key: "onReceiverDragOut",
	    value: function onReceiverDragOut() {
	      if (this.hovered) {
	        this.hovered = false;
	        this.emit('Marker:receiver:dragOut');
	      }
	    }
	    /** @private */

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
	    /** @private */

	  }, {
	    key: "onMarkerRootMouseMove",
	    value: function onMarkerRootMouseMove() {
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
	    }
	    /** @private */

	  }, {
	    key: "onMarkerRootMouseUp",
	    value: function onMarkerRootMouseUp() {
	      this.getMarkerRoot().on('mousemove', null).on('mouseup', null).style('z-index', null);
	      this.removeMarkerLine(); // @todo refactoring

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
	          if (!this.data.column.data.canEditTunnels) {
	            this.emit('Marker:error', {
	              message: main_core.Loc.getMessage('CRM_ST_TUNNEL_EDIT_ACCESS_DENIED')
	            });
	            return;
	          }

	          this.addLinkTo(destinationMarker);
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
	      return [{
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
	    key: "editLink",
	    value: function editLink(link) {
	      this.emit('Marker:editLink', {
	        link: link
	      });
	    }
	  }, {
	    key: "addLinkTo",
	    value: function addLinkTo(destination) {
	      var _this5 = this;

	      var preventSave = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
	      setTimeout(function () {
	        if (!babelHelpers.toConsumableArray(_this5.links).some(function (link) {
	          return link.to === destination;
	        })) {
	          var linksRoot = _this5.getLinksRoot();

	          var path = _this5.getLinkPath(destination);

	          var line = d3.line();

	          var fromId = _this5.data.column.getId().replace(':', '-');

	          var toId = destination.data.column.getId().replace(':', '-');
	          var arrowId = "".concat(fromId, "-").concat(toId);
	          var arrow = linksRoot.select('defs').append('svg:marker').attr('id', arrowId).attr('refX', 8).attr('refY', 6).attr('markerWidth', 30).attr('markerHeight', 30).attr('markerUnits', 'userSpaceOnUse').attr('orient', 'auto').append('path').attr('d', 'M 0 0 12 6 0 12 3 6').attr('class', 'crm-st-svg-link-arrow').select(function selectCallback() {
	            return this.parentNode;
	          });
	          var linkNode = linksRoot.append('path').attr('class', 'crm-st-svg-link').attr('marker-end', "url(#".concat(arrowId, ")")).attr('d', line(path));

	          _this5.showTunnelButton(path);

	          var link = {
	            from: _this5,
	            to: destination,
	            node: linkNode,
	            arrow: arrow,
	            path: path
	          };

	          _this5.emit('Marker:linkFrom', {
	            link: link,
	            preventSave: preventSave
	          });

	          destination.emit('Marker:linkTo', {
	            link: link,
	            preventSave: preventSave
	          });

	          _this5.links.add(link);

	          var menu = _this5.getTunnelsListMenu();

	          var id = menu.getMenuItems().length;
	          menu.addMenuItem({
	            id: "#".concat(id),
	            text: destination.name,
	            events: {
	              onMouseEnter: function onMouseEnter() {
	                Marker.highlightLink(link);
	              },
	              onMouseLeave: function onMouseLeave() {
	                Marker.unhighlightLinks();
	              }
	            },
	            items: _this5.getTunnelMenuItems(link)
	          });
	        }

	        if (_this5.links.size > 1) {
	          _this5.setTunnelsCounterValue(_this5.links.size);
	        }
	      });
	    }
	  }, {
	    key: "addStubLinkTo",
	    value: function addStubLinkTo(destination) {
	      var _this6 = this;

	      setTimeout(function () {
	        if (!babelHelpers.toConsumableArray(_this6.stubLinks).some(function (link) {
	          return link.to === destination;
	        })) {
	          var linksRoot = _this6.getLinksRoot();

	          var path = _this6.getLinkPath(destination);

	          var line = d3.line();

	          var fromId = _this6.data.column.getId().replace(':', '-');

	          var toId = destination.data.column.getId().replace(':', '-');
	          var arrowId = "".concat(fromId, "-").concat(toId);
	          var arrow = linksRoot.select('defs').append('svg:marker').attr('id', arrowId).attr('refX', 8).attr('refY', 6).attr('markerWidth', 30).attr('markerHeight', 30).attr('markerUnits', 'userSpaceOnUse').attr('orient', 'auto').append('path').attr('d', 'M 0 0 12 6 0 12 3 6').attr('class', 'crm-st-svg-link-arrow crm-st-svg-link-arrow-stub').select(function selectCallback() {
	            return this.parentNode;
	          });
	          var linkNode = linksRoot.append('path').attr('class', 'crm-st-svg-link crm-st-svg-link-stub').attr('marker-end', "url(#".concat(arrowId, ")")).attr('d', line(path));

	          _this6.showTunnelStubButton(path);

	          var link = {
	            from: _this6,
	            to: destination,
	            node: linkNode,
	            arrow: arrow,
	            path: path
	          };

	          _this6.emit('Marker:stubLinkFrom', {
	            link: link,
	            preventSave: true
	          });

	          destination.emit('Marker:stubLinkTo', {
	            link: link,
	            preventSave: true
	          });

	          _this6.stubLinks.add(link);
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
	        this.links.delete(link);
	      }

	      link.node.remove();
	      link.arrow.remove();

	      if (!this.isLinkedFrom()) {
	        main_core.Dom.remove(link.from.getTunnelButton());
	        this.getTunnelMenu().destroy();
	        this.deactivateTunnelButton();
	        this.cache.delete('tunnelMenu');
	      }

	      this.setTunnelsCounterValue(this.links.size);
	      var visibleLinks = babelHelpers.toConsumableArray(this.links).filter(function (item) {
	        return !item.hidden;
	      });

	      if (visibleLinks.length <= 1) {
	        if (this.getTunnelsListMenu().getPopupWindow().isShown()) {
	          this.getTunnelMenu().destroy();
	          this.cache.delete('tunnelMenu');
	          this.getTunnelMenu().show();
	        }

	        this.getTunnelsListMenu().destroy();
	        this.deactivateTunnelButton();
	        this.cache.delete('tunnelsListMenu');
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
	      var _this7 = this;

	      var preventSave = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
	      this.links.forEach(function (link) {
	        return _this7.removeLink(link, preventSave);
	      });
	    }
	  }, {
	    key: "removeStubLink",
	    value: function removeStubLink(link) {
	      this.stubLinks.delete(link);
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
	      var _this8 = this;

	      this.stubLinks.forEach(function (link) {
	        return _this8.removeStubLink(link);
	      });
	    }
	  }, {
	    key: "isLinked",
	    value: function isLinked() {
	      var _this9 = this;

	      return babelHelpers.toConsumableArray(Marker.getAllLinks()).some(function (item) {
	        return !item.hidden && (item.from === _this9 || item.to === _this9);
	      });
	    }
	  }, {
	    key: "isLinkedFrom",
	    value: function isLinkedFrom() {
	      var _this10 = this;

	      return babelHelpers.toConsumableArray(Marker.getAllLinks()).some(function (item) {
	        return !item.hidden && item.from === _this10;
	      });
	    }
	  }, {
	    key: "isLinkedTo",
	    value: function isLinkedTo() {
	      var _this11 = this;

	      return babelHelpers.toConsumableArray(Marker.getAllLinks()).some(function (item) {
	        return !item.hidden && item.to === _this11;
	      });
	    }
	  }, {
	    key: "isLinkedStub",
	    value: function isLinkedStub() {
	      var _this12 = this;

	      return babelHelpers.toConsumableArray(Marker.getAllLinks()).some(function (item) {
	        return !item.hidden && (item.from === _this12 || item.to === _this12);
	      });
	    }
	  }, {
	    key: "showTunnelButton",
	    value: function showTunnelButton(path) {
	      var button = this.getTunnelButton();
	      var category = this.getCategory();
	      var left = path[0][0];
	      main_core.Tag.style(button)(_templateObject$1(), left);

	      if (!category.contains(button)) {
	        main_core.Dom.append(button, category);
	      }
	    }
	  }, {
	    key: "getStubTunnelButton",
	    value: function getStubTunnelButton() {
	      var _this13 = this;

	      return this.cache.remember('tunnelStubButton', function () {
	        var button = main_core.Runtime.clone(_this13.getTunnelButton());
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
	      main_core.Tag.style(button)(_templateObject2(), left);

	      if (!category.contains(button)) {
	        main_core.Dom.append(button, category);
	      }
	    }
	  }, {
	    key: "getTunnelButton",
	    value: function getTunnelButton() {
	      var _this14 = this;

	      var canEdit = this.data.column.data.canEditTunnels;
	      return this.cache.remember('tunnelButton', function () {
	        return main_core.Tag.render(_templateObject3(), _this14.onTunnelButtonMouseEnter.bind(_this14), Marker.onTunnelButtonMouseLeave, _this14.onTunnelButtonClick.bind(_this14), main_core.Loc.getMessage('CRM_ST_TUNNEL_BUTTON_TITLE'), !canEdit ? 'pointer-events: none;' : '', main_core.Loc.getMessage('CRM_ST_TUNNEL_BUTTON_LABEL'));
	      });
	    }
	    /** @private */

	  }, {
	    key: "onTunnelButtonMouseEnter",
	    value: function onTunnelButtonMouseEnter() {
	      Marker.highlightLink.apply(Marker, babelHelpers.toConsumableArray(this.links));
	    }
	    /** @private */

	  }, {
	    key: "onTunnelButtonClick",

	    /** @private */
	    value: function onTunnelButtonClick() {
	      if (this.links.size > 1) {
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
	      var _this15 = this;

	      return this.cache.remember('tunnelsListMenu', new main_popup.PopupMenuWindow({
	        bindElement: this.getTunnelButton(),
	        items: [],
	        closeByEsc: true,
	        menuShowDelay: 0,
	        events: {
	          onPopupClose: function onPopupClose() {
	            return _this15.deactivateTunnelButton();
	          },
	          onPopupShow: function onPopupShow() {
	            return _this15.activateTunnelButton();
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
	      return this.cache.remember('tunnelsCounter', main_core.Tag.render(_templateObject4()));
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
	      var _this16 = this;

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

	        if (from !== _this16) {
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

	function _templateObject3$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\ttitle: ", ";\n\t\t\t\t"]);

	  _templateObject3$1 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\ttitle: ", ";\n\t\t"]);

	  _templateObject2$1 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject$2() {
	  var data = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-st-kanban-column-dot\" title=\"", "\">\n\t\t\t\t<span class=\"crm-st-kanban-column-dot-disallow-icon\"> </span>\n\t\t\t\t<span class=\"crm-st-kanban-column-dot-pulse\"> </span>\n\t\t\t</div>"]);

	  _templateObject$2 = function _templateObject() {
	    return data;
	  };

	  return data;
	}

	var Column =
	/*#__PURE__*/
	function (_Kanban$Column) {
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

	    _this.marker.subscribe('Marker:dragStart', _this.onMarkerDragStart.bind(babelHelpers.assertThisInitialized(_this))).subscribe('Marker:receiver:dragOver', _this.onMarkerDragOver.bind(babelHelpers.assertThisInitialized(_this))).subscribe('Marker:receiver:dragOut', _this.onMarkerDragOut.bind(babelHelpers.assertThisInitialized(_this))).subscribe('Marker:dragEnd', _this.onMarkerDragEnd.bind(babelHelpers.assertThisInitialized(_this))).subscribe('Marker:linkFrom', _this.onMarkerLinkFrom.bind(babelHelpers.assertThisInitialized(_this))).subscribe('Marker:stubLinkFrom', _this.onMarkerStubLinkFrom.bind(babelHelpers.assertThisInitialized(_this))).subscribe('Marker:linkTo', _this.onMarkerLinkTo.bind(babelHelpers.assertThisInitialized(_this))).subscribe('Marker:stubLinkTo', _this.onMarkerStubLinkTo.bind(babelHelpers.assertThisInitialized(_this))).subscribe('Marker:removeLinkFrom', _this.onRemoveLinkFrom.bind(babelHelpers.assertThisInitialized(_this))).subscribe('Marker:editLink', _this.onEditLink.bind(babelHelpers.assertThisInitialized(_this))).subscribe('Marker:unlink', _this.onMarkerUnlink.bind(babelHelpers.assertThisInitialized(_this))).subscribe('Marker:unlinkStub', _this.onMarkerUnlinkStub.bind(babelHelpers.assertThisInitialized(_this))).subscribe('Marker:error', _this.onMarkerError.bind(babelHelpers.assertThisInitialized(_this)));

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
	        this.marker.cache.clear();
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
	        this.dot = main_core.Tag.render(_templateObject$2(), title);
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

	      main_core.Tag.attrs(header)(_templateObject2$1(), this.getName());
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
	          main_core.Tag.attrs(_this2.getHeader())(_templateObject3$1(), _this2.getName());
	        }
	      }, 500);
	    }
	  }, {
	    key: "onColorSelected",
	    value: function onColorSelected(color) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Column.prototype), "onColorSelected", this).call(this, color);
	      this.onColorChangeHandler(this);
	    } // @todo: refactoring

	  }, {
	    key: "handleAddColumnButtonClick",
	    value: function handleAddColumnButtonClick(event) {
	      babelHelpers.get(babelHelpers.getPrototypeOf(Column.prototype), "handleAddColumnButtonClick", this).call(this, event);
	      var newColumn = this.getGrid().getNextColumnSibling(this);
	      newColumn.setOptions({
	        canRemove: true,
	        canSort: true
	      });
	      var applyEditMode = newColumn.applyEditMode;

	      newColumn.applyEditMode = function () {
	        applyEditMode.apply(newColumn);
	        Marker.adjustLinks();
	      };

	      main_core.Event.bind(newColumn.getRemoveButton(), 'click', function () {
	        Marker.adjustLinks();
	      });
	      this.onAddColumnHandler(newColumn);
	      Marker.adjustLinks();
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

	var Grid =
	/*#__PURE__*/
	function (_Kanban$Grid) {
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
	    key: "addColumn",
	    value: function addColumn(options) {
	      var column = babelHelpers.get(babelHelpers.getPrototypeOf(Grid.prototype), "addColumn", this).call(this, options);

	      if (column.onChangeHandler) {
	        column.onChangeHandler(column);
	      }

	      return column;
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

	function _templateObject31() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<span class=\"crm-st-generator-link-icon\" onclick=\"", "\">", "</span>\n\t\t\t"]);

	  _templateObject31 = function _templateObject31() {
	    return data;
	  };

	  return data;
	}

	function _templateObject30() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<span class=\"crm-st-robots-link-icon\"> </span>\n\t\t\t"]);

	  _templateObject30 = function _templateObject30() {
	    return data;
	  };

	  return data;
	}

	function _templateObject29() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<span \n\t\t\t\t\tclass=\"crm-st-category-action-drag\"\n\t\t\t\t\ttitle=\"", "\"\n\t\t\t\t\t>&nbsp;</span>\n\t\t\t"]);

	  _templateObject29 = function _templateObject29() {
	    return data;
	  };

	  return data;
	}

	function _templateObject28() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-st-category-info-title-container\">\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject28 = function _templateObject28() {
	    return data;
	  };

	  return data;
	}

	function _templateObject27() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-st-category-action-buttons\">\n\t\t\t\t\t", "\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject27 = function _templateObject27() {
	    return data;
	  };

	  return data;
	}

	function _templateObject26() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<input class=\"crm-st-category-info-title-editor\" \n\t\t\t\t\t onkeydown=\"", "\"\n\t\t\t\t\t onblur=\"", "\"\n\t\t\t\t\t value=\"", "\"\n\t\t\t\t\t placeholder=\"", "\"\n\t\t\t\t >\n\t\t\t"]);

	  _templateObject26 = function _templateObject26() {
	    return data;
	  };

	  return data;
	}

	function _templateObject25() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<h3 class=\"crm-st-category-info-title\" title=\"", "\">", "</h3>\n\t\t\t"]);

	  _templateObject25 = function _templateObject25() {
	    return data;
	  };

	  return data;
	}

	function _templateObject24() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\tdisplay: none;\n\t\t\t\t"]);

	  _templateObject24 = function _templateObject24() {
	    return data;
	  };

	  return data;
	}

	function _templateObject23() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<span \n\t\t\t\t\tclass=\"crm-st-remove-button\" \n\t\t\t\t\tonclick=\"", "\" \n\t\t\t\t\ttitle=\"", "\"\n\t\t\t\t\t> </span>\n\t\t\t"]);

	  _templateObject23 = function _templateObject23() {
	    return data;
	  };

	  return data;
	}

	function _templateObject22() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\ttitle: ", ";\n\t\t\t"]);

	  _templateObject22 = function _templateObject22() {
	    return data;
	  };

	  return data;
	}

	function _templateObject21() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\tdisplay: none;\n\t\t"]);

	  _templateObject21 = function _templateObject21() {
	    return data;
	  };

	  return data;
	}

	function _templateObject20() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\tdisplay: null;\n\t\t"]);

	  _templateObject20 = function _templateObject20() {
	    return data;
	  };

	  return data;
	}

	function _templateObject19() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\tdisplay: null;\n\t\t"]);

	  _templateObject19 = function _templateObject19() {
	    return data;
	  };

	  return data;
	}

	function _templateObject18() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\tdisplay: block;\n\t\t"]);

	  _templateObject18 = function _templateObject18() {
	    return data;
	  };

	  return data;
	}

	function _templateObject17() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<span \n\t\t\t\t\tclass=\"crm-st-edit-button\" \n\t\t\t\t\tonmousedown=\"", "\"\n\t\t\t\t\ttitle=\"", "\"\n\t\t\t\t\t> </span>\n\t\t\t"]);

	  _templateObject17 = function _templateObject17() {
	    return data;
	  };

	  return data;
	}

	function _templateObject16() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t", "\n\t\t\t\t<span class=\"crm-st-category-info-links-link crm-st-generator-link\" onclick=\"", "\">\n\t\t\t\t\t", "\n\t\t\t\t</span>\n\t\t\t"]);

	  _templateObject16 = function _templateObject16() {
	    return data;
	  };

	  return data;
	}

	function _templateObject15() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t", "\n\t\t\t\t<span class=\"crm-st-category-info-links-link crm-st-robots-link\" onclick=\"", "\">\n\t\t\t\t\t", "\n\t\t\t\t</span>\n\t\t\t"]);

	  _templateObject15 = function _templateObject15() {
	    return data;
	  };

	  return data;
	}

	function _templateObject14() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-st-category-stages-list\"></div>\n\t\t\t"]);

	  _templateObject14 = function _templateObject14() {
	    return data;
	  };

	  return data;
	}

	function _templateObject13() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-st-category-stages-group crm-st-category-stages-group-fail\">\n\t\t\t\t\t<div class=\"crm-st-category-stages-group-header\">\n\t\t\t\t\t\t<span class=\"crm-st-category-stages-group-in-fail\"> </span> \n\t\t\t\t\t\t<span class=\"crm-st-category-stages-group-header-text\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</span>\n\t\t\t\t\t</div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject13 = function _templateObject13() {
	    return data;
	  };

	  return data;
	}

	function _templateObject12() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-st-category-stages-list\"></div>\n\t\t\t"]);

	  _templateObject12 = function _templateObject12() {
	    return data;
	  };

	  return data;
	}

	function _templateObject11() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-st-category-stages-group crm-st-category-stages-group-success\">\n\t\t\t\t\t<div class=\"crm-st-category-stages-group-header\">\n\t\t\t\t\t\t<span class=\"crm-st-category-stages-group-in-success\"> </span> \n\t\t\t\t\t\t<span class=\"crm-st-category-stages-group-header-text\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</span>\n\t\t\t\t\t</div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject11 = function _templateObject11() {
	    return data;
	  };

	  return data;
	}

	function _templateObject10() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-st-category-stages-list\"></div>\n\t\t\t"]);

	  _templateObject10 = function _templateObject10() {
	    return data;
	  };

	  return data;
	}

	function _templateObject9() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-st-category-stages-group crm-st-category-stages-group-in-progress\">\n\t\t\t\t\t<div class=\"crm-st-category-stages-group-header\">\n\t\t\t\t\t\t<span class=\"crm-st-category-stages-group-header-text\">\n\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t</span>\n\t\t\t\t\t</div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject9 = function _templateObject9() {
	    return data;
	  };

	  return data;
	}

	function _templateObject8() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<span \n\t\t\t\t\tclass=\"crm-st-category-info-links-help\" \n\t\t\t\t\tonclick=\"", "\"\n\t\t\t\t\ttitle=\"", "\"\n\t\t\t\t\t> </span>\n\t\t\t"]);

	  _templateObject8 = function _templateObject8() {
	    return data;
	  };

	  return data;
	}

	function _templateObject7() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<span \n\t\t\t\t\tclass=\"crm-st-category-info-links-help\" \n\t\t\t\t\tonclick=\"", "\"\n\t\t\t\t\ttitle=\"", "\"\n\t\t\t\t\t> </span>\n\t\t\t"]);

	  _templateObject7 = function _templateObject7() {
	    return data;
	  };

	  return data;
	}

	function _templateObject6() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"crm-st-category\" data-id=\"", "\">\n\t\t\t\t\t<div class=\"crm-st-category-action\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"crm-st-category-info\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t<div class=\"crm-st-category-info-links\">\n\t\t\t\t\t\t\t<div class=\"crm-st-category-info-links-item\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"crm-st-category-info-links-item\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"crm-st-category-stages\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t"]);

	  _templateObject6 = function _templateObject6() {
	    return data;
	  };

	  return data;
	}

	function _templateObject5() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\ttransform: null;\n\t\t\t\ttransition: null;\n\t\t\t"]);

	  _templateObject5 = function _templateObject5() {
	    return data;
	  };

	  return data;
	}

	function _templateObject4$1() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\ttransition: 200ms;\n\t\t\t\t\ttransform: translate3d(0px, 0px, 0px);\n\t\t\t\t"]);

	  _templateObject4$1 = function _templateObject4() {
	    return data;
	  };

	  return data;
	}

	function _templateObject3$2() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\ttransition: 200ms;\n\t\t\t\t\ttransform: translate3d(0px, ", "px, 0px);\n\t\t\t\t"]);

	  _templateObject3$2 = function _templateObject3() {
	    return data;
	  };

	  return data;
	}

	function _templateObject2$2() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\ttransition: 200ms;\n\t\t\t\t\ttransform: translate3d(0px, ", "px, 0px);\n\t\t\t\t"]);

	  _templateObject2$2 = function _templateObject2() {
	    return data;
	  };

	  return data;
	}

	function _templateObject$3() {
	  var data = babelHelpers.taggedTemplateLiteral(["\n\t\t\ttransform: translate3d(0px, ", "px, 0px);\n\t\t"]);

	  _templateObject$3 = function _templateObject() {
	    return data;
	  };

	  return data;
	}
	var Category =
	/*#__PURE__*/
	function (_Event$EventEmitter) {
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
	    _this.sort = Number.parseInt(options.sort);
	    _this.default = options.default;
	    _this.generatorsCount = Number(options.generatorsCount);
	    _this.stages = options.stages;
	    _this.robotsSettingsLink = options.robotsSettingsLink.replace('{category}', _this.id);
	    _this.generatorSettingsLink = options.generatorSettingsLink;
	    _this.cache = new main_core.Cache.MemoryCache();
	    _this.drawed = false;
	    _this.allowWrite = Boolean(options.allowWrite);
	    _this.canEditTunnels = Boolean(options.canEditTunnels);
	    _this.isAvailableGenerator = options.isAvailableGenerator;
	    _this.showGeneratorRestrictionPopup = options.showGeneratorRestrictionPopup;
	    _this.isAvailableRobots = options.isAvailableRobots;
	    _this.showRobotsRestrictionPopup = options.showRobotsRestrictionPopup;

	    if (!options.lazy) {
	      _this.draw();
	    }

	    if (_this.generatorsCount > 0) {
	      _this.showGeneratorLinkIcon();
	    }

	    var dragButton = _this.getDragButton();

	    dragButton.onbxdragstart = _this.onDragStart.bind(babelHelpers.assertThisInitialized(_this));
	    dragButton.onbxdrag = _this.onDrag.bind(babelHelpers.assertThisInitialized(_this));
	    dragButton.onbxdragstop = _this.onDragStop.bind(babelHelpers.assertThisInitialized(_this)); // eslint-disable-next-line

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

	    return _this;
	  }

	  babelHelpers.createClass(Category, [{
	    key: "hasTunnels",
	    value: function hasTunnels() {
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
	    }
	    /** @private */

	  }, {
	    key: "onDragStart",
	    value: function onDragStart() {
	      main_core.Dom.addClass(this.getContainer(), 'crm-st-category-drag');
	      Marker.removeAllLinks(); // eslint-disable-next-line

	      this.dragOffset = jsDD.start_y - this.getRectArea().top;
	      this.dragIndex = this.getIndex();
	      this.dragTargetCategory = this.dragTargetCategory || this;
	    }
	    /** @private */

	  }, {
	    key: "onDrag",
	    value: function onDrag(x, y) {
	      var _this5 = this;

	      main_core.Tag.style(this.getContainer())(_templateObject$3(), y - this.dragOffset - this.getRectArea().top);
	      var categoryHeight = this.getRectArea().height;
	      Category.instances.forEach(function (category, curIndex) {
	        if (category === _this5 || main_core.Dom.hasClass(category.getContainer(), 'crm-st-category-stub')) {
	          return;
	        }

	        var categoryContainer = category.getContainer();
	        var categoryRectArea = category.getRectArea();
	        var categoryMiddle = categoryRectArea.middle;

	        if (y > categoryMiddle && curIndex > _this5.dragIndex && categoryContainer.style.transform !== "translate3d(0px, ".concat(-categoryHeight, "px, 0px)")) {
	          main_core.Tag.style(categoryContainer)(_templateObject2$2(), -categoryHeight);
	          _this5.dragTargetCategory = category.getNextCategorySibling();
	          category.cache.delete('rectArea');
	        }

	        if (y < categoryMiddle && curIndex < _this5.dragIndex && categoryContainer.style.transform !== "translate3d(0px, ".concat(categoryHeight, "px, 0px)")) {
	          main_core.Tag.style(categoryContainer)(_templateObject3$2(), categoryHeight);
	          _this5.dragTargetCategory = category;
	          category.cache.delete('rectArea');
	        }

	        var moveBackTop = y < categoryMiddle && curIndex > _this5.dragIndex && categoryContainer.style.transform !== '' && categoryContainer.style.transform !== 'translate3d(0, 0, 0)';
	        var moveBackBottom = y > categoryMiddle && curIndex < _this5.dragIndex && categoryContainer.style.transform !== '' && categoryContainer.style.transform !== 'translate3d(0, 0, 0)';

	        if (moveBackBottom || moveBackTop) {
	          main_core.Tag.style(categoryContainer)(_templateObject4$1());
	          _this5.dragTargetCategory = category;

	          if (!moveBackTop && main_core.Dom.hasClass(category.getNextCategorySibling(), 'crm-st-category-stub')) {
	            _this5.dragTargetCategory = category.getNextCategorySibling();
	          }

	          category.cache.delete('rectArea');
	        }
	      });
	    }
	    /** @private */

	  }, {
	    key: "onDragStop",
	    value: function onDragStop() {
	      main_core.Dom.removeClass(this.getContainer(), 'crm-st-category-drag');
	      requestAnimationFrame(function () {
	        Marker.restoreAllLinks();
	      });
	      Category.instances.forEach(function (category) {
	        main_core.Tag.style(category.getContainer())(_templateObject5());
	        category.cache.delete('rectArea');
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
	        return main_core.Tag.render(_templateObject6(), _this6.id, _this6.getDragButton(), _this6.getTitleContainer(), _this6.getRobotsLink(), _this6.getRobotsHelpLink(), _this6.getGeneratorLink(), _this6.getGeneratorHelpLink(), _this6.getProgressContainer(), _this6.getSuccessContainer(), _this6.getFailContainer());
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

	        return main_core.Tag.render(_templateObject7(), onClick, main_core.Text.encode(main_core.Loc.getMessage('CRM_ST_ROBOTS_HELP_BUTTON')));
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

	        return main_core.Tag.render(_templateObject8(), onClick, main_core.Text.encode(main_core.Loc.getMessage('CRM_ST_GENERATOR_HELP_BUTTON')));
	      });
	    }
	  }, {
	    key: "getProgressContainer",
	    value: function getProgressContainer() {
	      var _this7 = this;

	      return this.cache.remember('progressContainer', function () {
	        return main_core.Tag.render(_templateObject9(), main_core.Loc.getMessage('CRM_ST_STAGES_GROUP_IN_PROGRESS'), _this7.getProgressStagesContainer());
	      });
	    }
	  }, {
	    key: "getProgressStagesContainer",
	    value: function getProgressStagesContainer() {
	      return this.cache.remember('progressStagesContainer', function () {
	        return main_core.Tag.render(_templateObject10());
	      });
	    }
	  }, {
	    key: "getProgressKanban",
	    value: function getProgressKanban() {
	      var _this8 = this;

	      return this.cache.remember('progressKanban', function () {
	        return Category.createGrid({
	          renderTo: _this8.getProgressStagesContainer(),
	          editable: _this8.canEditTunnels,
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
	        return main_core.Tag.render(_templateObject11(), main_core.Loc.getMessage('CRM_ST_STAGES_GROUP_SUCCESS'), _this9.getSuccessStagesContainer());
	      });
	    }
	  }, {
	    key: "getSuccessStagesContainer",
	    value: function getSuccessStagesContainer() {
	      return this.cache.remember('successStagesContainer', function () {
	        return main_core.Tag.render(_templateObject12());
	      });
	    }
	  }, {
	    key: "getSuccessKanban",
	    value: function getSuccessKanban() {
	      var _this10 = this;

	      return this.cache.remember('successKanban', function () {
	        return Category.createGrid({
	          renderTo: _this10.getSuccessStagesContainer(),
	          canEditColumn: _this10.canEditTunnels,
	          editable: _this10.canEditTunnels,
	          canRemoveColumn: _this10.allowWrite,
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
	        return main_core.Tag.render(_templateObject13(), main_core.Loc.getMessage('CRM_ST_STAGES_GROUP_FAIL'), _this11.getFailStagesContainer());
	      });
	    }
	  }, {
	    key: "getFailStagesContainer",
	    value: function getFailStagesContainer() {
	      return this.cache.remember('failStagesContainer', function () {
	        return main_core.Tag.render(_templateObject14());
	      });
	    }
	  }, {
	    key: "getFailKanban",
	    value: function getFailKanban() {
	      var _this12 = this;

	      return this.cache.remember('failKanban', function () {
	        return Category.createGrid({
	          renderTo: _this12.getFailStagesContainer(),
	          editable: _this12.canEditTunnels,
	          canEditColumn: _this12.canEditTunnels,
	          canRemoveColumn: _this12.canEditTunnels,
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
	        categoryName: this.getTitle().innerText,
	        canEditTunnels: this.canEditTunnels
	      };
	    }
	  }, {
	    key: "getRobotsLink",
	    value: function getRobotsLink() {
	      var _this14 = this;

	      return this.cache.remember('robotsLink', function () {
	        var onClick = _this14.onRobotsLinkClick.bind(_this14);

	        return main_core.Tag.render(_templateObject15(), !_this14.isAvailableRobots ? ' <span class="tariff-lock"></span>' : '', onClick, main_core.Loc.getMessage('CRM_ST_ROBOT_SETTINGS_LINK_LABEL'));
	      });
	    }
	    /** @private */

	  }, {
	    key: "onRobotsLinkClick",
	    value: function onRobotsLinkClick(event) {
	      var _this15 = this;

	      event.preventDefault();

	      if (!this.isAvailableRobots) {
	        this.showRobotsRestrictionPopup();
	      } else {
	        // eslint-disable-next-line
	        BX.SidePanel.Instance.open(this.robotsSettingsLink, {
	          cacheable: false,
	          events: {
	            onClose: function onClose() {
	              _this15.emit('Category:slider:close');

	              _this15.emit('Category:slider:robots:close');
	            }
	          }
	        });
	      }
	    }
	  }, {
	    key: "getGeneratorLink",
	    value: function getGeneratorLink() {
	      var _this16 = this;

	      return this.cache.remember('generatorLink', function () {
	        var onClick = _this16.onGeneratorLinkClick.bind(_this16);

	        return main_core.Tag.render(_templateObject16(), !_this16.isAvailableGenerator ? ' <span class="tariff-lock"></span>' : '', onClick, main_core.Loc.getMessage('CRM_ST_GENERATOR_SETTINGS_LINK_LABEL'));
	      });
	    }
	    /** @private */

	  }, {
	    key: "onGeneratorLinkClick",
	    value: function onGeneratorLinkClick(event) {
	      var _this17 = this;

	      event.preventDefault();

	      if (!this.isAvailableGenerator) {
	        this.showGeneratorRestrictionPopup();
	      } else {
	        // eslint-disable-next-line
	        BX.SidePanel.Instance.open(this.generatorSettingsLink, {
	          cacheable: false,
	          events: {
	            onClose: function onClose() {
	              _this17.emit('Category:slider:close');

	              _this17.emit('Category:slider:generator:close', {
	                category: _this17
	              });
	            }
	          }
	        });
	      }
	    }
	  }, {
	    key: "getEditButton",
	    value: function getEditButton() {
	      var _this18 = this;

	      return this.cache.remember('editButton', function () {
	        return main_core.Tag.render(_templateObject17(), _this18.onEditButtonClick.bind(_this18), main_core.Loc.getMessage('CRM_ST_EDIT_CATEGORY_TITLE'));
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
	          innerText = _this$getTitle.innerText;

	      titleEditor.value = main_core.Type.isString(value) ? value : main_core.Text.decode(innerText);
	      main_core.Tag.style(titleEditor)(_templateObject18());
	    }
	  }, {
	    key: "hideTitleEditor",
	    value: function hideTitleEditor() {
	      var titleEditor = this.getTitleEditor();
	      main_core.Tag.style(titleEditor)(_templateObject19());
	    }
	  }, {
	    key: "focusOnTitleEditor",
	    value: function focusOnTitleEditor() {
	      var titleEditor = this.getTitleEditor();
	      titleEditor.focus();
	      var title = this.getTitle();
	      var titleLength = title.innerText.length;
	      titleEditor.setSelectionRange(titleLength, titleLength);
	    }
	  }, {
	    key: "showTitle",
	    value: function showTitle() {
	      main_core.Tag.style(this.getTitle())(_templateObject20());
	    }
	  }, {
	    key: "hideTitle",
	    value: function hideTitle() {
	      main_core.Tag.style(this.getTitle())(_templateObject21());
	    }
	  }, {
	    key: "saveTitle",
	    value: function saveTitle() {
	      var title = this.getTitle();
	      var titleEditor = this.getTitleEditor();
	      var value = titleEditor.value;
	      var safeValue = main_core.Text.encode(value.trim()) || main_core.Loc.getMessage('CRM_ST_TITLE_EDITOR_PLACEHOLDER');

	      if (title.innerHTML !== safeValue) {
	        title.innerHTML = safeValue;
	        main_core.Tag.attrs(title)(_templateObject22(), value.trim());
	        this.emit('Category:title:save', {
	          categoryId: this.id,
	          value: safeValue
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
	    key: "getRemoveButton",
	    value: function getRemoveButton() {
	      var _this19 = this;

	      return this.cache.remember('removeButton', function () {
	        var button = main_core.Tag.render(_templateObject23(), _this19.onRemoveButtonClick.bind(_this19), main_core.Loc.getMessage('CRM_ST_REMOVE_CATEGORY'));

	        if (String(_this19.id) === '0') {
	          main_core.Tag.style(button)(_templateObject24());
	        }

	        return button;
	      });
	    }
	  }, {
	    key: "onRemoveButtonClick",
	    value: function onRemoveButtonClick() {
	      var _this20 = this;

	      this.showConfirmRemovePopup().then(function (_ref) {
	        var confirm = _ref.confirm;

	        if (confirm) {
	          _this20.emit('Category:remove', {
	            categoryId: _this20.id,
	            onConfirm: function onConfirm() {
	              _this20.remove();
	            },
	            onCancel: function onCancel() {
	              _this20.removeBlur();
	            }
	          });

	          return;
	        }

	        _this20.removeBlur();
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
	      var _this21 = this;

	      main_core.Dom.remove(this.getContainer());
	      Marker.getAllLinks().forEach(function (link) {
	        var columnFrom = link.from.data.column;
	        var categoryFrom = columnFrom.getData().category;
	        var columnTo = link.to.data.column;
	        var categoryTo = columnTo.getData().category;

	        if (String(categoryFrom.id) === String(_this21.id)) {
	          link.from.removeLink(link);
	        }

	        if (String(categoryTo.id) === String(_this21.id)) {
	          link.to.removeLink(link);
	        }
	      });
	      Marker.getAllStubLinks().forEach(function (link) {
	        var columnFrom = link.from.data.column;
	        var categoryFrom = columnFrom.getData().category;
	        var columnTo = link.to.data.column;
	        var categoryTo = columnTo.getData().category;

	        if (String(categoryFrom.id) === String(_this21.id)) {
	          link.from.removeStubLink(link);
	        }

	        if (String(categoryTo.id) === String(_this21.id)) {
	          link.to.removeStubLink(link);
	        }
	      });
	      Category.instances = Category.instances.filter(function (item) {
	        return item !== _this21;
	      });
	    }
	  }, {
	    key: "getTitle",
	    value: function getTitle() {
	      var _this22 = this;

	      return this.cache.remember('title', function () {
	        return main_core.Tag.render(_templateObject25(), _this22.name, _this22.name);
	      });
	    }
	  }, {
	    key: "getTitleEditor",
	    value: function getTitleEditor() {
	      var _this23 = this;

	      return this.cache.remember('titleEditor', function () {
	        var onKeyDown = _this23.onTitleEditorKeyDown.bind(_this23);

	        var onBlur = _this23.onTitleEditorBlur.bind(_this23);

	        return main_core.Tag.render(_templateObject26(), onKeyDown, onBlur, _this23.name, main_core.Loc.getMessage('CRM_ST_TITLE_EDITOR_PLACEHOLDER'));
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
	      var _this24 = this;

	      return this.cache.remember('getActionsButtons', function () {
	        return main_core.Tag.render(_templateObject27(), _this24.canEditTunnels ? _this24.getEditButton() : '', _this24.canEditTunnels ? _this24.getRemoveButton() : '');
	      });
	    }
	  }, {
	    key: "getTitleContainer",
	    value: function getTitleContainer() {
	      var _this25 = this;

	      return this.cache.remember('titleContainer', function () {
	        return main_core.Tag.render(_templateObject28(), _this25.getTitle(), _this25.getTitleEditor(), _this25.getActionsButtons());
	      });
	    }
	  }, {
	    key: "getDragButton",
	    value: function getDragButton() {
	      return this.cache.remember('dragButton', function () {
	        return main_core.Tag.render(_templateObject29(), main_core.Loc.getMessage('CRM_ST_CATEGORY_DRAG_BUTTON'));
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
	      var _this26 = this;

	      return new Promise(function (resolve) {
	        void new main_popup.PopupWindow({
	          width: 400,
	          overlay: {
	            opacity: 30
	          },
	          titleBar: main_core.Loc.getMessage('CRM_ST_REMOVE_CATEGORY_CONFIRM_POPUP_TITLE').replace('#name#', _this26.getTitle().innerText),
	          content: main_core.Loc.getMessage('CRM_ST_REMOVE_CATEGORY_CONFIRM_POPUP_DESCRIPTION'),
	          buttons: [new main_popup.PopupWindowButton({
	            text: main_core.Loc.getMessage('CRM_ST_REMOVE_CATEGORY_CONFIRM_REMOVE_BUTTON_LABEL'),
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
	        return main_core.Tag.render(_templateObject30());
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
	      var _this27 = this;

	      setTimeout(function () {
	        if (_this27.hasTunnels()) {
	          _this27.showRobotsLinkIcon();

	          return;
	        }

	        _this27.hideRobotsLinkIcon();
	      });
	    }
	  }, {
	    key: "getGeneratorLinkIcon",
	    value: function getGeneratorLinkIcon() {
	      var _this28 = this;

	      return this.cache.remember('generatorLinkIcon', function () {
	        var onClick = function onClick() {
	          return window.top.open('/marketing/rc/');
	        };

	        return main_core.Tag.render(_templateObject31(), onClick, _this28.generatorsCount);
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

	var Backend =
	/*#__PURE__*/
	function () {
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
	          mode: 'ajax',
	          data: {
	            data: data
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

	var CategoryStub =
	/*#__PURE__*/
	function (_Category) {
	  babelHelpers.inherits(CategoryStub, _Category);

	  function CategoryStub(options) {
	    var _this;

	    babelHelpers.classCallCheck(this, CategoryStub);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(CategoryStub).call(this, options));
	    main_core.Dom.addClass(_this.getContainer(), 'crm-st-category-stub');

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

	function makeErrorMessageFromResponse(_ref) {
	  var data = _ref.data;

	  if (main_core.Type.isArray(data.errors) && data.errors.length > 0) {
	    return data.errors.join('<br>');
	  }

	  return main_core.Loc.getMessage('CRM_ST_SAVE_ERROR');
	}

	var Manager =
	/*#__PURE__*/
	function () {
	  function Manager(options) {
	    var _this = this;

	    babelHelpers.classCallCheck(this, Manager);
	    this.container = options.container;
	    this.addCategoryButtonTop = options.addCategoryButtonTop;
	    this.helpButton = options.helpButton;
	    this.categoriesOptions = options.categories;
	    this.robotsUrl = options.robotsUrl;
	    this.generatorUrl = options.generatorUrl;
	    this.tunnelScheme = options.tunnelScheme;
	    this.allowWrite = Boolean(options.allowWrite);
	    this.canEditTunnels = Boolean(options.canEditTunnels);
	    this.canAddCategory = Boolean(options.canAddCategory);
	    this.categoriesQuantityLimit = Number(options.categoriesQuantityLimit);
	    this.restrictionPopupCode = options.restrictionPopupCode;
	    this.isAvailableGenerator = options.isAvailableGenerator;
	    this.showGeneratorRestrictionPopup = options.showGeneratorRestrictionPopup;
	    this.isAvailableRobots = options.isAvailableRobots;
	    this.showRobotsRestrictionPopup = options.showRobotsRestrictionPopup;
	    this.categories = new Map();
	    this.cache = new main_core.Cache.MemoryCache();
	    this.initCategories();
	    this.initTunnels();
	    setTimeout(function () {
	      if (!_this.hasTunnels()) {
	        _this.showCategoryStub();
	      }
	    });
	    main_core.Event.bind(this.getAddCategoryButton(), 'click', this.onAddCategoryClick.bind(this));
	    main_core.Event.bind(this.addCategoryButtonTop, 'click', this.onAddCategoryTopClick.bind(this));
	    main_core.Event.bind(this.helpButton, 'click', this.onHelpButtonClick.bind(this));
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

	      if (this.canAddCategory || this.categoriesQuantityLimit <= 0 || this.categoriesQuantityLimit > this.categories.size) {
	        return Backend.createCategory({
	          name: main_core.Loc.getMessage('CRM_ST_TITLE_EDITOR_PLACEHOLDER'),
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
	        });
	      } else {
	        try {
	          eval(this.restrictionPopupCode);
	        } catch (e) {
	          console.error(e);
	        }

	        return Promise.resolve(false);
	      }
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
	          'default': false,
	          stages: {
	            P: createStageStubs(5),
	            S: createStageStubs(1),
	            F: createStageStubs(2)
	          },
	          sort: 0,
	          robotsSettingsLink: _this6.robotsUrl,
	          generatorSettingsLink: _this6.generatorUrl,
	          lazy: true,
	          isAvailableGenerator: true,
	          showGeneratorRestrictionPopup: function showGeneratorRestrictionPopup() {},
	          isAvailableRobots: true,
	          showRobotsRestrictionPopup: function showRobotsRestrictionPopup() {}
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

	      columnFrom.marker.addStubLinkTo(columnTo.marker, true);
	    }
	  }, {
	    key: "hideCategoryStub",
	    value: function hideCategoryStub() {
	      this.shownCategoryStub = false;
	      this.getCategoryStub().remove();
	      this.cache.delete('categoryStub');
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

	      var category = new Category({
	        renderTo: this.getCategoriesContainer(),
	        appContainer: this.getAppContainer(),
	        id: options.ID,
	        name: options.NAME,
	        'default': options.IS_DEFAULT,
	        stages: options.STAGES,
	        sort: options.SORT,
	        robotsSettingsLink: this.robotsUrl,
	        generatorSettingsLink: this.generatorUrl,
	        generatorsCount: options.RC_COUNT,
	        allowWrite: this.allowWrite,
	        canEditTunnels: this.canEditTunnels,
	        isAvailableGenerator: this.isAvailableGenerator,
	        showGeneratorRestrictionPopup: this.showGeneratorRestrictionPopup,
	        isAvailableRobots: this.isAvailableRobots,
	        showRobotsRestrictionPopup: this.showRobotsRestrictionPopup
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
	        }).then(function (_ref) {
	          var data = _ref.data;

	          if (data.success) {
	            ui_notification.UI.Notification.Center.notify({
	              content: main_core.Loc.getMessage('CRM_ST_NOTIFICATION_CHANGES_SAVED'),
	              autoHideDelay: 1500,
	              category: 'save'
	            });
	          } else {
	            _this7.showErrorPopup(makeErrorMessageFromResponse({
	              data: data
	            }));
	          }
	        });
	      }).subscribe('Category:remove', function (event) {
	        Backend.removeCategory({
	          id: event.data.categoryId
	        }).then(function (_ref2) {
	          var data = _ref2.data;

	          if (data.success) {
	            event.data.onConfirm();
	            ui_notification.UI.Notification.Center.notify({
	              content: main_core.Loc.getMessage('CRM_ST_NOTIFICATION_CHANGES_SAVED'),
	              autoHideDelay: 1500,
	              category: 'save'
	            });
	          } else {
	            event.data.onCancel();

	            _this7.showErrorPopup(makeErrorMessageFromResponse({
	              data: data
	            }));
	          }

	          setTimeout(function () {
	            if (_this7.isShownCategoryStub()) {
	              _this7.hideCategoryStub();

	              _this7.showCategoryStub();

	              return;
	            }

	            _this7.adjustCategoryStub();
	          });
	        });
	      }).subscribe('Column:link', function (event) {
	        if (!event.data.preventSave) {
	          var from = {
	            category: event.data.link.from.getData().column.getData().category.id,
	            stage: event.data.link.from.getData().column.data.stage.STATUS_ID
	          };
	          var to = {
	            category: event.data.link.to.getData().column.getData().category.id,
	            stage: event.data.link.to.getData().column.data.stage.STATUS_ID
	          };
	          Backend.createRobot({
	            from: from,
	            to: to
	          }).then(function (response) {
	            if (response.data.success) {
	              ui_notification.UI.Notification.Center.notify({
	                content: main_core.Loc.getMessage('CRM_ST_NOTIFICATION_CHANGES_SAVED'),
	                autoHideDelay: 1500,
	                category: 'save'
	              });

	              var stage = _this7.getStages().find(function (item) {
	                return String(item.CATEGORY_ID) === String(response.data.tunnel.srcCategory) && String(item.STATUS_ID) === String(response.data.tunnel.srcStage);
	              });

	              stage.TUNNELS.push(response.data.tunnel);
	            } else {
	              _this7.showErrorPopup(makeErrorMessageFromResponse({
	                data: response.data
	              }));
	            }
	          });
	        }

	        _this7.hideCategoryStub();
	      }).subscribe('Column:removeLinkFrom', function (event) {
	        if (!event.data.preventSave) {
	          var columnFrom = event.data.link.from.getData().column;
	          var columnTo = event.data.link.to.getData().column;
	          var srcCategory = columnFrom.getData().category.id;
	          var srcStage = columnFrom.getId();
	          var dstCategory = columnTo.getData().category.id;
	          var dstStage = columnTo.getId();

	          var tunnel = _this7.getTunnelByLink(event.data.link);

	          if (tunnel) {
	            var requestOptions = {
	              srcCategory: srcCategory,
	              srcStage: srcStage,
	              dstCategory: dstCategory,
	              dstStage: dstStage,
	              robot: tunnel.robot
	            };
	            Backend.removeRobot(requestOptions).then(function (_ref3) {
	              var data = _ref3.data;

	              if (data.success) {
	                ui_notification.UI.Notification.Center.notify({
	                  content: main_core.Loc.getMessage('CRM_ST_NOTIFICATION_CHANGES_SAVED'),
	                  autoHideDelay: 1500,
	                  category: 'save'
	                });
	              } else {
	                _this7.showErrorPopup(makeErrorMessageFromResponse({
	                  data: data
	                }));
	              }
	            });

	            var stage = _this7.getStageDataById(srcStage);

	            stage.TUNNELS = stage.TUNNELS.filter(function (item) {
	              return !(String(item.srcStage) === String(srcStage) && String(item.srcCategory) === String(srcCategory) && String(item.dstStage) === String(dstStage) && String(item.dstCategory) === String(dstCategory));
	            });

	            _this7.adjustCategoryStub();
	          }
	        }
	      }).subscribe('Column:editLink', function (event) {
	        var tunnel = _this7.getTunnelByLink(event.data.link); // eslint-disable-next-line


	        BX.Bizproc.Automation.API.showRobotSettings(tunnel.robot, ['crm', 'CCrmDocumentDeal', 'DEAL'], tunnel.srcStage, function (robot) {
	          tunnel.robot = robot.serialize();
	          Backend.request({
	            action: 'updateRobot',
	            analyticsLabel: {
	              component: Backend.component,
	              action: 'update.robot'
	            },
	            data: tunnel
	          }).then(function (_ref4) {
	            var data = _ref4.data;

	            if (data.success) {
	              ui_notification.UI.Notification.Center.notify({
	                content: main_core.Loc.getMessage('CRM_ST_NOTIFICATION_CHANGES_SAVED'),
	                autoHideDelay: 1500,
	                category: 'save'
	              });
	              tunnel.dstCategory = robot.getProperty('CategoryId');
	              tunnel.dstStage = robot.getProperty('StageId');

	              var _category = _this7.getCategory(tunnel.dstCategory);

	              var column = _category.getKanbanColumn(tunnel.dstStage);

	              event.data.link.from.updateLink(event.data.link, column.marker, true);

	              _this7.adjustCategoryStub();
	            } else {
	              _this7.showErrorPopup(makeErrorMessageFromResponse({
	                data: data
	              }));
	            }
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
	        });
	      }).subscribe('Column:remove', function (event) {
	        if (!main_core.Type.isNil(event.data.column.data.stageId)) {
	          var hasTunnels = babelHelpers.toConsumableArray(Marker.getAllLinks()).some(function (item) {
	            return event.data.column.marker === item.from || event.data.column.marker === item.to;
	          });
	          Backend.removeStage({
	            statusId: event.data.column.getId(),
	            stageId: event.data.column.data.stageId,
	            entityId: event.data.column.data.entityId
	          }).then(function (response) {
	            if (response.data.success) {
	              event.data.onConfirm();

	              if (!hasTunnels) {
	                ui_notification.UI.Notification.Center.notify({
	                  content: main_core.Loc.getMessage('CRM_ST_NOTIFICATION_CHANGES_SAVED'),
	                  autoHideDelay: 1500,
	                  category: 'save'
	                });
	              }
	            } else {
	              event.data.onCancel();

	              _this7.showErrorPopup(makeErrorMessageFromResponse({
	                data: response.data
	              }));
	            }
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
	        }).then(function (_ref5) {
	          var data = _ref5.data;

	          if (data.success) {
	            ui_notification.UI.Notification.Center.notify({
	              content: main_core.Loc.getMessage('CRM_ST_NOTIFICATION_CHANGES_SAVED'),
	              autoHideDelay: 1500,
	              category: 'save'
	            });
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
	            var grid = column.getGrid();
	            var prevColumn = grid.getPreviousColumnSibling(column);
	            return Number(prevColumn.data.stage.SORT) + 1;
	          }(),
	          entityId: function () {
	            var column = event.data.column;
	            var grid = column.getGrid();
	            var prevColumn = grid.getPreviousColumnSibling(column);
	            return prevColumn.data.stage.ENTITY_ID;
	          }()
	        }).then(function (_ref6) {
	          var data = _ref6.data;

	          if (data.success) {
	            ui_notification.UI.Notification.Center.notify({
	              content: main_core.Loc.getMessage('CRM_ST_NOTIFICATION_CHANGES_SAVED'),
	              autoHideDelay: 1500,
	              category: 'save'
	            });
	            var column = event.data.column;
	            var stage = data.stage;
	            var grid = column.getGrid();
	            var prevColumn = grid.getPreviousColumnSibling(column);

	            var _category2 = _this7.getCategory(prevColumn.data.category.id);

	            stage.TUNNELS = [];

	            _this7.getStages().push(stage);

	            column.setOptions({
	              data: _category2.getColumnData(stage)
	            });
	          } else {
	            _this7.showErrorPopup(makeErrorMessageFromResponse({
	              data: data
	            }));
	          }
	        });
	      }).subscribe('Column:sort', function (event) {
	        var sortData = event.data.columns.map(function (column, index) {
	          var newSorting = (index + 1) * 100;
	          var columnData = {
	            statusId: column.getId(),
	            stageId: column.data.stageId,
	            entityId: column.data.entityId,
	            name: column.getName(),
	            sort: newSorting
	          };
	          column.data.stage.SORT = newSorting;
	          return columnData;
	        });
	        Backend.updateStages(sortData).then(function (_ref7) {
	          var data = _ref7.data;
	          var success = data.every(function (item) {
	            return item.success;
	          });

	          if (success) {
	            ui_notification.UI.Notification.Center.notify({
	              content: main_core.Loc.getMessage('CRM_ST_NOTIFICATION_CHANGES_SAVED'),
	              autoHideDelay: 1500,
	              category: 'save'
	            });
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
	              columnFrom.marker.addLinkTo(columnTo.marker, preventEvent);
	            }
	          }
	        });
	      });
	    }
	  }]);
	  return Manager;
	}();

	var Kanban = {
	  Column: Column,
	  Grid: Grid
	};

	exports.Kanban = Kanban;
	exports.Manager = Manager;

}((this.BX.Crm.SalesTunnels = this.BX.Crm.SalesTunnels || {}),BX.Main,BX,BX,BX,BX));
//# sourceMappingURL=script.js.map
