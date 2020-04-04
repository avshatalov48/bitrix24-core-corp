<?
$APPLICATION->IncludeFile("support/ticket_message_edit/default.php", array(
	"ID"						=> $_REQUEST["ID"],				// ID ���������
	"TICKET_ID"					=> $_REQUEST["TICKET_ID"],		// ID ���������
	"TICKET_LIST_URL"			=> "ticket_list.php",			// �������� ������ ���������
	"TICKET_EDIT_URL"			=> "ticket_edit.php",			// �������� �������������� ���������
	));
?>