(function () {
	BX.namespace("BX.Crm.Report.Analytics");

	BX.Crm.Report.Analytics.Limit = function (options) {
		this.boardId = options.boardId;
		this.scope = options.scope;
		this.textScope = options.textScope;
		this.buttonScope = options.buttonScope;
		this.init();
	};

	BX.Crm.Report.Analytics.Limit.prototype = {
		init: function () {
			BX.bind(this.buttonScope, 'click', this.handlerOnResetClick.bind(this));
		},
		handlerOnResetClick: function (e) {
			e.preventDefault();
			var loader = new BX.Loader({size: 100});
			loader.show(this.textScope);
			BX.ajax.runAction('crm.api.reportboard.resetLimitCache', {data: {boardId: this.boardId}}).then(function (result) {
				if (result.data.limitEnabled)
				{
					this.textScope.innerHTML = result.data.text;
				}
				else
				{
					document.location.reload(true);
				}
			}.bind(this));
			console.log(this);
		}
}
})();
