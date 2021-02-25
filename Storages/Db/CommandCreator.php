<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\MailScheduledMessages\Storages\Db;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2021, Afterlogic Corp.
 */
class CommandCreator extends \Aurora\System\Db\AbstractCommandCreator
{
	/**
	 * @return string
	 */
	public function getMessagesForSend($iTimestamp)
	{
        $sSql = "SELECT * FROM %smail_scheduled_messages WHERE schedule_timestamp < %d ORDER BY schedule_timestamp";

        return sprintf($sSql, $this->prefix(), $iTimestamp);
	}

    /**
     * @param int $iAccountID
     * @param string $sFolderFullName
     * @param string $sMessageUid
     * @param string $sDeviceId
     *
     * @return string
     */
    public function addMessage($iAccountID, $sFolderFullName, $sMessageUid, $iScheduledTimestamp)
    {
        $sSql = 'INSERT INTO %smail_scheduled_messages ( account_id, folder_full_name, message_uid, schedule_timestamp ) VALUES ( %d, %s, %s, %d )';

        return sprintf($sSql, $this->prefix(), $iAccountID, $this->escapeString($sFolderFullName), $this->escapeString($sMessageUid), $iScheduledTimestamp);
    }

    /**
     * @param int $iAccountID
     * @param string $sFolderFullName
     * @param string $sMessageUid
     * @param string $sDeviceId
     *
     * @return string
     */
    public function updateMessageScheduleTimestamp($iAccountID, $sFolderFullName, $sMessageUid, $iScheduledTimestamp)
    {
        $sSql = 'UPDATE %smail_scheduled_messages SET schedule_timestamp = %d WHERE account_id = %d AND folder_full_name = %s AND message_uid = %s';

        return sprintf($sSql, $this->prefix(), $iScheduledTimestamp, $iAccountID, $this->escapeString($sFolderFullName), $this->escapeString($sMessageUid));
    }

    public function getMessage($iAccountID, $sFolderFullName, $sMessageUid)
    {
        $sSql = 'SELECT * FROM %smail_scheduled_messages WHERE account_id = %d AND folder_full_name = %s AND message_uid = %s';

        return sprintf($sSql, $this->prefix(), $iAccountID, $this->escapeString($sFolderFullName), $this->escapeString($sMessageUid));
    }

    /**
     * @param string $sDeviceId
     *
     * @return string
     */
    public function removeMessage($iAccountID, $sFolderFullName, $sMessageUid)
    {
        $sSql = 'DELETE FROM %smail_scheduled_messages WHERE account_id = %d AND folder_full_name = %s AND message_uid = %s';

        return sprintf($sSql, $this->prefix(), $iAccountID, $this->escapeString($sFolderFullName), $this->escapeString($sMessageUid));
    }
}
