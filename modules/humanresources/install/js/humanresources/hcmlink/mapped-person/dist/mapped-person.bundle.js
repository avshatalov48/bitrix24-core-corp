/* eslint-disable */
this.BX = this.BX || {};
this.BX.Humanresources = this.BX.Humanresources || {};
(function (exports,humanresources_hcmlink_api,humanresources_hcmlink_companyConfig,main_core,ui_tour) {
	'use strict';

	class MappedPerson {
	  static async deleteLinkMappedPerson() {
	    var _top$BX$SidePanel$Ins;
	    const grid = BX.Main.gridManager.getInstanceById('hcmlink_mapped_users');
	    if (!grid) {
	      return;
	    }
	    const api = new humanresources_hcmlink_api.Api();
	    const mappingIds = grid.getRows().getSelectedIds();
	    await api.removeLinkMapped({
	      mappingIds
	    });
	    (_top$BX$SidePanel$Ins = top.BX.SidePanel.Instance.getSliderByWindow(window)) == null ? void 0 : _top$BX$SidePanel$Ins.reload();
	  }
	  static showGuide(config) {
	    const guide = new ui_tour.Guide({
	      id: 'hr-guide-hcmlink-mapped-person',
	      steps: [{
	        target: config.selector,
	        title: config.title,
	        text: config.text,
	        article: '23264608',
	        position: 'bottom'
	      }],
	      autoSave: true,
	      onEvents: true
	    });
	    if (main_core.Type.isNull(config.lastShowGuideDate)) {
	      guide.start();
	    }
	  }
	  static openCompanyConfigSlider(options) {
	    humanresources_hcmlink_companyConfig.HcmlinkCompanyConfig.openSlider(options);
	  }
	}

	exports.MappedPerson = MappedPerson;

}((this.BX.Humanresources.Hcmlink = this.BX.Humanresources.Hcmlink || {}),BX.Humanresources.Hcmlink,BX.Humanresources.Hcmlink,BX,BX.UI.Tour));
//# sourceMappingURL=mapped-person.bundle.js.map
