(function() {

"use strict";

BX.namespace("BX.Tasks.Timeline");

/**
 *
 * @param options
 * @extends {BX.Tasks.Kanban.Item}
 * @constructor
 */
BX.Tasks.Timeline.Item = function(options)
{
	BX.Tasks.Kanban.Item.apply(this, arguments);

	/** @var {Element} **/
	this.container = null;
};

BX.Tasks.Timeline.Item.prototype = {
	__proto__: BX.Tasks.Kanban.Item.prototype,
	constructor: BX.Tasks.Timeline.Item
}

})();
