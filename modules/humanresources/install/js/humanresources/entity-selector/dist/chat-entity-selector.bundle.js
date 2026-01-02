/* eslint-disable */
this.BX = this.BX || {};
this.BX.Humanresources = this.BX.Humanresources || {};
(function (exports,ui_entitySelector) {
	'use strict';

	function _createForOfIteratorHelper(o, allowArrayLike) { var it = typeof Symbol !== "undefined" && o[Symbol.iterator] || o["@@iterator"]; if (!it) { if (Array.isArray(o) || (it = _unsupportedIterableToArray(o)) || allowArrayLike && o && typeof o.length === "number") { if (it) o = it; var i = 0; var F = function F() {}; return { s: F, n: function n() { if (i >= o.length) return { done: true }; return { done: false, value: o[i++] }; }, e: function e(_e) { throw _e; }, f: F }; } throw new TypeError("Invalid attempt to iterate non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); } var normalCompletion = true, didErr = false, err; return { s: function s() { it = it.call(o); }, n: function n() { var step = it.next(); normalCompletion = step.done; return step; }, e: function e(_e2) { didErr = true; err = _e2; }, f: function f() { try { if (!normalCompletion && it["return"] != null) it["return"](); } finally { if (didErr) throw err; } } }; }
	function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
	function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }

	// eslint-disable-next-line max-lines-per-function
	var createTagOptions = function createTagOptions(icons) {
	  var defaultIcons = {
	    channelTabIcon: 'data:image/svg+xml;charset=US-ASCII,%3Csvg%20width%3D%2228%22%20height%3D%2228%22%20viewBox%3D%220%200%2028%2028%22%20fill%3D%22none%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%0A%3Cpath%20fill-rule%3D%22evenodd%22%20clip-rule%3D%22evenodd%22%20d%3D%22M7.64096%2010.595C7.64332%2010.5943%207.64571%2010.5937%207.64814%2010.5933L22.1019%207.65054C22.5182%207.56578%2022.91%207.87153%2022.91%208.28122V18.3685C22.91%2018.7915%2022.4941%2019.0998%2022.0693%2018.9918L16.5248%2017.5807V19.2893C16.5248%2019.8826%2016.0252%2020.3636%2015.4089%2020.3636H10.1932C9.5769%2020.3636%209.0773%2019.8826%209.0773%2019.2893V15.6854L7.64271%2015.3203L7.63668%2015.3186C7.60627%2015.3404%207.57166%2015.3574%207.53385%2015.3685L5.52359%2015.9546C5.30862%2016.0172%205.0918%2015.8623%205.0918%2015.6461V10.2643C5.0918%2010.0481%205.30862%209.89318%205.52359%209.95585L7.53385%2010.542C7.57345%2010.5535%207.60953%2010.5717%207.64096%2010.595ZM15.0575%2017.2073L10.5447%2016.0588V18.779C10.5447%2018.957%2010.6945%2019.1013%2010.8794%2019.1013H14.7227C14.9076%2019.1013%2015.0575%2018.957%2015.0575%2018.779V17.2073Z%22%20fill%3D%22%23959CA4%22%2F%3E%0A%3C%2Fsvg%3E',
	    channelTagIcon: 'data:image/svg+xml;charset=US-ASCII,%3Csvg%20width%3D%2222%22%20height%3D%2223%22%20viewBox%3D%220%200%2022%2023%22%20fill%3D%22none%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%0A%3Cpath%20fill-rule%3D%22evenodd%22%20clip-rule%3D%22evenodd%22%20d%3D%22M11%200.5C17.0751%200.5%2022%205.42487%2022%2011.5C22%2017.5751%2017.0751%2022.5%2011%2022.5C4.92487%2022.5%200%2017.5751%200%2011.5C0%205.42487%204.92487%200.5%2011%200.5Z%22%20fill%3D%22%238DBB00%22%2F%3E%0A%3Cpath%20fill-rule%3D%22evenodd%22%20clip-rule%3D%22evenodd%22%20d%3D%22M6.00291%208.82461C6.00477%208.82411%206.00665%208.82367%206.00855%208.82328L17.3651%206.51115C17.6922%206.44455%2018%206.68479%2018%207.00668V14.9324C18%2015.2648%2017.6732%2015.507%2017.3395%2015.4221L12.9831%2014.3134V15.6558C12.9831%2016.1221%2012.5906%2016.5%2012.1063%2016.5H8.00824C7.52401%2016.5%207.13147%2016.1221%207.13147%2015.6559V12.8242L6.00429%2012.5374L5.99955%2012.536C5.97566%2012.5532%205.94847%2012.5666%205.91876%2012.5752L4.33926%2013.0357C4.17036%2013.085%204%2012.9633%204%2012.7934V8.56484C4%208.39493%204.17036%208.27322%204.33926%208.32246L5.91876%208.78297C5.94987%208.79204%205.97822%208.80631%206.00291%208.82461ZM11.8302%2014.02L8.28439%2013.1176V15.2549C8.28439%2015.3948%208.40215%2015.5082%208.54742%2015.5082H11.5672C11.7124%2015.5082%2011.8302%2015.3948%2011.8302%2015.2549V14.02Z%22%20fill%3D%22white%22%2F%3E%0A%3C%2Fsvg%3E',
	    chatTagIcon: 'data:image/svg+xml;charset=US-ASCII,%3Csvg%20width%3D%2223%22%20height%3D%2223%22%20viewBox%3D%220%200%2023%2023%22%20fill%3D%22none%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%0A%3Crect%20width%3D%2223%22%20height%3D%2223%22%20rx%3D%2211.5%22%20fill%3D%22%2311A9D9%22%2F%3E%0A%3Cpath%20d%3D%22M6.32934%205.3717C5.36702%205.3717%204.58691%206.15181%204.58691%207.11413V11.4702C4.58691%2012.4325%205.36702%2013.2126%206.32934%2013.2126H6.55371V14.5643C6.55371%2015.03%207.11676%2015.2632%207.44606%2014.9339L8.49727%2013.8827V10.5659C8.49727%209.12246%209.66743%207.95229%2011.1109%207.95229H15.0415V7.11413C15.0415%206.15181%2014.2614%205.3717%2013.299%205.3717H6.32934Z%22%20fill%3D%22white%22%2F%3E%0A%3Cpath%20d%3D%22M9.8035%2010.8446C9.8035%209.8823%2010.5836%209.10219%2011.5459%209.10219H17.4125C18.3748%209.10219%2019.1549%209.8823%2019.1549%2010.8446V14.2766C19.1549%2015.2378%2018.3767%2016.0171%2017.416%2016.019V17.1396C17.416%2017.6341%2016.7931%2017.8522%2016.4847%2017.4657L15.3302%2016.019H11.5459C10.5836%2016.019%209.8035%2015.2389%209.8035%2014.2766V10.8446Z%22%20fill%3D%22white%22%2F%3E%0A%3C%2Fsvg%3E',
	    chatTabIcon: 'data:image/svg+xml;charset=US-ASCII,%3Csvg%20width%3D%2228%22%20height%3D%2228%22%20viewBox%3D%220%200%2028%2028%22%20fill%3D%22none%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%0A%3Cpath%20d%3D%22M7.07552%205.79346C5.78686%205.79346%204.74219%206.83813%204.74219%208.12679V13.9601C4.74219%2015.2488%205.78686%2016.2935%207.07552%2016.2935H7.37598V18.1035C7.37598%2018.7271%208.12998%2019.0395%208.57096%2018.5985L9.97866%2017.1908V12.7492C9.97866%2010.8162%2011.5457%209.2492%2013.4787%209.2492H18.7422V8.12679C18.7422%206.83813%2017.6975%205.79346%2016.4089%205.79346H7.07552Z%22%20fill%3D%22%23959CA4%22%2F%3E%0A%3Cpath%20d%3D%22M11.7279%2013.1224C11.7279%2011.8337%2012.7725%2010.7891%2014.0612%2010.7891H21.9173C23.206%2010.7891%2024.2506%2011.8337%2024.2506%2013.1224V17.7183C24.2506%2019.0054%2023.2085%2020.0491%2021.922%2020.0516V21.5523C21.922%2022.2144%2021.0879%2022.5064%2020.6749%2021.9889L19.1288%2020.0516H14.0612C12.7725%2020.0516%2011.7279%2019.007%2011.7279%2017.7183V13.1224Z%22%20fill%3D%22%23959CA4%22%2F%3E%0A%3C%2Fsvg%3E'
	  };
	  defaultIcons.channelTabIconSelected = defaultIcons.channelTabIcon.replaceAll('959CA4', 'fff');
	  defaultIcons.chatTabIconSelected = defaultIcons.chatTabIcon.replaceAll('959CA4', 'fff');
	  defaultIcons.channelItemIcon = defaultIcons.channelTabIcon;
	  defaultIcons.chatItemIcon = defaultIcons.chatTabIcon;
	  var optionIcons = _objectSpread(_objectSpread({}, defaultIcons), icons);
	  return {
	    multiple: true,
	    dialogOptions: {
	      enableSearch: true,
	      height: 250,
	      width: 380,
	      tabs: [{
	        id: 'im-channel',
	        title: 'Каналы',
	        stub: true,
	        icon: {
	          "default": optionIcons.channelTabIcon,
	          selected: optionIcons.channelTabIconSelected
	        }
	      }, {
	        id: 'im-chat',
	        title: 'Чаты',
	        stub: true,
	        icon: {
	          "default": optionIcons.chatTabIcon,
	          selected: optionIcons.chatTabIconSelected
	        }
	      }],
	      entities: [{
	        id: 'im-channel',
	        filters: [{
	          id: 'im.channelDataFilter',
	          options: {
	            tabId: 'im-channel',
	            includeSubtitle: true
	          }
	        }],
	        dynamicLoad: true,
	        tagOptions: {
	          "default": {
	            textColor: '#8DBB00',
	            bgColor: '#EAF6C3',
	            avatar: defaultIcons.channelTagIcon
	          }
	        },
	        itemOptions: {
	          "default": {
	            avatar: defaultIcons.channelItemIcon
	          }
	        },
	        options: {
	          searchableChatTypes: ['N']
	        }
	      }, {
	        id: 'im-chat',
	        dynamicLoad: true,
	        filters: [{
	          id: 'im.chatDataFilter',
	          options: {
	            tabId: 'im-chat',
	            includeSubtitle: true
	          }
	        }],
	        tagOptions: {
	          "default": {
	            textColor: '#11A9D9',
	            bgColor: '#D3F4FF',
	            avatar: optionIcons.chatTagIcon
	          }
	        },
	        itemOptions: {
	          "default": {
	            avatar: defaultIcons.chatItemIcon
	          }
	        },
	        options: {
	          searchableChatTypes: ['C', 'L', 'O']
	        }
	      }]
	    }
	  };
	};
	var ChatTagSelector = /*#__PURE__*/function (_TagSelector) {
	  babelHelpers.inherits(ChatTagSelector, _TagSelector);
	  /**
	   * @param options - TagSelectorOptions
	   * @param stripExternalItemOptions - if true, it deletes all itemOptions from each entity which was not specified
	   * in options parameter
	   */
	  function ChatTagSelector() {
	    var _this;
	    var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : null;
	    var stripExternalItemOptions = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : true;
	    babelHelpers.classCallCheck(this, ChatTagSelector);
	    var tagOptions = options !== null && options !== void 0 ? options : createTagOptions();
	    _this = babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(ChatTagSelector).call(this, tagOptions));
	    if (stripExternalItemOptions) {
	      var _iterator = _createForOfIteratorHelper(_this.dialog.entities),
	        _step;
	      try {
	        var _loop = function _loop() {
	          var _step$value = babelHelpers.slicedToArray(_step.value, 2),
	            key = _step$value[0],
	            entity = _step$value[1];
	          var optionsEntity = tagOptions.dialogOptions.entities.find(function (item) {
	            return item.id === key;
	          });
	          if (optionsEntity !== null && optionsEntity !== void 0 && optionsEntity.itemOptions) {
	            entity.itemOptions = optionsEntity.itemOptions;
	          }
	        };
	        for (_iterator.s(); !(_step = _iterator.n()).done;) {
	          _loop();
	        }
	      } catch (err) {
	        _iterator.e(err);
	      } finally {
	        _iterator.f();
	      }
	    }
	    return _this;
	  }
	  return ChatTagSelector;
	}(ui_entitySelector.TagSelector);

	exports.createTagOptions = createTagOptions;
	exports.ChatTagSelector = ChatTagSelector;

}((this.BX.Humanresources.EntitySelectorEx = this.BX.Humanresources.EntitySelectorEx || {}),BX.UI.EntitySelector));
//# sourceMappingURL=chat-entity-selector.bundle.js.map
