<?php
    class JMSLiveChatModel {
        function postMessage($chatid, $userid, $username, $message, $currentDate, $status) {
            global $wpdb;

            //insert
            $table_name = $wpdb->prefix . "LivechatMessage";
            $query = $wpdb->prepare(
                "INSERT INTO $table_name (chat_id, user_id, user_name, message, post_time, status)
                    VALUES (%d, %d, %s, %s, %s, %d)",
                array(
                    $chatid,
                    $userid,
                    $username,
                    $message,
                    $currentDate,
                    $status
                    )
            );

            $result = $wpdb->query($query);
        }

        function startChatSession($username, $starttime) {
            global $wpdb;

            //insert
            $table_name = $wpdb->prefix . "LivechatSession";
            $query = $wpdb->prepare(
                "INSERT INTO $table_name (chat_name, start_time, status)
                    VALUES (%s, %s, %d)",
                array(
                    $username,
                    $starttime,
                    0
                    )
            );

            $result = $wpdb->query($query);
            return $wpdb->insert_id;
        }

        function getChatSession($chatid) {
            global $wpdb;
            $table_name = $wpdb->prefix . "LivechatSession";
            return $wpdb->get_results("SELECT * FROM $table_name WHERE chat_id=".(int)$chatid, ARRAY_A);
        }

        function updateChatSession($chatid, $username) {
            global $wpdb;
            $table_name = $wpdb->prefix . "LivechatSession";
            $query = $wpdb->prepare(
                "UPDATE $table_name SET status=IF(status=0 OR status=2, 0, 1), chat_name=\"%s\" WHERE chat_id = %d",
                array(
                    $username,
                    $chatid
                    )
            );
            $result = $wpdb->query($query);

            return $result; //true or false
        }

        function getChatMessageBySession($chatid, $oldestMessageID) {
            global $wpdb;
            $table_name = $wpdb->prefix . "LivechatRoom";
            return $wpdb->get_results("SELECT * FROM $table_name WHERE chat_id=".(int)$chatid." AND message_id > ".(int)$oldestMessageID, ARRAY_A);
	    }

        function markChatMessageAsRead($chatid, $oldestMessageID) {
            global $wpdb;
            $table_name = $wpdb->prefix . "LivechatMessage";
            $query = $wpdb->prepare(
                "UPDATE $table_name SET status=1 WHERE chat_id = %d AND message_id > %d",
                array(
                    $chatid,
                    $oldestMessageID
                    )
            );
            $result = $wpdb->query($query);

            return $result; //true or false
        }

        function markChatSessionAsReplied($chatid, $userid) {
            global $wpdb;
            $table_name = $wpdb->prefix . "LivechatSession";
            $query = $wpdb->prepare(
                "UPDATE $table_name SET status=1, reply_by=%d WHERE chat_id = %d",
                array(
                    $userid,
                    $chatid
                    )
            );
            $result = $wpdb->query($query);

            return $result; //true or false
        }

        function getNewClientList($userid) {
            global $wpdb;
            $table_name = $wpdb->prefix . "LivechatRoom";
            return $wpdb->get_results("SELECT COUNT(message_id) as count, chat_id, chat_name FROM $table_name WHERE (session_status = 0) OR (session_status = 1 AND status = 0 AND reply_by = ".(int)$userid.") GROUP BY chat_id", ARRAY_A);
        }

        function getMyClientList($userid) {
            global $wpdb;
            $table_name = $wpdb->prefix . "LivechatRoom";
            return $wpdb->get_results("SELECT chat_id, chat_name FROM $table_name WHERE session_status = 1 AND status = 1 AND reply_by = ".(int)$userid." GROUP BY chat_id", ARRAY_A);
        }

        function finishChatSession($chatid, $userid) {
            global $wpdb;
            $table_name = $wpdb->prefix . "LivechatSession";
            $query = $wpdb->prepare(
                "UPDATE $table_name SET status=2, reply_by=%d WHERE chat_id = %d",
                array(
                    $userid,
                    $chatid
                    )
            );
            $result = $wpdb->query($query);

            return $result; //true or false
        }

        function getAllClientList() {
            global $wpdb;
            $table_name = $wpdb->prefix . "LivechatSession";
            return $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
        }

        function getAllChatMessage($chatid) {
            global $wpdb;
            $table_name = $wpdb->prefix . "LivechatRoom";
            return $wpdb->get_results("SELECT * FROM $table_name WHERE chat_id=".(int)$chatid, ARRAY_A);
        }
    }
?>