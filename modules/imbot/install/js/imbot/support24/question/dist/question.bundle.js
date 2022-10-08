this.BX = this.BX || {};
this.BX.Imbot = this.BX.Imbot || {};
(function (exports,ui_vue_vuex,ui_notification,main_loader,ui_infoHelper,main_core_events,ui_fonts_opensans,ui_buttons,ui_designTokens,ui_vue,main_core) {
	'use strict';

	var QuestionModel = /*#__PURE__*/function (_VuexBuilderModel) {
	  babelHelpers.inherits(QuestionModel, _VuexBuilderModel);

	  function QuestionModel() {
	    babelHelpers.classCallCheck(this, QuestionModel);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(QuestionModel).apply(this, arguments));
	  }

	  babelHelpers.createClass(QuestionModel, [{
	    key: "getName",
	    value: function getName() {
	      return 'question';
	    }
	  }, {
	    key: "getState",
	    value: function getState() {
	      return {
	        history: [],
	        searchResult: []
	      };
	    }
	  }, {
	    key: "getActions",
	    value: function getActions() {
	      var _this = this;

	      return {
	        trimHistory: function trimHistory(store, count) {
	          store.commit('trimHistory', count);
	        },
	        setHistory: function setHistory(store, payload) {
	          store.commit('setHistory', _this.validateQuestionList(payload));
	        },
	        addHistory: function addHistory(store, payload) {
	          store.commit('addHistory', _this.validateQuestionList(payload));
	        },
	        setSearchResult: function setSearchResult(store, payload) {
	          store.commit('setSearchResult', _this.validateQuestionList(payload));
	        },
	        addSearchResult: function addSearchResult(store, payload) {
	          store.commit('addSearchResult', _this.validateQuestionList(payload));
	        },
	        setQuestionTitleById: function setQuestionTitleById(store, payload) {
	          store.commit('setQuestionTitleById', payload);
	        }
	      };
	    }
	  }, {
	    key: "getMutations",
	    value: function getMutations() {
	      var _this2 = this;

	      return {
	        trimHistory: function trimHistory(state, count) {
	          state.history = state.history.filter(function (question, questionIndex) {
	            return questionIndex < count;
	          });
	          babelHelpers.get(babelHelpers.getPrototypeOf(QuestionModel.prototype), "saveState", _this2).call(_this2, state);
	        },
	        setHistory: function setHistory(state, payload) {
	          state.history = payload;
	          babelHelpers.get(babelHelpers.getPrototypeOf(QuestionModel.prototype), "saveState", _this2).call(_this2, state);
	        },
	        addHistory: function addHistory(state, payload) {
	          state.history = state.history.concat(payload);
	          babelHelpers.get(babelHelpers.getPrototypeOf(QuestionModel.prototype), "saveState", _this2).call(_this2, state);
	        },
	        setSearchResult: function setSearchResult(state, payload) {
	          state.searchResult = payload;
	          babelHelpers.get(babelHelpers.getPrototypeOf(QuestionModel.prototype), "saveState", _this2).call(_this2, state);
	        },
	        addSearchResult: function addSearchResult(state, payload) {
	          state.searchResult = state.searchResult.concat(payload);
	          babelHelpers.get(babelHelpers.getPrototypeOf(QuestionModel.prototype), "saveState", _this2).call(_this2, state);
	        },
	        setQuestionTitleById: function setQuestionTitleById(state, payload) {
	          var question = state.history.find(function (question) {
	            return question.id === payload.id;
	          });
	          question.title = payload.title;
	          babelHelpers.get(babelHelpers.getPrototypeOf(QuestionModel.prototype), "saveState", _this2).call(_this2, state);
	        }
	      };
	    }
	  }, {
	    key: "validateQuestionList",
	    value: function validateQuestionList(questions) {
	      var _this3 = this;

	      if (!main_core.Type.isArrayFilled(questions)) {
	        return [];
	      }

	      return questions.filter(function (question) {
	        return _this3.isQuestion(question);
	      });
	    }
	  }, {
	    key: "isQuestion",
	    value: function isQuestion(question) {
	      return Object.keys(question).length === 2 && main_core.Type.isInteger(question.id) && main_core.Type.isString(question.title);
	    }
	  }]);
	  return QuestionModel;
	}(ui_vue_vuex.VuexBuilderModel);

	var SearchEvent = /*#__PURE__*/function (_BaseEvent) {
	  babelHelpers.inherits(SearchEvent, _BaseEvent);

	  function SearchEvent() {
	    babelHelpers.classCallCheck(this, SearchEvent);
	    return babelHelpers.possibleConstructorReturn(this, babelHelpers.getPrototypeOf(SearchEvent).apply(this, arguments));
	  }

	  return SearchEvent;
	}(main_core_events.BaseEvent);

	var Theme = {
	  computed: {
	    darkTheme: function darkTheme() {
	      return BX.MessengerTheme.isDark();
	    }
	  },
	  methods: {
	    getClassWithTheme: function getClassWithTheme(baseClass) {
	      var classWithTheme = {};
	      classWithTheme[baseClass] = true;
	      classWithTheme[baseClass + '-dark'] = this.darkTheme;
	      return classWithTheme;
	    }
	  }
	};

	var Search = ui_vue.BitrixVue.localComponent('imbot-support24-question-component-question-list-search', {
	  directives: {
	    focus: {
	      inserted: function inserted(element, params) {
	        element.focus();
	      }
	    }
	  },
	  mixins: [Theme],
	  data: function data() {
	    return {
	      searchQuery: '',
	      scheduleSearch: main_core.Runtime.debounce(this.search, 500, this)
	    };
	  },
	  computed: {
	    inputClass: function inputClass() {
	      return this.getClassWithTheme('bx-imbot-support24-question-list-search-input');
	    }
	  },
	  methods: {
	    search: function search() {
	      this.$emit('search', new SearchEvent({
	        data: {
	          searchQuery: this.searchQuery
	        }
	      }));
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<div class=\"bx-imbot-support24-question-list-search\">\n\t\t\t<div class=\"ui-ctl ui-ctl-textbox ui-ctl-block ui-ctl-w100 ui-ctl-sm bx-imbot-support24-question-list-search-hover\">\n\t\t\t\t<input\n\t\t\t\t\tclass=\"ui-ctl-element\"\n\t\t\t\t\t:class=\"inputClass\"\n\t\t\t\t\ttype=\"text\"\n\t\t\t\t\tv-model=\"searchQuery\"\n\t\t\t\t\tv-focus\n\t\t\t\t\t@input=\"scheduleSearch()\"\n\t\t\t\t\t:placeholder=\"$Bitrix.Loc.getMessage('IMBOT_SUPPORT24_QUESTION_LIST_SEARCH')\"\n\t\t\t\t>\n\t\t\t</div>\n\t\t</div>\n\t"
	});

	var _ButtonAskProps = Object.freeze({
	  Type: {
	    SECONDARY: 'secondary',
	    PRIMARY: 'primary'
	  }
	});
	var ButtonAsk = ui_vue.BitrixVue.localComponent('imbot-support24-question-component-question-list-button-ask', {
	  mixins: [Theme],
	  props: {
	    type: {
	      type: String,
	      "default": _ButtonAskProps.Type.SECONDARY
	    }
	  },
	  data: function data() {
	    return {
	      lastQuestionTime: null
	    };
	  },
	  computed: {
	    ButtonAskProps: function ButtonAskProps() {
	      return _ButtonAskProps;
	    },
	    buttonClass: function buttonClass() {
	      var buttonClass = this.getClassWithTheme('bx-imbot-support24-question-list-button-ask-' + this.type);

	      if (this.type === _ButtonAskProps.Type.PRIMARY) {
	        var largeButtonColor = this.darkTheme ? 'ui-btn-primary-dark' : 'ui-btn-primary';
	        buttonClass[largeButtonColor] = true;
	      }

	      return buttonClass;
	    }
	  },
	  methods: {
	    askQuestion: function askQuestion() {
	      var tenSeconds = 5000;

	      if (this.lastQuestionTime && Date.now() - this.lastQuestionTime < tenSeconds) {
	        return;
	      }

	      this.lastQuestionTime = Date.now();
	      this.$emit('askQuestion');
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<div class=\"bx-imbot-support24-question-list-button-ask\">\n\t\t\t<div\n\t\t\t\tv-if=\"type === ButtonAskProps.Type.SECONDARY\"\n\t\t\t\t:class=\"buttonClass\"\n\t\t\t\t@click=\"askQuestion\"\n\t\t\t>\n\t\t\t\t{{ $Bitrix.Loc.getMessage('IMBOT_SUPPORT24_QUESTION_BUTTON_ASK_NEW_TITLE') }}\n\t\t\t</div>\n\n\t\t\t<button\n\t\t\t\tv-if=\"type === ButtonAskProps.Type.PRIMARY\"\n\t\t\t\t:class=\"buttonClass\"\n\t\t\t\t@click=\"askQuestion\"\n\t\t\t\tclass=\"ui-btn ui-btn-sm ui-btn-round ui-btn-no-caps\"\n\t\t\t>\n\t\t\t\t{{ $Bitrix.Loc.getMessage('IMBOT_SUPPORT24_QUESTION_BUTTON_ASK_TITLE') }}\n\t\t\t</button>\n\t\t</div>\n\t"
	});

	var _QuestionState = Object.freeze({
	  DEFAULT: 'default',
	  EDIT: 'edit'
	});

	var Question = ui_vue.BitrixVue.localComponent('imbot-support24-question-component-question-list-question', {
	  directives: {
	    focus: {
	      inserted: function inserted(element, params) {
	        element.focus();
	      }
	    }
	  },
	  mixins: [Theme],
	  props: {
	    id: Number,
	    title: String
	  },
	  data: function data() {
	    return {
	      state: _QuestionState.DEFAULT,
	      newTitle: this.title
	    };
	  },
	  computed: {
	    QuestionState: function QuestionState() {
	      return _QuestionState;
	    },
	    questionClass: function questionClass() {
	      return this.getClassWithTheme('bx-imbot-support24-question-list-question');
	    },
	    titleClass: function titleClass() {
	      return this.getClassWithTheme('bx-imbot-support24-question-list-question-title');
	    },
	    inputClass: function inputClass() {
	      return this.getClassWithTheme('bx-imbot-support24-question-list-question-input');
	    }
	  },
	  methods: {
	    callMethod: function callMethod(method, params) {
	      return this.$Bitrix.RestClient.get().callMethod(method, params);
	    },
	    click: function click() {
	      if (this.state === _QuestionState.EDIT) {
	        return;
	      }

	      this.$emit('click', this.id);
	    },
	    edit: function edit(event) {
	      event.stopPropagation();
	      this.state = _QuestionState.EDIT;
	    },
	    rename: function rename() {
	      var _this = this;

	      if (this.title === this.newTitle || this.newTitle.trim() === '') {
	        this.state = _QuestionState.DEFAULT;
	        return;
	      }

	      var oldTitle = this.title;
	      this.setTitleById(this.id, this.newTitle).then(function () {
	        _this.setRecentListTitleById(_this.id, _this.newTitle);

	        _this.state = _QuestionState.DEFAULT;
	      });
	      this.callMethod('im.chat.updateTitle', {
	        CHAT_ID: this.id,
	        TITLE: this.newTitle
	      })["catch"](function () {
	        _this.setRecentListTitleById(_this.id, oldTitle);

	        _this.setTitleById(_this.id, oldTitle);
	      });
	    },
	    setTitleById: function setTitleById(id, title) {
	      return this.$store.dispatch('question/setQuestionTitleById', {
	        id: id,
	        title: title
	      });
	    },
	    setRecentListTitleById: function setRecentListTitleById(id, title) {
	      if (BXIM && BXIM.messenger && BXIM.messenger.chat && BXIM.messenger.chat[id]) {
	        BXIM.messenger.chat[id].name = main_core.Text.encode(title);
	        BX.MessengerCommon.recentListRedraw();
	      }
	    },
	    inputClick: function inputClick(event) {
	      event.stopPropagation();
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<div :class=\"questionClass\" @click=\"click\">\n\t\t\t<template v-if=\"state === QuestionState.DEFAULT\">\n\t\t\t\t<div \n\t\t\t\t\tclass=\"\n\t\t\t\t\t\tbx-imbot-support24-question-list-question-icon\n\t\t\t\t\t\tbx-imbot-support24-question-list-question-icon-chat\n\t\t\t\t\t\"/>\n\n\t\t\t\t<div :class=\"titleClass\">\n\t\t\t\t\t{{ title }}\n\t\t\t\t</div>\n\n\t\t\t\t<div\n\t\t\t\t\tclass=\"\n\t\t\t\t\t\tbx-imbot-support24-question-list-question-icon\n\t\t\t\t\t\tbx-imbot-support24-question-list-question-icon-edit\n\t\t\t\t\t\"\n\t\t\t\t\t@click=\"edit\"\n\t\t\t\t/>\n\t\t\t</template>\n\n\t\t\t<template v-else-if=\"state === QuestionState.EDIT\">\n\t\t\t\t<div \n\t\t\t\t\tclass=\"ui-ctl ui-ctl-textbox ui-ctl-block ui-ctl-w100 ui-ctl-sm\"\n\t\t\t\t\t:class=\"inputClass\"\n\t\t\t\t>\n\t\t\t\t\t<input\n\t\t\t\t\t\tclass=\"ui-ctl-element\"\n\t\t\t\t\t\ttype=\"text\"\n\t\t\t\t\t\t:class=\"inputClass\"\n\t\t\t\t\t\tv-model=\"newTitle\"\n\t\t\t\t\t\tv-focus\n\t\t\t\t\t\t@keydown.enter=\"rename\"\n\t\t\t\t\t\t@blur=\"rename\"\n\t\t\t\t\t\t@click=\"inputClick\"\n\t\t\t\t\t>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t</div>\n\t"
	});

	function _classPrivateFieldInitSpec(obj, privateMap, value) { _checkPrivateRedeclaration(obj, privateMap); privateMap.set(obj, value); }

	function _checkPrivateRedeclaration(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }

	var _isAdmin = /*#__PURE__*/new WeakMap();

	var _canAskQuestion = /*#__PURE__*/new WeakMap();

	var _canImproveTariff = /*#__PURE__*/new WeakMap();

	var Permissions = /*#__PURE__*/function () {
	  function Permissions(options) {
	    babelHelpers.classCallCheck(this, Permissions);

	    _classPrivateFieldInitSpec(this, _isAdmin, {
	      writable: true,
	      value: void 0
	    });

	    _classPrivateFieldInitSpec(this, _canAskQuestion, {
	      writable: true,
	      value: void 0
	    });

	    _classPrivateFieldInitSpec(this, _canImproveTariff, {
	      writable: true,
	      value: void 0
	    });

	    babelHelpers.classPrivateFieldSet(this, _isAdmin, Boolean(options.isAdmin));
	    babelHelpers.classPrivateFieldSet(this, _canAskQuestion, Boolean(options.canAskQuestion));
	    babelHelpers.classPrivateFieldSet(this, _canImproveTariff, Boolean(options.canImproveTariff));
	  }

	  babelHelpers.createClass(Permissions, [{
	    key: "isAdmin",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _isAdmin);
	    }
	  }, {
	    key: "canAskQuestion",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _canAskQuestion);
	    }
	  }, {
	    key: "canImproveTariff",
	    get: function get() {
	      return babelHelpers.classPrivateFieldGet(this, _canImproveTariff);
	    }
	  }]);
	  return Permissions;
	}();

	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }

	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }

	var _QuestionListState = Object.freeze({
	  DEFAULT: 'default',
	  SEARCH: 'search'
	});

	var QuestionList = ui_vue.BitrixVue.localComponent('imbot-support24-question-component-question-list', {
	  components: {
	    Search: Search,
	    ButtonAsk: ButtonAsk,
	    Question: Question
	  },
	  directives: {
	    'bx-imbot-directive-question-list-observer': {
	      inserted: function inserted(element, bindings, vnode) {
	        vnode.context.loaderObserver = vnode.context.getLoaderObserver();
	        vnode.context.loaderObserver.observe(element);
	        return true;
	      },
	      unbind: function unbind(element, bindings, vnode) {
	        if (vnode.context.loaderObserver) {
	          vnode.context.loaderObserver.unobserve(element);
	        }

	        return true;
	      }
	    }
	  },
	  mixins: [Theme],
	  data: function data() {
	    return {
	      state: _QuestionListState.DEFAULT,
	      permissions: null,
	      itemsPerPage: 50,
	      historyPageNumber: 0,
	      searchResultPageNumber: 0,
	      hasHistoryToLoad: false,
	      hasSearchResultToLoad: false,
	      searchQuery: '',
	      searchRequestCount: 0
	    };
	  },
	  computed: {
	    QuestionListState: function QuestionListState() {
	      return _QuestionListState;
	    },
	    ButtonAskProps: function ButtonAskProps$$1() {
	      return _ButtonAskProps;
	    },
	    items: function items() {
	      var _this = this;

	      if (this.state === _QuestionListState.DEFAULT) {
	        return this.$store.state.question.history;
	      }

	      if (this.isSearchFromCache) {
	        return this.$store.state.question.history.filter(function (question) {
	          return question.title.toLowerCase().includes(_this.searchQuery);
	        });
	      }

	      return this.$store.state.question.searchResult;
	    },
	    isEmpty: function isEmpty() {
	      return this.items.length === 0;
	    },
	    isSearchFromCache: function isSearchFromCache() {
	      return this.state === _QuestionListState.SEARCH && this.searchQuery !== '' && this.searchQuery.length < 3;
	    },
	    isLoadingInProgress: function isLoadingInProgress() {
	      return this.searchRequestCount > 0;
	    },
	    historyNavigationParams: function historyNavigationParams() {
	      return {
	        limit: this.itemsPerPage,
	        offset: this.itemsPerPage * this.historyPageNumber
	      };
	    },
	    searchNavigationParams: function searchNavigationParams() {
	      return {
	        limit: this.itemsPerPage,
	        offset: this.itemsPerPage * this.searchResultPageNumber
	      };
	    },
	    showTariffLock: function showTariffLock() {
	      return this.permissions && (!this.permissions.isAdmin || !this.permissions.canAskQuestion);
	    },
	    showLoader: function showLoader() {
	      if (this.state === _QuestionListState.DEFAULT) {
	        return this.hasHistoryToLoad;
	      }

	      if (this.isSearchFromCache) {
	        return false;
	      }

	      return this.hasSearchResultToLoad;
	    },
	    searchFieldBorderClass: function searchFieldBorderClass() {
	      return this.getClassWithTheme('bx-imbot-support24-question-list-search-field-border');
	    },
	    listItemsClass: function listItemsClass() {
	      return this.getClassWithTheme('bx-imbot-support24-question-list-items');
	    },
	    emptyTitleClass: function emptyTitleClass() {
	      return this.getClassWithTheme('bx-imbot-support24-question-list-empty-title');
	    },
	    emptyDescriptionClass: function emptyDescriptionClass() {
	      return this.getClassWithTheme('bx-imbot-support24-question-list-empty-description');
	    },
	    placeholderTextClass: function placeholderTextClass() {
	      return this.getClassWithTheme('bx-imbot-support24-question-list-placeholder-text');
	    },
	    notFoundIconClass: function notFoundIconClass() {
	      return this.getClassWithTheme('bx-imbot-support24-question-list-not-found-icon');
	    },
	    questionListLoaderClass: function questionListLoaderClass() {
	      return this.getClassWithTheme('bx-imbot-support24-question-list-items-loader-svg-circle');
	    },
	    questionListSearchLoaderClass: function questionListSearchLoaderClass() {
	      return this.getClassWithTheme('bx-imbot-support24-question-list-items-search-loader-svg-circle');
	    }
	  },
	  created: function created() {
	    var _this2 = this;

	    this.$store.dispatch('question/trimHistory', this.itemsPerPage).then(function () {
	      var initRequests = {
	        config: {
	          method: 'imbot.support24.question.config.get'
	        },
	        questions: {
	          method: 'imbot.support24.question.list',
	          params: _this2.historyNavigationParams
	        }
	      };

	      var initCallback = function initCallback(response) {
	        _this2.permissions = new Permissions(response.config.data());

	        _this2.afterHistoryPageLoaded(response.questions);
	      };

	      _this2.getRestClient().callBatch(initRequests, initCallback);
	    });
	  },
	  methods: {
	    getRestClient: function getRestClient() {
	      return this.$Bitrix.RestClient.get();
	    },
	    searchQuestions: function searchQuestions(event) {
	      var _this3 = this;

	      this.searchQuery = event.getData().searchQuery.toLowerCase();
	      var truncatedSearchQuery = this.searchQuery.trim();

	      if (truncatedSearchQuery === '') {
	        this.state = _QuestionListState.DEFAULT;
	        return;
	      }

	      this.state = _QuestionListState.SEARCH;

	      if (truncatedSearchQuery.length < 3) {
	        return;
	      }

	      this.searchRequestCount++;
	      var searchParams = {
	        searchQuery: this.searchQuery,
	        limit: this.itemsPerPage
	      };
	      this.getRestClient().callMethod('imbot.support24.question.search', searchParams).then(function (response) {
	        var questions = response.data();

	        if (_this3.searchRequestCount === 1) {
	          _this3.$store.dispatch('question/setSearchResult', questions).then(function () {
	            _this3.searchResultPageNumber = 1;
	            _this3.hasSearchResultToLoad = questions.length >= _this3.itemsPerPage;
	            _this3.searchRequestCount--;
	          });
	        } else {
	          _this3.searchRequestCount--;
	        }
	      })["catch"](function () {
	        _this3.searchRequestCount--;
	      });
	    },
	    loadNextPage: function loadNextPage() {
	      if (this.state === _QuestionListState.DEFAULT) {
	        this.loadNextHistoryPage();
	        return;
	      }

	      this.loadNextSearchPage();
	    },
	    loadNextHistoryPage: function loadNextHistoryPage() {
	      var _this4 = this;

	      this.getRestClient().callMethod('imbot.support24.question.list', this.historyNavigationParams).then(function (response) {
	        return _this4.afterHistoryPageLoaded(response);
	      });
	    },
	    loadNextSearchPage: function loadNextSearchPage() {
	      var _this5 = this;

	      if (this.searchQuery === '') {
	        return;
	      }

	      var params = _objectSpread({
	        searchQuery: this.searchQuery
	      }, this.searchNavigationParams);

	      this.getRestClient().callMethod('imbot.support24.question.search', params).then(function (response) {
	        return _this5.afterSearchPageLoaded(response);
	      });
	    },
	    afterHistoryPageLoaded: function afterHistoryPageLoaded(response) {
	      var _this6 = this;

	      var questions = response.data();
	      this.hasHistoryToLoad = questions.length >= this.itemsPerPage;
	      var addMethod = this.historyPageNumber === 0 ? 'question/setHistory' : 'question/addHistory';
	      this.$store.dispatch(addMethod, questions).then(function () {
	        _this6.historyPageNumber++;
	      });
	    },
	    afterSearchPageLoaded: function afterSearchPageLoaded(response) {
	      var _this7 = this;

	      var questions = response.data();
	      this.hasSearchResultToLoad = questions.length >= this.itemsPerPage;
	      var addMethod = this.searchResultPageNumber === 0 ? 'question/setSearchResult' : 'question/addSearchResult';
	      this.$store.dispatch(addMethod, questions).then(function () {
	        _this7.searchResultPageNumber++;
	      });
	    },
	    onAskQuestion: function onAskQuestion() {
	      if (!this.permissions) {
	        //Access rights are not yet known
	        this.addQuestion();
	        return;
	      }

	      if (!this.permissions.isAdmin) {
	        this.sendRestrictionNotification();
	        return;
	      }

	      if (!this.permissions.canAskQuestion && this.permissions.canImproveTariff) {
	        this.openTariffSlider();
	        return;
	      }

	      this.addQuestion();
	    },
	    addQuestion: function addQuestion() {
	      var _this8 = this;

	      this.getRestClient().callMethod('imbot.support24.question.add').then(function (response) {
	        var dialogId = response.data();

	        _this8.openDialog(dialogId);
	      })["catch"](function (response) {
	        if (!response.answer || !response.answer.error) {
	          console.error(response);
	        }

	        var errorCode = response.answer.error;

	        switch (errorCode) {
	          case 'ACCESS_DENIED':
	            _this8.sendRestrictionNotification();

	            break;

	          case 'QUESTION_LIMIT_EXCEEDED':
	            _this8.openTariffSlider();

	            break;
	        }
	      });
	    },
	    sendRestrictionNotification: function sendRestrictionNotification() {
	      ui_notification.UI.Notification.Center.notify({
	        id: 'imbot_support24_question_list_restriction_not_admin',
	        content: this.$Bitrix.Loc.getMessage('IMBOT_SUPPORT24_QUESTION_LIST_RESTRICTION_NOT_ADMIN'),
	        autoHideDelay: 5000
	      });
	    },
	    openTariffSlider: function openTariffSlider() {
	      BX.UI.InfoHelper.show('limit_admin_multidialogues');
	    },
	    openDialog: function openDialog(dialogId) {
	      var popupContext = this.$Bitrix.Data.get('popupContext');

	      if (popupContext) {
	        popupContext.closePopup();
	      }

	      BXIM.openMessenger('chat' + dialogId);
	    },
	    getLoaderObserver: function getLoaderObserver() {
	      var _this9 = this;

	      var options = {
	        root: document.querySelector('.bx-imbot-support24-question-list-items'),
	        threshold: 0.01
	      };

	      var callback = function callback(entries, observer) {
	        entries.forEach(function (entry) {
	          if (entry.isIntersecting && entry.intersectionRatio > 0.01) {
	            _this9.loadNextPage();
	          }
	        });
	      };

	      return new IntersectionObserver(callback, options);
	    }
	  },
	  // language=Vue
	  template: "\n\t\t<div class=\"bx-imbot-support24-question-list\">\n\t\t\t<template v-if=\"isLoadingInProgress\">\n\t\t\t\t<div class=\"bx-imbot-support24-question-list-search-field\">\n\t\t\t\t\t<div class=\"bx-imbot-support24-question-list-search-container\">\n\t\t\t\t\t\t<Search\n\t\t\t\t\t\t\t@search=\"searchQuestions\"\n\t\t\t\t\t\t/>\n\t\t\t\t\t</div>\n\n\t\t\t\t\t<div class=\"bx-imbot-support24-question-list-ask-container\">\n\t\t\t\t\t\t<ButtonAsk\n\t\t\t\t\t\t\t:type=\"ButtonAskProps.Type.SECONDARY\"\n\t\t\t\t\t\t\t@askQuestion=\"onAskQuestion\"\n\t\t\t\t\t\t/>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t\t<div class=\"bx-imbot-support24-question-list-placeholder\">\n\t\t\t\t\t<div class=\"bx-imbot-support24-question-list-loading-icon\">\n\t\t\t\t\t\t<div class=\"main-ui-loader main-ui-show\" style=\"width: 45px; height: 45px;\" data-is-shown=\"true\">\n\t\t\t\t\t\t\t<svg class=\"main-ui-loader-svg\" viewBox=\"25 25 50 50\">\n\t\t\t\t\t\t\t\t<circle\n\t\t\t\t\t\t\t\t\tclass=\"main-ui-loader-svg-circle\"\n\t\t\t\t\t\t\t\t\t:class=\"questionListSearchLoaderClass\"\n\t\t\t\t\t\t\t\t\tcx=\"50\"\n\t\t\t\t\t\t\t\t\tcy=\"50\"\n\t\t\t\t\t\t\t\t\tr=\"20\"\n\t\t\t\t\t\t\t\t\tfill=\"none\"\n\t\t\t\t\t\t\t\t\tstroke-miterlimit=\"10\"\n\t\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t</svg>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t\t<div :class=\"placeholderTextClass\">\n\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('IMBOT_SUPPORT24_QUESTION_LIST_SEARCHING') }}\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</template>\n\n\t\t\t<template v-else-if=\"isEmpty && state === QuestionListState.SEARCH\">\n\t\t\t\t<div class=\"bx-imbot-support24-question-list-search-field\">\n\t\t\t\t\t<div class=\"bx-imbot-support24-question-list-search-container\">\n\t\t\t\t\t\t<Search\n\t\t\t\t\t\t\t@search=\"searchQuestions\"\n\t\t\t\t\t\t/>\n\t\t\t\t\t</div>\n\n\t\t\t\t\t<div class=\"bx-imbot-support24-question-list-ask-container\">\n\t\t\t\t\t\t<ButtonAsk\n\t\t\t\t\t\t\t:type=\"ButtonAskProps.Type.SECONDARY\"\n\t\t\t\t\t\t\t@askQuestion=\"onAskQuestion\"\n\t\t\t\t\t\t/>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\n\t\t\t\t<div class=\"bx-imbot-support24-question-list-placeholder\">\n\t\t\t\t\t<div :class=\"notFoundIconClass\">:(</div>\n\t\t\t\t\t<div :class=\"placeholderTextClass\">\n\t\t\t\t\t\t{{ $Bitrix.Loc.getMessage('IMBOT_SUPPORT24_QUESTION_LIST_SEARCH_NOT_FOUND') }}\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</template>\n\n\t\t\t<template v-else-if=\"!isEmpty\">\n\t\t\t\t<div \n\t\t\t\t\tclass=\"bx-imbot-support24-question-list-search-field\"\n\t\t\t\t\t:class=\"searchFieldBorderClass\"\n\t\t\t\t>\n\t\t\t\t\t<div class=\"bx-imbot-support24-question-list-search-container\">\n\t\t\t\t\t\t<Search\n\t\t\t\t\t\t\t@search=\"searchQuestions\"\n\t\t\t\t\t\t/>\n\t\t\t\t\t</div>\n\t\t\t\t\t\n\t\t\t\t\t<div class=\"bx-imbot-support24-question-list-ask-container\">\n\t\t\t\t\t\t<ButtonAsk\n\t\t\t\t\t\t\t:type=\"ButtonAskProps.Type.SECONDARY\"\n\t\t\t\t\t\t\t@askQuestion=\"onAskQuestion\"\n\t\t\t\t\t\t/>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\n\t\t\t\t<div :class=\"listItemsClass\">\n\t\t\t\t\t<Question\n\t\t\t\t\t\tv-for=\"item of items\"\n\t\t\t\t\t\t:key=\"item.id\"\n\t\t\t\t\t\t:id=\"item.id\"\n\t\t\t\t\t\t:title=\"item.title\"\n\t\t\t\t\t\t@click=\"openDialog\"\n\t\t\t\t\t/>\n\t\t\t\t\t\n\t\t\t\t\t<div \n\t\t\t\t\t\tclass=\"bx-imbot-support24-question-list-items-loader\"\n\t\t\t\t\t\tv-if=\"showLoader\" \n\t\t\t\t\t\t:key=\"'question-list-items-loader'\" \n\t\t\t\t\t\tv-bx-imbot-directive-question-list-observer\n\t\t\t\t\t>\n\t\t\t\t\t\t<div class=\"main-ui-loader main-ui-show\" style=\"width: 23px; height: 23px;\" data-is-shown=\"true\">\n\t\t\t\t\t\t\t<svg class=\"main-ui-loader-svg\" viewBox=\"25 25 50 50\">\n\t\t\t\t\t\t\t\t<circle \n\t\t\t\t\t\t\t\t\tclass=\"main-ui-loader-svg-circle\"\n\t\t\t\t\t\t\t\t\t:class=\"questionListLoaderClass\"\n\t\t\t\t\t\t\t\t\tcx=\"50\"\n\t\t\t\t\t\t\t\t\tcy=\"50\"\n\t\t\t\t\t\t\t\t\tr=\"20\"\n\t\t\t\t\t\t\t\t\tfill=\"none\"\n\t\t\t\t\t\t\t\t\tstroke-miterlimit=\"10\"\n\t\t\t\t\t\t\t\t/>\n\t\t\t\t\t\t\t</svg>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</template>\n\t\t\t\n\t\t\t<div\n\t\t\t\tclass=\"bx-imbot-support24-question-list-empty\"\n\t\t\t\tv-else-if=\"isEmpty && state === QuestionListState.DEFAULT\"\n\t\t\t>\n\t\t\t\t<div :class=\"emptyTitleClass\">\n\t\t\t\t\t{{ $Bitrix.Loc.getMessage('IMBOT_SUPPORT24_QUESTION_LIST_EMPTY_TITLE') }}\n\t\t\t\t</div>\n\t\t\t\t<div :class=\"emptyDescriptionClass\">\n\t\t\t\t\t{{ $Bitrix.Loc.getMessage('IMBOT_SUPPORT24_QUESTION_LIST_EMPTY_DESCRIPTION') }}\n\t\t\t\t</div>\n\t\t\t\t<div class=\"bx-imbot-support24-question-list-button-ask-container\">\n\t\t\t\t\t<ButtonAsk\n\t\t\t\t\t\t:type=\"ButtonAskProps.Type.PRIMARY\"\n\t\t\t\t\t\t@askQuestion=\"onAskQuestion\"\n\t\t\t\t\t/>\n\t\t\t\t\t<span \n\t\t\t\t\t\tclass=\"bx-imbot-support24-question-list-tariff-lock\"\n\t\t\t\t\t\tv-if=\"showTariffLock\"\n\t\t\t\t\t/>\n\t\t\t\t</div>\n\t\t\t</div>\n\t\t</div>\n\t"
	});

	function _classPrivateFieldInitSpec$1(obj, privateMap, value) { _checkPrivateRedeclaration$1(obj, privateMap); privateMap.set(obj, value); }

	function _checkPrivateRedeclaration$1(obj, privateCollection) { if (privateCollection.has(obj)) { throw new TypeError("Cannot initialize the same private elements twice on an object"); } }

	var _viewModel = /*#__PURE__*/new WeakMap();

	var Question$1 = /*#__PURE__*/function () {
	  function Question(options) {
	    var _this = this;

	    babelHelpers.classCallCheck(this, Question);

	    _classPrivateFieldInitSpec$1(this, _viewModel, {
	      writable: true,
	      value: void 0
	    });

	    this.rootNode = document.getElementById(options.nodeId);
	    this.popupContext = options.popupContext ? options.popupContext : null;
	    this.createStorage().then(function (builder) {
	      var store = builder.store;

	      _this.createApplication(store);
	    });
	  }

	  babelHelpers.createClass(Question, [{
	    key: "createStorage",
	    value: function createStorage() {
	      var model = QuestionModel.create().useDatabase(true);
	      var databaseConfig = {
	        name: 'imbot-support24-question',
	        type: ui_vue_vuex.VuexBuilder.DatabaseType.indexedDb,
	        siteId: main_core.Loc.getMessage('SITE_ID'),
	        userId: main_core.Loc.getMessage('USER_ID')
	      };
	      return new ui_vue_vuex.VuexBuilder().addModel(model).setDatabaseConfig(databaseConfig).build();
	    }
	  }, {
	    key: "createApplication",
	    value: function createApplication(store) {
	      main_core.Dom.clean(this.rootNode);
	      main_core.Dom.append(main_core.Dom.create('div'), this.rootNode);
	      var applicationContext = this;
	      var popupContext = this.popupContext;
	      babelHelpers.classPrivateFieldSet(this, _viewModel, ui_vue.BitrixVue.createApp({
	        store: store,
	        components: {
	          QuestionList: QuestionList
	        },
	        beforeCreate: function beforeCreate() {
	          this.$bitrix.Application.set(applicationContext);
	          this.$bitrix.Data.set('popupContext', popupContext);
	        },
	        template: "\n\t\t\t\t<QuestionList/>\n\t\t\t"
	      }).mount(this.rootNode.firstChild));
	    }
	  }]);
	  return Question;
	}();

	exports.Question = Question$1;

}((this.BX.Imbot.Support24 = this.BX.Imbot.Support24 || {}),BX,BX,BX,BX,BX.Event,BX,BX.UI,BX,BX,BX));
//# sourceMappingURL=question.bundle.js.map
