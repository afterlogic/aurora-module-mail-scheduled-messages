<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\MailScheduledMessages;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2023, Afterlogic Corp.
 *
 * @property Module $oModule
 */
class Manager extends \Aurora\System\Managers\AbstractManager
{
    public function __construct(\Aurora\System\Module\AbstractModule $oModule = null)
    {
        parent::__construct($oModule);
    }

    /**
     * @return array|bool
     */
    public function getMessagesForSend($iTimestamp)
    {
        return Models\Message::where('schedule_timestamp', '<', $iTimestamp)
             ->orderBy('schedule_timestamp')
             ->get()
             ->map(function ($message) {
                 return [
                     'AccountId' => (int) $message->account_id,
                     'FolderFullName' => $message->folder_full_name,
                     'MessageUid' => $message->message_uid,
                     'ScheduleTimestamp' => (int) $message->schedule_timestamp
                 ];
             });
    }

    /**
     * @param int $iAccountID
     * @param string $sFolderFullName
     * @param string $sMessageUid
     * @param int $iTimestamp
     *
     * @return bool
     */
    public function addMessage($iAccountID, $sFolderFullName, $sMessageUid, $iTimestamp)
    {
        return Models\Message::query()->create([
            'account_id' => $iAccountID,
            'folder_full_name' => $sFolderFullName,
            'message_uid' => $sMessageUid,
            'schedule_timestamp' => $iTimestamp
        ]);
    }

    public function updateMessageScheduleTimestamp($iAccountID, $sFolderFullName, $sMessageUid, $iTimestamp)
    {
        return Models\Message::where('account_id', $iAccountID)
            ->where('folder_full_name', $sFolderFullName)
            ->where('message_uid', $sMessageUid)
            ->update(['schedule_timestamp' => $iTimestamp]);
    }

    public function getMessage($iAccountID, $sFolderFullName, $sMessageUid)
    {
        $mResult = false;

        $message = Models\Message::where('account_id', $iAccountID)
            ->where('folder_full_name', $sFolderFullName)
            ->where('message_uid', $sMessageUid)
            ->first();
        if ($message) {
            $mResult = [
                'AccountId' => (int) $message->account_id,
                'FolderFullName' => $message->folder_full_name,
                'MessageUid' => $message->message_uid,
                'ScheduleTimestamp' => (int) $message->schedule_timestamp
            ];
        }
        return $mResult;
    }

    public function removeMessage($iAccountID, $sFolderFullName, $sMessageUid)
    {
        return Models\Message::where('account_id', $iAccountID)
            ->where('folder_full_name', $sFolderFullName)
            ->where('message_uid', $sMessageUid)
            ->delete();
    }

    public function removeAccountMessages($iAccountID)
    {
        return Models\Message::where('account_id', $iAccountID)->delete();
    }
}
