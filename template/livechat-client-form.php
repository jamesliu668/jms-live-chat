<?php
    $pluginURL = plugins_url()."/jms-live-chat/";
    $requestURL = admin_url( 'admin-ajax.php' );

    if ( is_user_logged_in() ) {
        $user = wp_get_current_user();
        $chatName = $user->user_nicename;
    } else {
        $chatName = __('Guest','jms-live-chat');
    }

    //check if the browser is mobile
    $isMobileBrowser = false;
    $useragent=$_SERVER['HTTP_USER_AGENT'];
    if(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4))) {
        //this is mobile, don't show the ads
        $isMobileBrowser = true;
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
            createCookie("chat_session_id", id, 30);
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

    function createCookie(name, value, days) {
        var expires = "";
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days*24*60*60*1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + value + expires + "; path=/";
    }

    function readCookie(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for(var i=0;i < ca.length;i++) {
            var c = ca[i];
            while (c.charAt(0)==' ') c = c.substring(1,c.length);
            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
        }
        return null;
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
    <div class="form-group msg-form-group">
        <label for="exampleInputPassword1"><?php echo __('Message','jms-live-chat'); ?></label>
        <textarea class="form-control" rows="
        <?php
            if($isMobileBrowser) {
                echo "1";
            } else {
                echo "3";
            }
        ?>
        " id="txt_message" name="txt_message" onkeyup="onEnter(event);"></textarea>
    </div>
    <button type="submit" class="btn btn-primary send-btn"><?php echo __('Submit','jms-live-chat'); ?></button>
</form>

<?php
    if($isMobileBrowser) {
?>

<style>
    #txt_message {
        height: 60px;
    }

    .send-btn {
        height: 60px;
        width: 20%;
    }

    .msg-form-group {
        float: left;
        width: 80%;
    }

    #frmmain {
        position: fixed;
        bottom: 0;
    }

</style>
<?php
    }
?>



<script>

    id = readCookie("chat_session_id");
    if(id == null) {
        id = 0;
    } else {
        getChatText()
    }

<?php
    if($isMobileBrowser) {
?>
/* For mobile version */
    jQuery( document ).ready(function() {
        var pw = jQuery("#div_chat").outerWidth()
        jQuery("#frmmain").width(pw);

        var formHeight = jQuery("#frmmain").outerHeight()
        jQuery("#div_chat").outerHeight(window.innerHeight - formHeight);
    });
<?php
    }
?>
</script>

