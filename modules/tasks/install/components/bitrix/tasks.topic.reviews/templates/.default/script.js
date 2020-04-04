if (typeof oForumForm != "object")
	var oForumForm = {};
var MessageMax = 64000;

function ShowCommentForm(key, content)
{
	BX("form-comment-" + key).appendChild(BX("task-comments-form-wrap", true));

	BX("task-comments-form-wrap", true).style.display = "block";
	BX("task-comments-add-new-0").style.display = "block";
	if (BX("task-comments-add-new-00"))
	{
		BX("task-comments-add-new-00").style.display = "block";
	}
	if (BX("task-comments-add-new-" + key))
	{
		BX("task-comments-add-new-" + key).style.display = "none";
	}

	oLHE.ReInit(content || "");
}

function AttachFile(iNumber, iCount, sIndex, oObj)
{
	var element = null;
	var bFined = false;
	iNumber = parseInt(iNumber);
	iCount = parseInt(iCount);

	document.getElementById('upload_files_info_' + sIndex).style.display = 'block';
	for (var ii = iNumber; ii < (iNumber + iCount); ii++)
	{
		element = document.getElementById('upload_files_' + ii + '_' + sIndex);
		if (!element || typeof(element) == null)
			break;
		if (element.style.display == 'none')
		{
			bFined = true;
			element.style.display = 'block';
			break;
		}
	}
	var bHide = (!bFined ? true : (ii >= (iNumber + iCount - 1)));
	if (bHide == true)
		oObj.style.display = 'none';
}

function Reply(userName, text, id)
{
	ShowCommentForm(id, "[b]" + userName + ":[/b][quote]" + text + "[/quote]");

}

function Edit(text, id)
{
	ShowCommentForm(id, text);
	document.REPLIER.COMMENT_ID.value = id;
}

function Remove(id)
{
	if ( ! confirm(BX.message('TASKS_COMMENTS_CONFIRM_REMOVE')) )
		return;

	document.REPLIER.COMMENT_ID.value = id;
	document.REPLIER.remove_comment.value = 'Y';
	tasksCommentCtrlEnterHandler();
}

function tasksCommentCtrlEnterHandler() {
	BX.submit(document.forms["REPLIER"]);
}


// hack for low coupling
function tasksCommentsShowButton()
{
	if (BX('task-comments-add-new-btn-add'))
		BX('task-comments-add-new-btn-add').style.display = '';
	else
		window.setTimeout(tasksCommentsShowButton, 100);
}
tasksCommentsShowButton();
