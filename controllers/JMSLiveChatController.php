<?php
    require_once(dirname(__FILE__)."/../models/JMSLiveChatModel.php");

    class JMSLiveChatController {
        private $model;

        function JMSLiveChatController() {
            $this->model = new JMSLiveChatModel();
        }

        function postMessageByClient() {
            global $wp;

            $chatid = isset($_SESSION["chatid"]) ? $_SESSION["chatid"] : null;
            $name = $_REQUEST['name'];

            //will it be null?
            if($chatid == null) {
                $chatid = $_REQUEST['chat'];
            }

            $message = $_REQUEST['message'];
            if($message != null) {
                $message = trim($message);
            } else {

            }
            
            //check if the chatid exists in database; admin may delete the ongoing chat
            //in backend. In this case, set this chat as new chat session
            $chatSessionList = $this->model->getChatSession($chatid);
            if(empty($chatSessionList)) {
                $chatid = 0;
            }
            
            $currentTime = date("Y-m-d H:i:s", time());
            if($chatid == 0) {
                $chatid = $this->model->startChatSession($name, $currentTime);
                $_SESSION["chatid"] = $chatid;
                $_SESSION["chatname"] = $name;
            } else {
                $oldName = $_SESSION["chatname"];
                //if($oldName != $name) {
                    $this->model->updateChatSession($chatid, $name);
                    $_SESSION["chatname"] = $name;
                //}
            }
            
            if($chatid != -1 && !empty($message)) {
                $userid = get_current_user_id(); 
                $messageid = $this->model->postMessage($chatid, $userid, $name, $message, $currentTime, 0); //insert a new message
            }
            
            //$this->getMessageByClient();
            header("Content-Type: text/xml; charset=utf-8");
            $xml = '<?xml version="1.0" encoding="utf-8"?>';
            $xml .= '<sessionid>'.$chatid.'</sessionid>';
            echo $xml;
            exit;
        }

        function postMessageByAdmin() {
            global $wp;

            $chatid = isset($_REQUEST['chat']) ? $_REQUEST['chat'] : 0;
            $name = $_REQUEST['name'];
            $message = $_REQUEST['message'];
            if($message != null) {
                $message = trim($message);
            }
            
            $currentTime = date("Y-m-d H:i:s", time());
            
            if($chatid != 0 && $chatid != -1 && !empty($message)) {
                $userid = get_current_user_id();
                $messageid = $this->model->postMessage($chatid, $userid, $name, $message, $currentTime, 1); //insert as old message
                $this->model->markChatSessionAsReplied($chatid, $userid);
            }
            
            //don't need to return message list;
            //$this->getMessageByAdmin();
            echo "1";
            exit;
        }

        function getMessage($markAsRead = false) {
            global $wp;
            $chatid = isset($_REQUEST['chat']) ? $_REQUEST['chat'] : 0;
            
            $lastMessageID = $_REQUEST['last'];
            $lastMessageID = $lastMessageID == null ? 0 : $lastMessageID;
            $messageList = $this->model->getChatMessageBySession($chatid, $lastMessageID);

            if($markAsRead) {
                $this->model->markChatMessageAsRead($chatid, $lastMessageID);
            }
            
            $xml = '<?xml version="1.0" encoding="utf-8"?>';
            $xml .= "<root>";
            $xml .= '<room id="' . $chatid . '">' . sizeof($messageList) .'</room>';
            foreach( $messageList as $msg ) {
                $xml .= '<message id="' . $msg["message_id"] . '">';
                $xml .= '<user>' . htmlspecialchars($msg["user_name"]) . '</user>';
                $xml .= '<text>' . htmlspecialchars($msg["message"]) . '</text>';
                $xml .= '<time>' . $msg["post_time"] . '</time>';
                $xml .= '</message>';
            }
            $xml .= '</root>';
            
            header("Content-Type: text/xml; charset=utf-8");
            echo $xml;
            exit;
        }

        //only available for admin
        function refreshChatRoom() {
            global $wp;
            $userid = get_current_user_id();
            
            $xml = '<?xml version="1.0" ?><root>';
            $clientList = $this->model->getNewClientList($userid);
            $clientID = array();
            foreach($clientList as $v) {
                $xml .= '<chat id="' . $v["chat_id"] . '">';
                $xml .= '<name>' . htmlspecialchars($v["chat_name"]) . '</name>';
                $xml .= '<count>' . $v["count"] . '</count>';
                $xml .= '</chat>';
                
                $clientID[] = $v["chat_id"];
            }
            
            $clientList = $this->model->getMyClientList($userid);
            foreach($clientList as $v) {
                if(!in_array($v["chat_id"], $clientID)) {
                    $xml .= '<chat id="' . $v["chat_id"] . '">';
                    $xml .= '<name>' . htmlspecialchars($v["chat_name"]) . '</name>';
                    $xml .= '<count>' . 0 . '</count>';
                    $xml .= '</chat>';
                    $clientID[] = $v["chat_id"];
                }
            }
            
            $xml .= '</root>';
            
            header("Content-Type: text/xml; charset=utf-8");
            echo $xml;
            exit;
        }

        function finishChatSession() {
            $chatid = isset($_REQUEST['chat']) ? $_REQUEST['chat'] : 0;
            if($chatid != 0 && $chatid != -1) {
                $userid = get_current_user_id();
                $this->model->finishChatSession($chatid, $userid);
            }
            
            echo "1";
            exit;
        }
    }
?>