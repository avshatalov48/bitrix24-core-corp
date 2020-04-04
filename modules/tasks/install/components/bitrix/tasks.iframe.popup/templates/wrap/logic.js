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

                BX.bindDelegate(this.scope(), 'click', {
                    className: 'js-id-copy-page-url'
                }, this.onCopyUrl.bind(this));
            },

            onCopyUrl: function(e)
            {
                e = e || window.event;

                this.onCopyUrlByNode(e.target, this.getWindowHref());
            },

            onCopyUrlByNode: function(node, text)
            {
                this.timeoutIds = this.timeoutIds || [];

                if(!BX.clipboard.copy(text))
                {
                    return;
                }

                var popupParams = {
                    content: BX.message('TASKS_TIP_TEMPLATE_LINK_COPIED'),
                    darkMode: true,
                    autoHide: true,
                    zIndex: 1000,
                    angle: true,
                    offsetLeft: 20,
                    bindOptions: {
                        position: 'top'
                    }
                };
                var popup = new BX.PopupWindow(
                    'tasks_clipboard_copy',
                    node,
                    popupParams
                );
                popup.show();

                var timeoutId;
                while(timeoutId = this.timeoutIds.pop()) clearTimeout(timeoutId);
                timeoutId = setTimeout(function(){
                    popup.close();
                }, 1500);
                this.timeoutIds.push(timeoutId);
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
                        // just forward "close" event to the side slider controller
                        window.top.BX.onCustomEvent("BX.Bitrix24.PageSlider:close", [false]);
                    }
                }
            }
        }
    });

}).call(this);