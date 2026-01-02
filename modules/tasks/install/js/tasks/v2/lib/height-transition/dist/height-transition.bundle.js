/* eslint-disable */
this.BX = this.BX || {};
this.BX.Tasks = this.BX.Tasks || {};
this.BX.Tasks.V2 = this.BX.Tasks.V2 || {};
(function (exports,main_core) {
	'use strict';

	class HeightTransition {
	  animate(container) {
	    const prevHeight = main_core.Dom.style(container, 'height');
	    main_core.Dom.style(container, 'transition', 'height 0.2s');
	    main_core.Dom.style(container, 'height', null);
	    const height = container.offsetHeight;
	    main_core.Dom.style(container, 'height', prevHeight);
	    if (height === 0) {
	      return;
	    }
	    setTimeout(() => main_core.Dom.style(container, 'height', `${height}px`));
	  }
	}
	const heightTransition = new HeightTransition();

	exports.heightTransition = heightTransition;

}((this.BX.Tasks.V2.Lib = this.BX.Tasks.V2.Lib || {}),BX));
//# sourceMappingURL=height-transition.bundle.js.map
