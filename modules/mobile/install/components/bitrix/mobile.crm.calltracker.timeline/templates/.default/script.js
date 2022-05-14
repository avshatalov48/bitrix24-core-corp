this.BX = this.BX || {};
this.BX.Mobile = this.BX.Mobile || {};
this.BX.Mobile.Crm = this.BX.Mobile.Crm || {};
(function (exports,main_loader,main_polyfill_intersectionobserver,ui_vue_components_audioplayer,ui_vue,main_core,mobile_utils,main_core_events) {
	'use strict';

	var Configuration = /*#__PURE__*/function () {
	  function Configuration() {
	    babelHelpers.classCallCheck(this, Configuration);
	  }

	  babelHelpers.createClass(Configuration, null, [{
	    key: "set",
	    value: function set(_ref) {
	      var componentName = _ref.componentName,
	          signedParameters = _ref.signedParameters,
	          currentAuthor = _ref.currentAuthor;
	      Configuration.componentName = componentName;
	      Configuration.signedParameters = signedParameters;
	      Configuration.currentAuthor = currentAuthor;
	    }
	  }]);
	  return Configuration;
	}();

	babelHelpers.defineProperty(Configuration, "componentName", 'bitrix:mobile.crm.calltracker.timeline');
	babelHelpers.defineProperty(Configuration, "signedParameters", '');
	babelHelpers.defineProperty(Configuration, "currentAuthor", {
	  'AUTHOR_ID': 0,
	  'AUTHOR': {
	    'FORMATTED_NAME': 'Guest',
	    'SHOW_URL': '',
	    'IMAGE_URL': ''
	  }
	});

	var Backend = /*#__PURE__*/function () {
	  function Backend() {
	    babelHelpers.classCallCheck(this, Backend);
	  }

	  babelHelpers.createClass(Backend, null, [{
	    key: "request",
	    value: function request(_ref) {
	      var action = _ref.action,
	          data = _ref.data;
	      return main_core.ajax.runComponentAction(Configuration.componentName, action, {
	        mode: 'class',
	        data: data,
	        signedParameters: Configuration.signedParameters
	      });
	    }
	  }, {
	    key: "getItem",
	    value: function getItem(id, options) {
	      return Backend.request({
	        action: 'getItem',
	        data: {
	          id: id,
	          options: options
	        }
	      });
	    }
	  }, {
	    key: "createItem",
	    value: function createItem(_ref2) {
	      var text = _ref2.text,
	          files = _ref2.files;
	      return Backend.request({
	        action: 'createItem',
	        data: {
	          text: text,
	          files: files
	        }
	      });
	    }
	  }, {
	    key: "getItemsFromPage",
	    value: function getItemsFromPage(itemId, pageNumber) {
	      return new Promise(function (resolve, reject) {
	        main_core.ajax.runComponentAction(Configuration.componentName, 'getItems', {
	          mode: 'class',
	          data: {
	            itemId: itemId
	          },
	          navigation: {
	            page: pageNumber
	          },
	          signedParameters: Configuration.signedParameters
	        }).then(resolve, reject);
	      });
	    }
	  }]);
	  return Backend;
	}();

	var _templateObject, _templateObject2;
	var Pagination = /*#__PURE__*/function () {
	  function Pagination(itemId, callback) {
	    babelHelpers.classCallCheck(this, Pagination);
	    this.itemId = itemId;
	    this.callback = callback;
	    this.pointer = 1;
	    this.busy = false;
	    this.cache = new main_core.Cache.MemoryCache();
	  }

	  babelHelpers.createClass(Pagination, [{
	    key: "getNode",
	    value: function getNode() {
	      var _this = this;

	      return this.cache.remember('mainNode', function () {
	        return main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["<div class=\"crm-phonetracker-detail-comments-btn-container\" onclick=\"", "\">\n\t\t\t\t<div class=\"crm-phonetracker-detail-comments-btn\">", "</div>\n\t\t\t</div>"])), _this.sendPagination.bind(_this), main_core.Loc.getMessage('MPT_PREVIOUS_COMMENTS'));
	      });
	    }
	  }, {
	    key: "getLoader",
	    value: function getLoader() {
	      if (!this.cache.has('PaginationLoader')) {
	        var target = this.getNode().appendChild(main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<div class=\"loader\"></div>"]))));
	        this.cache.set('PaginationLoader', new main_loader.Loader({
	          target: target,
	          size: 20
	        }));
	      }

	      return this.cache.get('PaginationLoader');
	    }
	  }, {
	    key: "sendPagination",
	    value: function sendPagination() {
	      var _this2 = this,
	          _arguments = arguments;

	      if (this.busy === true) {
	        return false;
	      }

	      this.getLoader().show();
	      this.busy = true;
	      Backend.getItemsFromPage(this.itemId, ++this.pointer).then(function (_ref) {
	        var _ref$data = _ref.data,
	            items = _ref$data.items,
	            paginationHasMore = _ref$data.paginationHasMore,
	            errors = _ref.errors;

	        _this2.getLoader().hide();

	        _this2.callback.call(_this2, items);

	        if (paginationHasMore !== true) {
	          _this2.destroy();
	        }

	        if (errors.length > 0) {
	          _this2.showErrors(errors);
	        }
	      }, function () {
	        _this2.showErrors(_arguments);
	      })["finally"](function () {
	        _this2.busy = false;
	      });
	    }
	  }, {
	    key: "destroy",
	    value: function destroy() {
	      var _this3 = this;

	      if (this.getNode().parentNode) {
	        this.getNode().parentNode.removeChild(this.getNode());
	      }

	      this.getNode().style.display = 'none';
	      this.cache.keys().forEach(function (key) {
	        _this3.cache["delete"](key);
	      });
	    }
	  }, {
	    key: "showErrors",
	    value: function showErrors(errors) {
	      console.log('Pagination errors: ', errors);
	    }
	  }]);
	  return Pagination;
	}();

	var _templateObject$1, _templateObject2$1, _templateObject3, _templateObject4, _templateObject5, _templateObject6, _templateObject7;
	var intersectionObserver;

	function observeIntersection(entity, callback) {
	  if (!intersectionObserver) {
	    intersectionObserver = new IntersectionObserver(function (entries) {
	      entries.forEach(function (entry) {
	        if (entry.isIntersecting) {
	          intersectionObserver.unobserve(entry.target);
	          var observedCallback = entry.target.observedCallback;
	          delete entry.target.observedCallback;
	          setTimeout(observedCallback);
	        }
	      });
	    }, {
	      threshold: 0
	    });
	  }

	  entity.observedCallback = callback;
	  intersectionObserver.observe(entity);
	}

	var Item = /*#__PURE__*/function () {
	  babelHelpers.createClass(Item, null, [{
	    key: "checkForPaternity",
	    value: function checkForPaternity() {
	      return true;
	    }
	  }]);

	  function Item(data) {
	    babelHelpers.classCallCheck(this, Item);
	    this.id = data['ID'];
	    this.data = data;
	    this.cache = new main_core.Cache.MemoryCache();
	    Item.renderWithDebounce();
	  }

	  babelHelpers.createClass(Item, [{
	    key: "getId",
	    value: function getId() {
	      return this.id;
	    }
	  }, {
	    key: "getOwnerId",
	    value: function getOwnerId() {
	      return ['OWN', this.getId()].join('_');
	    }
	  }, {
	    key: "getNode",
	    value: function getNode() {
	      var _this = this;

	      return this.cache.remember('mainNode', function () {
	        var avatarUrl = main_core.Text.encode(_this.data['AUTHOR'] && _this.data['AUTHOR']['IMAGE_URL'] ? _this.data['AUTHOR']['IMAGE_URL'] : '');

	        var expand = function expand() {
	          var node = _this.getNode().querySelector('div[data-bx-role="more-button"]');

	          node.parentNode.removeChild(node);

	          var wrapper = _this.getTextNode().parentNode;

	          var startHeight = main_core.pos(wrapper).height;
	          var endHeight = main_core.pos(_this.getTextNode()).height;
	          wrapper.style.maxHeight = startHeight + 'px';
	          wrapper.style.overflow = 'hidden';
	          var time = (endHeight - startHeight) / (2000 - startHeight);
	          time = time < 0.3 ? 0.3 : time > 0.8 ? 0.8 : time;
	          new BX["easing"]({
	            duration: time * 1000,
	            start: {
	              height: startHeight
	            },
	            finish: {
	              height: endHeight
	            },
	            transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
	            step: function step(state) {
	              wrapper.style.maxHeight = state.height + "px";
	            },
	            complete: function complete() {
	              wrapper.style.cssText = '';
	              wrapper.style.maxHeight = 'none';
	              BX.LazyLoad.showImages(true);
	            }
	          }).animate();
	        };

	        var render = main_core.Tag.render(_templateObject$1 || (_templateObject$1 = babelHelpers.taggedTemplateLiteral(["<div class=\"feed-com-block-cover\">\n\t\t\t\t<div class=\"post-comment-block post-comment-block-old post-comment-block-approved mobile-longtap-menu\">\n\t\t\t\t\t<div class=\"ui-icon ui-icon-common-user post-comment-block-avatar\">\n\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"post-comment-detail\">\n\t\t\t\t\t\t<div class=\"post-comment-balloon\">\n\t\t\t\t\t\t\t<div class=\"post-comment-cont\">\n\t\t\t\t\t\t\t\t<a href=\"\" class=\"post-comment-author\">", "</a>\n\t\t\t\t\t\t\t\t<div class=\"post-comment-time\">", "</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"post-comment-wrap-outer\">\n\t\t\t\t\t\t\t\t<div class=\"post-comment-wrap\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<div class=\"post-comment-more\" data-bx-role=\"more-button\" onclick=\"", "\">\n\t\t\t\t\t\t\t\t\t<div class=\"post-comment-more-but\"></div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>"])), avatarUrl ? main_core.Tag.render(_templateObject2$1 || (_templateObject2$1 = babelHelpers.taggedTemplateLiteral(["<i style=\"background-image:url('", "')\"></i>"])), avatarUrl) : main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["<i></i>"]))), main_core.Text.encode(_this.data['AUTHOR']['FORMATTED_NAME']), _this.getDateNode(), _this.getTextNode(), expand, _this.getFilesNode(), _this.getActionNode());
	        return render;
	      });
	    }
	  }, {
	    key: "getTextNode",
	    value: function getTextNode() {
	      var _this2 = this;

	      return this.cache.remember('textNode', function () {
	        var renderTag = main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["<div class=\"post-comment-text\"></div>"])));
	        main_core.Runtime.html(renderTag, _this2.data['COMMENT']);
	        return renderTag;
	      });
	    }
	  }, {
	    key: "getDateNode",
	    value: function getDateNode() {
	      var _this3 = this;

	      return this.cache.remember('dateNode', function () {
	        if (main_core.Type.isStringFilled(_this3.data['CREATED'])) {
	          return _this3.data['CREATED'];
	        }

	        return BX.formatDate();
	      });
	    }
	  }, {
	    key: "getFilesNode",
	    value: function getFilesNode() {
	      var _this4 = this;

	      return this.cache.remember('filesBlock', function () {
	        if (_this4.data['HAS_FILES'] !== 'Y') {
	          return '';
	        }

	        var renderTag = main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["\n\t\t<div class=\"post-item-attached-file-wrap\">\n\t\t\t<div class=\"post-item-attached-file-list\">\n\t\t\t</div>\n\t\t</div>"])));

	        if (main_core.Type.isStringFilled(_this4.data['PARSED_ATTACHMENT'])) {
	          main_core.Runtime.html(renderTag, _this4.data['PARSED_ATTACHMENT']);
	        } else {
	          setTimeout(function () {
	            observeIntersection(renderTag, function () {
	              var options = ['GET_FILE_BLOCK'];
	              _this4.data['HAS_INLINE_ATTACHMENT'] === 'Y' ? options.push('GET_COMMENT') : null;
	              Backend.getItem(_this4.id, options).then(function (_ref) {
	                var _ref$data = _ref.data,
	                    files = _ref$data.files,
	                    text = _ref$data.text,
	                    errors = _ref.errors;

	                if (main_core.Type.isStringFilled(files)) {
	                  main_core.Runtime.html(renderTag, files);
	                }

	                if (main_core.Type.isStringFilled(text)) {
	                  main_core.Runtime.html(_this4.getTextNode(), text);
	                }
	              }, function (_ref2) {
	                var errors = _ref2.errors;
	                var errorMessages = [];
	                errors.forEach(function (error) {
	                  errorMessages.push(error.message);
	                });
	              });
	            });
	          }, 100);
	        }

	        return main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["<div class=\"post-item-attached-file-wrap\">", "</div>"])), renderTag);
	      });
	    }
	  }, {
	    key: "getActionNode",
	    value: function getActionNode() {
	      return this.cache.remember('actionNode', function () {
	        return '';
	        return main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"post-comment-control-box\">\n\t\t\t\t\t<div class=\"post-comment-control-item\">Edit</div>\n\t\t\t\t\t<div class=\"post-comment-control-item\">Delete</div>\n\t\t\t\t</div>"])));
	      });
	    }
	  }]);
	  return Item;
	}();

	babelHelpers.defineProperty(Item, "renderWithDebounce", main_core.Runtime.debounce(function () {
	  BitrixMobile.LazyLoad.showImages();
	}, 500));

	var ItemComment = /*#__PURE__*/function (_Item) {
	  babelHelpers.inherits(ItemComment, _Item);

	  function ItemComment() {
	    babelHelpers.classCallCheck(this, ItemComment);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ItemComment).apply(this, arguments));
	  }

	  babelHelpers.createClass(ItemComment, null, [{
	    key: "checkForPaternity",
	    value: function checkForPaternity(data) {
	      return data['TYPE_CODE'] === 'COMMENT';
	    }
	  }]);
	  return ItemComment;
	}(Item);

	var Utils = /*#__PURE__*/function () {
	  function Utils() {
	    babelHelpers.classCallCheck(this, Utils);
	  }

	  babelHelpers.createClass(Utils, null, [{
	    key: "formatInterval",
	    value: function formatInterval(timestamp) {
	      var item = {
	        DAY: Math.floor(timestamp / 86400),
	        HOUR: Math.floor(timestamp % 86400 / 3600),
	        MINUTE: Math.floor(timestamp % 86400 % 3600 / 60),
	        SECOND: timestamp % 86400 % 3600 % 60
	      };
	      var result = [];

	      for (var ii in item) {
	        if (item[ii] > 0) {
	          result.push([item[ii], main_core.Loc.getMessage(['INTERVAL', ii, item[ii] === 1 ? 'SINGLE' : 'PLURAL'].join('_'))].join(' '));
	        }
	      }

	      if (result.length <= 0) {
	        result.push(['0', main_core.Loc.getMessage(['INTERVAL_SECOND_SINGLE'].join('_'))].join(' '));
	      }

	      return result.join(' ');
	    }
	  }]);
	  return Utils;
	}();

	var _templateObject$2, _templateObject2$2, _templateObject3$1, _templateObject4$1;

	var ItemCalltracker = /*#__PURE__*/function (_Item) {
	  babelHelpers.inherits(ItemCalltracker, _Item);

	  function ItemCalltracker() {
	    babelHelpers.classCallCheck(this, ItemCalltracker);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ItemCalltracker).apply(this, arguments));
	  }

	  babelHelpers.createClass(ItemCalltracker, [{
	    key: "getOwnerId",
	    value: function getOwnerId() {
	      return [this.data['ASSOCIATED_ENTITY']['TYPE_ID'], this.data['ASSOCIATED_ENTITY']['ID']].join('_');
	    }
	  }, {
	    key: "getNode",
	    value: function getNode() {
	      var _this = this;

	      return this.cache.remember('mainNode', function () {
	        var direction = parseInt(_this.data['ASSOCIATED_ENTITY']['DIRECTION']);
	        var rawDuration = parseInt(_this.data['ASSOCIATED_ENTITY']['SETTINGS']['DURATION']);
	        var hasDuration = rawDuration > 0;
	        var duration = Utils.formatInterval(rawDuration);
	        var created = main_core.Text.encode(_this.data['CREATED']);
	        var comment = main_core.Loc.getMessage('MPL_CALL_IS_PROCESSED');
	        var hasStatus = _this.data['ASSOCIATED_ENTITY']['CALL_INFO'] ? _this.data['ASSOCIATED_ENTITY']['CALL_INFO']['HAS_STATUS'] : false;
	        var status = _this.data['ASSOCIATED_ENTITY']['CALL_INFO'] ? _this.data['ASSOCIATED_ENTITY']['CALL_INFO']['SUCCESSFUL'] : null;
	        var iconClasses = [direction === BX.CrmActivityDirection.incoming ? 'ui-icon-service-call-in' : direction === BX.CrmActivityDirection.outgoing ? 'ui-icon-service-call-out' : 'ui-icon-service-callback'];
	        var render = main_core.Tag.render(_templateObject$2 || (_templateObject$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"feed-com-block-cover crm-phonetracker-notification\">\n\t\t\t\t<div class=\"post-comment-block post-comment-block-old post-comment-block-approved  mobile-longtap-menu \">\n\t\t\t\t\t<div class=\"ui-icon ", " crm-phonetracker-icon\">\n\t\t\t\t\t\t<i></i>\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"post-comment-detail\">\n\t\t\t\t\t\t<div class=\"post-comment-balloon\">\n\t\t\t\t\t\t\t<div class=\"post-comment-cont\">\n\t\t\t\t\t\t\t\t<span class=\"post-comment-author crm-phonetracker-event-name\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t<div class=\"post-comment-time\">", "</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"post-comment-wrap-outer\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t<div class=\"post-label-wrap\">\n\t\t\t\t\t\t\t\t\t<svg width=\"19\" height=\"18\" viewBox=\"0 0 19 18\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\">\n\t\t\t\t\t\t\t\t\t\t<path fill-rule=\"evenodd\" clip-rule=\"evenodd\" d=\"M12.5615 10.4593L14.0291 11.559C14.4726 11.8959 14.507 12.5616 14.0978 12.9414C12.5634 14.4077 10.0067 14.977 6.64094 11.4185C3.27513 7.86011 3.94451 5.31279 5.47892 3.8464C5.88763 3.46681 6.53873 3.52962 6.8498 3.99195L7.87625 5.49314C8.23575 6.04419 8.00868 6.79498 7.45713 7.16309L6.84098 7.57434C6.6994 7.66453 6.65996 7.85351 6.73593 7.99833C7.53276 9.39692 8.68397 10.6226 10.0304 11.5009C10.1646 11.5784 10.3575 11.5383 10.4511 11.4106L10.8862 10.7938C11.2666 10.2563 12.0403 10.0677 12.5615 10.4593Z\" fill=\"white\"/>\n\t\t\t\t\t\t\t\t\t\t<path d=\"M13.8358 4.26291C12.8706 3.2977 11.577 2.7646 10.1993 2.77788C9.9525 2.79346 9.7557 2.99026 9.75345 3.22376C9.76427 3.47033 9.95731 3.66337 10.1908 3.66111C11.3472 3.63671 12.4084 4.0686 13.2193 4.8795C14.0303 5.69041 14.4621 6.75159 14.4372 7.90855C14.436 8.0385 14.4867 8.14155 14.5641 8.21887C14.6415 8.29628 14.7579 8.33373 14.875 8.34529C15.1218 8.32972 15.3186 8.13292 15.3209 7.89942C15.3348 6.52238 14.801 5.22812 13.8358 4.26291Z\" fill=\"white\"/>\n\t\t\t\t\t\t\t\t\t\t<path d=\"M12.5749 5.52404C12.0088 4.95793 11.258 4.65303 10.4524 4.66131C10.2056 4.67688 10.0088 4.87368 10.0065 5.10718C10.0174 5.35375 10.2104 5.54679 10.4439 5.54453C11.0155 5.53902 11.5458 5.7547 11.945 6.15386C12.3436 6.5525 12.5598 7.08336 12.5543 7.65492C12.553 7.78487 12.6038 7.88792 12.6812 7.96534L12.7231 8.00097C12.7976 8.05494 12.8946 8.08326 12.9923 8.0929C13.2391 8.07732 13.4359 7.88052 13.4381 7.64702C13.4459 6.84088 13.141 6.09014 12.5749 5.52404Z\" fill=\"white\"/>\n\t\t\t\t\t\t\t\t\t</svg>\n\t\t\t\t\t\t\t\t\t<div class=\"post-label-text\">", "</div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t"])), iconClasses.join(' '), hasStatus && status !== true ? "<div class=\"ui-icon-cross\">\n\t\t\t\t\t\t\t\t<svg width=\"11\" height=\"11\" viewBox=\"0 0 11 11\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\">\n\t\t\t\t\t\t\t\t\t<path fill-rule=\"evenodd\" clip-rule=\"evenodd\" d=\"M7.19252 5.532L10.7451 9.08457L9.08463 10.745L5.53206 7.19246L1.91046 10.8141L0.25 9.15361L3.87161 5.532L0.319037 1.97943L1.97949 0.318976L5.53206 3.87155L9.15367 0.249939L10.8141 1.91039L7.19252 5.532Z\" fill=\"#767C87\"/>\n\t\t\t\t\t\t\t\t</svg>\n\t\t\t\t\t\t\t</div>" : '', comment, created, hasDuration ? main_core.Tag.render(_templateObject2$2 || (_templateObject2$2 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t\t\t\t\t\t<div class=\"post-comment-wrap\">\n\t\t\t\t\t\t\t\t\t\t\t\t<div class=\"post-comment-text\">", "</div>\n\t\t\t\t\t\t\t\t\t\t\t</div>"])), duration) : '', main_core.Text.encode(_this.data['ASSOCIATED_ENTITY']['CREATED']), _this.getFilesNode(), _this.getActionNode());
	        return render;
	      });
	    }
	  }, {
	    key: "getFilesNode",
	    value: function getFilesNode() {
	      var _this2 = this;

	      return this.cache.remember('filesBlock', function () {
	        if (!_this2.data['ASSOCIATED_ENTITY']['MEDIA_FILE_INFO']) {
	          return '';
	        }

	        var renderTag = main_core.Tag.render(_templateObject3$1 || (_templateObject3$1 = babelHelpers.taggedTemplateLiteral(["<div class=\"post-item-attached-audio\"></div>"])));
	        ui_vue.Vue.create({
	          el: renderTag.appendChild(document.createElement('DIV')),
	          template: "<bx-audioplayer src=\"".concat(_this2.data['ASSOCIATED_ENTITY']['MEDIA_FILE_INFO']['URL'], "\" background=\"dark\"/>")
	        });
	        return main_core.Tag.render(_templateObject4$1 || (_templateObject4$1 = babelHelpers.taggedTemplateLiteral(["<div class=\"post-item-attached-file-wrap\">\n\t\t\t\t<div class=\"post-item-attached-file-wrap\">\n\t\t\t\t\t<div class=\"post-item-attached-file-list\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>"])), renderTag);
	      });
	    }
	  }], [{
	    key: "checkForPaternity",
	    value: function checkForPaternity(data) {
	      return data['TYPE_CODE'] === 'CALL_TRACKER' && !!data['ASSOCIATED_ENTITY'];
	    }
	  }]);
	  return ItemCalltracker;
	}(Item);

	var _templateObject$3, _templateObject2$3, _templateObject3$2;

	var ItemPreview = /*#__PURE__*/function (_Item) {
	  babelHelpers.inherits(ItemPreview, _Item);
	  babelHelpers.createClass(ItemPreview, null, [{
	    key: "checkForPaternity",
	    value: function checkForPaternity(data) {
	      return data.constructor.name === 'Comment';
	    }
	  }]);

	  function ItemPreview(data) {
	    var _this;

	    babelHelpers.classCallCheck(this, ItemPreview);
	    var itemData = {
	      'ID': 'preview_node_' + ItemPreview.count++,
	      'COMMENT': main_core.Text.encode(data.text),
	      'AUTHOR_ID': Configuration.currentAuthor.AUTHOR_ID,
	      'AUTHOR': Configuration.currentAuthor.AUTHOR,
	      'CREATED': new Date().getTime() / 1000
	    };
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ItemPreview).call(this, itemData));
	    data.previewObj = babelHelpers.assertThisInitialized(_this);
	    return _this;
	  }

	  babelHelpers.createClass(ItemPreview, [{
	    key: "getNode",
	    value: function getNode() {
	      var _this2 = this;

	      return this.cache.remember('mainNode', function () {
	        var avatarUrl = main_core.Text.encode(_this2.data['AUTHOR'] && _this2.data['AUTHOR']['IMAGE_URL'] ? _this2.data['AUTHOR']['IMAGE_URL'] : '');
	        var render = main_core.Tag.render(_templateObject$3 || (_templateObject$3 = babelHelpers.taggedTemplateLiteral(["<div class=\"feed-com-block-cover\">\n\t\t\t\t<div class=\"post-comment-block post-comment-block-old post-comment-block-approved mobile-longtap-menu\">\n\t\t\t\t\t<div class=\"ui-icon ui-icon-common-user post-comment-block-avatar\">\n\t\t\t\t\t\t", "\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"post-comment-detail\">\n\t\t\t\t\t\t<div class=\"post-comment-balloon\">\n\t\t\t\t\t\t\t<div class=\"post-comment-cont\">\n\t\t\t\t\t\t\t\t<a href=\"\" class=\"post-comment-author\">", "</a>\n\t\t\t\t\t\t\t\t<div class=\"post-comment-time\">", "</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"post-comment-wrap-outer\">\n\t\t\t\t\t\t\t\t<div class=\"post-comment-wrap\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<div class=\"post-comment-more\" style=\"display: none;\">\n\t\t\t\t\t\t\t\t\t<div class=\"post-comment-more-but\"></div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"post-comment-control-box\" data-bx-role=\"loader-block\">\n\t\t\t\t\t\t\t<div class=\"post-comment-control-item\">", "</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<div class=\"post-comment-control-box\" data-bx-role=\"error-block\">\n\t\t\t\t\t\t\t<div class=\"post-comment-control-item\" data-bx-role=\"error-text\"></div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>"])), avatarUrl ? main_core.Tag.render(_templateObject2$3 || (_templateObject2$3 = babelHelpers.taggedTemplateLiteral(["<i style=\"background-image:url('", "')\"></i>"])), avatarUrl) : main_core.Tag.render(_templateObject3$2 || (_templateObject3$2 = babelHelpers.taggedTemplateLiteral(["<i></i>"]))), main_core.Text.encode(_this2.data['AUTHOR']['FORMATTED_NAME']), _this2.getDateNode(), _this2.getTextNode(), main_core.Loc.getMessage('MPL_MOBILE_PUBLISHING'));
	        return render;
	      });
	    }
	  }, {
	    key: "getDateNode",
	    value: function getDateNode() {
	      return '';
	    }
	  }, {
	    key: "setError",
	    value: function setError(error) {
	      this.getNode().setAttribute('data-bx-status', 'failed');
	      var errorNode = this.getNode().querySelector('[data-bx-role="error-text"]');
	      errorNode.innerHTML = error.message;
	    }
	  }]);
	  return ItemPreview;
	}(Item);

	babelHelpers.defineProperty(ItemPreview, "count", 0);

	var _templateObject$4, _templateObject2$4;

	var ItemActivity = /*#__PURE__*/function (_Item) {
	  babelHelpers.inherits(ItemActivity, _Item);

	  function ItemActivity(data) {
	    var _this;

	    babelHelpers.classCallCheck(this, ItemActivity);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ItemActivity).call(this, data));
	    _this.id = _this.data['ASSOCIATED_ENTITY']['ID'];
	    console.log('this.data: ', _this.data);
	    return _this;
	  }

	  babelHelpers.createClass(ItemActivity, [{
	    key: "getOwnerId",
	    value: function getOwnerId() {
	      return [this.data['ASSOCIATED_ENTITY']['TYPE_ID'], this.getId()].join('_');
	    }
	  }, {
	    key: "getNode",
	    value: function getNode() {
	      var _this2 = this;

	      return this.cache.remember('mainNode', function () {
	        var direction = parseInt(_this2.data['ASSOCIATED_ENTITY']['DIRECTION']);
	        var rawDuration = parseInt(_this2.data['ASSOCIATED_ENTITY']['SETTINGS']['DURATION']);
	        var hasDuration = rawDuration > 0;
	        var duration = Utils.formatInterval(rawDuration);
	        var deadline = main_core.Text.encode(_this2.data['ASSOCIATED_ENTITY']['DEADLINE']);
	        var comment = direction === BX.CrmActivityDirection.incoming ? main_core.Loc.getMessage('MPL_MOBILE_INCOMING_CALL') : direction === BX.CrmActivityDirection.outgoing ? main_core.Loc.getMessage('MPL_MOBILE_OUTBOUND_CALL') : main_core.Loc.getMessage('MPL_MOBILE_CALL');
	        var hasStatus = _this2.data['ASSOCIATED_ENTITY']['CALL_INFO'] ? _this2.data['ASSOCIATED_ENTITY']['CALL_INFO']['HAS_STATUS'] : false;
	        var status = _this2.data['ASSOCIATED_ENTITY']['CALL_INFO'] ? _this2.data['ASSOCIATED_ENTITY']['CALL_INFO']['SUCCESSFUL'] : null;
	        var iconClasses = [direction === BX.CrmActivityDirection.incoming ? 'ui-icon-service-call-in' : direction === BX.CrmActivityDirection.outgoing ? 'ui-icon-service-call-out' : 'ui-icon-service-callback'];
	        var render = main_core.Tag.render(_templateObject$4 || (_templateObject$4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<div class=\"feed-com-block-cover crm-calltracker-notification\">\n\t\t\t\t<div class=\"post-comment-block post-comment-block-old post-comment-block-approved  mobile-longtap-menu \">\n\t\t\t\t\t<div class=\"ui-icon ", " crm-phonetracker-icon\">\n\t\t\t\t\t\t<i></i>\n\t\t\t\t\t\t", "\n\t\t\t\t\t\t<div class=\"ui-icon-counter\">1</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div class=\"post-comment-detail\">\n\t\t\t\t\t\t<div class=\"post-comment-balloon\">\n\t\t\t\t\t\t\t<div class=\"post-comment-cont\">\n\t\t\t\t\t\t\t\t<span class=\"post-comment-author    crm-phonetracker-event-name\">\n\t\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t</span>\n\t\t\t\t\t\t\t\t<div class=\"post-comment-time\">", "</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t<div class=\"post-comment-wrap-outer\">\n\t\t\t\t\t\t\t\t", "\n\t\t\t\t\t\t\t\t<div class=\"post-label-wrap\">\n\t\t\t\t\t\t\t\t\t<svg width=\"19\" height=\"18\" viewBox=\"0 0 19 18\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\">\n\t\t\t\t\t\t\t\t\t\t<path d=\"M8.26297 5.9911H9.76297V8.2411H12.013V9.7411H8.26297V5.9911Z\" fill=\"white\"/>\n\t\t\t\t\t\t\t\t\t\t<path fill-rule=\"evenodd\" clip-rule=\"evenodd\" d=\"M3.5682 11.4511C4.56845 13.6754 6.81991 15.0688 9.25682 14.9716C12.4898 14.9055 15.0579 12.2327 14.995 8.99971C14.9949 6.56087 13.5128 4.36679 11.2504 3.45607C8.98799 2.54536 6.39916 3.10075 4.70939 4.85933C3.01962 6.61792 2.56795 9.22685 3.5682 11.4511ZM4.95948 10.8255C5.70444 12.4821 7.38125 13.5198 9.19618 13.4475C11.604 13.3982 13.5167 11.4076 13.4698 8.99978C13.4697 7.18341 12.3659 5.54933 10.6809 4.87106C8.99597 4.19279 7.06789 4.60642 5.8094 5.91616C4.55092 7.2259 4.21453 9.16894 4.95948 10.8255Z\" fill=\"white\"/>\n\t\t\t\t\t\t\t\t\t</svg>\n\t\t\t\t\t\t\t\t\t<div class=\"post-label-text\">", "</div>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t\t"])), iconClasses.join(' '), hasStatus && status !== true ? "<div class=\"ui-icon-cross\">\n\t\t\t\t\t\t\t\t<svg width=\"11\" height=\"11\" viewBox=\"0 0 11 11\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\">\n\t\t\t\t\t\t\t\t\t<path fill-rule=\"evenodd\" clip-rule=\"evenodd\" d=\"M7.19252 5.532L10.7451 9.08457L9.08463 10.745L5.53206 7.19246L1.91046 10.8141L0.25 9.15361L3.87161 5.532L0.319037 1.97943L1.97949 0.318976L5.53206 3.87155L9.15367 0.249939L10.8141 1.91039L7.19252 5.532Z\" fill=\"#767C87\"/>\n\t\t\t\t\t\t\t\t</svg>\n\t\t\t\t\t\t\t</div>" : '', comment, main_core.Text.encode(_this2.data['ASSOCIATED_ENTITY']['CREATED']), hasDuration ? main_core.Tag.render(_templateObject2$4 || (_templateObject2$4 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t\t\t\t\t\t\t<div class=\"post-comment-wrap\">\n\t\t\t\t\t\t\t\t\t\t\t<div class=\"post-comment-text\">", "</div>\n\t\t\t\t\t\t\t\t\t\t</div>"])), duration) : '', deadline);
	        return render;
	      });
	    }
	  }, {
	    key: "solve",
	    value: function solve() {}
	  }], [{
	    key: "checkForPaternity",
	    value: function checkForPaternity(data) {
	      return false;
	    }
	  }]);
	  return ItemActivity;
	}(Item);

	var Entity = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Entity, _EventEmitter);

	  function Entity() {
	    var _this;

	    babelHelpers.classCallCheck(this, Entity);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Entity).call(this));

	    _this.setEventNamespace('CRM:Calltracker:');

	    _this.status = 'ready';
	    _this.error = null;
	    return _this;
	  }

	  babelHelpers.createClass(Entity, [{
	    key: "isReady",
	    value: function isReady() {
	      return this.status === 'ready';
	    }
	  }, {
	    key: "isFailed",
	    value: function isFailed() {
	      return this.error !== null;
	    }
	  }, {
	    key: "execute",
	    value: function execute() {
	      var _this2 = this;

	      this.status = 'busy';
	      this.emit('start');
	      this.prepare().then(this.submit.bind(this)).then(this.succeed.bind(this)).then(this.finalise.bind(this))["catch"](function (err) {
	        _this2.fail(err);

	        _this2.finalise();
	      });
	    }
	  }, {
	    key: "prepare",
	    value: function prepare() {
	      return Promise.resolve();
	    }
	  }, {
	    key: "submit",
	    value: function submit() {
	      return Promise.resolve();
	    }
	  }, {
	    key: "succeed",
	    value: function succeed(_ref) {
	      var data = _ref.data;
	      this.emit('success', {
	        entity: this,
	        data: data
	      });
	    }
	  }, {
	    key: "fail",
	    value: function fail(error) {
	      this.error = error;
	      this.emit('error', {
	        entity: this,
	        error: error
	      });
	    }
	  }, {
	    key: "finalise",
	    value: function finalise() {
	      this.status = 'finished';
	      this.emit('finish', {
	        entity: this
	      });
	    }
	  }]);
	  return Entity;
	}(main_core_events.EventEmitter);

	var File = /*#__PURE__*/function (_Entity) {
	  babelHelpers.inherits(File, _Entity);

	  function File(data) {
	    var _this;

	    babelHelpers.classCallCheck(this, File);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(File).call(this));
	    _this.data = data || {};
	    _this.file = null;
	    _this.id = ['crm_timeline_file_comment', File.counter++].join('_');
	    return _this;
	  }

	  babelHelpers.createClass(File, [{
	    key: "prepare",
	    value: function prepare() {
	      if (this.data.url && (/^file:\/\//.test(this.data.url) || this.data.type === 'audio/mp4')) {
	        return Promise.resolve();
	      }

	      return Promise.reject('Empty file body');
	    }
	  }, {
	    key: "submit",
	    value: function submit() {
	      var _this2 = this;

	      return new Promise(function (resolve, reject) {
	        var name = typeof mobile_utils.Utils.getUploadFilename === 'function' ? mobile_utils.Utils.getUploadFilename(_this2.data.name, _this2.data.type) : _this2.data.name;
	        var uploadTask = {
	          taskId: _this2.id,
	          type: _this2.data.type,
	          mimeType: BX.MobileUtils.getFileMimeType(_this2.data.type),
	          folderId: parseInt(main_core.Loc.getMessage('MOBILE_EXT_UTILS_USER_FOLDER_FOR_SAVED_FILES')),
	          name: name,
	          url: _this2.data.url,
	          previewUrl: _this2.data.previewUrl ? _this2.data.previewUrl : null,
	          resize: BX.MobileUtils.getResizeOptions(_this2.data.type)
	        };

	        if (_this2.data.type === 'audio/mp4') {
	          uploadTask = {
	            taskId: _this2.id,
	            type: 'mp3',
	            mimeType: 'audio/mp4',
	            folderId: parseInt(main_core.Loc.getMessage('MOBILE_EXT_UTILS_USER_FOLDER_FOR_SAVED_FILES')),
	            name: 'mobile_audio_' + new Date().toJSON().slice(0, 19).replace('T', '_').split(':').join('-') + '.mp3',
	            url: _this2.data.url,
	            previewUrl: null
	          };
	        }

	        var fileReceive = function (_ref) {
	          var event = _ref.event,
	              data = _ref.data,
	              taskId = _ref.taskId;

	          if (taskId !== this.id) {
	            return;
	          }

	          if (event === 'onfilecreated') {
	            BX.removeCustomEvent('onFileUploadStatusChanged', fileReceive);

	            if (data.result.status !== 'error') {
	              var file = data.result.data.file;
	              this.file = {
	                ID: file.id,
	                IMAGE: typeof file.extra.imagePreviewUri != 'undefined' ? file.extra.imagePreviewUri : '',
	                NAME: file.name,
	                URL: {
	                  URL: typeof file.extra.downloadUri != 'undefined' ? file.extra.downloadUri : '',
	                  EXTERNAL: 'YES',
	                  PREVIEW: typeof file.extra.imagePreviewUri != 'undefined' ? file.extra.imagePreviewUri : ''
	                },
	                VALUE: 'n' + file.id
	              };
	              return resolve({
	                data: this.file
	              });
	            }

	            var errors = [];

	            if (main_core.Type.isArrayFilled(data.result.errors)) {
	              data.result.errors.forEach(function (_ref2) {
	                var message = _ref2.message,
	                    code = _ref2.code;
	                errors.push(main_core.Type.isStringFilled(message) ? message : code);
	              });
	            }

	            data.error = {
	              message: errors.join(''),
	              code: 'Receiver response error.'
	            };
	          }

	          if (data && data.error) {
	            BX.removeCustomEvent('onFileUploadStatusChanged', fileReceive);
	            var errorMessage = main_core.Type.isStringFilled(data.error.message) ? data.error.message : 'File uploading error.';
	            var errorCode = main_core.Type.isStringFilled(data.error.code) ? data.error.code : 'File uploading code.';
	            return reject(new Error(errorMessage, errorCode));
	          }
	        }.bind(_this2);

	        BX.addCustomEvent('onFileUploadStatusChanged', fileReceive);
	        BXMobileApp.onCustomEvent('onFileUploadTaskReceived', {
	          files: [uploadTask]
	        }, true);
	      });
	    }
	  }, {
	    key: "getText",
	    value: function getText() {
	      if (this.file !== null) {
	        return '[DISK FILE ID=' + this.file['VALUE'] + ']';
	      }

	      return 'text';
	    }
	  }, {
	    key: "getSavedData",
	    value: function getSavedData() {
	      return this.file;
	    }
	  }]);
	  return File;
	}(Entity);

	babelHelpers.defineProperty(File, "counter", 0);

	var Queue = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Queue, _EventEmitter);

	  function Queue() {
	    var _this;

	    babelHelpers.classCallCheck(this, Queue);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Queue).call(this, 'CRM:Calltracker:'));

	    _this.setEventNamespace('CRM:Calltracker:');

	    _this.queue = [];
	    _this.erroredQueue = [];
	    _this.next = _this.next.bind(babelHelpers.assertThisInitialized(_this));
	    return _this;
	  }

	  babelHelpers.createClass(Queue, [{
	    key: "send",
	    value: function send(entity) {
	      this.queue.push(entity);
	      this.check();
	    }
	  }, {
	    key: "check",
	    value: function check() {
	      if (this.queue.length > 0) {
	        return this.execute(this.queue[0]);
	      }

	      return this.finish();
	    }
	  }, {
	    key: "next",
	    value: function next(_ref) {
	      var entity = _ref.data.entity;

	      if (entity.isFailed()) {
	        this.erroredQueue.push(entity);
	      }

	      if (this.queue[0] === entity) {
	        this.queue.shift();
	      } else {
	        var index = 0;
	        this.queue.forEach(function (ent, ind) {
	          if (ent === entity) {
	            index = ind;
	          }
	        });
	        this.queue.splice(index, 1);
	      }

	      this.check();
	    }
	  }, {
	    key: "execute",
	    value: function execute(entity) {
	      if (entity.isReady()) {
	        entity.subscribe('finish', this.next);
	        entity.execute();
	      }
	    }
	  }, {
	    key: "finish",
	    value: function finish() {
	      if (this.erroredQueue.length > 0) {
	        this.emit('error');
	      } else {
	        this.emit('success');
	      }

	      this.emit('finish');
	    }
	  }]);
	  return Queue;
	}(main_core_events.EventEmitter);

	var CommentSender = /*#__PURE__*/function (_Queue) {
	  babelHelpers.inherits(CommentSender, _Queue);

	  function CommentSender() {
	    babelHelpers.classCallCheck(this, CommentSender);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(CommentSender).apply(this, arguments));
	  }

	  babelHelpers.createClass(CommentSender, null, [{
	    key: "getInstance",
	    value: function getInstance() {
	      if (CommentSender.instance === null) {
	        CommentSender.instance = new CommentSender();
	      }

	      return CommentSender.instance;
	    }
	  }]);
	  return CommentSender;
	}(Queue);

	babelHelpers.defineProperty(CommentSender, "instance", null);

	var FileSender = /*#__PURE__*/function (_Queue) {
	  babelHelpers.inherits(FileSender, _Queue);

	  function FileSender() {
	    babelHelpers.classCallCheck(this, FileSender);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(FileSender).apply(this, arguments));
	  }

	  babelHelpers.createClass(FileSender, [{
	    key: "check",
	    value: function check() {
	      if (this.erroredQueue.length <= 0 && this.queue.length > 0) {
	        return this.execute(this.queue[0]);
	      }

	      return this.finish();
	    }
	  }]);
	  return FileSender;
	}(Queue);

	var Comment = /*#__PURE__*/function (_Entity) {
	  babelHelpers.inherits(Comment, _Entity);

	  function Comment(data) {
	    var _this;

	    babelHelpers.classCallCheck(this, Comment);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Comment).call(this));
	    _this.text = '';

	    if (main_core.Type.isStringFilled(data.text)) {
	      _this.text = data.text;
	    }

	    _this.files = [];

	    if (main_core.Type.isArrayFilled(data.files)) {
	      _this.files = data.files;
	    }

	    if (data.events) {
	      ['start', 'success', 'error', 'finish'].forEach(function (eventName) {
	        if (data.events[eventName]) {
	          _this.subscribe(eventName, data.events[eventName]);
	        }
	      });
	    }

	    CommentSender.getInstance().send(babelHelpers.assertThisInitialized(_this));
	    return _this;
	  }

	  babelHelpers.createClass(Comment, [{
	    key: "prepare",
	    value: function prepare() {
	      var _this2 = this;

	      if (main_core.Type.isArrayFilled(this.files)) {
	        return new Promise(function (resolve, reject) {
	          var fileSender = new FileSender(true);
	          fileSender.subscribe('success', function () {
	            if (!main_core.Type.isStringFilled(_this2.text)) {
	              _this2.files.forEach(function (file) {
	                _this2.text += file.getText();
	              });
	            }

	            resolve();
	          });
	          fileSender.subscribe('error', function () {
	            var errors = [];

	            _this2.files.forEach(function (file) {
	              if (file.isFailed()) {
	                errors.push(file.error.message);
	              }
	            });

	            reject(new Error(errors.join(' '), 'File upload error.'));
	          });

	          _this2.files.forEach(function (fileData, index) {
	            _this2.files[index] = new File(fileData);
	            fileSender.send(_this2.files[index]);
	          });
	        });
	      }

	      if (main_core.Type.isStringFilled(this.text)) {
	        return Promise.resolve();
	      }

	      return Promise.reject('Empty comment data.');
	    }
	  }, {
	    key: "submit",
	    value: function submit() {
	      return Backend.createItem({
	        text: this.text,
	        files: this.files.map(function (file) {
	          return file.file['VALUE'];
	        })
	      })["catch"](function (result) {
	        var errors = [];

	        if (main_core.Type.isArrayFilled(result.errors)) {
	          result.errors.forEach(function (_ref) {
	            var message = _ref.message,
	                code = _ref.code;
	            errors.push(main_core.Type.isStringFilled(message) ? message : code);
	          });
	        } else {
	          errors.push('Receiver response error.');
	        }

	        return Promise.reject({
	          message: errors.join(''),
	          code: 'Receiver response error.'
	        });
	      });
	    }
	  }]);
	  return Comment;
	}(Entity);

	var Form = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Form, _EventEmitter);

	  function Form() {
	    var _this;

	    babelHelpers.classCallCheck(this, Form);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Form).call(this));

	    _this.setEventNamespace('CRM:Calltracker:');

	    _this.params = {
	      useAudioMessages: true,
	      placeholder: '',
	      //onEvent: this.onFormIsActive.bind(this),
	      onSend: _this.onSendButtonPressed.bind(babelHelpers.assertThisInitialized(_this))
	    };
	    window.BX.MobileUI.TextField.show(_this.params);
	    _this.showCommentStart = _this.showCommentStart.bind(babelHelpers.assertThisInitialized(_this));
	    _this.showCommentError = _this.showCommentError.bind(babelHelpers.assertThisInitialized(_this));
	    _this.showCommentSucceed = _this.showCommentSucceed.bind(babelHelpers.assertThisInitialized(_this));
	    return _this;
	  }

	  babelHelpers.createClass(Form, [{
	    key: "show",
	    value: function show() {
	      window.BXMobileApp.UI.Page.TextPanel.setText('');
	      window.BXMobileApp.UI.Page.TextPanel.focus();
	    }
	  }, {
	    key: "onFormIsActive",
	    value: function onFormIsActive(event) {
	      console.log('event: ', event);
	    }
	  }, {
	    key: "onSendButtonPressed",
	    value: function onSendButtonPressed(_ref) {
	      var text = _ref.text,
	          attachedFiles = _ref.attachedFiles;
	      window.BXMPage.TextPanel.clear();
	      var cleanText = String(text).trim();

	      if (cleanText.length > 0 || attachedFiles && attachedFiles.length > 0) {
	        var entity = new Comment({
	          text: cleanText,
	          files: attachedFiles,
	          events: {
	            start: this.showCommentStart,
	            error: this.showCommentError,
	            success: this.showCommentSucceed
	          }
	        });
	        this.emit('onNewComment', {
	          comment: entity
	        });
	      }
	    }
	  }, {
	    key: "showCommentStart",
	    value: function showCommentStart(_ref2) {
	      var entity = _ref2.data.entity;
	    }
	  }, {
	    key: "showCommentError",
	    value: function showCommentError(_ref3) {
	      var _ref3$data = _ref3.data,
	          entity = _ref3$data.entity,
	          error = _ref3$data.error;
	      this.emit('onFailedComment', {
	        comment: entity,
	        error: error
	      });
	    }
	  }, {
	    key: "showCommentSucceed",
	    value: function showCommentSucceed(_ref4) {
	      var _ref4$data = _ref4.data,
	          entity = _ref4$data.entity,
	          _ref4$data$data = _ref4$data.data,
	          item = _ref4$data$data.item,
	          items = _ref4$data$data.items;
	      this.emit('onSucceedComment', {
	        comment: entity,
	        commentData: item,
	        comments: items
	      });
	    }
	  }, {
	    key: "showWait",
	    value: function showWait() {
	      window.BXMobileApp.UI.Page.TextPanel.showLoading(true);
	    }
	  }, {
	    key: "closeWait",
	    value: function closeWait() {
	      window.BXMobileApp.UI.Page.TextPanel.showLoading(false);
	    }
	  }]);
	  return Form;
	}(main_core_events.EventEmitter);

	var itemMappings = [Item, ItemComment, ItemCalltracker];

	function getItemByData(itemData) {
	  var itemClassName = Item;
	  itemMappings.forEach(function (itemClass) {
	    if (itemClass.checkForPaternity(itemData)) {
	      itemClassName = itemClass;
	    }
	  });
	  return new itemClassName(itemData);
	}

	window.app.exec("enableCaptureKeyboard", true);
	var keyBoardIsShown = false;
	main_core.addCustomEvent("onKeyboardWillShow", function () {
	  keyBoardIsShown = true;
	});
	main_core.addCustomEvent("onKeyboardDidHide", function () {
	  keyBoardIsShown = false;
	});

	var Timeline = /*#__PURE__*/function (_EventEmitter) {
	  babelHelpers.inherits(Timeline, _EventEmitter);

	  function Timeline(_ref) {
	    var _this;

	    var entity = _ref.entity,
	        containerScheduleItems = _ref.containerScheduleItems,
	        scheduleItems = _ref.scheduleItems,
	        containerHistoryItems = _ref.containerHistoryItems,
	        historyItems = _ref.historyItems;
	    babelHelpers.classCallCheck(this, Timeline);
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(Timeline).call(this));

	    _this.setEventNamespace('CRM:Calltracker:');

	    _this.activities = new Map();
	    _this.items = new Map();
	    _this.entity = entity;

	    _this.addScheduleItems(scheduleItems, containerScheduleItems);

	    _this.container = containerHistoryItems;

	    _this.addItems(historyItems);

	    _this.pagination = null;
	    _this.form = new Form();

	    _this.form.subscribe('onNewComment', _this.onNewComment.bind(babelHelpers.assertThisInitialized(_this)));

	    _this.form.subscribe('onFailedComment', _this.onFailedComment.bind(babelHelpers.assertThisInitialized(_this)));

	    _this.form.subscribe('onSucceedComment', _this.onSucceedComment.bind(babelHelpers.assertThisInitialized(_this))); //@todo remove this test

	    /*				setTimeout(() => {
	    					const previewItem = new ItemPreview({text: 'Text for test error'});
	    					previewItem.setError(new Error('Just error to check template.'));
	    
	    					this.container.appendChild(previewItem.getNode());
	    					const previewItemAnoterOne = new ItemPreview({text: 'Text for preview test'});
	    					this.container.appendChild(previewItemAnoterOne.getNode());
	    				}, 100);
	    */


	    return _this;
	  }

	  babelHelpers.createClass(Timeline, [{
	    key: "addScheduleItems",
	    value: function addScheduleItems(items, containerScheduleItems) {
	      var _this2 = this;

	      items.forEach(function (itemData) {
	        var item = new ItemActivity(itemData);

	        _this2.activities.set(item.getOwnerId(), item);

	        containerScheduleItems.appendChild(item.getNode());
	      });
	    }
	  }, {
	    key: "addItems",
	    value: function addItems(items) {
	      var _this3 = this;

	      var pointerNode = this.container.firstChild;
	      items.forEach(function (itemData) {
	        var item = getItemByData(itemData);

	        _this3.items.set(item.getId(), item);

	        if (_this3.activities.has(item.getOwnerId())) {
	          var activity = _this3.activities.get(item.getOwnerId());

	          activity.getNode().parentNode.removeChild(activity.getNode());
	          activity.solve();

	          _this3.activities["delete"](activity.getOwnerId());
	        }

	        _this3.container.insertBefore(item.getNode(), pointerNode);
	      });
	    }
	  }, {
	    key: "onNewComment",
	    value: function onNewComment(_ref2) {
	      var comment = _ref2.data.comment;
	      var previewItem = new ItemPreview(comment);
	      this.container.appendChild(previewItem.getNode());
	      comment.item = previewItem;
	      comment.node = previewItem.getNode();
	      var iosPatchNeeded = false;

	      if (main_core.Browser.isIOS()) {
	        var res = navigator.appVersion.match(/OS (\d+)_(\d+)_?(\d+)?/);
	        var iOSVersion = parseInt(res[1], 10);
	        iosPatchNeeded = iOSVersion >= 11 && keyBoardIsShown;
	      }

	      var iosPatchDelta = iosPatchNeeded ? 260 : 0;
	      var thumbPos = main_core.pos(previewItem.getNode());
	      var visibleTop = main_core.GetWindowInnerSize().innerHeight - iosPatchDelta;

	      if (iosPatchNeeded === false || thumbPos.top > visibleTop) {
	        window.scrollTo(0, thumbPos.top - iosPatchDelta);
	      }
	    }
	  }, {
	    key: "onFailedComment",
	    value: function onFailedComment(_ref3) {
	      var comment = _ref3.data.comment;

	      if (comment.item) {
	        comment.item.setError(comment.error);
	      }
	    }
	  }, {
	    key: "onSucceedComment",
	    value: function onSucceedComment(_ref4) {
	      var _this4 = this;

	      var _ref4$data = _ref4.data,
	          comment = _ref4$data.comment,
	          commentData = _ref4$data.commentData,
	          comments = _ref4$data.comments;
	      console.log('commentData: ', commentData, comments);
	      comments.forEach(function (itemData) {
	        var item = getItemByData(itemData);

	        _this4.items.set(item.getId(), item);

	        if (_this4.activities.has(item.getOwnerId())) {
	          var activity = _this4.activities.get(item.getOwnerId());

	          activity.getNode().parentNode.removeChild(activity.getNode());
	          activity.solve();

	          _this4.activities["delete"](activity.getOwnerId());
	        }

	        if (item.getId() < commentData['ID']) {
	          _this4.container.insertBefore(item.getNode(), comment.node);
	        } else if (item.getId() > commentData['ID']) {
	          _this4.container.insertBefore(item.getNode(), comment.node.nextSibling);
	        } else {
	          comment.node.parentNode.replaceChild(item.getNode(), comment.node);
	          delete comment.node;
	          delete comment.item;
	        }
	      });

	      if (BXMobileApp && this.entity) {
	        BXMobileApp.Events.postToComponent('onCrmCallTrackerItemCommentAdded', {
	          ID: this.entity.ID
	        });
	        BX.onCustomEvent('onCrmCallTrackerItemCommentAdded', {
	          ID: this.entity.ID
	        });
	      }
	    }
	  }, {
	    key: "initPagination",
	    value: function initPagination(itemId) {
	      this.pagination = new Pagination(itemId, this.addItems.bind(this));
	      this.container.parentNode.insertBefore(this.pagination.getNode(), this.container);
	    }
	  }]);
	  return Timeline;
	}(main_core_events.EventEmitter);

	exports.Configuration = Configuration;
	exports.Timeline = Timeline;

}((this.BX.Mobile.Crm.Calltracker = this.BX.Mobile.Crm.Calltracker || {}),BX,BX,window,BX,BX,BX,BX.Event));
//# sourceMappingURL=script.js.map
