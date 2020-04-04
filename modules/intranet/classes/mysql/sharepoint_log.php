<?
require($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/intranet/classes/general/sharepoint_log.php"); 

class CIntranetSharepointLog extends CAllIntranetSharepointLog
{
	protected static function _LimitQuery($strWhere, $cnt)
	{
		return '
SELECT *
FROM b_intranet_sharepoint_log ISPL 
LEFT JOIN b_intranet_sharepoint ISP ON ISP.IBLOCK_ID=ISPL.IBLOCK_ID 
'.$strWhere.'
ORDER BY ID ASC LIMIT 0,'.$cnt;
	}
}
?>