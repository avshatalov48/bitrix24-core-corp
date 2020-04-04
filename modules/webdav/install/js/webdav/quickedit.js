WDQuickEdit = function(viewRoot, actionURL, elCommit, elRollback, arEnterSubmitControls) {
    this.viewRoot = viewRoot;
	this.actionURL = actionURL;
	this.elCommit = elCommit;
	this.elRollback = elRollback;
	this.arEnterSubmitControls = arEnterSubmitControls;

	this.viewElements = BX.findChild(viewRoot, {'class': 'quick-view'}, true, true);
	this.editElements = BX.findChild(viewRoot, {'class': 'quick-edit'}, true, true);
	this.viewButtons = BX.findChild(viewRoot, {'class': 'button-view'}, true, true);
	this.editButtons = BX.findChild(viewRoot, {'class': 'button-edit'}, true, true);
}

WDQuickEdit.prototype.Init = function()
{
	var _this = this;

	BX.bind(this.elCommit, 'click', BX.delegate(_this.Commit, _this));
	BX.bind(this.elRollback, 'click', BX.delegate(_this.Rollback, _this));

	for (i in this.arEnterSubmitControls)
		if (!!this.arEnterSubmitControls[i])
			BX.bind(this.arEnterSubmitControls[i], 'keypress', BX.delegate(this.EnterSubmit, _this));

    this.ChangeMode(false, false);

    // hover hilight and edit
    var aElements = BX.findChild(this.viewRoot, {'class':'wd-toggle-edit'}, true, true);
    for (i in aElements)
        if (! BX.hasClass(aElements[i], 'no-quickedit'))
            this.ActivateQuickEdit(aElements[i]);
}

WDQuickEdit.prototype.ChangeMode = function(fields, buttons)
{
    fields = !!fields;
    buttons = !!buttons;
    var localViewElements = this.viewElements;
    var localEditElements = this.editElements;

    var on  = (fields ? 'block' : 'none');
    var off = (fields ? 'none' : 'block');

    for (var i in localViewElements)
        localViewElements[i].style.display = off;

    for (var i in localEditElements)
        localEditElements[i].style.display = on;

    var on  = (buttons ? 'block' : 'none');
    var off = (buttons ? 'none' : 'block');

    for (var i in this.viewButtons)
        this.viewButtons[i].style.display = off;

    for (var i in this.editButtons)
        this.editButtons[i].style.display = on;
}

WDQuickEdit.prototype.Commit = function()
{
	this.elCommit.disabled = true;
	this.elRollback.disabled = true; 
    var obTable = this.viewRoot;
    var formParent = obTable.parentNode;
	var obForm = BX.create('FORM', {attrs:{
		'enctype': 'multipart/form-data',
		'method': 'POST',
		'id': 'tab_main_form',
		'action': this.actionURL
	}});
    formParent.appendChild(obForm);
    obForm.appendChild(obTable);
    obForm.submit();
}

WDQuickEdit.prototype.Rollback = function()
{
	this.elCommit.disabled = true;
	this.elRollback.disabled = true; // ie9 hides the button itself if we remove timeout !
	setTimeout(function() {
		window.location.reload(true);
	}, 100);
}

WDQuickEdit.prototype.EnterSubmit = function(e)
{
    var ev = e || window.event;
    var key = ev.keyCode;

    if (key == 13)
        this.elCommit.click();
    else if (key == 27)
        this.elRollback.click();
}

WDQuickEdit.prototype.ActivateEdit = function(elm)
{
    this.ChangeMode(true, true);
	if (this.editElements && this.editElements.length > 0)
	{
		elm = elm || this.editElements[0];
		inputField = BX.findChild(elm.parentNode, {'tag': 'input'}, true);
		if (! inputField) inputField = BX.findChild(elm.parentNode, {'tag': 'textarea'}, true);
		if (! inputField) inputField = BX.findChild(elm.parentNode, {'tag': 'select'}, true);
		if (inputField)
		{
			try {
				inputField.focus();
			} catch (e) {}
		}
	}
	BX.onCustomEvent(this, 'OnEdit', [this]);
}


WDQuickEdit.prototype.Hover = function(elm, className)
{
    BX.bind(elm, 'mouseover', function() { BX.addClass(elm, className); });
    BX.bind(elm, 'mouseout',  function() { BX.removeClass(elm, className); });
}

WDQuickEdit.prototype.ActivateQuickEdit = function(elm)
{
    var aHrefs = BX.findChild(elm, {'tag': 'a'}, true, true);
	var _this = this;
    for (j in aHrefs)
    {
        BX.bind(aHrefs[j], 'click', function(e) {
            if (!e) var e = window.event;
            if (e.stopPropagation) 
                e.stopPropagation();
            else
                e.cancelBubble = true;
        });
    }
	this.Hover(elm, 'wd-input-hover');
    BX.bind(elm, 'click',     function() { _this.ActivateEdit(elm);});
}
