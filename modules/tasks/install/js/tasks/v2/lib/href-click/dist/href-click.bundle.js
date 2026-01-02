/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
(function (exports,main_core) {
	'use strict';

	let _ = t => t,
	  _t;
	const hrefClick = href => {
	  const a = main_core.Tag.render(_t || (_t = _`
		<a href="${0}" target="_top"></a>
	`), href);
	  main_core.Dom.append(a, document.body);
	  a.click();
	  a.remove();
	};

	exports.hrefClick = hrefClick;

}((this.BX.Tasks.V2.Lib = this.BX.Tasks.V2.Lib || {}),BX));
//# sourceMappingURL=href-click.bundle.js.map
