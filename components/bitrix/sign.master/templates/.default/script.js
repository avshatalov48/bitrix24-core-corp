/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
(function (exports,main_popup,ui_notification,sign_tour,main_core,sign_document) {
	'use strict';

	var _templateObject, _templateObject2, _templateObject3, _templateObject4, _templateObject5, _templateObject6, _templateObject7, _templateObject8, _templateObject9, _templateObject10, _templateObject11, _templateObject12, _templateObject13, _templateObject14, _templateObject15, _templateObject16;
	function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }
	function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { babelHelpers.defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }
	var Preview = /*#__PURE__*/function () {
	  function Preview(options) {
	    var _this = this;
	    babelHelpers.classCallCheck(this, Preview);
	    babelHelpers.defineProperty(this, "readonly", true);
	    babelHelpers.defineProperty(this, "imageIndex", 0);
	    babelHelpers.defineProperty(this, "imageTotal", 0);
	    babelHelpers.defineProperty(this, "blockTagCollection", []);
	    babelHelpers.defineProperty(this, "currentZoomValue", 100);
	    babelHelpers.defineProperty(this, "firstRender", false);
	    this.containerTag = document.querySelector('[data-role="sign-master__preview"]');
	    if (!this.containerTag) {
	      return;
	    }
	    this.imageCollection = options.items;
	    this.blockCollection = options.blocks;
	    this.imageTotal = this.imageCollection.length;
	    if (main_core.Type.isBoolean(options.readonly)) {
	      this.readonly = options.readonly;
	    }
	    if (this.imageCollection.length > 0) {
	      this.buildPreview();
	    } else if (options.documentHash) {
	      var interval = setInterval(function () {
	        BX.Sign.Backend.controller({
	          command: 'document.getLayout',
	          postData: {
	            documentHash: options.documentHash
	          }
	        }).then(function (result) {
	          var layout = result === null || result === void 0 ? void 0 : result.layout;
	          if (main_core.Type.isArray(layout) && layout.length > 0) {
	            _this.imageCollection = layout;
	            _this.imageTotal = _this.imageCollection.length;
	            _this.buildPreview();
	            clearInterval(interval);
	          }
	        });
	      }, 2000);
	    }
	  }
	  babelHelpers.createClass(Preview, [{
	    key: "getBottomContainer",
	    value: function getBottomContainer() {
	      if (!this.bottomContainer) {
	        this.bottomContainer = main_core.Tag.render(_templateObject || (_templateObject = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"sign-master__preview-container_bottom\"></div>\n\t\t\t"])));
	      }
	      return this.bottomContainer;
	    }
	    /**
	     * Builds preview area.
	     */
	  }, {
	    key: "buildPreview",
	    value: function buildPreview() {
	      main_core.Dom.clean(this.containerTag);
	      main_core.Dom.append(this.buildImage(this.imageCollection[0]), this.containerTag);
	      main_core.Dom.append(this.buildNavigation(), this.getBottomContainer());
	      main_core.Dom.append(this.buildZoom(), this.getBottomContainer());
	      main_core.Dom.append(this.getBottomContainer(), this.containerTag);
	      /*Dom.append(
	      	this.buildRemoveButton(),
	      	this.containerTag
	      );*/
	    }
	    /**
	     * Draws block element.
	     * @return {HTMLElement}
	     */
	  }, {
	    key: "drawBlock",
	    value: function drawBlock(blockData) {
	      if (blockData.text) {
	        var content = blockData.text;
	        return main_core.Tag.render(_templateObject2 || (_templateObject2 = babelHelpers.taggedTemplateLiteral(["<div class=\"sign-master__block\">", "</div>"])), main_core.Text.encode(content));
	      } else if (blockData.base64) {
	        var src = 'data:image;base64,' + blockData.base64;
	        var style = "background: url(".concat(src, ") no-repeat top; background-size: cover;");
	        return main_core.Tag.render(_templateObject3 || (_templateObject3 = babelHelpers.taggedTemplateLiteral(["<div class=\"sign-master__block\" style=\"", "\"></div>"])), style);
	      }
	      return main_core.Tag.render(_templateObject4 || (_templateObject4 = babelHelpers.taggedTemplateLiteral(["<div class=\"sign-master__block\"></div>"])));
	    }
	    /**
	     * Draws blocks on preview by page number.
	     * @param {number} pageNumber
	     */
	  }, {
	    key: "drawBlocks",
	    value: function drawBlocks(pageNumber) {
	      var _this2 = this;
	      var blocks = [];
	      this.blockCollection.map(function (block) {
	        if (pageNumber === parseInt(block.position.page)) {
	          var tag = _this2.drawBlock(block.data);
	          var style = main_core.Type.isArray(block.style) || main_core.Type.isPlainObject(block.style) ? _objectSpread({}, block.style) : {};
	          style.top = block.position.top + '%';
	          style.left = block.position.left + '%';
	          style.width = block.position.width + 14 + '%';
	          style.height = block.position.height + 14 + '%';
	          main_core.Dom.style(tag, style);
	          blocks.push(tag);
	        }
	      });
	      setTimeout(function () {
	        babelHelpers.toConsumableArray(document.querySelectorAll('.sign-master__block')).map(function (tag) {
	          return main_core.Dom.remove(tag);
	        });
	        blocks.map(function (tag) {
	          _this2.imageTagContainer.appendChild(tag);
	        });
	      }, 0);
	    }
	    /**
	     * Builds preview image.
	     */
	  }, {
	    key: "buildImageTag",
	    value: function buildImageTag() {
	      var _this3 = this;
	      var preview = this.imageCollection[this.imageIndex];
	      if (!preview) {
	        return;
	      }
	      this.imageTag = main_core.Tag.render(_templateObject5 || (_templateObject5 = babelHelpers.taggedTemplateLiteral(["<img src=\"", "\" alt=\"", "\" class=\"sign-master__preview-image\">"])), preview.path, preview.name);
	      this.imageTagContainer = main_core.Tag.render(_templateObject6 || (_templateObject6 = babelHelpers.taggedTemplateLiteral(["<div class=\"sign-master__preview-container_image\"></div>"])));
	      this.imageTag.onload = function () {
	        Master.unLockContent();
	        main_core.Dom.clean(_this3.imageTagContainer);
	        main_core.Dom.append(_this3.imageTag, _this3.imageTagContainer);
	        _this3.drawBlocks(_this3.imageIndex + 1);
	      };
	    }
	    /**
	     * Build preview image container.
	     * @return {HTMLElement}
	     */
	  }, {
	    key: "buildImage",
	    value: function buildImage() {
	      if (!this.imageTagWrapper) {
	        this.buildImageTag();
	        this.imageTagWrapper = main_core.Tag.render(_templateObject7 || (_templateObject7 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"sign-master__preview-container\">\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), this.imageTagContainer);
	      }
	      return this.imageTagWrapper;
	    }
	    /**
	     * Fires on prev navigation click.
	     */
	  }, {
	    key: "onPrevClick",
	    value: function onPrevClick() {
	      if (this.imageIndex > 0) {
	        this.imageIndex--;
	        this.btnNextTag.classList.remove('--disabled');
	      }
	      if (this.imageIndex === 0) {
	        this.btnPrevTag.classList.add('--disabled');
	      }
	      this.navigationTag.classList.add('--lock');
	      this.buildImageTag();
	      this.buildNavigation();
	    }
	    /**
	     * Fires on next navigation click.
	     */
	  }, {
	    key: "onNextClick",
	    value: function onNextClick() {
	      if (this.imageIndex < this.imageTotal - 1) {
	        this.imageIndex++;
	        this.btnPrevTag.classList.remove('--disabled');
	      }
	      if (this.imageIndex === this.imageTotal - 1) {
	        this.btnNextTag.classList.add('--disabled');
	      }
	      this.navigationTag.classList.add('--lock');
	      this.buildImageTag();
	      this.buildNavigation();
	    }
	    /**
	     * Builds page navigation between images.
	     * @return {HTMLElement}
	     */
	  }, {
	    key: "buildNavigation",
	    value: function buildNavigation() {
	      if (!this.navigationTag) {
	        this.btnPrevTag = main_core.Tag.render(_templateObject8 || (_templateObject8 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<span class=\"sign-master__preview-nav--btn --prev --disabled\" onclick=\"", "\"></span>\n\t\t\t"])), this.onPrevClick.bind(this));
	        this.btnNextTag = main_core.Tag.render(_templateObject9 || (_templateObject9 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<span class=\"sign-master__preview-nav--btn --next\" onclick=\"", "\"></span>\n\t\t\t"])), this.onNextClick.bind(this));
	        this.navigationTag = main_core.Tag.render(_templateObject10 || (_templateObject10 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<div class=\"sign-master__preview-nav\">\n\t\t\t\t\t", "\n\t\t\t\t\t<div class=\"sign-master__preview-nav--info\">\n\t\t\t\t\t\t<div class=\"sign-master__preview-nav--info-dark\">\n\t\t\t\t\t\t\t", " \n\t\t\t\t\t\t\t<span class=\"sign-master__preview-nav--current\">0</span>\n\t\t\t\t\t\t</div>\n\t\t\t\t\t\t<span>/</span>\n\t\t\t\t\t\t<span class=\"sign-master__preview-nav--total\">0</span>\n\t\t\t\t\t</div>\n\t\t\t\t\t", "\n\t\t\t\t</div>\n\t\t\t"])), this.btnPrevTag, main_core.Loc.getMessage('SIGN_CMP_MASTER_TPL_PREVIEW_PAGE'), this.btnNextTag);
	      }
	      this.navigationTag.querySelector('.sign-master__preview-nav--current').innerHTML = this.imageIndex + 1;
	      this.navigationTag.querySelector('.sign-master__preview-nav--total').innerHTML = this.imageTotal;
	      main_core.Dom.removeClass(this.navigationTag.querySelector('.sign-master__preview-nav--prev'), 'sign-master__preview-nav--active');
	      main_core.Dom.removeClass(this.navigationTag.querySelector('.sign-master__preview-nav--next'), 'sign-master__preview-nav--active');
	      if (this.imageIndex > 0) {
	        main_core.Dom.addClass(this.navigationTag.querySelector('.sign-master__preview-nav--prev'), 'sign-master__preview-nav--active');
	      }
	      if (this.imageIndex < this.imageTotal - 1) {
	        main_core.Dom.addClass(this.navigationTag.querySelector('.sign-master__preview-nav--next'), 'sign-master__preview-nav--active');
	      }
	      return this.navigationTag;
	    }
	    /**
	     * Fires on zoom page click.
	     */
	  }, {
	    key: "onZoomClick",
	    value: function onZoomClick() {
	      var preview = this.imageCollection[this.imageIndex];
	      if (!preview) {
	        return;
	      }
	      console.log('image path to show: ', preview.path);
	    }
	  }, {
	    key: "adjustZoomStatus",
	    value: function adjustZoomStatus() {
	      this.zoomValue = this.defaultZoomValue / 100 * this.currentZoomValue;
	      this.imageTagContainer.style.setProperty('zoom', this.zoomValue + '%');
	      this.zoomLayout.value.innerText = this.currentZoomValue;
	      switch (true) {
	        case this.currentZoomValue > 100:
	          this.imageTagWrapper.classList.add('--scroll');
	          break;
	        default:
	          this.imageTagWrapper.classList.remove('--scroll');
	      }
	      switch (true) {
	        case this.currentZoomValue === 100:
	          this.imageTagContainer.style.setProperty('left', 0);
	          this.imageTagContainer.style.setProperty('top', 0);
	          this.zoomLayout.minus.classList.add('--hold');
	          break;
	        case this.currentZoomValue === 200:
	          this.zoomLayout.plus.classList.add('--hold');
	          break;
	        case this.currentZoomValue === 25:
	          this.zoomLayout.minus.classList.add('--hold');
	          break;
	        default:
	          this.zoomLayout.plus.classList.remove('--hold');
	          this.zoomLayout.minus.classList.remove('--hold');
	      }
	    }
	  }, {
	    key: "zoomPlus",
	    value: function zoomPlus() {
	      if (this.imageTag) {
	        this.currentZoomValue += 25;
	        this.adjustZoomStatus();
	      }
	    }
	  }, {
	    key: "zoomMinus",
	    value: function zoomMinus() {
	      if (this.imageTag) {
	        this.currentZoomValue -= 25;
	        this.adjustZoomStatus();
	      }
	    }
	    /**
	     * Builds zoom button.
	     * @return {HTMLElement}
	     */
	  }, {
	    key: "buildZoom",
	    value: function buildZoom() {
	      this.zoomLayout = {
	        value: main_core.Tag.render(_templateObject11 || (_templateObject11 = babelHelpers.taggedTemplateLiteral(["<span class=\"sign-master__preview-zoom-value\">", "</span>"])), this.currentZoomValue),
	        minus: main_core.Tag.render(_templateObject12 || (_templateObject12 = babelHelpers.taggedTemplateLiteral(["<span class=\"sign-master__preview-zoom_control --minus\" onclick=\"", "\"></span>"])), this.zoomMinus.bind(this)),
	        plus: main_core.Tag.render(_templateObject13 || (_templateObject13 = babelHelpers.taggedTemplateLiteral(["<span class=\"sign-master__preview-zoom_control --plus\" onclick=\"", "\"></span>"])), this.zoomPlus.bind(this))
	      };
	      return this.zoomLayout.container = main_core.Tag.render(_templateObject14 || (_templateObject14 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t<span class=\"sign-master__preview-zoom\">\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t\t", "\n\t\t\t</span>\n\t\t"])), this.zoomLayout.minus, this.zoomLayout.value, this.zoomLayout.plus);
	    }
	    /**
	     * Fires on remove page click.
	     */
	  }, {
	    key: "onRemoveClick",
	    value: function onRemoveClick() {
	      var preview = this.imageCollection[this.imageIndex];
	      if (!preview) {
	        return;
	      }
	    }
	    /**
	     * Builds remove button.
	     * @return {HTMLElement}
	     */
	  }, {
	    key: "buildRemoveButton",
	    value: function buildRemoveButton() {
	      if (this.readonly) {
	        return main_core.Tag.render(_templateObject15 || (_templateObject15 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<span class=\"sign-master__preview-remove --readonly\" title=\"", "\" >\n\t\t\t\t\t", "\n\t\t\t\t</span>\n\t\t\t"])), main_core.Loc.getMessage('SIGN_CMP_MASTER_TPL_PREVIEW_REMOVE_ALERT'), main_core.Loc.getMessage('SIGN_CMP_MASTER_TPL_PREVIEW_REMOVE'));
	      } else {
	        return main_core.Tag.render(_templateObject16 || (_templateObject16 = babelHelpers.taggedTemplateLiteral(["\n\t\t\t\t<span class=\"sign-master__preview-remove\" onclick=\"", "\">\n\t\t\t\t\t", "\n\t\t\t\t</span>\n\t\t\t"])), this.onRemoveClick.bind(this), main_core.Loc.getMessage('SIGN_CMP_MASTER_TPL_PREVIEW_REMOVE'));
	      }
	    }
	  }]);
	  return Preview;
	}();

	function _classStaticPrivateFieldSpecGet(receiver, classConstructor, descriptor) { _classCheckPrivateStaticAccess(receiver, classConstructor); _classCheckPrivateStaticFieldDescriptor(descriptor, "get"); return _classApplyDescriptorGet(receiver, descriptor); }
	function _classCheckPrivateStaticFieldDescriptor(descriptor, action) { if (descriptor === undefined) { throw new TypeError("attempted to " + action + " private static field before its declaration"); } }
	function _classApplyDescriptorGet(receiver, descriptor) { if (descriptor.get) { return descriptor.get.call(receiver); } return descriptor.value; }
	function _classStaticPrivateMethodGet(receiver, classConstructor, method) { _classCheckPrivateStaticAccess(receiver, classConstructor); return method; }
	function _classCheckPrivateStaticAccess(receiver, classConstructor) { if (receiver !== classConstructor) { throw new TypeError("Private static access of wrong provenance"); } }
	var EntityEditor = main_core.Reflection.namespace('BX.Crm.EntityEditor');
	var Master = /*#__PURE__*/function () {
	  function Master() {
	    babelHelpers.classCallCheck(this, Master);
	  }
	  babelHelpers.createClass(Master, null, [{
	    key: "nextStepHandler",
	    value: function nextStepHandler(attrInputFileSingle, attrInputFileMulti, attrItemsFileElements) {
	      if (!attrInputFileSingle || !attrInputFileMulti || !attrItemsFileElements) {
	        return;
	      }
	      var inputFileSingleNode = document.querySelector(attrInputFileSingle);
	      var inputFileMultiNode = document.querySelector(attrInputFileMulti);
	      var itemFileElements = document.querySelectorAll(attrItemsFileElements);
	      var checkedInput = null;
	      var activeElementItem = null;

	      // load file controller
	      var onChangeFileInput = function onChangeFileInput(inputNode) {
	        if (inputNode) {
	          inputNode.onchange = function () {
	            if (inputNode.value !== '') {
	              if (activeElementItem) {
	                activeElementItem.classList.add('--active');
	              }
	              Master.unLockNavigation();
	            }
	            if (inputNode.value === '') {
	              if (activeElementItem) {
	                activeElementItem.classList.remove('--active');
	              }
	              Master.lockNavigation();
	            }
	          };
	        }
	      };
	      onChangeFileInput(inputFileSingleNode);
	      onChangeFileInput(inputFileMultiNode);
	      var _loop = function _loop(i) {
	        itemFileElements[i].addEventListener('click', function () {
	          inputFileSingleNode.value = null;
	          inputFileMultiNode.value = null;
	          Master.lockNavigation();
	          if (checkedInput) {
	            checkedInput.checked = false;
	          }
	          if (activeElementItem) {
	            activeElementItem.classList.remove('--active');
	          }
	          activeElementItem = itemFileElements[i];
	          checkedInput = itemFileElements[i].querySelector('[type="radio"]');
	          if (checkedInput) {
	            checkedInput.checked = true;
	            activeElementItem.classList.add('--active');
	            Master.unLockNavigation();
	          }
	        });
	      };
	      for (var i = 0; i < itemFileElements.length; i++) {
	        _loop(i);
	      }
	    }
	    /**
	     * Handler for file selectors.
	     * @param {string} attrSelector Attribute name for file elector.
	     */
	  }, {
	    key: "loadFileHandler",
	    value: function loadFileHandler(attrSelector) {
	      var actionButton = document.querySelectorAll(attrSelector);
	      var inputFile = document.querySelector('[data-action="inputFile"]');
	      var inputFileMulti = document.querySelector('[data-action="inputFileMulti"]');
	      var form = inputFile ? inputFile.closest('form') : null;
	      if (!actionButton || !inputFile || !form) {
	        return;
	      }
	      babelHelpers.toConsumableArray(actionButton).map(function (node) {
	        node.addEventListener('click', function (e) {
	          if (node.getAttribute('data-multiple') === 'Y' && inputFileMulti) {
	            inputFile = inputFileMulti;
	          }
	          inputFile.addEventListener('change', function (e) {
	            if (e.target.files.length > 10) {
	              var errorBlock = document.getElementById('sign-master__too_many_pics');
	              if (errorBlock) {
	                errorBlock.style.display = 'block';
	              }
	              window.scrollTo({
	                top: 0,
	                left: 0,
	                behavior: 'smooth'
	              });
	              return;
	            }
	            var nextStepButton = document.querySelector('[data-master-next-step-button]') ? document.querySelector('[data-master-next-step-button]') : null;
	            Master.lockNavigation(nextStepButton);
	            Master.lockContent();
	            form.submit();
	          });
	          inputFile.click();
	          e.preventDefault();
	        });
	      });
	    }
	    /**
	     * Handler for blank select.
	     * @param {NodeList} radios List of radio elements to blank select.
	     */
	  }, {
	    key: "selectBlankHandler",
	    value: function selectBlankHandler(radios) {
	      babelHelpers.toConsumableArray(radios).map(function (radio) {
	        radio.addEventListener('click', function () {
	          radio.closest('form').submit();
	        });
	      });
	    }
	    /**
	     * Creates entity editor for company selector.
	     * @param userData
	     */
	  }, {
	    key: "loadCrmEntityEditor",
	    value: function loadCrmEntityEditor(userData) {
	      var _this = this;
	      _classStaticPrivateMethodGet(Master, Master, _getNextStepBtn).call(Master).addEventListener('click', Master.onNextBtnClickAtPartnerStep);
	      Master.adjustLockNavigation(_classStaticPrivateMethodGet(Master, Master, _getNextStepBtn).call(Master), _classStaticPrivateMethodGet(Master, Master, _getPreviousStepBtn).call(Master));
	      BX.addCustomEvent('BX.Sign.Preview:firstImageIsLoaded', Master.onPreviewFirstImageIsLoadedInPartnerStep);
	      Master.reloadCrmElementsList();
	      var actionButton = document.querySelector('[data-action="changePartner"]');
	      var initiatorName = document.querySelector('[name="initiatorName"]');
	      Master.lockNavigation();
	      var loader = new BX.Loader({
	        target: userData.container
	      });
	      loader.show();
	      BX.ajax.runAction('crm.api.item.getEditor', {
	        data: {
	          id: userData.id,
	          entityTypeId: userData.entityTypeId,
	          guid: userData.guid,
	          stageId: userData.stageId,
	          categoryId: userData.categoryId,
	          params: {
	            'ENABLE_PAGE_TITLE_CONTROLS': false,
	            'ENABLE_MODE_TOGGLE': true,
	            'IS_EMBEDDED': 'N',
	            'forceDefaultConfig': 'Y',
	            'enableSingleSectionCombining': 'N'
	          }
	        }
	      }).then(function (response) {
	        var _response$data;
	        loader.destroy();
	        _classStaticPrivateFieldSpecGet(Master, Master, _loadedElementsInPartnerStep).crmItemEditor = true;
	        if (_classStaticPrivateFieldSpecGet(Master, Master, _loadedElementsInPartnerStep).preview) {
	          Master.unLockNavigation();
	          var btn = _classStaticPrivateMethodGet(Master, Master, _getNextStepBtn).call(Master);
	          if (btn) {
	            btn.title = '';
	          }
	        }
	        if (!(response !== null && response !== void 0 && (_response$data = response.data) !== null && _response$data !== void 0 && _response$data.html)) {
	          return;
	        }
	        main_core.Runtime.html(userData.container, response.data.html).then(function () {
	          var editor = Master.getEditor(userData.guid);
	          if (!editor) {
	            return;
	          }
	          var myCompanyField = editor.getControlById('MYCOMPANY_ID');
	          if (myCompanyField) {
	            var myCompanySection = editor.getControlById('myCompany');
	            if (myCompanyField.hasCompanies()) {
	              if (myCompanySection) {
	                editor.switchControlMode(myCompanySection, BX.UI.EntityEditorMode.view);
	              }
	            } else if (myCompanySection) {
	              myCompanySection.enableToggling(false);
	            }
	            myCompanyField.isRequired = function () {
	              return true;
	            };
	            myCompanyField.switchToSingleEditMode_prev = myCompanyField.switchToSingleEditMode;
	            myCompanyField.switchToSingleEditMode = function (params) {
	              if (myCompanySection && myCompanySection.getMode() === BX.UI.EntityEditorMode.view) {
	                editor.switchControlMode(myCompanySection, BX.UI.EntityEditorMode.edit);
	              }
	              myCompanyField.switchToSingleEditMode_prev(params);
	            };
	          }
	          var clientSection = editor.getControlById('client');
	          var contactsField = editor.getControlById('CLIENT');
	          if (contactsField) {
	            if (contactsField.hasContacts()) {
	              if (clientSection) {
	                editor.switchControlMode(clientSection, BX.UI.EntityEditorMode.view);
	              }
	            } else {
	              if (clientSection) {
	                clientSection.enableToggling(false);
	              }
	              main_core.Dom.remove(contactsField._addContactButton);
	            }
	            contactsField.isRequired = function () {
	              return true;
	            };
	            if (contactsField._contactSearchBoxes[0]) {
	              contactsField._contactSearchBoxes[0]._isRequired = true;
	            }
	            contactsField.switchToSingleEditMode_prev = contactsField.switchToSingleEditMode;
	            contactsField.switchToSingleEditMode = function (params) {
	              if (clientSection && clientSection.getMode() === BX.UI.EntityEditorMode.view) {
	                editor.switchControlMode(clientSection, BX.UI.EntityEditorMode.edit);
	              }
	              contactsField.switchToSingleEditMode_prev(params);
	            };
	            contactsField.layout_prev = contactsField.layout;
	            contactsField.layout = function (options) {
	              contactsField.layout_prev(options);
	              setTimeout(function () {
	                main_core.Dom.remove(contactsField._addContactButton);
	              }, 10);
	            };
	          }
	        });
	      })["catch"](function (error) {
	        console.log('error', error);
	      });
	      var form = actionButton ? actionButton.closest('form') : null;
	      if (!actionButton || !form) {
	        return;
	      }
	      actionButton.addEventListener('click', function (e) {
	        e.preventDefault();
	        Master.hideErrors();
	        var showError = function showError(message) {
	          if (loader) {
	            loader.destroy();
	          }
	          Master.unLockNavigation();
	          Master.unLockContent();
	          Master.showError(message);
	        };
	        if (initiatorName && initiatorName.value.trim() === '') {
	          showError(main_core.Loc.getMessage('SIGN_CMP_MASTER_TPL_ERROR_WRONG_INITIATOR'));
	        } else if (Master.checkFilledContacts(userData)) {
	          BX.Crm.EntityEditor.getDefault().save();
	        } else {
	          showError(main_core.Loc.getMessage('SIGN_CMP_MASTER_TPL_ERROR_WRONG_CONTACTS_NUMBER'));
	        }
	      });
	      BX.addCustomEvent(window, 'BX.Crm.EntityEditor:onFailedValidation', function (data) {
	        Master.unLockNavigation();
	        Master.unLockContent();
	      });
	      BX.addCustomEvent(window, 'onCrmEntityUpdateError', function (data) {
	        Master.unLockNavigation();
	        Master.unLockContent();
	      });
	      BX.addCustomEvent(window, 'BX.Crm.EntityEditor:onEntitySaveFailure', function (data) {
	        Master.unLockNavigation();
	        Master.unLockContent();
	        if (data.errors) {
	          ui_notification.UI.Notification.Center.notify({
	            content: data.errors.join(', ')
	          });
	        }
	      });
	      BX.addCustomEvent(window, 'onCrmEntityUpdate', function (data) {
	        Master.reloadCrmElementsList();
	        var entityData = data.entityData;
	        if (!(entityData !== null && entityData !== void 0 && entityData.CONTACT_ID)) {
	          Master.unLockNavigation();
	          Master.unLockContent();
	        }
	        if (entityData !== null && entityData !== void 0 && entityData.CONTACT_ID) {
	          BX.Sign.Backend.controller({
	            command: 'internal.document.assignMembers',
	            postData: {
	              documentId: userData.documentId
	            }
	          }).then(function () {
	            Master.openEditor(userData.documentEditorUrl, form);
	          })["catch"](function (response) {
	            Master.unLockNavigation();
	            Master.unLockContent();
	            _this.showError(response.errors[0].message, response.errors[0].customData);
	          });
	        }
	      });
	    }
	  }, {
	    key: "showInfoHelperSlider",
	    value: function showInfoHelperSlider(code) {
	      Master.lockContent();
	      Master.lockNavigation();
	      var currentSlider = BX.SidePanel.Instance.getTopSlider();
	      var infoHelper = top.BX.UI.InfoHelper;
	      infoHelper.show(code);
	      top.BX.addCustomEvent(infoHelper.getSlider(), 'SidePanel.Slider:onCloseComplete', function () {
	        return currentSlider === null || currentSlider === void 0 ? void 0 : currentSlider.close();
	      });
	    }
	    /**
	     * Saves new document title.
	     * @param {number} documentId
	     */
	  }, {
	    key: "saveTitle",
	    value: function saveTitle(documentId) {
	      var actionButton = document.querySelector('[data-action="saveTitle"]');
	      var cancelButton = document.querySelector('[data-action="cancel"]');
	      var newTitleContainer = document.querySelector('[data-param="saveTitleValue"]');
	      var newTitleWrapper = document.querySelector('[data-wrapper="title"]');
	      var inputWrapper = document.querySelector('[data-wrapper="inputWrapper"]');
	      var editButton = document.querySelector('[data-wrapper="edit"]');
	      var editorWrapper = document.querySelector('[data-wrapper="titleEditor"]');
	      if (editButton && editorWrapper) {
	        editButton.addEventListener('click', function () {
	          main_core.Dom.addClass(editorWrapper, '--edit');
	        });
	      }
	      if (actionButton && newTitleContainer) {
	        newTitleContainer.addEventListener('keydown', function (ev) {
	          if (ev.code.toLowerCase() === 'escape') {
	            main_core.Dom.removeClass(editorWrapper, '--edit');
	            newTitleContainer.value = newTitleWrapper.innerText;
	            ev.stopPropagation();
	          }
	          if (ev.code.toLowerCase() === 'enter') {
	            saveTitle();
	            ev.stopPropagation();
	            ev.preventDefault();
	          }
	        });
	        cancelButton.addEventListener('click', function () {
	          main_core.Dom.removeClass(editorWrapper, '--edit');
	          newTitleContainer.value = newTitleWrapper.innerText;
	        });
	        actionButton.addEventListener('click', function () {
	          return saveTitle();
	        });
	        var saveTitle = function saveTitle() {
	          main_core.Dom.addClass(actionButton, 'ui-btn-wait');
	          main_core.Dom.addClass(inputWrapper, 'ui-ctl-disabled');
	          var newTitle = newTitleContainer.value;
	          BX.Sign.Backend.controller({
	            command: 'document.setTitle',
	            postData: {
	              documentId: documentId,
	              title: newTitle
	            }
	          }).then(function (result) {
	            if (result && newTitleWrapper) {
	              newTitleWrapper.innerHTML = BX.Text.encode(newTitle);
	            }
	            main_core.Dom.removeClass(actionButton, 'ui-btn-wait');
	            main_core.Dom.removeClass(inputWrapper, 'ui-ctl-disabled');
	            main_core.Dom.removeClass(editorWrapper, '--edit');
	          })["catch"](function () {});
	        };
	      }
	    }
	  }, {
	    key: "adjustLockNavigation",
	    value: function adjustLockNavigation(buttonNext, buttonPrev) {
	      buttonNext = buttonNext || null;
	      buttonPrev = buttonPrev || null;
	      if (buttonNext) {
	        buttonNext.addEventListener('click', function () {
	          Master.lockContent();
	          Master.lockNavigation(buttonNext, buttonPrev);
	        });
	      }
	      if (buttonPrev) {
	        buttonPrev.addEventListener('click', function () {
	          Master.lockContent();
	          Master.lockNavigation(buttonPrev, buttonNext);
	        });
	      }
	    }
	  }, {
	    key: "lockNavigation",
	    value: function lockNavigation(buttonClick, button) {
	      if (buttonClick) {
	        buttonClick.classList.add('ui-btn-wait');
	        buttonClick.classList.add('ui-btn-disabled');
	      }
	      if (button) {
	        button.classList.add('ui-btn-disabled');
	      }
	      var disabled = function disabled(node) {
	        node.classList.add('ui-btn-disabled');
	      };
	      if (!buttonClick) {
	        var buttonNext = document.querySelector('[data-master-next-step-button]');
	        buttonNext ? disabled(buttonNext) : null;
	      }
	      if (!button) {
	        var buttonPrev = document.querySelector('[data-master-prev-step-button]');
	        buttonPrev ? disabled(buttonPrev) : null;
	      }
	    }
	  }, {
	    key: "unLockNavigation",
	    value: function unLockNavigation() {
	      var buttonNext = document.querySelector('[data-master-next-step-button]');
	      var buttonPrev = document.querySelector('[data-master-prev-step-button]');
	      if (buttonNext) {
	        buttonNext.classList.remove('ui-btn-wait');
	        buttonNext.classList.remove('ui-btn-disabled');
	      }
	      if (buttonPrev) {
	        buttonPrev.classList.remove('ui-btn-wait');
	        buttonPrev.classList.remove('ui-btn-disabled');
	      }
	    }
	  }, {
	    key: "lockContent",
	    value: function lockContent() {
	      var contentNode = document.querySelector('[data-role="sign-master__content"]');
	      if (contentNode) {
	        contentNode.classList.add('--lock');
	      }
	    }
	  }, {
	    key: "unLockContent",
	    value: function unLockContent() {
	      var contentNode = document.querySelector('[data-role="sign-master__content"]');
	      if (contentNode) {
	        contentNode.classList.remove('--lock');
	      }
	    }
	    /**
	     * Checks contacts fields.
	     * @param {loadCrmEntityEditorData} userData
	     * @return {boolean}
	     */
	  }, {
	    key: "checkFilledContacts",
	    value: function checkFilledContacts(userData) {
	      var isSaveAllowed = true;
	      if (Number(userData.contactsCount) <= 0) {
	        return isSaveAllowed;
	      }
	      var editor = Master.getEditor(userData.guid);
	      if (!editor) {
	        return isSaveAllowed;
	      }
	      var contactsField = editor.getControlById('CLIENT');
	      if (!contactsField) {
	        return isSaveAllowed;
	      }
	      var myCompanyField = editor.getControlById('MYCOMPANY_ID');
	      if (!myCompanyField) {
	        return isSaveAllowed;
	      }
	      var filledContactsCount = contactsField._contactInfos.length() + (myCompanyField.hasCompanies() ? 1 : 0);
	      if (filledContactsCount < userData.contactsCount) {
	        isSaveAllowed = false;
	      }
	      return isSaveAllowed;
	    }
	    /**
	     * Returns editor instance by GUID.
	     * @param {string }guid
	     * @return {EntityEditor|null}
	     */
	  }, {
	    key: "getEditor",
	    value: function getEditor(guid) {
	      if (EntityEditor) {
	        return EntityEditor.get(guid);
	      }
	      return null;
	    }
	    /**
	     * Opens editor in slider.
	     * @param {string} editorUrl
	     * @param {HTMLElement} formElement
	     */
	  }, {
	    key: "openEditor",
	    value: function openEditor(editorUrl, formElement) {
	      if (typeof BX.SidePanel !== 'undefined' && typeof BX.SidePanel.Instance !== 'undefined') {
	        BX.SidePanel.Instance.open(editorUrl, {
	          data: {
	            bxSignEditorAllSaved: true
	          },
	          events: {
	            onClose: function onClose(event) {
	              if (event.getSlider().getData().get('bxSignEditorAllSaved') === true) {
	                if (formElement) {
	                  formElement.submit();
	                } else {
	                  window.location.reload();
	                }
	              } else {
	                event.denyAction();
	              }
	            }
	          },
	          width: 1200,
	          cacheable: false,
	          allowChangeHistory: false
	        });
	      }
	    }
	    /**
	     * Toggles some areas when mute-checkbox was checked.
	     * @param {NodeList} checkboxes List of checkboxes to mute.
	     * @param {string} closestSelector Closest selector of checkbox which contain member.
	     * @param {string} attrName Attribute name which toggle on click.
	     */
	  }, {
	    key: "initMuteCheckbox",
	    value: function initMuteCheckbox(checkboxes, closestSelector, attrName) {
	      if (!attrName) {
	        return;
	      }
	      babelHelpers.toConsumableArray(checkboxes).map(function (checkbox) {
	        main_core.Event.bind(checkbox, 'click', function () {
	          var toToggle = checkbox.closest(closestSelector).querySelectorAll('[' + attrName + '="1"]');
	          babelHelpers.toConsumableArray(toToggle).map(function (element) {
	            element.hidden = checkbox.checked;
	          });
	        });
	      });
	    }
	    /**
	     * Shows popup menu to select communication type with member.
	     * @param {InitCommunicationsSelectorType} data
	     */
	  }, {
	    key: "initCommunicationsSelector",
	    value: function initCommunicationsSelector(data) {
	      var containers = data.containers,
	        attrMemberIdName = data.attrMemberIdName,
	        attrMemberUrlName = data.attrMemberUrlName,
	        communications = data.communications,
	        smsAllowed = data.smsAllowed,
	        smsNotAllowedCallback = data.smsNotAllowedCallback;
	      Array.from(containers).map(function (container) {
	        var memberId = parseInt(container.getAttribute(attrMemberIdName));
	        var memberUrl = container.getAttribute(attrMemberUrlName);
	        var communicationsLocal = communications[memberId].length ? communications[memberId] : [];
	        var menuManager;
	        var menuId = 'sign-member-communications-' + memberId;
	        var input = container.querySelector('input');
	        var span = container.querySelector('.sign-master__send-member--communications-arrow span');
	        var menuItems = [];
	        var openItemFunc = function openItemFunc() {
	          return BX.SidePanel.Instance.open(memberUrl, {
	            cacheable: false,
	            allowChangeHistory: false,
	            events: {
	              onClose: function onClose() {
	                window.location.reload();
	                if (menuManager) {
	                  menuManager.close();
	                }
	              }
	            }
	          });
	        };
	        communicationsLocal.map(function (communication) {
	          menuItems.push({
	            text: communication.value,
	            onclick: function onclick() {
	              if (communication.type === 'PHONE' && !smsAllowed) {
	                smsNotAllowedCallback();
	              } else {
	                input.setAttribute('value', communication.type + '|' + communication.value);
	                span.innerText = communication.value;
	                span.parentNode.setAttribute('title', communication.value);
	              }
	              main_popup.MenuManager.getMenuById(menuId).close();
	            }
	          });
	        });
	        if (menuItems.length <= 0) {
	          main_core.Event.bind(container, 'click', function () {
	            openItemFunc();
	          });
	          return;
	        }

	        // add new communication item
	        menuItems.push({
	          text: main_core.Loc.getMessage('SIGN_CMP_MASTER_TPL_MEMBER_NEW_COMMUNICATION'),
	          onclick: function onclick() {
	            openItemFunc();
	          }
	        });
	        menuManager = main_popup.MenuManager.create({
	          id: menuId,
	          bindElement: container,
	          items: menuItems
	        });
	        main_core.Event.bind(container, 'click', function () {
	          menuManager.show();
	        });
	      });
	      var guide = new sign_tour.Guide({
	        steps: [{
	          target: document.querySelector('.sign-master__send-member--communications'),
	          title: main_core.Loc.getMessage('SIGN_CMP_MASTER_TPL_TOUR_STEP_SEND_MEMBER_COMMUNICATION_TITLE'),
	          text: main_core.Loc.getMessage('SIGN_CMP_MASTER_TPL_TOUR_STEP_SEND_MEMBER_COMMUNICATION_TEXT'),
	          position: 'right'
	        }],
	        id: 'sign-tour-guide-onboarding-master-member-communication',
	        autoSave: true,
	        simpleMode: true
	      });
	      guide.startOnce();
	    }
	    /**
	     * Shows document preview.
	     * @param options
	     */
	  }, {
	    key: "showPreview",
	    value: function showPreview(options) {
	      new Preview(options);
	    }
	    /**
	     * Shows error block with message.
	     * @param {string} error
	     * @param link
	     */
	  }, {
	    key: "showError",
	    value: function showError(error, link) {
	      var errorsContainer = document.querySelector('[data-role="sign-error-container"]');
	      if (errorsContainer) {
	        main_core.Dom.style(errorsContainer.parentNode, 'display', 'block');
	        errorsContainer.innerHTML = BX.util.htmlspecialchars(error);
	        if (link !== null && link !== void 0 && link.href) {
	          var href = BX.util.htmlspecialchars(link.href);
	          var linkStart = '<a href="' + href + '" target="_blank">';
	          var linkEnd = '</a>';
	          var button = BX.util.htmlspecialchars(link.button);
	          errorsContainer.innerHTML += ' ' + button.replace('#LINK_START#', linkStart).replace('#LINK_END#', linkEnd);
	        }
	        window.scrollTo({
	          top: 0,
	          left: 0,
	          behavior: 'smooth'
	        });
	      }
	    }
	    /**
	     * Hides error block.
	     */
	  }, {
	    key: "hideErrors",
	    value: function hideErrors() {
	      var errorsContainer = document.querySelector('[data-role="sign-error-container"]');
	      if (errorsContainer) {
	        errorsContainer.parentNode.style.display = 'none';
	        errorsContainer.innerText = '';
	      }
	    }
	  }, {
	    key: "reloadCrmElementsList",
	    value: function reloadCrmElementsList() {
	      var _top$BX, _top$BX$CRM, _top$BX$CRM$Kanban, _top$BX$CRM$Kanban$Gr, _top$BX$CRM$Kanban$Gr2, _top$BX2, _top$BX2$Main, _top$BX2$Main$gridMan, _top$BX2$Main$gridMan2, _top$BX2$Main$gridMan3;
	      (_top$BX = top.BX) === null || _top$BX === void 0 ? void 0 : (_top$BX$CRM = _top$BX.CRM) === null || _top$BX$CRM === void 0 ? void 0 : (_top$BX$CRM$Kanban = _top$BX$CRM.Kanban) === null || _top$BX$CRM$Kanban === void 0 ? void 0 : (_top$BX$CRM$Kanban$Gr = _top$BX$CRM$Kanban.Grid) === null || _top$BX$CRM$Kanban$Gr === void 0 ? void 0 : (_top$BX$CRM$Kanban$Gr2 = _top$BX$CRM$Kanban$Gr.getInstance()) === null || _top$BX$CRM$Kanban$Gr2 === void 0 ? void 0 : _top$BX$CRM$Kanban$Gr2.reload();
	      (_top$BX2 = top.BX) === null || _top$BX2 === void 0 ? void 0 : (_top$BX2$Main = _top$BX2.Main) === null || _top$BX2$Main === void 0 ? void 0 : (_top$BX2$Main$gridMan = _top$BX2$Main.gridManager) === null || _top$BX2$Main$gridMan === void 0 ? void 0 : (_top$BX2$Main$gridMan2 = _top$BX2$Main$gridMan.data[0]) === null || _top$BX2$Main$gridMan2 === void 0 ? void 0 : (_top$BX2$Main$gridMan3 = _top$BX2$Main$gridMan2.instance) === null || _top$BX2$Main$gridMan3 === void 0 ? void 0 : _top$BX2$Main$gridMan3.reload();
	    }
	  }, {
	    key: "onPreviewFirstImageIsLoadedInPartnerStep",
	    value: function onPreviewFirstImageIsLoadedInPartnerStep() {
	      _classStaticPrivateFieldSpecGet(Master, Master, _loadedElementsInPartnerStep).preview = true;
	      if (_classStaticPrivateFieldSpecGet(Master, Master, _loadedElementsInPartnerStep).crmItemEditor) {
	        Master.unLockNavigation();
	        var btn = _classStaticPrivateMethodGet(Master, Master, _getNextStepBtn).call(Master);
	        if (btn) {
	          btn.title = '';
	        }
	      }
	    }
	  }, {
	    key: "onNextBtnClickAtPartnerStep",
	    value: function onNextBtnClickAtPartnerStep(event) {
	      if (!_classStaticPrivateFieldSpecGet(Master, Master, _loadedElementsInPartnerStep).preview || !_classStaticPrivateFieldSpecGet(Master, Master, _loadedElementsInPartnerStep).crmItemEditor) {
	        event.stopImmediatePropagation();
	      }
	    }
	  }]);
	  return Master;
	}();
	function _getNextStepBtn() {
	  return document.querySelector('[data-master-next-step-button]');
	}
	function _getPreviousStepBtn() {
	  return document.querySelector('[data-master-prev-step-button]');
	}
	var _loadedElementsInPartnerStep = {
	  writable: true,
	  value: {
	    preview: false,
	    crmItemEditor: false
	  }
	};

	exports.Master = Master;

}((this.BX.Sign.Component = this.BX.Sign.Component || {}),BX.Main,BX,BX.Sign.Tour,BX,BX.Sign));
//# sourceMappingURL=script.js.map
