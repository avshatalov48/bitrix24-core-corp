/* eslint-disable */
this.BX = this.BX || {};
this.BX.Intranet = this.BX.Intranet || {};
this.BX.Intranet.Bitrix24 = this.BX.Intranet.Bitrix24 || {};
(function (exports,ui_infoHelper,main_core_cache,main_core_events,main_popup,main_loader,main_core,ui_uploader_core) {
	'use strict';

	let _ = t => t,
	  _t,
	  _t2,
	  _t3;
	var _themePicker = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("themePicker");
	var _checkbox = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("checkbox");
	var _handleCheckboxClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleCheckboxClick");
	class CheckboxButton extends main_popup.PopupWindowButton {
	  constructor(themePicker) {
	    super({
	      id: 'checkbox'
	    });
	    Object.defineProperty(this, _handleCheckboxClick, {
	      value: _handleCheckboxClick2
	    });
	    Object.defineProperty(this, _themePicker, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _checkbox, {
	      writable: true,
	      value: void 0
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _themePicker)[_themePicker] = themePicker;
	    babelHelpers.classPrivateFieldLooseBase(this, _checkbox)[_checkbox] = main_core.Tag.render(_t || (_t = _`
			<input
				id="theme-dialog-checkbox-input"
				class="theme-dialog-checkbox-input"
				type="checkbox"
				name="defaultTheme"
				value="Y"
				onclick="${0}"
			>
		`), babelHelpers.classPrivateFieldLooseBase(this, _handleCheckboxClick)[_handleCheckboxClick].bind(this));
	    this.buttonNode = main_core.Tag.render(_t2 || (_t2 = _`
			<div class="theme-dialog-checkbox-button">
				${0}
				<label for="theme-dialog-checkbox-input" class="theme-dialog-checkbox-label">
					${0}
				</label>
				${0}
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _checkbox)[_checkbox], main_core.Loc.getMessage('BITRIX24_THEME_DEFAULT_THEME_FOR_ALL'), babelHelpers.classPrivateFieldLooseBase(this, _themePicker)[_themePicker].canSetDefaultTheme() ? '' : main_core.Tag.render(_t3 || (_t3 = _`<span class="tariff-lock"></span>`)));
	  }
	  isChecked() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _checkbox)[_checkbox].checked;
	  }
	  check() {
	    babelHelpers.classPrivateFieldLooseBase(this, _checkbox)[_checkbox].checked = true;
	  }
	  uncheck() {
	    babelHelpers.classPrivateFieldLooseBase(this, _checkbox)[_checkbox].checked = false;
	  }
	}
	function _handleCheckboxClick2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _themePicker)[_themePicker].canSetDefaultTheme()) {
	    return;
	  }
	  ui_infoHelper.InfoHelper.show('limit_office_background_to_all');
	  this.uncheck();
	}

	async function blur(file, radius = 7) {
	  const {
	    image,
	    width,
	    height
	  } = main_core.Type.isFile(file) ? await ui_uploader_core.Helpers.loadImage(file) : {
	    image: file,
	    width: file.naturalWidth || file.width,
	    height: file.naturalHeight || file.height
	  };
	  const canvas = document.createElement('canvas');
	  canvas.width = width;
	  canvas.height = height;
	  const ctx = canvas.getContext('2d');
	  if ('filter' in ctx) {
	    const padding = Math.ceil(radius) * 2;
	    const tmpCanvas = document.createElement('canvas');
	    const tmpContext = tmpCanvas.getContext('2d');
	    const w = image.width;
	    const h = image.height;
	    tmpCanvas.width = w + padding * 2;
	    tmpCanvas.height = h + padding * 2;
	    tmpContext.drawImage(image, 0, 0, w, 1, padding, 0, w, padding);
	    tmpContext.drawImage(image, 0, h - 1, w, 1, padding, h + padding, w, padding);
	    tmpContext.drawImage(image, 0, 0, 1, h, 0, padding, padding, h);
	    tmpContext.drawImage(image, w - 1, 0, 1, h, w + padding, padding, padding, h);
	    tmpContext.drawImage(image, 0, 0, 1, 1, 0, 0, padding, padding);
	    tmpContext.drawImage(image, w - 1, 0, 1, 1, w + padding, 0, padding, padding);
	    tmpContext.drawImage(image, 0, h - 1, 1, 1, 0, h + padding, padding, padding);
	    tmpContext.drawImage(image, w - 1, h - 1, 1, 1, w + padding, h + padding, padding, padding);
	    tmpContext.drawImage(image, padding, padding);
	    tmpContext.filter = `blur(${radius}px)`;
	    tmpContext.drawImage(tmpCanvas, 0, 0, tmpCanvas.width, tmpCanvas.height, 0, 0, tmpCanvas.width, tmpCanvas.height);
	    ctx.drawImage(tmpCanvas, padding, padding, w, h, 0, 0, w, h);
	  } else {
	    console.log('blur manually');
	    ctx.drawImage(image, 0, 0);
	    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
	    const blurredImageData = blurManually(imageData, radius);
	    ctx.putImageData(blurredImageData, 0, 0);
	  }
	  const pixel = ctx.getImageData(0, 0, 10, 10);
	  const [r, g, b] = pixel.data;
	  const hex = `#${[r, g, b].map(x => x.toString(16).padStart(2, '0')).join('')}`;
	  const type = main_core.Browser.isSafari() ? 'image/jpeg' : 'image/webp';
	  const extension = main_core.Browser.isSafari() ? 'jpg' : 'webp';
	  const blob = await new Promise(resolve => {
	    canvas.toBlob(resolve, type);
	  });
	  const fileName = file.name ? `blurred_${ui_uploader_core.Helpers.getFilenameWithoutExtension(file.name)}.${extension}` : `blurred_${main_core.Text.getRandom()}.${extension}`;
	  return {
	    file: new File([blob], fileName, {
	      type
	    }),
	    color: hex
	  };
	}
	function blurManually(imageData, radius) {
	  if (radius < 1) {
	    return imageData;
	  }
	  const data = imageData.data;
	  const width = imageData.width;
	  const height = imageData.height;
	  const intRadius = Math.round(radius);
	  const boxes = calculateBoxSizes(intRadius, 3);
	  const tempData = new Uint8ClampedArray(data);
	  let currentSrc = data;
	  let currentDst = tempData;
	  for (let i = 0; i < 3; i++) {
	    const boxRadius = Math.floor((boxes[i] - 1) / 2);
	    blurSymmetric(currentSrc, currentDst, width, height, boxRadius);
	    [currentSrc, currentDst] = [currentDst, currentSrc];
	  }
	  if (currentSrc !== data) {
	    data.set(currentSrc);
	  }
	  return imageData;
	}
	function blurSymmetric(srcData, dstData, width, height, radius) {
	  const tempData = new Uint8ClampedArray(srcData.length);
	  blurHorizontal(srcData, tempData, width, height, radius);
	  blurVertical(tempData, dstData, width, height, radius);
	}
	function calculateBoxSizes(sigma, n) {
	  const wIdeal = Math.sqrt(12 * sigma * sigma / n + 1);
	  let wl = Math.floor(wIdeal);
	  if (wl % 2 === 0) {
	    wl--;
	  }
	  const wu = wl + 2;
	  const mIdeal = (12 * sigma * sigma - n * wl * wl - 4 * n * wl - 3 * n) / (-4 * wl - 4);
	  const m = Math.round(mIdeal);
	  const sizes = [];
	  for (let i = 0; i < n; i++) {
	    sizes.push(i < m ? wl : wu);
	  }
	  return sizes;
	}
	function blurHorizontal(srcData, dstData, width, height, radius) {
	  if (radius === 0) {
	    dstData.set(srcData);
	    return;
	  }
	  const kernelSize = radius * 2 + 1;
	  const iarr = 1 / kernelSize;
	  for (let y = 0; y < height; y++) {
	    const rowStart = y * width * 4;
	    let r = 0;
	    let g = 0;
	    let b = 0;
	    let a = 0;
	    for (let j = -radius; j <= radius; j++) {
	      const x = Math.max(0, Math.min(width - 1, j));
	      const offset = rowStart + x * 4;
	      r += srcData[offset];
	      g += srcData[offset + 1];
	      b += srcData[offset + 2];
	      a += srcData[offset + 3];
	    }
	    let dstOffset = rowStart;
	    dstData[dstOffset] = Math.round(r * iarr);
	    dstData[dstOffset + 1] = Math.round(g * iarr);
	    dstData[dstOffset + 2] = Math.round(b * iarr);
	    dstData[dstOffset + 3] = Math.round(a * iarr);
	    for (let x = 1; x < width; x++) {
	      const leftX = Math.max(0, Math.min(width - 1, x - radius - 1));
	      const leftOffset = rowStart + leftX * 4;
	      r -= srcData[leftOffset];
	      g -= srcData[leftOffset + 1];
	      b -= srcData[leftOffset + 2];
	      a -= srcData[leftOffset + 3];
	      const rightX = Math.max(0, Math.min(width - 1, x + radius));
	      const rightOffset = rowStart + rightX * 4;
	      r += srcData[rightOffset];
	      g += srcData[rightOffset + 1];
	      b += srcData[rightOffset + 2];
	      a += srcData[rightOffset + 3];
	      dstOffset = rowStart + x * 4;
	      dstData[dstOffset] = Math.round(r * iarr);
	      dstData[dstOffset + 1] = Math.round(g * iarr);
	      dstData[dstOffset + 2] = Math.round(b * iarr);
	      dstData[dstOffset + 3] = Math.round(a * iarr);
	    }
	  }
	}
	function blurVertical(srcData, dstData, width, height, radius) {
	  if (radius === 0) {
	    dstData.set(srcData);
	    return;
	  }
	  const kernelSize = radius * 2 + 1;
	  const iarr = 1 / kernelSize;
	  const widthBytes = width * 4;
	  for (let x = 0; x < width; x++) {
	    const colStart = x * 4;
	    let r = 0;
	    let g = 0;
	    let b = 0;
	    let a = 0;
	    for (let j = -radius; j <= radius; j++) {
	      const y = Math.max(0, Math.min(height - 1, j));
	      const offset = y * widthBytes + colStart;
	      r += srcData[offset];
	      g += srcData[offset + 1];
	      b += srcData[offset + 2];
	      a += srcData[offset + 3];
	    }
	    let dstOffset = colStart;
	    dstData[dstOffset] = Math.round(r * iarr);
	    dstData[dstOffset + 1] = Math.round(g * iarr);
	    dstData[dstOffset + 2] = Math.round(b * iarr);
	    dstData[dstOffset + 3] = Math.round(a * iarr);
	    for (let y = 1; y < height; y++) {
	      const topY = Math.max(0, Math.min(height - 1, y - radius - 1));
	      const topOffset = topY * widthBytes + colStart;
	      r -= srcData[topOffset];
	      g -= srcData[topOffset + 1];
	      b -= srcData[topOffset + 2];
	      a -= srcData[topOffset + 3];
	      const bottomY = Math.max(0, Math.min(height - 1, y + radius));
	      const bottomOffset = bottomY * widthBytes + colStart;
	      r += srcData[bottomOffset];
	      g += srcData[bottomOffset + 1];
	      b += srcData[bottomOffset + 2];
	      a += srcData[bottomOffset + 3];
	      dstOffset = y * widthBytes + colStart;
	      dstData[dstOffset] = Math.round(r * iarr);
	      dstData[dstOffset + 1] = Math.round(g * iarr);
	      dstData[dstOffset + 2] = Math.round(b * iarr);
	      dstData[dstOffset + 3] = Math.round(a * iarr);
	    }
	  }
	}

	let _$1 = t => t,
	  _t$1,
	  _t2$1,
	  _t3$1,
	  _t4,
	  _t5;
	var _themePicker$1 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("themePicker");
	var _listDialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("listDialog");
	var _colorPicker = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("colorPicker");
	var _popup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("popup");
	var _previewApplied = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("previewApplied");
	var _origAppliedThemeId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("origAppliedThemeId");
	var _uploader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("uploader");
	var _refs = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("refs");
	var _previewLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("previewLoader");
	var _resetResources = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("resetResources");
	var _getBgImage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getBgImage");
	var _getBgImageUrl = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getBgImageUrl");
	var _getBgImageBlurred = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getBgImageBlurred");
	var _getBgImageBlurredUrl = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getBgImageBlurredUrl");
	var _getBgColor = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getBgColor");
	var _getTextColor = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getTextColor");
	var _getControl = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getControl");
	var _getControls = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getControls");
	var _getPopup = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPopup");
	var _getColorPicker = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getColorPicker");
	var _handleCreateButtonClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleCreateButtonClick");
	var _getContent = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getContent");
	var _showError = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showError");
	var _hideError = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hideError");
	var _createField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createField");
	var _getBgColorField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getBgColorField");
	var _getBgImageField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getBgImageField");
	var _validateForm = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("validateForm");
	var _validateBgColor = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("validateBgColor");
	var _showPreview = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showPreview");
	var _hidePreview = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hidePreview");
	var _handleBgImageEnter = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleBgImageEnter");
	var _handleBgImageLeave = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleBgImageLeave");
	var _handleBgImageOver = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleBgImageOver");
	var _handleBgColorClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleBgColorClick");
	var _handleBgColorChange = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleBgColorChange");
	var _handleBgColorClear = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleBgColorClear");
	var _handleBgColorSelect = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleBgColorSelect");
	var _setBgColor = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setBgColor");
	var _setTextColor = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setTextColor");
	var _getTextColorField = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getTextColorField");
	var _handleSwitcherClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleSwitcherClick");
	var _applyThemePreview = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("applyThemePreview");
	var _removeThemePreview = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("removeThemePreview");
	var _getPreviewThemeId = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getPreviewThemeId");
	var _chooseTextColor = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("chooseTextColor");
	class NewThemeDialog extends main_core_events.EventEmitter {
	  constructor(themePicker, listDialog) {
	    super();
	    Object.defineProperty(this, _chooseTextColor, {
	      value: _chooseTextColor2
	    });
	    Object.defineProperty(this, _getPreviewThemeId, {
	      value: _getPreviewThemeId2
	    });
	    Object.defineProperty(this, _removeThemePreview, {
	      value: _removeThemePreview2
	    });
	    Object.defineProperty(this, _applyThemePreview, {
	      value: _applyThemePreview2
	    });
	    Object.defineProperty(this, _handleSwitcherClick, {
	      value: _handleSwitcherClick2
	    });
	    Object.defineProperty(this, _getTextColorField, {
	      value: _getTextColorField2
	    });
	    Object.defineProperty(this, _setTextColor, {
	      value: _setTextColor2
	    });
	    Object.defineProperty(this, _setBgColor, {
	      value: _setBgColor2
	    });
	    Object.defineProperty(this, _handleBgColorSelect, {
	      value: _handleBgColorSelect2
	    });
	    Object.defineProperty(this, _handleBgColorClear, {
	      value: _handleBgColorClear2
	    });
	    Object.defineProperty(this, _handleBgColorChange, {
	      value: _handleBgColorChange2
	    });
	    Object.defineProperty(this, _handleBgColorClick, {
	      value: _handleBgColorClick2
	    });
	    Object.defineProperty(this, _handleBgImageOver, {
	      value: _handleBgImageOver2
	    });
	    Object.defineProperty(this, _handleBgImageLeave, {
	      value: _handleBgImageLeave2
	    });
	    Object.defineProperty(this, _handleBgImageEnter, {
	      value: _handleBgImageEnter2
	    });
	    Object.defineProperty(this, _hidePreview, {
	      value: _hidePreview2
	    });
	    Object.defineProperty(this, _showPreview, {
	      value: _showPreview2
	    });
	    Object.defineProperty(this, _validateBgColor, {
	      value: _validateBgColor2
	    });
	    Object.defineProperty(this, _validateForm, {
	      value: _validateForm2
	    });
	    Object.defineProperty(this, _getBgImageField, {
	      value: _getBgImageField2
	    });
	    Object.defineProperty(this, _getBgColorField, {
	      value: _getBgColorField2
	    });
	    Object.defineProperty(this, _createField, {
	      value: _createField2
	    });
	    Object.defineProperty(this, _hideError, {
	      value: _hideError2
	    });
	    Object.defineProperty(this, _showError, {
	      value: _showError2
	    });
	    Object.defineProperty(this, _getContent, {
	      value: _getContent2
	    });
	    Object.defineProperty(this, _handleCreateButtonClick, {
	      value: _handleCreateButtonClick2
	    });
	    Object.defineProperty(this, _getColorPicker, {
	      value: _getColorPicker2
	    });
	    Object.defineProperty(this, _getPopup, {
	      value: _getPopup2
	    });
	    Object.defineProperty(this, _getControls, {
	      value: _getControls2
	    });
	    Object.defineProperty(this, _getControl, {
	      value: _getControl2
	    });
	    Object.defineProperty(this, _getTextColor, {
	      value: _getTextColor2
	    });
	    Object.defineProperty(this, _getBgColor, {
	      value: _getBgColor2
	    });
	    Object.defineProperty(this, _getBgImageBlurredUrl, {
	      value: _getBgImageBlurredUrl2
	    });
	    Object.defineProperty(this, _getBgImageBlurred, {
	      value: _getBgImageBlurred2
	    });
	    Object.defineProperty(this, _getBgImageUrl, {
	      value: _getBgImageUrl2
	    });
	    Object.defineProperty(this, _getBgImage, {
	      value: _getBgImage2
	    });
	    Object.defineProperty(this, _resetResources, {
	      value: _resetResources2
	    });
	    Object.defineProperty(this, _themePicker$1, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _listDialog, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _colorPicker, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _popup, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _previewApplied, {
	      writable: true,
	      value: false
	    });
	    Object.defineProperty(this, _origAppliedThemeId, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _uploader, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _refs, {
	      writable: true,
	      value: new main_core_cache.MemoryCache()
	    });
	    Object.defineProperty(this, _previewLoader, {
	      writable: true,
	      value: null
	    });
	    this.setEventNamespace('BX.Intranet.Bitrix24.ThemePicker.NewThemeDialog');
	    babelHelpers.classPrivateFieldLooseBase(this, _themePicker$1)[_themePicker$1] = themePicker;
	    babelHelpers.classPrivateFieldLooseBase(this, _listDialog)[_listDialog] = listDialog;
	  }
	  show() {
	    babelHelpers.classPrivateFieldLooseBase(this, _getPopup)[_getPopup]().setContent(babelHelpers.classPrivateFieldLooseBase(this, _getContent)[_getContent]());
	    babelHelpers.classPrivateFieldLooseBase(this, _getPopup)[_getPopup]().show();
	    babelHelpers.classPrivateFieldLooseBase(this, _themePicker$1)[_themePicker$1].disableThemeListDialog();
	  }
	  destroy() {
	    babelHelpers.classPrivateFieldLooseBase(this, _resetResources)[_resetResources]();
	    babelHelpers.classPrivateFieldLooseBase(this, _getPopup)[_getPopup]().destroy();
	    babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = null;
	    babelHelpers.classPrivateFieldLooseBase(this, _refs)[_refs] = null;
	  }
	  getUploader() {
	    var _babelHelpers$classPr, _babelHelpers$classPr2;
	    (_babelHelpers$classPr2 = (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _uploader))[_uploader]) != null ? _babelHelpers$classPr2 : _babelHelpers$classPr[_uploader] = new ui_uploader_core.Uploader({
	      multiple: false,
	      acceptOnlyImages: true,
	      allowReplaceSingle: true,
	      imagePreviewWidth: 1920,
	      imagePreviewHeight: 1080,
	      // imagePreviewWidth: 4096,
	      // imagePreviewHeight: 2160,
	      imagePreviewMimeType: 'image/webp',
	      imagePreviewMimeTypeMode: 'force',
	      imagePreviewQuality: 0.85,
	      imageMinWidth: 300,
	      imageMinHeight: 200,
	      imageMaxWidth: 10000,
	      imageMaxHeight: 10000,
	      imageMaxFileSize: 20 * 1024 * 1024,
	      events: {
	        [ui_uploader_core.UploaderEvent.FILE_LOAD_START]: () => {
	          babelHelpers.classPrivateFieldLooseBase(this, _hidePreview)[_hidePreview]();
	          babelHelpers.classPrivateFieldLooseBase(this, _previewLoader)[_previewLoader] = new main_loader.Loader({
	            size: 40
	          });
	          babelHelpers.classPrivateFieldLooseBase(this, _previewLoader)[_previewLoader].show(babelHelpers.classPrivateFieldLooseBase(this, _getControl)[_getControl]('field-file-preview'));
	        },
	        [ui_uploader_core.UploaderEvent.FILE_ERROR]: event => {
	          var _babelHelpers$classPr3;
	          const error = event.getData().error;
	          babelHelpers.classPrivateFieldLooseBase(this, _showError)[_showError](`${error.getMessage()}<br>${error.getDescription()}`);
	          (_babelHelpers$classPr3 = babelHelpers.classPrivateFieldLooseBase(this, _previewLoader)[_previewLoader]) == null ? void 0 : _babelHelpers$classPr3.destroy();
	          babelHelpers.classPrivateFieldLooseBase(this, _previewLoader)[_previewLoader] = null;
	        },
	        [ui_uploader_core.UploaderEvent.FILE_COMPLETE]: async event => {
	          var _babelHelpers$classPr4;
	          babelHelpers.classPrivateFieldLooseBase(this, _hideError)[_hideError]();
	          const file = event.getData().file;
	          const preview = file.getClientPreview();

	          // console.time('blur');
	          const {
	            file: bgImageBlurred,
	            color
	          } = await blur(preview, 7);
	          // console.timeEnd('blur');

	          const bgImageBlurredUrl = URL.createObjectURL(bgImageBlurred);
	          file.setCustomData('bgImageBlurred', bgImageBlurred);
	          file.setCustomData('bgImageBlurredUrl', bgImageBlurredUrl);
	          (_babelHelpers$classPr4 = babelHelpers.classPrivateFieldLooseBase(this, _previewLoader)[_previewLoader]) == null ? void 0 : _babelHelpers$classPr4.destroy();
	          babelHelpers.classPrivateFieldLooseBase(this, _previewLoader)[_previewLoader] = null;
	          babelHelpers.classPrivateFieldLooseBase(this, _setBgColor)[_setBgColor](color);
	          babelHelpers.classPrivateFieldLooseBase(this, _setTextColor)[_setTextColor](babelHelpers.classPrivateFieldLooseBase(this, _chooseTextColor)[_chooseTextColor](color));
	          babelHelpers.classPrivateFieldLooseBase(this, _showPreview)[_showPreview](babelHelpers.classPrivateFieldLooseBase(this, _getBgImageUrl)[_getBgImageUrl]());
	          babelHelpers.classPrivateFieldLooseBase(this, _applyThemePreview)[_applyThemePreview]();
	        }
	      }
	    });
	    return babelHelpers.classPrivateFieldLooseBase(this, _uploader)[_uploader];
	  }
	}
	function _resetResources2() {
	  var _babelHelpers$classPr5;
	  if (babelHelpers.classPrivateFieldLooseBase(this, _getBgImageBlurredUrl)[_getBgImageBlurredUrl]() !== null) {
	    URL.revokeObjectURL(babelHelpers.classPrivateFieldLooseBase(this, _getBgImageBlurredUrl)[_getBgImageBlurredUrl]());
	  }
	  (_babelHelpers$classPr5 = babelHelpers.classPrivateFieldLooseBase(this, _uploader)[_uploader]) == null ? void 0 : _babelHelpers$classPr5.destroy();
	  babelHelpers.classPrivateFieldLooseBase(this, _uploader)[_uploader] = null;
	  if (babelHelpers.classPrivateFieldLooseBase(this, _previewApplied)[_previewApplied]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _listDialog)[_listDialog].applyTheme(babelHelpers.classPrivateFieldLooseBase(this, _origAppliedThemeId)[_origAppliedThemeId]);
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _removeThemePreview)[_removeThemePreview]();
	  babelHelpers.classPrivateFieldLooseBase(this, _themePicker$1)[_themePicker$1].enableThemeListDialog();
	}
	function _getBgImage2() {
	  return this.getUploader().getFiles()[0] || null;
	}
	function _getBgImageUrl2() {
	  var _babelHelpers$classPr6;
	  return ((_babelHelpers$classPr6 = babelHelpers.classPrivateFieldLooseBase(this, _getBgImage)[_getBgImage]()) == null ? void 0 : _babelHelpers$classPr6.getPreviewUrl()) || null;
	}
	function _getBgImageBlurred2() {
	  var _babelHelpers$classPr7;
	  return ((_babelHelpers$classPr7 = babelHelpers.classPrivateFieldLooseBase(this, _getBgImage)[_getBgImage]()) == null ? void 0 : _babelHelpers$classPr7.getCustomData('bgImageBlurred')) || null;
	}
	function _getBgImageBlurredUrl2() {
	  var _babelHelpers$classPr8;
	  return ((_babelHelpers$classPr8 = babelHelpers.classPrivateFieldLooseBase(this, _getBgImage)[_getBgImage]()) == null ? void 0 : _babelHelpers$classPr8.getCustomData('bgImageBlurredUrl')) || null;
	}
	function _getBgColor2() {
	  const color = babelHelpers.classPrivateFieldLooseBase(this, _getControl)[_getControl]('field-bg-color').value;
	  return babelHelpers.classPrivateFieldLooseBase(this, _validateBgColor)[_validateBgColor](color) ? color : null;
	}
	function _getTextColor2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _getControl)[_getControl]('field-text-color').value;
	}
	function _getControl2(name) {
	  return babelHelpers.classPrivateFieldLooseBase(this, _getPopup)[_getPopup]().getContentContainer().querySelector(`.theme-dialog-${name}`);
	}
	function _getControls2(name) {
	  return babelHelpers.classPrivateFieldLooseBase(this, _getPopup)[_getPopup]().getContentContainer().querySelectorAll(`.theme-dialog-${name}`);
	}
	function _getPopup2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup]) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup];
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup] = new main_popup.Popup({
	    width: 500,
	    height: 560,
	    fixed: true,
	    titleBar: main_core.Loc.getMessage('BITRIX24_THEME_CREATE_YOUR_OWN_THEME'),
	    closeByEsc: true,
	    bindOnResize: false,
	    closeIcon: true,
	    draggable: true,
	    cacheable: false,
	    events: {
	      onClose: () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _resetResources)[_resetResources]();
	      },
	      onDestroy: () => {
	        this.emit('onDestroy');
	      }
	    },
	    buttons: [new main_popup.PopupWindowButton({
	      id: 'theme-dialog-create-button',
	      text: main_core.Loc.getMessage('BITRIX24_THEME_DIALOG_CREATE_BUTTON'),
	      className: 'popup-window-button-accept',
	      events: {
	        click: babelHelpers.classPrivateFieldLooseBase(this, _handleCreateButtonClick)[_handleCreateButtonClick].bind(this)
	      }
	    }), new main_popup.PopupWindowButtonLink({
	      text: main_core.Loc.getMessage('BITRIX24_THEME_DIALOG_CANCEL_BUTTON'),
	      className: 'popup-window-button-link theme-dialog-button-link',
	      events: {
	        click: () => {
	          babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup].close();
	        }
	      }
	    })]
	  });
	  return babelHelpers.classPrivateFieldLooseBase(this, _popup)[_popup];
	}
	function _getColorPicker2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _colorPicker)[_colorPicker]) {
	    return babelHelpers.classPrivateFieldLooseBase(this, _colorPicker)[_colorPicker];
	  }
	  const ColorPicker = main_core.Reflection.getClass('BX.ColorPicker');
	  if (ColorPicker) {
	    babelHelpers.classPrivateFieldLooseBase(this, _colorPicker)[_colorPicker] = new ColorPicker({
	      onColorSelected: babelHelpers.classPrivateFieldLooseBase(this, _handleBgColorSelect)[_handleBgColorSelect].bind(this),
	      popupOptions: {
	        fixed: true
	      }
	    });
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _colorPicker)[_colorPicker];
	}
	function _handleCreateButtonClick2() {
	  const error = babelHelpers.classPrivateFieldLooseBase(this, _validateForm)[_validateForm]();
	  if (error !== null) {
	    babelHelpers.classPrivateFieldLooseBase(this, _showError)[_showError](error);
	    return;
	  }
	  const createButton = babelHelpers.classPrivateFieldLooseBase(this, _getPopup)[_getPopup]().getButton('theme-dialog-create-button');
	  if (main_core.Dom.hasClass(createButton.getContainer(), 'popup-window-button-wait')) {
	    // double click protection
	    return;
	  }
	  const data = new FormData();
	  data.append('bgImage', babelHelpers.classPrivateFieldLooseBase(this, _getBgImage)[_getBgImage]() ? babelHelpers.classPrivateFieldLooseBase(this, _getBgImage)[_getBgImage]().getClientPreview() : '');
	  data.append('bgImageBlurred', babelHelpers.classPrivateFieldLooseBase(this, _getBgImageBlurred)[_getBgImageBlurred]() || '');
	  data.append('bgColor', babelHelpers.classPrivateFieldLooseBase(this, _getBgColor)[_getBgColor]() || '');
	  data.append('textColor', babelHelpers.classPrivateFieldLooseBase(this, _getTextColor)[_getTextColor]() || '');
	  data.append('action', 'create');
	  data.append('sessid', main_core.Loc.getMessage('bitrix_sessid'));
	  data.append('siteId', babelHelpers.classPrivateFieldLooseBase(this, _themePicker$1)[_themePicker$1].getSiteId());
	  data.append('templateId', babelHelpers.classPrivateFieldLooseBase(this, _themePicker$1)[_themePicker$1].getTemplateId());
	  let postSize = 0;
	  for (const [key, value] of data.entries()) {
	    postSize += key.length;
	    postSize += value instanceof Blob ? value.size : value.length;
	  }
	  if (postSize > babelHelpers.classPrivateFieldLooseBase(this, _themePicker$1)[_themePicker$1].getMaxUploadSize()) {
	    const limit = babelHelpers.classPrivateFieldLooseBase(this, _themePicker$1)[_themePicker$1].getMaxUploadSize() / 1024 / 1024;
	    babelHelpers.classPrivateFieldLooseBase(this, _showError)[_showError](main_core.Loc.getMessage('BITRIX24_THEME_FILE_SIZE_EXCEEDED', {
	      '#LIMIT#': `${limit.toFixed(0)}Mb`
	    }));
	    return;
	  }
	  const form = document.forms['theme-new-theme-form'];
	  createButton.addClassName('popup-window-button-wait');
	  main_core.Dom.addClass(form, 'theme-dialog-form-disabled');
	  main_core.ajax({
	    url: babelHelpers.classPrivateFieldLooseBase(this, _themePicker$1)[_themePicker$1].getAjaxHandlerPath(),
	    method: 'POST',
	    dataType: 'json',
	    preparePost: false,
	    data,
	    onsuccess: response => {
	      if (response && response.success && response.theme) {
	        babelHelpers.classPrivateFieldLooseBase(this, _listDialog)[_listDialog].addItem(response.theme);
	        babelHelpers.classPrivateFieldLooseBase(this, _listDialog)[_listDialog].preloadTheme(response.theme.id, () => {
	          createButton.removeClassName('popup-window-button-wait');
	          main_core.Dom.removeClass(form, 'theme-dialog-form-disabled');
	          babelHelpers.classPrivateFieldLooseBase(this, _removeThemePreview)[_removeThemePreview]();
	          babelHelpers.classPrivateFieldLooseBase(this, _getPopup)[_getPopup]().close();
	        });
	      } else {
	        createButton.removeClassName('popup-window-button-wait');
	        main_core.Dom.removeClass(form, 'theme-dialog-form-disabled');
	        babelHelpers.classPrivateFieldLooseBase(this, _showError)[_showError](response.error || main_core.Loc.getMessage('BITRIX24_THEME_UNKNOWN_ERROR'));
	      }
	    },
	    onfailure: () => {
	      createButton.removeClassName('popup-window-button-wait');
	      main_core.Dom.removeClass(form, 'theme-dialog-form-disabled');
	      babelHelpers.classPrivateFieldLooseBase(this, _showError)[_showError](main_core.Loc.getMessage('BITRIX24_THEME_UNKNOWN_ERROR'));
	    }
	  });
	}
	function _getContent2() {
	  return main_core.Tag.render(_t$1 || (_t$1 = _$1`
			<form name="theme-new-theme-form" method="post" onsubmit="${0}">
				<div class="theme-dialog-form-alert">
					<div class="theme-dialog-form-alert-content"></div>
					<div
						class="theme-dialog-form-alert-remove"
						onclick="${0}"
					></div>
				</div>
				<div class="theme-dialog-form">
					${0}
					${0}
					${0}
				</div>
			</form>
		`), event => event.preventDefault(), babelHelpers.classPrivateFieldLooseBase(this, _hideError)[_hideError].bind(this), babelHelpers.classPrivateFieldLooseBase(this, _createField)[_createField](main_core.Loc.getMessage('BITRIX24_THEME_THEME_BG_IMAGE'), babelHelpers.classPrivateFieldLooseBase(this, _getBgImageField)[_getBgImageField]()), babelHelpers.classPrivateFieldLooseBase(this, _createField)[_createField](main_core.Loc.getMessage('BITRIX24_THEME_THEME_BG_COLOR'), babelHelpers.classPrivateFieldLooseBase(this, _getBgColorField)[_getBgColorField]()), babelHelpers.classPrivateFieldLooseBase(this, _createField)[_createField](main_core.Loc.getMessage('BITRIX24_THEME_THEME_TEXT_COLOR'), babelHelpers.classPrivateFieldLooseBase(this, _getTextColorField)[_getTextColorField]()));
	}
	function _showError2(error) {
	  main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _getControl)[_getControl]('form-alert'), 'theme-dialog-form-alert-show');
	  babelHelpers.classPrivateFieldLooseBase(this, _getControl)[_getControl]('form-alert-content').innerHTML = error;
	}
	function _hideError2() {
	  main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _getControl)[_getControl]('form-alert'), 'theme-dialog-form-alert-show');
	}
	function _createField2(title, field) {
	  return main_core.Tag.render(_t2$1 || (_t2$1 = _$1`
			<div class="theme-dialog-field">
				<div class="theme-dialog-field-label">${0}</div>
				<div class="theme-dialog-field-value">${0}</div>
			</div>
		`), title, field);
	}
	function _getBgColorField2() {
	  return main_core.Tag.render(_t3$1 || (_t3$1 = _$1`
			<div class="theme-dialog-field-textbox-wrapper" onclick="${0}">
				<div class="theme-dialog-field-textbox-color"></div>
				<input
					type="text"
					placeholder=""
					name="bgColor"
					maxlength="7"
					class="theme-dialog-field-textbox theme-dialog-field-bg-color"
					oninput="${0}"
					autocomplete="off"
				>
				<div
					class="theme-dialog-field-textbox-remove"
					onclick="${0}"
				></div>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _handleBgColorClick)[_handleBgColorClick].bind(this), babelHelpers.classPrivateFieldLooseBase(this, _handleBgColorChange)[_handleBgColorChange].bind(this), babelHelpers.classPrivateFieldLooseBase(this, _handleBgColorClear)[_handleBgColorClear].bind(this));
	}
	function _getBgImageField2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _refs)[_refs].remember('image-field', () => {
	    const imageField = main_core.Tag.render(_t4 || (_t4 = _$1`
				<div class="theme-dialog-field-file">
					<div
						class="theme-dialog-field-button"
						ondragenter="${0}"
						ondragleave="${0}"
						ondragover="${0}"
					>
						<div class="theme-dialog-field-file-preview"></div>
						<div class="theme-dialog-field-file-text">
							<span class="theme-dialog-field-file-add">
								${0}
							</span>
							<span class="theme-dialog-field-file-add-info">
								${0}
							</span>
						</div>
					</div>
				</div>
			`), babelHelpers.classPrivateFieldLooseBase(this, _handleBgImageEnter)[_handleBgImageEnter].bind(this), babelHelpers.classPrivateFieldLooseBase(this, _handleBgImageLeave)[_handleBgImageLeave].bind(this), babelHelpers.classPrivateFieldLooseBase(this, _handleBgImageOver)[_handleBgImageOver].bind(this), main_core.Loc.getMessage('BITRIX24_THEME_UPLOAD_BG_IMAGE'), main_core.Loc.getMessage('BITRIX24_THEME_DRAG_BG_IMAGE'));
	    this.getUploader().assignBrowse(imageField);
	    this.getUploader().assignDropzone(imageField);
	    return imageField;
	  });
	}
	function _validateForm2() {
	  const bgImage = babelHelpers.classPrivateFieldLooseBase(this, _getBgImage)[_getBgImage]();
	  const bgColor = babelHelpers.classPrivateFieldLooseBase(this, _getControl)[_getControl]('field-bg-color').value;
	  if (main_core.Type.isStringFilled(bgColor) && !babelHelpers.classPrivateFieldLooseBase(this, _validateBgColor)[_validateBgColor](bgColor)) {
	    return main_core.Loc.getMessage('BITRIX24_THEME_WRONG_BG_COLOR');
	  }
	  if (!bgImage && !main_core.Type.isStringFilled(bgColor)) {
	    return main_core.Loc.getMessage('BITRIX24_THEME_EMPTY_FORM_DATA');
	  }
	  if (bgImage && bgImage.isFailed()) {
	    return `${bgImage.getError().getMessage()}<br>${bgImage.getError().getDescription()}`;
	  }
	  return null;
	}
	function _validateBgColor2(color) {
	  return main_core.Type.isStringFilled(color) && color.match(/^#([\dA-Fa-f]{6})$/);
	}
	function _showPreview2(previewUrl) {
	  const preview = babelHelpers.classPrivateFieldLooseBase(this, _getControl)[_getControl]('field-file-preview');
	  main_core.Dom.style(preview, 'background-image', `url("${encodeURI(previewUrl)}")`);
	}
	function _hidePreview2() {
	  const preview = babelHelpers.classPrivateFieldLooseBase(this, _getControl)[_getControl]('field-file-preview');
	  main_core.Dom.style(preview, 'background-image', null);
	}
	function _handleBgImageEnter2(event) {
	  main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _getControl)[_getControl]('field-button'), 'theme-dialog-field-button-hover');
	  event.stopPropagation();
	  event.preventDefault();
	}
	function _handleBgImageLeave2(event) {
	  main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _getControl)[_getControl]('field-button'), 'theme-dialog-field-button-hover');
	  event.stopPropagation();
	  event.preventDefault();
	}
	function _handleBgImageOver2(event) {
	  event.stopPropagation();
	  event.preventDefault();
	}
	function _handleBgColorClick2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _getColorPicker)[_getColorPicker]().open({
	    bindElement: babelHelpers.classPrivateFieldLooseBase(this, _getControl)[_getControl]('field-bg-color')
	  });
	}
	function _handleBgColorChange2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _getBgColor)[_getBgColor]()) {
	    babelHelpers.classPrivateFieldLooseBase(this, _hideError)[_hideError]();
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _applyThemePreview)[_applyThemePreview]();
	}
	function _handleBgColorClear2(event) {
	  babelHelpers.classPrivateFieldLooseBase(this, _getColorPicker)[_getColorPicker]().close();
	  main_core.Dom.removeClass(babelHelpers.classPrivateFieldLooseBase(this, _getControl)[_getControl]('field-bg-color'), 'theme-dialog-field-textbox-not-empty');
	  babelHelpers.classPrivateFieldLooseBase(this, _getControl)[_getControl]('field-bg-color').value = '';
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _getControl)[_getControl]('field-textbox-color'), 'backgroundColor', null);
	  babelHelpers.classPrivateFieldLooseBase(this, _applyThemePreview)[_applyThemePreview]();
	  event.stopPropagation();
	}
	function _handleBgColorSelect2(color) {
	  babelHelpers.classPrivateFieldLooseBase(this, _setBgColor)[_setBgColor](color);
	  babelHelpers.classPrivateFieldLooseBase(this, _hideError)[_hideError]();
	  babelHelpers.classPrivateFieldLooseBase(this, _applyThemePreview)[_applyThemePreview]();
	}
	function _setBgColor2(color) {
	  babelHelpers.classPrivateFieldLooseBase(this, _getControl)[_getControl]('field-bg-color').value = color;
	  main_core.Dom.addClass(babelHelpers.classPrivateFieldLooseBase(this, _getControl)[_getControl]('field-bg-color'), 'theme-dialog-field-textbox-not-empty');
	  main_core.Dom.style(babelHelpers.classPrivateFieldLooseBase(this, _getControl)[_getControl]('field-textbox-color'), 'backgroundColor', color);
	}
	function _setTextColor2(color) {
	  const switchers = babelHelpers.classPrivateFieldLooseBase(this, _getControls)[_getControls]('field-button-switcher-item');
	  [].forEach.call(switchers, switcher => {
	    if (switcher.dataset.textColor === color) {
	      main_core.Dom.addClass(switcher, 'theme-dialog-field-button-switcher-item-pressed');
	    } else {
	      main_core.Dom.removeClass(switcher, 'theme-dialog-field-button-switcher-item-pressed');
	    }
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _getControl)[_getControl]('field-text-color').value = color;
	}
	function _getTextColorField2() {
	  return main_core.Tag.render(_t5 || (_t5 = _$1`
			<div class="theme-dialog-field-button-switcher">
				<div
					class="theme-dialog-field-button-switcher-item theme-dialog-field-button-switcher-item-left theme-dialog-field-button-switcher-item-pressed"
					data-text-color="light"
					onclick="${0}"
				>
					${0}
				</div>
				<div
					class="theme-dialog-field-button-switcher-item theme-dialog-field-button-switcher-item-right"
					data-text-color="dark"
					onclick="${0}"
				>
					${0}
				</div>
				<input
					type="hidden"
					name="textColor"
					value="light"
					class="theme-dialog-field-text-color"
				>
			</div>
		`), babelHelpers.classPrivateFieldLooseBase(this, _handleSwitcherClick)[_handleSwitcherClick].bind(this), main_core.Loc.getMessage('BITRIX24_THEME_THEME_LIGHT_COLOR'), babelHelpers.classPrivateFieldLooseBase(this, _handleSwitcherClick)[_handleSwitcherClick].bind(this), main_core.Loc.getMessage('BITRIX24_THEME_THEME_DARK_COLOR'));
	}
	function _handleSwitcherClick2(event) {
	  const color = event.target.dataset.textColor;
	  babelHelpers.classPrivateFieldLooseBase(this, _setTextColor)[_setTextColor](color);
	  babelHelpers.classPrivateFieldLooseBase(this, _applyThemePreview)[_applyThemePreview]();
	}
	function _applyThemePreview2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _getBgImage)[_getBgImage]() === null && babelHelpers.classPrivateFieldLooseBase(this, _getBgColor)[_getBgColor]() === null) {
	    if (babelHelpers.classPrivateFieldLooseBase(this, _previewApplied)[_previewApplied]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _listDialog)[_listDialog].applyTheme(babelHelpers.classPrivateFieldLooseBase(this, _origAppliedThemeId)[_origAppliedThemeId]);
	    }
	    return;
	  }
	  const baseThemes = babelHelpers.classPrivateFieldLooseBase(this, _themePicker$1)[_themePicker$1].getBaseThemes();
	  const baseThemeId = babelHelpers.classPrivateFieldLooseBase(this, _getTextColor)[_getTextColor]();
	  if (!baseThemes[baseThemeId] || !main_core.Type.isArray(baseThemes[baseThemeId].css)) {
	    return;
	  }
	  let style = ':root { ';
	  if (babelHelpers.classPrivateFieldLooseBase(this, _getBgImageUrl)[_getBgImageUrl]() !== null) {
	    style += '--air-theme-bg-size: cover;';
	    style += '--air-theme-bg-repeat: no-repeat;';
	    style += '--air-theme-bg-position: 0 0;';
	    style += '--air-theme-bg-attachment: fixed;';
	    style += `--air-theme-bg-image: url("${encodeURI(babelHelpers.classPrivateFieldLooseBase(this, _getBgImageUrl)[_getBgImageUrl]())}");`;
	    if (babelHelpers.classPrivateFieldLooseBase(this, _getBgImageBlurredUrl)[_getBgImageBlurredUrl]() !== null) {
	      style += `--air-theme-bg-image-blurred: url("${encodeURI(babelHelpers.classPrivateFieldLooseBase(this, _getBgImageBlurredUrl)[_getBgImageBlurredUrl]())}");`;
	    }
	  }
	  if (babelHelpers.classPrivateFieldLooseBase(this, _getBgColor)[_getBgColor]()) {
	    style += `--air-theme-bg-color: ${babelHelpers.classPrivateFieldLooseBase(this, _getBgColor)[_getBgColor]()}; `;
	  }
	  style += ' }';
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _previewApplied)[_previewApplied]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _origAppliedThemeId)[_origAppliedThemeId] = babelHelpers.classPrivateFieldLooseBase(this, _themePicker$1)[_themePicker$1].getAppliedThemeId();
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _listDialog)[_listDialog].removeThemeAssets(babelHelpers.classPrivateFieldLooseBase(this, _themePicker$1)[_themePicker$1].getAppliedThemeId());
	  babelHelpers.classPrivateFieldLooseBase(this, _listDialog)[_listDialog].applyThemeAssets({
	    id: babelHelpers.classPrivateFieldLooseBase(this, _getPreviewThemeId)[_getPreviewThemeId](),
	    css: baseThemes[baseThemeId].css,
	    style
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _themePicker$1)[_themePicker$1].setAppliedThemeId(babelHelpers.classPrivateFieldLooseBase(this, _getPreviewThemeId)[_getPreviewThemeId]());
	  babelHelpers.classPrivateFieldLooseBase(this, _previewApplied)[_previewApplied] = true;
	}
	function _removeThemePreview2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _listDialog)[_listDialog].removeThemeAssets(babelHelpers.classPrivateFieldLooseBase(this, _getPreviewThemeId)[_getPreviewThemeId]());
	  babelHelpers.classPrivateFieldLooseBase(this, _previewApplied)[_previewApplied] = false;
	}
	function _getPreviewThemeId2() {
	  return `${babelHelpers.classPrivateFieldLooseBase(this, _getTextColor)[_getTextColor]()}:custom_live_preview`;
	}
	function _chooseTextColor2(color) {
	  let hexColor = color.replace(/^#/, '');
	  if (hexColor.length === 3) {
	    hexColor = [...hexColor].map(c => c + c).join('');
	  }
	  const r = parseInt(hexColor.slice(0, 2), 16);
	  const g = parseInt(hexColor.slice(2, 4), 16);
	  const b = parseInt(hexColor.slice(4, 6), 16);
	  const brightness = (r * 299 + g * 587 + b * 114) / 1000;
	  return brightness > 186 ? 'dark' : 'light';
	}

	let _$2 = t => t,
	  _t$2,
	  _t2$2,
	  _t3$2,
	  _t4$1,
	  _t5$1,
	  _t6,
	  _t7,
	  _t8,
	  _t9,
	  _t10,
	  _t11,
	  _t12;
	var _themePicker$2 = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("themePicker");
	var _newThemeDialog = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("newThemeDialog");
	var _checkboxButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("checkboxButton");
	var _loadImage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadImage");
	var _urnEncode = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("urnEncode");
	var _ajax = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("ajax");
	var _loadThemes = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("loadThemes");
	var _setButtons = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("setButtons");
	var _getCheckboxButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCheckboxButton");
	var _showFatalError = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("showFatalError");
	var _hideLoader = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("hideLoader");
	var _getSaveButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getSaveButton");
	var _handleSaveButtonClick = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("handleSaveButtonClick");
	var _isCheckboxChecked = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("isCheckboxChecked");
	var _getCancelButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCancelButton");
	var _getCreateButton = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("getCreateButton");
	var _renderLayout = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("renderLayout");
	var _saveTheme = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("saveTheme");
	var _createDefaultLabel = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createDefaultLabel");
	var _createNewLabel = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("createNewLabel");
	var _selectItem = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("selectItem");
	var _preloadCss = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("preloadCss");
	class ThemePickerDialog {
	  constructor(themePicker) {
	    Object.defineProperty(this, _preloadCss, {
	      value: _preloadCss2
	    });
	    Object.defineProperty(this, _selectItem, {
	      value: _selectItem2
	    });
	    Object.defineProperty(this, _createNewLabel, {
	      value: _createNewLabel2
	    });
	    Object.defineProperty(this, _createDefaultLabel, {
	      value: _createDefaultLabel2
	    });
	    Object.defineProperty(this, _saveTheme, {
	      value: _saveTheme2
	    });
	    Object.defineProperty(this, _renderLayout, {
	      value: _renderLayout2
	    });
	    Object.defineProperty(this, _getCreateButton, {
	      value: _getCreateButton2
	    });
	    Object.defineProperty(this, _getCancelButton, {
	      value: _getCancelButton2
	    });
	    Object.defineProperty(this, _isCheckboxChecked, {
	      value: _isCheckboxChecked2
	    });
	    Object.defineProperty(this, _handleSaveButtonClick, {
	      value: _handleSaveButtonClick2
	    });
	    Object.defineProperty(this, _getSaveButton, {
	      value: _getSaveButton2
	    });
	    Object.defineProperty(this, _hideLoader, {
	      value: _hideLoader2
	    });
	    Object.defineProperty(this, _showFatalError, {
	      value: _showFatalError2
	    });
	    Object.defineProperty(this, _getCheckboxButton, {
	      value: _getCheckboxButton2
	    });
	    Object.defineProperty(this, _setButtons, {
	      value: _setButtons2
	    });
	    Object.defineProperty(this, _loadThemes, {
	      value: _loadThemes2
	    });
	    Object.defineProperty(this, _ajax, {
	      value: _ajax2
	    });
	    Object.defineProperty(this, _urnEncode, {
	      value: _urnEncode2
	    });
	    Object.defineProperty(this, _loadImage, {
	      value: _loadImage2
	    });
	    Object.defineProperty(this, _themePicker$2, {
	      writable: true,
	      value: void 0
	    });
	    Object.defineProperty(this, _newThemeDialog, {
	      writable: true,
	      value: null
	    });
	    Object.defineProperty(this, _checkboxButton, {
	      writable: true,
	      value: null
	    });
	    babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2] = themePicker;
	  }
	  show() {
	    babelHelpers.classPrivateFieldLooseBase(this, _loadThemes)[_loadThemes]();
	  }
	  close() {
	    var _babelHelpers$classPr;
	    (_babelHelpers$classPr = babelHelpers.classPrivateFieldLooseBase(this, _newThemeDialog)[_newThemeDialog]) == null ? void 0 : _babelHelpers$classPr.destroy();
	    babelHelpers.classPrivateFieldLooseBase(this, _newThemeDialog)[_newThemeDialog] = null;
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].needReturnValue()) {
	      this.applyTheme(babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].getThemeId());
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].setThemes([]);
	  }
	  async makeCustomThemeBlurred(theme, options = {}) {
	    var _localStorage$get;
	    if (!main_core.Type.isStringFilled(theme.bgImageUrlToBlur)) {
	      return Promise.resolve();
	    }
	    const lsKey = 'custom_themes_errors';
	    const localStorage = main_core.Reflection.getClass('BX.localStorage');
	    if (localStorage && (_localStorage$get = localStorage.get(lsKey)) != null && _localStorage$get.includes(theme.id)) {
	      console.log('Skipping blurred image for theme', theme.id);
	      return Promise.resolve();
	    }
	    const saveError = (timeout = 3600) => {
	      if (localStorage) {
	        const errorCustomThemes = localStorage.get(lsKey) || '';
	        localStorage.set(lsKey, `${errorCustomThemes}|${theme.id}`, timeout);
	      }
	    };
	    let bgImageBlurred = null;
	    let color = null;
	    try {
	      const {
	        image
	      } = await babelHelpers.classPrivateFieldLooseBase(this, _loadImage)[_loadImage](theme.bgImageUrlToBlur);
	      const result = await blur(image, 7);
	      bgImageBlurred = result.file;
	      color = result.color;
	    } catch (error) {
	      saveError();
	      return Promise.reject(error);
	    }
	    const {
	      ignoreErrors = false,
	      applyTheme = false
	    } = options;
	    const data = new FormData();
	    data.append('action', 'save-blurred-image');
	    data.append('themeId', theme.id);
	    data.append('bgImageBlurred', bgImageBlurred);
	    data.append('bgColor', color);
	    data.append('ignoreErrors', ignoreErrors);
	    return babelHelpers.classPrivateFieldLooseBase(this, _ajax)[_ajax](data).then(response => {
	      if (response && response.success && response.theme) {
	        babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].updateTheme(response.theme.id, {
	          bgImageUrlToBlur: null
	        });
	        babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].updateTheme(response.theme.id, response.theme);
	        this.preloadTheme(response.theme.id, () => {
	          if (applyTheme) {
	            this.removeThemeAssets(babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].getAppliedThemeId());
	            this.applyThemeAssets(response.theme);
	          }
	        });
	      } else {
	        console.log('Error while saving blurred image for theme', theme.id);
	        saveError();
	      }
	    }).catch(error => {
	      console.error(error);
	      saveError();
	    });
	  }
	  addItem(theme) {
	    const themes = babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].getThemes();
	    themes.unshift(theme);
	    babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].setThemes(themes);
	    const newItem = this.createItem(theme);
	    main_core.Dom.prepend(newItem, this.getContentContainer());
	    babelHelpers.classPrivateFieldLooseBase(this, _selectItem)[_selectItem](newItem);
	  }
	  createItem(theme) {
	    let className = 'theme-dialog-item';
	    if (theme.video) {
	      className += ' theme-dialog-item-video';
	    }
	    if (babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].getAppliedThemeId() === theme.id) {
	      className += ' theme-dialog-item-selected';
	    }
	    const div = main_core.Tag.render(_t$2 || (_t$2 = _$2`
			<div
				class="${0}"
				data-theme-id="${0}"
				onclick="${0}"
			>
				<div class="theme-dialog-item-title">
					<span class="theme-dialog-item-title-text">${0}</span>
					${0}
				</div>
				${0}
				${0}
			</div>
		`), className, theme.id, this.handleItemClick.bind(this), theme.title, theme.removable ? main_core.Tag.render(_t2$2 || (_t2$2 = _$2`
						<div
							class="theme-dialog-item-remove"
							data-theme-id="${0}"
							title="${0}"
							onclick="${0}"
						></div>
					`), theme.id, main_core.Loc.getMessage('BITRIX24_THEME_REMOVE_THEME'), this.handleRemoveBtnClick.bind(this)) : '', theme.default === true ? babelHelpers.classPrivateFieldLooseBase(this, _createDefaultLabel)[_createDefaultLabel]() : '', theme.new === true ? babelHelpers.classPrivateFieldLooseBase(this, _createNewLabel)[_createNewLabel]() : '');
	    if (main_core.Type.isStringFilled(theme.previewImage)) {
	      const img = main_core.Tag.render(_t3$2 || (_t3$2 = _$2`
				<img 
					src="${0}" 
					alt="${0}" 
					class="theme-dialog-item-preview-img" 
					loading="lazy"
				>
			`), encodeURI(theme.previewImage), theme.title);
	      main_core.Dom.append(img, div);
	    }
	    if (main_core.Type.isStringFilled(theme.previewColor)) {
	      main_core.Dom.style(div, 'background-color', theme.previewColor);
	    }
	    return div;
	  }
	  getNewThemeDialog() {
	    if (!babelHelpers.classPrivateFieldLooseBase(this, _newThemeDialog)[_newThemeDialog]) {
	      babelHelpers.classPrivateFieldLooseBase(this, _newThemeDialog)[_newThemeDialog] = new NewThemeDialog(babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2], this);
	      babelHelpers.classPrivateFieldLooseBase(this, _newThemeDialog)[_newThemeDialog].subscribe('onDestroy', () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _newThemeDialog)[_newThemeDialog] = null;
	      });
	    }
	    return babelHelpers.classPrivateFieldLooseBase(this, _newThemeDialog)[_newThemeDialog];
	  }
	  handleItemClick(event) {
	    babelHelpers.classPrivateFieldLooseBase(this, _selectItem)[_selectItem](this.getItemNode(event));
	  }
	  handleRemoveBtnClick(event) {
	    const item = this.getItemNode(event);
	    if (!item) {
	      return;
	    }
	    const themeId = item.dataset.themeId;
	    const theme = babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].getTheme(themeId);
	    if (theme && theme.default) {
	      const defaultThemeItem = this.getContentContainer().querySelector('[data-theme-id="default"]');
	      if (defaultThemeItem) {
	        main_core.Dom.append(babelHelpers.classPrivateFieldLooseBase(this, _createDefaultLabel)[_createDefaultLabel](), defaultThemeItem);
	      }
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].removeTheme(themeId);
	    main_core.Dom.remove(item);
	    if (babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].getAppliedThemeId() === themeId) {
	      const firstItem = this.getContentContainer().children[0];
	      babelHelpers.classPrivateFieldLooseBase(this, _selectItem)[_selectItem](firstItem);
	      if (babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].getThemeId() === themeId && firstItem && firstItem.dataset.themeId) {
	        babelHelpers.classPrivateFieldLooseBase(this, _saveTheme)[_saveTheme](firstItem.dataset.themeId);
	      }
	    } else if (babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].getThemeId() === themeId) {
	      babelHelpers.classPrivateFieldLooseBase(this, _saveTheme)[_saveTheme](babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].getAppliedThemeId());
	    }
	    void babelHelpers.classPrivateFieldLooseBase(this, _ajax)[_ajax]({
	      action: 'remove',
	      themeId
	    });
	    event.stopPropagation();
	  }
	  getItemNode(event) {
	    if (!event || !event.target) {
	      return null;
	    }
	    const item = event.target.closest('.theme-dialog-item');
	    return main_core.Type.isDomNode(item) ? item : null;
	  }
	  getContentContainer() {
	    return babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].getDialogPopup().getContentContainer().querySelector('.theme-dialog-content');
	  }
	  applyTheme(themeId) {
	    if (!main_core.Type.isStringFilled(themeId) || themeId === babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].getAppliedThemeId()) {
	      return;
	    }
	    const theme = babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].getTheme(themeId);
	    if (!theme) {
	      return;
	    }
	    const eventData = {
	      id: themeId,
	      theme
	    };
	    main_core_events.EventEmitter.emit('BX.Intranet.Bitrix24:ThemePicker:onThemeApply', new main_core_events.BaseEvent({
	      data: eventData,
	      compatData: [eventData]
	    }));
	    this.applyThemeAssets(theme);
	    this.removeThemeAssets(babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].getAppliedThemeId());
	    babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].setAppliedThemeId(themeId);
	    this.appliedTheme = theme;
	    main_core_events.EventEmitter.emit('BX.Intranet.Bitrix24:ThemePicker:onThemeAfterApply', {
	      id: themeId,
	      theme
	    });
	  }
	  applyThemeAssets(assets) {
	    if (!assets || !main_core.Type.isArray(assets.css) || !main_core.Type.isStringFilled(assets.id)) {
	      return;
	    }
	    const head = document.head;
	    const themeId = assets.id;
	    assets.css.forEach(file => {
	      const link = main_core.Tag.render(_t4$1 || (_t4$1 = _$2`
				<link type="text/css" rel="stylesheet" href="${0}" data-theme-id="${0}">
			`), file, themeId);
	      head.appendChild(link);
	    });
	    if (main_core.Type.isStringFilled(assets.style)) {
	      const style = main_core.Tag.render(_t5$1 || (_t5$1 = _$2`
				<style type="text/css" data-theme-id="${0}">
					${0}
				</style>
			`), themeId, assets.style);
	      head.appendChild(style);
	    }
	    if (assets.video && main_core.Type.isPlainObject(assets.video.sources)) {
	      const sources = [];
	      for (const type in assets.video.sources) {
	        sources.push(main_core.Tag.render(_t6 || (_t6 = _$2`<source type="video/${0}" src="${0}">`), type, assets.video.sources[type]));
	      }
	      const video = main_core.Tag.render(_t7 || (_t7 = _$2`
				<div class="theme-video-container" data-theme-id="${0}">
					<video
						class="theme-video"
						poster="${0}"
						autoplay
						loop
						muted
						playsinline
						data-theme-id="${0}"
					>
						${0}
					</video>
				</div>
			`), themeId, assets.video.poster, themeId, sources);
	      document.body.insertBefore(video, document.body.firstElementChild);
	    }
	    const appliedBaseThemeId = babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].getAppliedThemeId().split(':')[0];
	    const baseThemeId = themeId.split(':')[0];
	    const contexts = {
	      light: '--ui-context-edge-dark',
	      dark: '--ui-context-edge-light',
	      default: '--ui-context-edge-light'
	    };
	    if (appliedBaseThemeId !== baseThemeId) {
	      main_core.Dom.removeClass(document.body, [`bitrix24-${appliedBaseThemeId}-theme`, contexts[appliedBaseThemeId]]);
	      main_core.Dom.addClass(document.body, [`bitrix24-${baseThemeId}-theme`, contexts[baseThemeId]]);
	    }
	  }
	  removeThemeAssets(themeId) {
	    const styles = document.head.querySelectorAll(`[data-theme-id="${themeId}"]`);
	    for (const style of styles) {
	      main_core.Dom.remove(style);
	    }
	    main_core.Dom.remove(document.querySelector(`body > [data-theme-id="${themeId}"]`));
	  }
	  preloadTheme(themeId, callback) {
	    const fn = main_core.Type.isFunction(callback) ? callback : () => {};
	    const theme = babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].getTheme(themeId);
	    if (!theme) {
	      fn();
	      return;
	    }
	    let asyncCount = 2; // preloadImages & preloadCss
	    const onload = () => {
	      asyncCount--;
	      if (asyncCount === 0) {
	        fn();
	      }
	    };
	    this.preloadImages(theme.prefetchImages, onload);
	    babelHelpers.classPrivateFieldLooseBase(this, _preloadCss)[_preloadCss](theme.css, onload);
	  }
	  preloadImages(images, callback) {
	    const fn = main_core.Type.isFunction(callback) ? callback : () => {};
	    if (!main_core.Type.isArrayFilled(images)) {
	      fn();
	      return;
	    }
	    let loaded = 0;
	    images.forEach(imageSrc => {
	      const image = new Image();
	      image.src = encodeURI(imageSrc);
	      image.onload = image.onerror = () => {
	        loaded++;
	        if (loaded === images.length) {
	          fn();
	        }
	      };
	    });
	  }
	}
	function _loadImage2(src) {
	  return new Promise((resolve, reject) => {
	    const image = document.createElement('img');
	    let url = src;
	    if (url.startsWith('http') && !url.startsWith(window.location.origin)) {
	      url = '/bitrix/tools/landing/proxy.php';
	      url += `?sessid=${main_core.Loc.getMessage('bitrix_sessid')}&url=${encodeURIComponent(babelHelpers.classPrivateFieldLooseBase(this, _urnEncode)[_urnEncode](src))}`;
	    }
	    image.src = url;
	    image.onerror = error => {
	      reject(error);
	    };
	    image.onload = () => {
	      resolve({
	        width: image.naturalWidth,
	        height: image.naturalHeight,
	        image
	      });
	    };
	  });
	}
	function _urnEncode2(str) {
	  let result = '';
	  const parts = str.split(/(:\/\/|:\d+\/|\/|\?|=|&)/);
	  for (const [i, part] of parts.entries()) {
	    result += i % 2 === 1 ? part : encodeURIComponent(part);
	  }
	  return result;
	}
	function _ajax2(data) {
	  const formData = main_core.Type.isFormData(data) ? data : new FormData();
	  if (main_core.Type.isPlainObject(data)) {
	    for (const [key, value] of Object.entries(data)) {
	      formData.append(key, value);
	    }
	  }
	  formData.append('sessid', main_core.Loc.getMessage('bitrix_sessid'));
	  formData.append('templateId', babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].getTemplateId());
	  formData.append('siteId', babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].getSiteId());
	  formData.append('entityType', babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].getEntityType());
	  formData.append('entityId', babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].getEntityId());
	  return new Promise((resolve, reject) => {
	    main_core.ajax({
	      method: 'POST',
	      dataType: 'json',
	      preparePost: false,
	      url: babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].getAjaxHandlerPath(),
	      data: formData,
	      onsuccess: resolve,
	      onfailure: reject
	    });
	  });
	}
	function _loadThemes2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _ajax)[_ajax]({
	    action: 'getlist'
	  }).then(data => {
	    if (!data || !data.success || !main_core.Type.isArray(data.themes) || data.themes.length === 0) {
	      babelHelpers.classPrivateFieldLooseBase(this, _showFatalError)[_showFatalError]();
	      return;
	    }
	    babelHelpers.classPrivateFieldLooseBase(this, _hideLoader)[_hideLoader]();
	    babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].setThemes(data.themes);
	    babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].setBaseThemes(data.baseThemes);
	    babelHelpers.classPrivateFieldLooseBase(this, _setButtons)[_setButtons]();
	    babelHelpers.classPrivateFieldLooseBase(this, _renderLayout)[_renderLayout]();
	  }).catch(() => {
	    babelHelpers.classPrivateFieldLooseBase(this, _showFatalError)[_showFatalError]();
	  });
	}
	function _setButtons2() {
	  const buttons = [];
	  if (babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].isCurrentUserAdmin() && babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].getEntityType() === 'USER') {
	    buttons.push(babelHelpers.classPrivateFieldLooseBase(this, _getCheckboxButton)[_getCheckboxButton]());
	  }
	  buttons.push(babelHelpers.classPrivateFieldLooseBase(this, _getSaveButton)[_getSaveButton](), babelHelpers.classPrivateFieldLooseBase(this, _getCancelButton)[_getCancelButton](), babelHelpers.classPrivateFieldLooseBase(this, _getCreateButton)[_getCreateButton]());
	  babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].getDialogPopup().setButtons(buttons);
	}
	function _getCheckboxButton2() {
	  if (!babelHelpers.classPrivateFieldLooseBase(this, _checkboxButton)[_checkboxButton]) {
	    babelHelpers.classPrivateFieldLooseBase(this, _checkboxButton)[_checkboxButton] = new CheckboxButton(babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2]);
	  }
	  return babelHelpers.classPrivateFieldLooseBase(this, _checkboxButton)[_checkboxButton];
	}
	function _showFatalError2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _hideLoader)[_hideLoader]();
	  babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].getDialogPopup().setContent(main_core.Loc.getMessage('BITRIX24_THEME_UNKNOWN_ERROR'));
	  babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].getDialogPopup().setButtons([babelHelpers.classPrivateFieldLooseBase(this, _getCancelButton)[_getCancelButton]()]);
	}
	function _hideLoader2() {
	  babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].hideLoader();
	}
	function _getSaveButton2() {
	  return new main_popup.PopupWindowButton({
	    id: 'save-button',
	    text: main_core.Loc.getMessage('BITRIX24_THEME_DIALOG_SAVE_BUTTON'),
	    className: 'popup-window-button-accept',
	    events: {
	      click: () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _handleSaveButtonClick)[_handleSaveButtonClick]();
	      }
	    }
	  });
	}
	function _handleSaveButtonClick2() {
	  if (babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].needReturnValue()) {
	    const eventData = {
	      theme: babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].getTheme(babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].getReturnValue())
	    };
	    main_core_events.EventEmitter.emit('Intranet.ThemePicker:onSave', new main_core_events.BaseEvent({
	      data: [eventData],
	      compatData: [eventData]
	    }));
	  } else if (babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].getThemeId() !== babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].getAppliedThemeId() || babelHelpers.classPrivateFieldLooseBase(this, _isCheckboxChecked)[_isCheckboxChecked]()) {
	    babelHelpers.classPrivateFieldLooseBase(this, _saveTheme)[_saveTheme](babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].getAppliedThemeId());
	  }
	  babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].closeDialog();
	}
	function _isCheckboxChecked2() {
	  return babelHelpers.classPrivateFieldLooseBase(this, _checkboxButton)[_checkboxButton] ? babelHelpers.classPrivateFieldLooseBase(this, _checkboxButton)[_checkboxButton].isChecked() : false;
	}
	function _getCancelButton2() {
	  return new main_popup.PopupWindowButtonLink({
	    id: 'cancel-button',
	    text: main_core.Loc.getMessage('BITRIX24_THEME_DIALOG_CANCEL_BUTTON'),
	    className: 'popup-window-button-link theme-dialog-button-link',
	    events: {
	      click: () => {
	        babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].closeDialog();
	      }
	    }
	  });
	}
	function _getCreateButton2() {
	  return new main_popup.PopupWindowButtonLink({
	    id: 'create-button',
	    text: main_core.Loc.getMessage('BITRIX24_THEME_DIALOG_NEW_THEME'),
	    className: 'popup-window-button-link theme-dialog-button-link theme-dialog-new-theme-btn',
	    events: {
	      click: () => {
	        this.getNewThemeDialog().show();
	      }
	    }
	  });
	}
	function _renderLayout2() {
	  const container = main_core.Tag.render(_t8 || (_t8 = _$2`
			<div class="theme-dialog-content"></div>
		`));
	  babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].getThemes().forEach(theme => {
	    main_core.Dom.append(this.createItem(theme), container);
	  });
	  babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].getDialogPopup().setContent(main_core.Tag.render(_t9 || (_t9 = _$2`
				<div class="theme-dialog-container">${0}</div>
			`), container));
	}
	function _saveTheme2(themeId) {
	  const theme = babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].getTheme(themeId);
	  const eventData = {
	    theme,
	    themeId,
	    setDefaultTheme: babelHelpers.classPrivateFieldLooseBase(this, _isCheckboxChecked)[_isCheckboxChecked]()
	  };
	  main_core_events.EventEmitter.emit('Intranet.ThemePicker:onSaveTheme', new main_core_events.BaseEvent({
	    data: eventData,
	    compatData: [eventData]
	  }));
	  const data = new FormData();
	  data.append('action', 'save');
	  data.append('themeId', themeId);
	  data.append('setDefaultTheme', babelHelpers.classPrivateFieldLooseBase(this, _isCheckboxChecked)[_isCheckboxChecked]());
	  void babelHelpers.classPrivateFieldLooseBase(this, _ajax)[_ajax](data);
	  babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].setThemeId(themeId);
	}
	function _createDefaultLabel2() {
	  return main_core.Tag.render(_t10 || (_t10 = _$2`
			<div class="theme-dialog-item-default">
				${0}
			</div>
		`), main_core.Loc.getMessage('BITRIX24_THEME_DEFAULT_THEME'));
	}
	function _createNewLabel2() {
	  return main_core.Tag.render(_t11 || (_t11 = _$2`
			<div class="theme-dialog-item-new">
				${0}
			</div>
		`), main_core.Loc.getMessage('BITRIX24_THEME_NEW_THEME'));
	}
	function _selectItem2(item) {
	  if (!main_core.Type.isDomNode(item) || !main_core.Dom.hasClass(item, 'theme-dialog-item')) {
	    return;
	  }
	  const themeId = item.dataset.themeId;
	  this.getContentContainer().querySelectorAll('div.theme-dialog-item[data-theme-id]').forEach(currentItem => main_core.Dom.removeClass(currentItem, 'theme-dialog-item-selected'));
	  main_core.Dom.addClass(item, 'theme-dialog-item-selected');
	  if (babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].needReturnValue()) {
	    babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].setReturnValue(themeId);
	  } else {
	    const loader = new main_loader.Loader({
	      size: 100
	    });
	    loader.show(item);
	    this.preloadTheme(themeId, async () => {
	      const theme = babelHelpers.classPrivateFieldLooseBase(this, _themePicker$2)[_themePicker$2].getTheme(themeId);
	      if (main_core.Type.isStringFilled(theme.bgImageUrlToBlur)) {
	        try {
	          await this.makeCustomThemeBlurred(theme);
	        } catch (ex) {
	          console.error(ex);
	        }
	      }
	      if (main_core.Dom.hasClass(item, 'theme-dialog-item-selected'))
	        // by this time user could select another theme
	        {
	          loader.hide();
	          this.applyTheme(themeId);
	        }
	    });
	  }
	}
	function _preloadCss2(css, fn) {
	  if (!main_core.Type.isArray(css)) {
	    if (main_core.Type.isFunction(fn)) {
	      fn();
	    }
	    return;
	  }
	  const iframe = main_core.Tag.render(_t12 || (_t12 = _$2`<iframe src="javascript:void(0)" style="display: none"></iframe>`));
	  document.body.appendChild(iframe);
	  const iframeDoc = iframe.contentWindow.document;
	  if (!iframeDoc.body) {
	    // null in IE
	    iframeDoc.write('<body></body>');
	  }

	  // to avoid a conflict between a theme's preload image and a <body>'s image from preload css files
	  iframeDoc.body.style.cssText = 'background: #fff !important';
	  BX.load(css, () => {
	    main_core.Dom.remove(iframe);
	    if (main_core.Type.isFunction(fn)) {
	      fn();
	    }
	  }, iframeDoc);
	}

	exports.ThemePickerDialog = ThemePickerDialog;

}((this.BX.Intranet.Bitrix24.ThemePicker = this.BX.Intranet.Bitrix24.ThemePicker || {}),BX.UI,BX.Cache,BX.Event,BX.Main,BX,BX,BX.UI.Uploader));
//# sourceMappingURL=dialog.bundle.js.map
