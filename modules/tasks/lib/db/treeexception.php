<?
namespace Bitrix\Tasks\DB;

class TreeException 					extends \Bitrix\Main\SystemException {};
class TreeNodeNotFoundException 		extends TreeException {};
class TreeTargetNodeNotFoundException	extends TreeNodeNotFoundException {};
class TreeParentNodeNotFoundException 	extends TreeNodeNotFoundException {};
class TreeLinkExistsException 			extends TreeException {};