'use strict';

BX.namespace('Tasks.Component');

(function(){

    if(typeof BX.Tasks.Component.IframePopup != 'undefined')
    {
        return;
    }

    BX.Tasks.Component.IframePopup = {};

    // different wrapper js controllers

    BX.Tasks.Component.IframePopup.SideSlider = BX.Tasks.Component.extend({
        sys: {
            code: 'iframe-popup-side-slider'
        },
        methods: {
            bindEvents: function()
            {
                // special event binding, that may come from inside iframe content
                BX.addCustomEvent(window, 'tasksTaskEvent', this.onTaskGlobalEvent.bind(this));
            },

            getWindowHref: function()
            {
            	return BX.util.remove_url_param(window.location.href, ["IFRAME", "IFRAME_TYPE"]);
            },

            onTaskGlobalEvent: function(eventType, params)
            {
                if(BX.type.isNotEmptyString(eventType))
                {
                    params = params || {};

                    if (!params.options.STAY_AT_PAGE)
                    {
						const topSlider = window.top.BX.SidePanel.Instance.getTopSlider();
						window.top.BX.SidePanel.Instance.close(false, () => {
							topSlider.destroy();
						});
                    }
                }
            }
        }
    });

}).call(this);
