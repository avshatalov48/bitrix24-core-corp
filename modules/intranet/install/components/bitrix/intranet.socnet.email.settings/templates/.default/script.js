;(function ()
{
	var namespace = BX.namespace('BX.Intranet');
	if (namespace.SocnetEmailSettings)
	{
		return;
	}

	namespace.SocnetEmailSettings = function(params)
	{
		this.init(params);
	};

	namespace.SocnetEmailSettings.prototype =
	{
		init: function(params)
		{
			BX("intranet-socnet-email").style.minHeight =
				BX("ui-page-slider-workarea") ? (BX("ui-page-slider-workarea").offsetHeight - 80) + "px" :
					(BX("workarea-content").offsetHeight - 15) + "px";

			var blogCopyElemet = document.querySelector("[data-role='copyBlog']");
			if (BX.type.isDomNode(blogCopyElemet))
			{
				BX.bind(blogCopyElemet, "click", function () {
					this.copyLink(blogCopyElemet);
				}.bind(this))
			}

			var taskCopyElemet = document.querySelector("[data-role='copyTask']");
			if (BX.type.isDomNode(taskCopyElemet))
			{
				BX.bind(taskCopyElemet, "click", function () {
					this.copyLink(taskCopyElemet);
				}.bind(this))
			}
		},

		copyLink: function(element)
		{
			if (!BX.type.isDomNode(element))
			{
				return;
			}

			var inputNode;
			var elementType = element.getAttribute("data-role");

			if (elementType === "copyBlog")
			{
				inputNode = document.querySelector("[data-role='copy-blog-input']");
			}
			else if (elementType === "copyTask")
			{
				inputNode = document.querySelector("[data-role='copy-task-input']");
			}

			if (!inputNode)
			{
				return;
			}

			BX.clipboard.copy(inputNode.value);

			BX.PopupWindowManager.create("socnetEmailCopyUrl", element, {
				content: BX.message("INTRANET_SOCNET_EMAIL_SETTINGS_COPY_SUCCESS"),
				zIndex: 15000,
				angle: true,
				offsetTop: 0,
				offsetLeft: 50,
				closeIcon: false,
				autoHide: true,
				darkMode: true,
				overlay: false,
				events : {
					onAfterPopupShow: function () {
						setTimeout(function () {
							this.close();
						}.bind(this), 1500);
					},
					onPopupClose: function ()
					{
						this.destroy();
					}
				}
			}).show();
		}
	};
})();