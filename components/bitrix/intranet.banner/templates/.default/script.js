function BXIntrCloseBnr(bnr_id)
{
	var obBnr = document.getElementById('bx_intranet_bnr_' + bnr_id);
	if (null != obBnr)
		obBnr.parentNode.removeChild(obBnr);

	if (null != jsUserOptions)
		jsUserOptions.SaveOption('intranet_bnr',  'hide_banner',  bnr_id, true);
}