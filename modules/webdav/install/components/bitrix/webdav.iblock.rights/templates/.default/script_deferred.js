(function() {
var BX = window.BX || top.BX;
if(BX.WebDavRightsDialog)
	return;

BX.WebDavRightsDialog = function(params)
{
	this.tab = params.tab || null;
	this.group_id = params.group_id || null;
	//this.cur_page = params.cur_page || '';
	this.extranet = !!params.extranet;
	this.perms = params.perms || {};
	this.disableGroup = params.disableGroup || false;

	this.wdPermsEdit = null;

	var accessParams = {};
	if (!!this.group_id)
		accessParams["socnetgroup"] = {"group_id": this.group_id};
	if (this.extranet)
		accessParams["other"] = {"disabled_g2":true, "disabled_au":true};
	if(this.disableGroup)
	{
		accessParams['groups'] = { "disabled": true };
	}

	BX.Access.Init(accessParams);
	aAddControl = BX.findChild(this.tab, {'className':'wd_add_permission'}, true);
	if (aAddControl)
		BX.bind(aAddControl, 'click', BX.delegate(this.AddRight, this));

	if (!!window["WDQuickEdit"])
		this._Init();
}

BX.WebDavRightsDialog.prototype.AddRight = function()
{
	var bind = 'RIGHTS';
	BX.Access.SetSelected({}, bind);;
	BX.Access.ShowForm({callback: BX.delegate(this.wdAddSubjs, this), bind: bind});
	if (!! BX.WindowManager.Get())
		BX.style(BX.WindowManager.Get().DIV, 'z-index', '1000');
	BX.style(BX.Access.popup.popupContainer, 'z-index', '1500');
	this.wdPermsEdit.ActivateEdit();
}

BX.WebDavRightsDialog.prototype._Init = function()
{
	this.wdPermsEdit = new WDQuickEdit(this.tab, BX.cur_page,
		BX.findChild(this.tab, {'class':'wd_commit'}, true),
		BX.findChild(this.tab, {'class':'wd_rollback'}, true),
		[
			BX.findChild(this.tab, {'tag':'input', 'class':'wd-file-name'}, true),
			BX.findChild(this.tab, {'tag':'input', 'property':{'name':'TAGS'}}, true)
		]);
	this.wdPermsEdit.Init();
	//btnChange = BX.findChild(this.tab, {'tag':'input', 'class':'wd_edit'}, true);
	arRemoveControls = BX.findChildren(this.tab, {'className': 'wd-rights-delete'}, true);
	for (i in arRemoveControls)
		BX.bind(arRemoveControls[i], 'click',  BX.delegate(this.wdRemoveRight, this));

	/*
	 *BX.addCustomEvent(wdPermsEdit, 'onEdit', function(ob) {
	 *    var tbl = BX.findChild(tab, {'tagName':'tbody'});
	 *    if (!tbl) tbl = tab;
	 *    var rows = BX.findChildren(tbl, {'tag':'tr'});
	 *    for (i in rows)
	 *        if (! BX.hasClass(rows[i], 'bx-bottom'))
	 *            wdPermsEdit.Hover(rows[i], 'hilight');
	 *});
	 */
}

BX.WebDavRightsDialog.prototype.wdRemoveRight = function()
{
	BX.remove(BX.proxy_context.parentNode.parentNode.parentNode);
}

BX.WebDavRightsDialog.prototype.createSelect = function(attrs, values)
{
	var c = BX.create('select', attrs);
	for (i in values)
		c.appendChild(BX.create('option', {'attrs':{'value': i}, 'html':values[i]}));
	return c;
}

BX.WebDavRightsDialog.ShowDialog = function(_this)
{
	target = _this.getAttribute('target');
	if (!!target)
		(new BX.CDialog({'width': 750, 'heght':400, 'content_url':target})).Show()
	return false;
}

BX.WebDavRightsDialog.prototype.wdAddRight = function(subjID, subjName)
{
	var row = BX.findChild(this.tab, {"className":"bx-bottom"}, true);

	var groups = BX.create('div', {"children": [
		BX.create('input', {'attrs': {'type':'hidden', 'name':'RIGHTS[][RIGHT_ID]', 'value':''}}),
		BX.create('input', {'attrs': {'type':'hidden', 'name':'RIGHTS[][GROUP_CODE]', 'value':subjID}}),
		BX.create('span', {'html': subjName+':'})
	]});
	BX.style(groups, "float", "right");
	var perms = this.createSelect({"attrs": {"name":"RIGHTS[][TASK_ID]"}}, this.perms);
	var removeControl = BX.create("div", {"attrs": {"class":"wd-rights-delete"}, 'events':{'click':BX.delegate(this.wdRemoveRight, this)}});
	BX.style(perms, 'display', 'block');
	BX.style(perms, 'float', 'left');

	var newRow = BX.create('tr', {'children':[
		BX.create('td', {'attrs':{'class':'bx-field-name  bx-padding'}, 'children':[ groups ]}),
		BX.create('td', {'attrs':{'class':'bx-field-value'}, 'children':[ BX.create('div', {'attrs':{'class':'wd_right_set'}, 'children':[perms, removeControl]})]})
	]});

	row.parentNode.insertBefore(newRow, row);
	this.wdPermsEdit.Hover(newRow, 'hilight' );
}

BX.WebDavRightsDialog.prototype.wdAddSubjs = function(arSubj)
{
	if (BX.WindowManager && (wnd = BX.WindowManager.Get()))
		BX.style(wnd.DIV, 'z-index', 100+BX.style(wnd.DIV, 'z-index'));

	for (i in arSubj)
	{
		oSubj = arSubj[i];
		for (id in oSubj)
		{
			subj = oSubj[id];
			this.wdAddRight(subj.id, subj.name);
		}
	}
}

})();

