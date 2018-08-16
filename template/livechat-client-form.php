<?php
    $pluginURL = plugins_url()."/jms-live-chat/";
    $requestURL = admin_url( 'admin-ajax.php' );

    if ( is_user_logged_in() ) {
        $user = wp_get_current_user();
        $chatName = $user->user_nicename;
    } else {
        $chatName = __('Guest','jms-live-chat');
    }
?>

<script language="JavaScript" type="text/javascript">
    var sendReq = getXmlHttpRequestObject();
    var receiveReq = getXmlHttpRequestObject();
    var lastMessage = 0;
    var id = 0;
    var mTimer;
    
    //Gets the browser specific XmlHttpRequest Object
    function getXmlHttpRequestObject() {
        if (window.XMLHttpRequest) {
            return new XMLHttpRequest();
        } else if(window.ActiveXObject) {
            return new ActiveXObject("Microsoft.XMLHTTP");
        } else {
            document.getElementById('p_status').innerHTML = 'Status: Cound not create XmlHttpRequest Object.  Consider upgrading your browser.';
        }
    }
    
    //Gets the current messages from the server
    function getChatText() {
        if(mTimer != null) {
            clearTimeout(mTimer);
            mTimer = null;
        }
        
        if(id == 0) {
            return;
        }
        
        if (receiveReq.readyState == 4 || receiveReq.readyState == 0) {
            receiveReq.open("POST", '<?php echo $requestURL;?>', true);
            receiveReq.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
            receiveReq.onreadystatechange = handleReceiveChat;
            var param = 'chat=' + id;
            param += '&last=' + lastMessage;
            param += '&task=get';
            param += '&action=jms_livechat';
            receiveReq.send(param);
        }
    }
    
    //Add a message to the chat server.
    function sendChatText() {
        var message = document.getElementById('txt_message').value;
        
        if(message == '' || message.trim() == '') {
            alert("<?php echo __('You cannot send empty message!','jms-live-chat'); ?>");
            return;
        } else {
            message = message.trim();
        }
        
        if (sendReq.readyState == 4 || sendReq.readyState == 0) {
            sendReq.open("POST", '<?php echo $requestURL;?>', true);
            sendReq.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
            sendReq.onreadystatechange = handleSendChat; 
            var param = 'message=' + document.getElementById('txt_message').value;
            param += '&name=' + document.getElementById('txt_name').value;
            param += '&chat=' + id;
            param += '&last=' + lastMessage;
            param += '&task=new';
            param += '&action=jms_livechat';
            sendReq.send(param);
            document.getElementById('txt_message').value = '';
        }							
    }
    
    //When our message has been sent, update our page.
    function handleSendChat() {
        if (sendReq.readyState == 4) {
            var xmldoc = sendReq.responseXML;
            var sessionid_node = xmldoc.getElementsByTagName("sessionid");
            id = sessionid_node[0].firstChild.nodeValue;
            //wait for timer to get the text
            if(mTimer != null) {
                clearTimeout(mTimer);
                mTimer = null;
            }
            
            getChatText();
        }
    }
    
    //Function for handling the return of chat text
    function handleReceiveChat() {
        if (receiveReq.readyState == 4) {
            var myName = document.getElementById('txt_name').value;
            var msgBoxStytle = "minebox";
        
            var chat_div = document.getElementById('div_chat');
            var xmldoc = receiveReq.responseXML;
            var message_nodes = xmldoc.getElementsByTagName("message");
            var n_messages = message_nodes.length
            for (i = 0; i < n_messages; i++) {
                var user_node = message_nodes[i].getElementsByTagName("user");
                var text_node = message_nodes[i].getElementsByTagName("text");
                var time_node = message_nodes[i].getElementsByTagName("time");
                
                if(myName != user_node[0].firstChild.nodeValue) {
                    msgBoxStytle = "adminbox";
                } else {
                    msgBoxStytle = "minebox";
                }
                var msgBox = getMyChatBox(
                    user_node[0].firstChild.nodeValue,
                    time_node[0].firstChild.nodeValue,
                    text_node[0].firstChild.nodeValue,
                    msgBoxStytle);
                chat_div.appendChild(msgBox);
                chat_div.scrollTop = chat_div.scrollHeight;
                lastMessage = (message_nodes[i].getAttribute('id'));
            }
            var room = xmldoc.getElementsByTagName("room"); 
            id = room[0].getAttribute('id');
        }
        
        if(!mTimer) {
            mTimer = setTimeout('getChatText();', 2000); //Refresh our chat in 1,5 seconds
        }
    }
    
    function getMyChatBox(username, time, message, substyle) {
        var mybox = document.createElement("div");
        mybox.className = "messagebox " +  substyle;
        
        var row1 = document.createElement("div");
        row1.innerHTML = "<span class=\"name\">"+username+"</span><span class=\"time\">"+time+"</span>";
        mybox.appendChild(row1);

        var row2 = document.createElement("div");
		row2.innerHTML = message;
        mybox.appendChild(row2);
        
        return mybox;
    }
    
    //This functions handles when the user presses enter.  Instead of submitting the form, we
    //send a new message to the server and return false.
    function blockSubmit() {
        sendChatText();
        return false;
    }
    
    
    
    
    
    
    
    
    //This cleans out the database so we can start a new chat session.
    function resetChat() {
        if (sendReq.readyState == 4 || sendReq.readyState == 0) {
            sendReq.open("POST", '<?php echo $requestURL;?>?chat=' + id + '&last=' + lastMessage, true);
            sendReq.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
            sendReq.onreadystatechange = handleResetChat; 
            var param = 'action=reset';
            sendReq.send(param);
            document.getElementById('txt_message').value = '';
        }							
    }
    //This function handles the response after the page has been refreshed.
    function handleResetChat() {
        document.getElementById('div_chat').innerHTML = '';
        getChatText();
    }	
    
    function smile(code){				
        this.code = code;
        document.getElementById('txt_message').value += code
    }
    
    function onEnter(event) {
        var key = event.keyCode;
        if (key == 13) {
            blockSubmit();
            return false;
        }
    }
    
</script>




<p id="p_status"><img src="<?php echo $pluginURL."res/user.png"; ?>"></p>
<div id="div_chat" class="panel panel-default">
    <div><b><?php echo __('Welcome to Live Chat','jms-live-chat'); ?></b></div>
</div>

<form id="frmmain" name="frmmain" onsubmit="return blockSubmit();">
    <div class="form-group">
        <label for="exampleInputEmail1"><?php echo __('Name','jms-live-chat'); ?></label>
        <input type="text" class="form-control" id="txt_name" name="txt_name" value="<?php echo $chatName;?>">
    </div>
    <div class="form-group">
        <label for="exampleInputPassword1"><?php echo __('Message','jms-live-chat'); ?></label>
        <textarea class="form-control" rows="3" id="txt_message" name="txt_message" onkeyup="onEnter(event);"></textarea>
    </div>
    <button type="submit" class="btn btn-primary"><?php echo __('Submit','jms-live-chat'); ?></button>
</form>