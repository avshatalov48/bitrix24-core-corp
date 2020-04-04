<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sender
 * @copyright 2001-2012 Bitrix
 */

namespace Bitrix\Sender\Integration\Sender\Connectors;

use Bitrix\Main\Localization\Loc;
use Bitrix\Sender\Connector\Base as ConnectorBase;
use Bitrix\Sender\PostingRecipientTable;
use Bitrix\Sender\MailingTable;

Loc::loadMessages(__FILE__);

class Recipient extends ConnectorBase
{
	/**
	 * @return string
	 */
	public function getName()
	{
		return Loc::getMessage('sender_connector_recipient_name1');
	}

	/**
	 * @return string
	 */
	public function getCode()
	{
		return "recipient_list";
	}

	/**
	 * @return \Bitrix\Main\DB\Result
	 */
	public function getData()
	{
		$mailingId = $this->getFieldValue('MAILING_ID', 0);
		$send = $this->getFieldValue('SEND', null);
		$read = $this->getFieldValue('READ', null);
		$click = $this->getFieldValue('CLICK', null);
		$unsub = $this->getFieldValue('UNSUB', null);

		$filter = array(
			'=POSTING.MAILING_ID' => $mailingId,
		);

		if($send=='Y')
			$filter['!STATUS'] = PostingRecipientTable::SEND_RESULT_NONE;
		elseif($send=='N')
			$filter['=STATUS'] = PostingRecipientTable::SEND_RESULT_NONE;

		if($read=='Y')
			$filter['=IS_READ'] = 'Y';
		elseif($read=='N')
			$filter['=IS_READ'] = 'N';

		if($click=='Y')
			$filter['=IS_CLICK'] = 'Y';
		elseif($click=='N')
			$filter['=IS_CLICK'] = 'N';
			
		if($unsub=='Y')
			$filter['=IS_UNSUB'] = 'Y';
		elseif($unsub=='N')
			$filter['=IS_UNSUB'] = 'N';

		return PostingRecipientTable::getList(array(
			'select' => array('NAME' => 'CONTACT.NAME', 'EMAIL' => 'CONTACT.CODE'),
			'filter' => $filter,
			'group' => array('NAME', 'EMAIL'),
		));
	}

	/**
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function getForm()
	{
		$mailingInput = '<select name="'.$this->getFieldName('MAILING_ID').'">';
		$mailingDb = MailingTable::getList(array(
			'select' => array('ID','NAME',),
			'order' => array('NAME' => 'ASC', 'ID' => 'DESC')
		));
		while($mailing = $mailingDb->fetch())
		{
			$inputSelected = ($mailing['ID'] == $this->getFieldValue('MAILING_ID') ? 'selected' : '');
			$mailingInput .= '<option value="'.$mailing['ID'].'" '.$inputSelected.'>';
			$mailingInput .= htmlspecialcharsbx($mailing['NAME']);
			$mailingInput .= '</option>';
		}
		$mailingInput .= '</select>';


		$booleanValues = array(
			'' => Loc::getMessage('sender_connector_recipient_all'),
			'Y' => Loc::getMessage('sender_connector_recipient_y'),
			'N' => Loc::getMessage('sender_connector_recipient_n'),
		);


		$sentInput = '<select name="'.$this->getFieldName('SEND').'">';
		foreach($booleanValues as $k => $v)
		{
			$inputSelected = ($k == $this->getFieldValue('SEND') ? 'selected' : '');
			$sentInput .= '<option value="'.$k.'" '.$inputSelected.'>';
			$sentInput .= htmlspecialcharsbx($v);
			$sentInput .= '</option>';
		}
		$sentInput .= '</select>';


		$readInput = '<select name="'.$this->getFieldName('READ').'">';
		foreach($booleanValues as $k => $v)
		{
			$inputSelected = ($k == $this->getFieldValue('READ') ? 'selected' : '');
			$readInput .= '<option value="'.$k.'" '.$inputSelected.'>';
			$readInput .= htmlspecialcharsbx($v);
			$readInput .= '</option>';
		}
		$readInput .= '</select>';


		$clickInput = '<select name="'.$this->getFieldName('CLICK').'">';
		foreach($booleanValues as $k => $v)
		{
			$inputSelected = ($k == $this->getFieldValue('CLICK') ? 'selected' : '');
			$clickInput .= '<option value="'.$k.'" '.$inputSelected.'>';
			$clickInput .= htmlspecialcharsbx($v);
			$clickInput .= '</option>';
		}
		$clickInput .= '</select>';


		$unsubInput = '<select name="'.$this->getFieldName('UNSUB').'">';
		foreach($booleanValues as $k => $v)
		{
			$inputSelected = ($k == $this->getFieldValue('UNSUB') ? 'selected' : '');
			$unsubInput .= '<option value="'.$k.'" '.$inputSelected.'>';
			$unsubInput .= htmlspecialcharsbx($v);
			$unsubInput .= '</option>';
		}
		$unsubInput .= '</select>';


		return '
			<table>
				<tr>
					<td>'.Loc::getMessage('sender_connector_recipient_mailing').'</td>
					<td>'.$mailingInput.'</td>
				</tr>
				<tr>
					<td>'.Loc::getMessage('sender_connector_recipient_sent').'</td>
					<td>'.$sentInput.'</td>
				</tr>
				<tr>
					<td>'.Loc::getMessage('sender_connector_recipient_read').'</td>
					<td>'.$readInput.'</td>
				</tr>
				<tr>
					<td>'.Loc::getMessage('sender_connector_recipient_click').'</td>
					<td>'.$clickInput.'</td>
				</tr>
				<tr>
					<td>'.Loc::getMessage('sender_connector_recipient_unsub').'</td>
					<td>'.$unsubInput.'</td>
				</tr>
			</table>
		';
	}
}
