/* eslint-disable */
this.BX = this.BX || {};
this.BX.Sign = this.BX.Sign || {};
(function (exports,main_core,main_loader) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3,
	  _t4,
	  _t5,
	  _t6,
	  _t7,
	  _t8,
	  _t9,
	  _t10,
	  _t11;
	const ratio = 0.25;
	var _placeholder = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("placeholder");
	var _page = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("page");
	var _pageNumber = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("pageNumber");
	var _scale = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("scale");
	var _controls = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("controls");
	var _blocksContainer = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("blocksContainer");
	var _blocks = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("blocks");
	var _urls = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("urls");
	var _loader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loader");
	var _content = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("content");
	var _options = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("options");
	var _createBlockStyles = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createBlockStyles");
	var _createBlocks = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createBlocks");
	var _getBlockContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getBlockContent");
	var _getImageLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getImageLayout");
	var _getTextLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getTextLayout");
	var _createControls = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createControls");
	var _render = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("render");
	var _renderContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderContent");
	var _renderBlocks = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderBlocks");
	var _renderControls = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderControls");
	var _renderPagination = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderPagination");
	var _renderZoom = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderZoom");
	class Preview {
	  constructor(options = {}) {
	    Object.defineProperty(this, _renderZoom, {
	      value: _renderZoom2
	    });
	    Object.defineProperty(this, _renderPagination, {
	      value: _renderPagination2
	    });
	    Object.defineProperty(this, _renderControls, {
	      value: _renderControls2
	    });
	    Object.defineProperty(this, _renderBlocks, {
	      value: _renderBlocks2
	    });
	    Object.defineProperty(this, _renderContent, {
	      value: _renderContent2
	    });
	    Object.defineProperty(this, _render, {
	      value: _render2
	    });
	    Object.defineProperty(this, _createControls, {
	      value: _createControls2
	    });
	    Object.defineProperty(this, _getTextLayout, {
	      value: _getTextLayout2
	    });
	    Object.defineProperty(this, _getImageLayout, {
	      value: _getImageLayout2
	    });
	    Object.defineProperty(this, _getBlockContent, {
	      value: _getBlockContent2
	    });
	    Object.defineProperty(this, _createBlocks, {
	      value: _createBlocks2
	    });
	    Object.defineProperty(this, _createBlockStyles, {
	      value: _createBlockStyles2
	    });
	    Object.defineProperty(this, _placeholder, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _page, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _pageNumber, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _scale, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _controls, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _blocksContainer, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _blocks, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _urls, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _loader, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _content, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _options, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _options)[_options] = options;
	    babelHelpers.classPrivateFieldLooseBase(this, _placeholder)[_placeholder] = main_core.Tag.render(_t || (_t = _`
			<div class="sign-preview__placeholder">
				<p class="sign-preview__placeholder_text">
					${0}
				</p>
				<img
					src="/bitrix/js/sign/v2/preview/src/images/placeholder.png"
					class="sign-preview__placeholder__img"
				/>
			</div>`), main_core.Loc.getMessage('SIGN_PREVIEW_PLACEHOLDER_TEXT'));
	    babelHelpers.classPrivateFieldLooseBase(this, _page)[_page] = main_core.Tag.render(_t2 || (_t2 = _`<img class="sign-preview__page" />`));
	    babelHelpers.classPrivateFieldLooseBase(this, _blocksContainer)[_blocksContainer] = main_core.Tag.render(_t3 || (_t3 = _`<div class="sign-preview__blocks"></div>`));
	    babelHelpers.classPrivateFieldLooseBase(this, _content)[_content] = main_core.Tag.render(_t4 || (_t4 = _`
			<div class="sign-preview__content">
				<div class="sign-preview__content_scalable">
					${0}
					${0}
					${0}
				</div>
			</div>`), babelHelpers.classPrivateFieldLooseBase(this, _placeholder)[_placeholder], babelHelpers.classPrivateFieldLooseBase(this, _page)[_page], babelHelpers.classPrivateFieldLooseBase(this, _blocksContainer)[_blocksContainer]);
	    babelHelpers.classPrivateFieldLooseBase(this, _blocks)[_blocks] = new Map();
	    babelHelpers.classPrivateFieldLooseBase(this, _controls)[_controls] = babelHelpers.classPrivateFieldLooseBase(this, _createControls)[_createControls]();
	    babelHelpers.classPrivateFieldLooseBase(this, _urls)[_urls] = [];
	    babelHelpers.classPrivateFieldLooseBase(this, _pageNumber)[_pageNumber] = 0;
	    babelHelpers.classPrivateFieldLooseBase(this, _scale)[_scale] = 1;
	    babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader] = new main_loader.Loader({
	      target: babelHelpers.classPrivateFieldLooseBase(this, _content)[_content],
	      size: 80
	    });
	  }
	  getLayout() {
	    var _babelHelpers$classPr, _babelHelpers$classPr2, _babelHelpers$classPr3;
	    const layout = main_core.Tag.render(_t5 || (_t5 = _`
			<div class="sign-preview">
				${0}
				${0}
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _content)[_content], babelHelpers.classPrivateFieldLooseBase(this, _controls)[_controls], (_babelHelpers$classPr = (_babelHelpers$classPr2 = babelHelpers.classPrivateFieldLooseBase(this, _options)[_options]) == null ? void 0 : (_babelHelpers$classPr3 = _babelHelpers$classPr2.layout) == null ? void 0 : _babelHelpers$classPr3.getAfterPreviewLayoutCallback == null ? void 0 : _babelHelpers$classPr3.getAfterPreviewLayoutCallback()) != null ? _babelHelpers$classPr : '');
	    babelHelpers.classPrivateFieldLooseBase(this, _render)[_render]();
	    return layout;
	  }
	  set urls(urls) {
	    babelHelpers.classPrivateFieldLooseBase(this, _urls)[_urls] = urls.length ? [...babelHelpers.classPrivateFieldLooseBase(this, _urls)[_urls], ...urls] : [];
	    babelHelpers.classPrivateFieldLooseBase(this, _renderContent)[_renderContent]();
	    babelHelpers.classPrivateFieldLooseBase(this, _renderControls)[_renderControls]();
	  }
	  hasUrls() {
	    return main_core.Type.isArrayFilled(babelHelpers.classPrivateFieldLooseBase(this, _urls)[_urls]);
	  }
	  set ready(isReady) {
	    if (isReady) {
	      babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader].hide();
	      main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _content)[_content], '--with-overlay');
	      return;
	    }
	    main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _content)[_content], '--with-overlay');
	    babelHelpers.classPrivateFieldLooseBase(this, _loader)[_loader].show();
	  }
	  async setBlocks(blocks = []) {
	    main_core.Dom.clean(babelHelpers.classPrivateFieldLooseBase(this, _blocksContainer)[_blocksContainer]);
	    if (!(blocks != null && blocks.length)) {
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _blocks)[_blocks] = await babelHelpers.classPrivateFieldLooseBase(this, _createBlocks)[_createBlocks](blocks);
	    babelHelpers.classPrivateFieldLooseBase(this, _renderBlocks)[_renderBlocks]();
	  }
	}
	function _createBlockStyles2(block) {
	  const {
	    style,
	    position,
	    type
	  } = block;
	  const {
	    width,
	    height,
	    widthPx,
	    heightPx
	  } = position;
	  const inlineStyles = {
	    ...style,
	    top: `${position.top}%`,
	    left: `${position.left}%`,
	    width: `${width}%`,
	    height: `${height}%`
	  };
	  if (type === 'image') {
	    return inlineStyles;
	  }
	  const {
	    width: pageWidth,
	    height: pageHeight
	  } = babelHelpers.classPrivateFieldLooseBase(this, _page)[_page].getBoundingClientRect();
	  const widthRatio = widthPx / (width / 100 * pageWidth);
	  const heightRatio = heightPx / (height / 100 * pageHeight);
	  const fontSize = (parseFloat(style['fontSize']) || 14) / widthRatio;
	  const padding = `${5 / heightRatio}px ${8 / widthRatio}px`;
	  Object.assign(inlineStyles, {
	    padding,
	    fontSize: `${fontSize}px`
	  });
	  return inlineStyles;
	}
	async function _createBlocks2(blocksData) {
	  const isLoaded = babelHelpers.classPrivateFieldLooseBase(this, _page)[_page].complete && babelHelpers.classPrivateFieldLooseBase(this, _page)[_page].naturalHeight !== 0;
	  if (!isLoaded) {
	    await new Promise(resolve => main_core.Event.bindOnce(babelHelpers.classPrivateFieldLooseBase(this, _page)[_page], 'load', resolve));
	  }
	  const {
	    blocksTemplate,
	    blocks
	  } = blocksData.reduce((acc, block) => {
	    var _blocks$get;
	    const node = main_core.Tag.render(_t6 || (_t6 = _`
				<div class="sign-preview__block"></div>
			`));
	    const blockContent = babelHelpers.classPrivateFieldLooseBase(this, _getBlockContent)[_getBlockContent](block);
	    if (blockContent) {
	      main_core.Dom.append(blockContent, node);
	      main_core.Dom.addClass(node, '--filled');
	    }
	    const inlineStyles = babelHelpers.classPrivateFieldLooseBase(this, _createBlockStyles)[_createBlockStyles](block);
	    Object.keys(inlineStyles).forEach(styleName => {
	      node.style[styleName] = inlineStyles[styleName];
	    });
	    const {
	      blocks,
	      blocksTemplate
	    } = acc;
	    const {
	      page
	    } = block.position;
	    blocks.set(page, [...((_blocks$get = blocks.get(page)) != null ? _blocks$get : []), node]);
	    main_core.Dom.append(node, blocksTemplate);
	    return acc;
	  }, {
	    blocks: new Map(),
	    blocksTemplate: new DocumentFragment()
	  });
	  main_core.Dom.append(blocksTemplate, babelHelpers.classPrivateFieldLooseBase(this, _blocksContainer)[_blocksContainer]);
	  return blocks;
	}
	function _getBlockContent2(block) {
	  if (block.type === 'image') {
	    return babelHelpers.classPrivateFieldLooseBase(this, _getImageLayout)[_getImageLayout](block);
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _getTextLayout)[_getTextLayout](block);
	}
	function _getImageLayout2(block) {
	  var _block$data$__view;
	  const url = (_block$data$__view = block.data.__view) == null ? void 0 : _block$data$__view.base64;
	  if (!url) {
	    return null;
	  }
	  return main_core.Tag.render(_t7 || (_t7 = _`<img src="data:image;base64,${0}" />`), url);
	}
	function _getTextLayout2(block) {
	  let text = block.data.text;
	  if (!text) {
	    return null;
	  }
	  text = main_core.Text.encode(text);
	  const span = main_core.Tag.render(_t8 || (_t8 = _`<span>${0}</span>`), text);
	  span.innerHTML = text.replaceAll('[br]', '<br />');
	  return span;
	}
	function _createControls2() {
	  const pagination = main_core.Tag.render(_t9 || (_t9 = _`
			<div class="sign-preview__pagination">
				<span
					class="sign-preview__btn sign-preview__pagination_btn --prev"
					onclick="${0}"
				>
				</span>
				<span class="sign-preview__pagination_page-num"></span>
				<span
					class="sign-preview__btn sign-preview__pagination_btn --next"
					onclick="${0}"
				>
				</span>
			</div>
		`), () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _pageNumber)[_pageNumber] -= 1;
	    babelHelpers.classPrivateFieldLooseBase(this, _render)[_render]();
	  }, () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _pageNumber)[_pageNumber] += 1;
	    babelHelpers.classPrivateFieldLooseBase(this, _render)[_render]();
	  });
	  const zoom = main_core.Tag.render(_t10 || (_t10 = _`
			<div class="sign-preview__zoom">
				<span
					class="sign-preview__btn sign-preview__zoom_btn --plus"
					onclick="${0}"
				>
				</span>
				<span class="sign-preview__zoom_value">100%</span>
				<span
					class="sign-preview__btn sign-preview__zoom_btn --minus"
					onclick="${0}"
				></span>
			</div>
		`), () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _scale)[_scale] -= ratio;
	    babelHelpers.classPrivateFieldLooseBase(this, _renderContent)[_renderContent]();
	    babelHelpers.classPrivateFieldLooseBase(this, _renderZoom)[_renderZoom]();
	  }, () => {
	    babelHelpers.classPrivateFieldLooseBase(this, _scale)[_scale] += ratio;
	    babelHelpers.classPrivateFieldLooseBase(this, _renderContent)[_renderContent]();
	    babelHelpers.classPrivateFieldLooseBase(this, _renderZoom)[_renderZoom]();
	  });
	  return main_core.Tag.render(_t11 || (_t11 = _`
			<div class="sign-preview__controls">
				${0}
				${0}
			</div>
		`), pagination, zoom);
	}
	function _render2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _renderContent)[_renderContent]();
	  babelHelpers.classPrivateFieldLooseBase(this, _renderBlocks)[_renderBlocks]();
	  babelHelpers.classPrivateFieldLooseBase(this, _renderControls)[_renderControls]();
	}
	function _renderContent2() {
	  main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _placeholder)[_placeholder], '--hidden');
	  main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _page)[_page], '--hidden');
	  const {
	    parentElement: scalable
	  } = babelHelpers.classPrivateFieldLooseBase(this, _page)[_page];
	  if (babelHelpers.classPrivateFieldLooseBase(this, _urls)[_urls].length === 0) {
	    main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _page)[_page], '--hidden');
	    babelHelpers.classPrivateFieldLooseBase(this, _page)[_page].src = '';
	    scalable.style.transform = '';
	    return;
	  }
	  main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _placeholder)[_placeholder], '--hidden');
	  const url = babelHelpers.classPrivateFieldLooseBase(this, _urls)[_urls][babelHelpers.classPrivateFieldLooseBase(this, _pageNumber)[_pageNumber]];
	  babelHelpers.classPrivateFieldLooseBase(this, _page)[_page].src = url;
	  scalable.style.transform = `scale(${babelHelpers.classPrivateFieldLooseBase(this, _scale)[_scale]})`;
	}
	function _renderBlocks2() {
	  var _babelHelpers$classPr4;
	  const visibleBlocks = (_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _blocks)[_blocks].get(babelHelpers.classPrivateFieldLooseBase(this, _pageNumber)[_pageNumber] + 1)) != null ? _babelHelpers$classPr4 : [];
	  const allBlocks = [...babelHelpers.classPrivateFieldLooseBase(this, _blocks)[_blocks].values()].flat();
	  allBlocks.forEach(block => {
	    if (visibleBlocks.includes(block)) {
	      main_core.Dom.removeClass(block, '--hidden');
	      return;
	    }
	    main_core.Dom.addClass(block, '--hidden');
	  });
	}
	function _renderControls2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _urls)[_urls].length === 0) {
	    main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _controls)[_controls], '--locked');
	    babelHelpers.classPrivateFieldLooseBase(this, _pageNumber)[_pageNumber] = 0;
	    babelHelpers.classPrivateFieldLooseBase(this, _scale)[_scale] = 1;
	    return;
	  }
	  main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _controls)[_controls], '--locked');
	  babelHelpers.classPrivateFieldLooseBase(this, _renderPagination)[_renderPagination]();
	  babelHelpers.classPrivateFieldLooseBase(this, _renderZoom)[_renderZoom]();
	}
	function _renderPagination2() {
	  const [pagination] = babelHelpers.classPrivateFieldLooseBase(this, _controls)[_controls].children;
	  const [prevBtn, content, nextBtn] = pagination.children;
	  const message = main_core.Loc.getMessage('SIGN_PREVIEW_PAGE');
	  const totalPages = babelHelpers.classPrivateFieldLooseBase(this, _urls)[_urls].length;
	  main_core.Dom.removeClass(prevBtn, '--disabled');
	  main_core.Dom.removeClass(nextBtn, '--disabled');
	  content.textContent = `${message} ${babelHelpers.classPrivateFieldLooseBase(this, _pageNumber)[_pageNumber] + 1}/${totalPages}`;
	  if (babelHelpers.classPrivateFieldLooseBase(this, _pageNumber)[_pageNumber] === 0) {
	    main_core.Dom.addClass(prevBtn, '--disabled');
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _pageNumber)[_pageNumber] === totalPages - 1) {
	    main_core.Dom.addClass(nextBtn, '--disabled');
	  }
	}
	function _renderZoom2() {
	  const [, zoom] = babelHelpers.classPrivateFieldLooseBase(this, _controls)[_controls].children;
	  const [zoomOutBtn, content, zoomInBtn] = zoom.children;
	  content.textContent = `${babelHelpers.classPrivateFieldLooseBase(this, _scale)[_scale] * 100}%`;
	  const overflowed = babelHelpers.classPrivateFieldLooseBase(this, _page)[_page].parentElement.parentElement;
	  if (babelHelpers.classPrivateFieldLooseBase(this, _scale)[_scale] !== 1) {
	    main_core.Dom.addClass(overflowed, '--overflowed');
	    main_core.Dom.removeClass(zoomOutBtn, '--disabled');
	    if (babelHelpers.classPrivateFieldLooseBase(this, _scale)[_scale] === 2) {
	      main_core.Dom.addClass(zoomInBtn, '--disabled');
	    }
	  } else {
	    main_core.Dom.addClass(zoomOutBtn, '--disabled');
	    main_core.Dom.removeClass(zoomInBtn, '--disabled');
	    main_core.Dom.removeClass(overflowed, '--overflowed');
	  }
	}

	exports.Preview = Preview;

}((this.BX.Sign.V2 = this.BX.Sign.V2 || {}),BX,BX));
//# sourceMappingURL=preview.bundle.js.map
