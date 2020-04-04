if (window.ShowWaitWindow == null)
{
    function ShowWaitWindow()
    {
        CloseWaitWindow();
    
        var obWndSize = jsUtils.GetWindowSize();
    
        var div = document.body.appendChild(document.createElement("DIV"));
        div.id = "wait_window_div";
        div.innerHTML = phpVars.messLoading;
        div.className = "waitwindow";
        //div.style.left = obWndSize.scrollLeft + (obWndSize.innerWidth - div.offsetWidth) - (jsUtils.IsIE() ? 5 : 20) + "px";
        div.style.right = (5 - obWndSize.scrollLeft) + 'px';
        div.style.top = obWndSize.scrollTop + 5 + "px";
    
        if(jsUtils.IsIE())
        {
            var frame = document.createElement("IFRAME");
            frame.src = "javascript:''";
            frame.id = "wait_window_frame";
            frame.className = "waitwindow";
            frame.style.width = div.offsetWidth + "px";
            frame.style.height = div.offsetHeight + "px";
            frame.style.right = div.style.right;
            frame.style.top = div.style.top;
            document.body.appendChild(frame);
        }
        jsUtils.addEvent(document, "keypress", WaitOnKeyPress);
    }
}

if (window.CloseWaitWindow == null)
{
    function CloseWaitWindow()
    {
        jsUtils.removeEvent(document, "keypress", WaitOnKeyPress);
    
        var frame = document.getElementById("wait_window_frame");
        if(frame)
            frame.parentNode.removeChild(frame);
    
        var div = document.getElementById("wait_window_div");
        if(div)
            div.parentNode.removeChild(div);
    }
}

if (window.WaitOnKeyPress == null)
{
    function WaitOnKeyPress(e)
    {
        if(!e) e = window.event
        if(!e) return;
        if(e.keyCode == 27)
            CloseWaitWindow();
    }
}