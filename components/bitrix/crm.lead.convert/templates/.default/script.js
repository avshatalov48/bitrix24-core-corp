function showAdditionalFields(entity)
{
	BX(entity + '_a').className = (BX(entity + '_a').className == '' ? 'close' : '');
	var bClose = false;
	var rows = BX('tab_convert_' + entity + '_edit_table').lastChild.rows;
	for(var i = 0; i < rows.length; i++)
	{
		if (bClose)
			rows[i].style.display = (rows[i].style.display != 'none' ? 'none' : '');		
		if (!bClose && rows[i].id == 'tr_' + entity + '_add_fields')
		{
			bClose = true;
			rows[i].className = 'bx-add-fields-section' + (rows[i].className == 'bx-add-fields-section' ? '-close' : '');
		}		
	}	
}