/* eslint-disable */
this.BX = this.BX || {};
this.BX.Humanresources = this.BX.Humanresources || {};
(function (exports,main_core) {
	'use strict';

	let _ = t => t,
	  _t;
	const appUrl = '/marketplace/detail/bitrix.1chrm/?ver=1&install=Y&hash=c2dddb1ce87267585b1dcfde4893cc77&check_hash=61c01468e268f41c6d1aba8a13b34e84&install_hash=445915b49fdf3c6b801bbb6c6b70e848';
	var _openAppPage = /*#__PURE__*/babelHelpers.classPrivateFieldLooseKey("openAppPage");
	class CompanyConnectPage {
	  constructor() {
	    Object.defineProperty(this, _openAppPage, {
	      value: _openAppPage2
	    });
	  }
	  static openSlider() {
	    BX.SidePanel.Instance.open('humanresources:hcmlink1c-slider', {
	      contentCallback: () => {
	        return BX.UI.SidePanel.Layout.createContent({
	          extensions: ['humanresources.hcmlink.company-connect-page'],
	          design: {
	            section: false,
	            margin: true
	          },
	          content: () => {
	            return new CompanyConnectPage().getLayout();
	          },
	          buttons() {
	            return [];
	          }
	        });
	      },
	      animationDuration: 200,
	      width: 920
	    });
	  }
	  getLayout() {
	    return main_core.Tag.render(_t || (_t = _`
			<div class="integration-slider-content">
				<div class="integration-slider-text">
				    <h2>${0}</h2>
				    <p>${0}</p>
				    <ul style="padding-bottom: 20px">
				        <li>${0}</li>
				        <li>${0}</li>
				        <li>${0}</li>
				        <li>${0}</li>
					</ul>
					<div>
						<button class="ui-btn ui-btn-success ui-btn-md ui-btn-round" 
								onclick="${0}">
							${0}	
					   </button>
		           </div>
				</div>
			</div>
		`), main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_CONNECTOR_PAGE_TITLE'), main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_CONNECTOR_PAGE_HEAD_DESCRIPTION'), main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_CONNECTOR_PAGE_PARAGRAPH_1'), main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_CONNECTOR_PAGE_PARAGRAPH_2'), main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_CONNECTOR_PAGE_PARAGRAPH_3'), main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_CONNECTOR_PAGE_PARAGRAPH_4'), babelHelpers.classPrivateFieldLooseBase(this, _openAppPage)[_openAppPage], main_core.Loc.getMessage('HUMANRESOURCES_HCMLINK_CONNECTOR_PAGE_ACTION_INSTALL'));
	  }
	}
	function _openAppPage2() {
	  BX.SidePanel.Instance.open(appUrl);
	}

	exports.CompanyConnectPage = CompanyConnectPage;

}((this.BX.Humanresources.Hcmlink = this.BX.Humanresources.Hcmlink || {}),BX));
//# sourceMappingURL=company-connect-page.bundle.js.map
