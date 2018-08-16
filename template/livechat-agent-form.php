<?php
    $pluginURL = plugins_url()."/jms-live-chat/";
    $requestURL = admin_url( 'admin-ajax.php' );
    if ( is_user_logged_in() ) {
        $user = wp_get_current_user();
        $username = $user->user_nicename;
    } else {
        $username = __('Guest','jms-live-chat');
    }
?>

<div>
<script language="JavaScript" type="text/javascript">
	var sendReq = getXmlHttpRequestObject();
	var receiveReq = getXmlHttpRequestObject();
	var receiveReqRoom = getXmlHttpRequestObject();	
	var lastMessage = 0;
	var lastRoom = 0;			
	var id = 0;
	var mTimer = null;
	var mTimerRooms;
	var currentCustomerObj = null;
	var chatSessionList = new Array();
    var chatSessionMsgCounter = {};
	
	var predefinedMsg = new Array();

	jQuery( document ).ready(function() {
		getChatRoom();
	});

	//Function for initializating the page.
	function startChat(room_id) {
		//Clear message counter
		lastMessage = 0;

		//Set the focus to the Message Box.
		document.getElementById('txt_message').focus();
		document.getElementById('div_chat').innerHTML = '';
		
		//indicate the customer
		var customerObj = document.getElementById('customer_' + room_id);
		customerObj.className = "list-group-item chatting";
		if(currentCustomerObj != null) {
			var oldcustomerObj = document.getElementById(currentCustomerObj);
			if(oldcustomerObj != null && oldcustomerObj.className != "list-group-item not-active") {
				oldcustomerObj.className = "list-group-item";
			}
		}
		currentCustomerObj = 'customer_' + room_id;

		//Start Recieving Messages.
		id = room_id;
		getChatText();
	}
	
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
			alert("Please choose a customer to chat!");
			return;
		}
	
		if (receiveReq.readyState == 4 || receiveReq.readyState == 0) {
            receiveReq.open("POST", '<?php echo $requestURL;?>', true);
            receiveReq.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
            receiveReq.onreadystatechange = handleReceiveChat;
            var param = 'chat=' + id;
            param += '&last=' + lastMessage;
            param += '&task=get-by-agent';
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
			var param = 'message=' + message;
			param += '&name=' + document.getElementById('txt_name').value;
			param += '&chat=' + id;
			param += '&last=' + lastMessage;
			param += '&task=reply-by-agent';
			param += '&action=jms_livechat';
			sendReq.send(param);
			document.getElementById('txt_message').value = '';
		}
	}
	
	//When our message has been sent, update our page.
	function handleSendChat() {
		//Clear out the existing timer so we don't have 
		//multiple timer instances running.
		if (sendReq.readyState == 4) {
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
				if(user_node[0].firstChild == null) {
					user_node[0].append("guest");
				}
				var text_node = message_nodes[i].getElementsByTagName("text");
				var time_node = message_nodes[i].getElementsByTagName("time");
				
				if(myName != user_node[0].firstChild.nodeValue) {
					msgBoxStytle = "adminbox";
				} else  {
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
            
            if(n_messages > 0) {
                document.getElementById('soundNotification').play();
            }

			if(!mTimer) {
				mTimer = setTimeout('getChatText();',1500); //Refresh our chat in 1,5 seconds
			}
		}
	}

	//This functions handles when the user presses enter.  Instead of submitting the form, we
	//send a new message to the server and return false.
	function blockSubmit() {
		sendChatText();
		return false;
	}
	
	
/*
	//This saves the chat to another table, so we can review it on a later time.
	function saveChat() {
		if (sendReq.readyState == 4 || sendReq.readyState == 0) {
			sendReq.open("POST", 'components/com_livechatsupport/getChatRoom.php?chat=' + id + '&last=' + lastMessage, true);
			sendReq.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
			var param = 'action=save';
			sendReq.send(param);
			document.getElementById('txt_message').value = '';
			document.getElementById('div_chat').innerHTML = 'chat saved...';
		}							
	}			

	
	//This cleans out the database so we can start a new chat session.
	function resetChatRoom() {
		if (sendReq.readyState == 4 || sendReq.readyState == 0) {
			sendReq.open("POST", 'components/com_livechatsupport/getChatRoom.php?chat=' + id + '&last=' + lastMessage, true);
			sendReq.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
			sendReq.onreadystatechange = handleResetChatRoom; 
			var param = 'action=reset';
			sendReq.send(param);
			document.getElementById('txt_message').value = '';
		}							
	}

	//This function handles the response after the page has been refreshed.
	function handleResetChatRoom() {
		document.getElementById('div_chat').innerHTML = '';
		document.getElementById('div_rooms').innerHTML = '';
		lastRoom = 0;
		getChatText();
	}
	*/
	

	//Gets the current chat rooms from the server
	function getChatRoom() {
		if (receiveReqRoom.readyState == 4 || receiveReqRoom.readyState == 0) {
			receiveReqRoom.open("POST", '<?php echo $requestURL; ?>', true);
			receiveReqRoom.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
			receiveReqRoom.onreadystatechange = handleChatRooms;
			var param = 'last=' + lastRoom;
			param += '&task=getroom';
			param += '&action=jms_livechat';
			receiveReqRoom.send(param);
		}		
	}

	//Function for handling the chat rooms
	function handleChatRooms() {
		if (receiveReqRoom.readyState == 4) {
			var room_div = document.getElementById('div-chat-room');
			var roomHTML = "<div class=\"list-group-title\"><?php echo __('Customers Who Need Help','jms-live-chat'); ?></div>";
			var customerStyle = "list-group-item";
			var isNewMsg = false;
			var xmldoc = receiveReqRoom.responseXML;
			if(xmldoc) {
				var message_nodes = xmldoc.getElementsByTagName("chat"); 
				var n_messages = message_nodes.length;
				for (i = 0; i < n_messages; i++) {
					var name_node = message_nodes[i].getElementsByTagName("name");
					var count_node = message_nodes[i].getElementsByTagName("count");
					
                    //a new customer is coming
					if(chatSessionList.indexOf(message_nodes[i].getAttribute('id')) == -1) {
						chatSessionList.push(message_nodes[i].getAttribute('id'));
					}
					
                    //it's current served customer
					if(message_nodes[i].getAttribute('id') == id) {
						customerStyle = "list-group-item chatting";
						currentCustomerObj = "customer_" + id;
					} else {
						customerStyle = "list-group-item";
					}
					
                    var key = "session" + message_nodes[i].getAttribute('id');
                    if(chatSessionMsgCounter[key] == undefined) {
                        chatSessionMsgCounter[key] = 0;
                    }
                    //build the chat room customer items
					if(count_node[0].firstChild.nodeValue != 0) {
						if(name_node[0].firstChild != null) {
							roomHTML += "<a id=\"customer_" + message_nodes[i].getAttribute('id') + "\" href=\"javascript:startChat(" + message_nodes[i].getAttribute('id') + ");\" class=\""+ customerStyle +"\"><span>" + name_node[0].firstChild.nodeValue + "</span><span class=\"badge\">" + count_node[0].firstChild.nodeValue +"</span></a>";
						} else {
							//the client doesn't provide name
							roomHTML += "<a id=\"customer_" + message_nodes[i].getAttribute('id') + "\" href=\"javascript:startChat(" + message_nodes[i].getAttribute('id') + ");\" class=\""+ customerStyle +"\"><span>" + "Guest" + "</span><span class=\"badge\">" + count_node[0].firstChild.nodeValue +"</span></a>";
						}
                        
                        //mark as new message
                        if(chatSessionMsgCounter[key] < count_node[0].firstChild.nodeValue) {
                            isNewMsg = true;
                            chatSessionMsgCounter[key] = count_node[0].firstChild.nodeValue;
                        }
					} else {
						if(name_node[0].firstChild != null) {
							roomHTML += "<a id=\"customer_" + message_nodes[i].getAttribute('id') + "\" href=\"javascript:startChat(" + message_nodes[i].getAttribute('id') + ");\" class=\""+ customerStyle +"\"><span>" + name_node[0].firstChild.nodeValue + "</span></a>";
						} else {
							roomHTML += "<a id=\"customer_" + message_nodes[i].getAttribute('id') + "\" href=\"javascript:startChat(" + message_nodes[i].getAttribute('id') + ");\" class=\""+ customerStyle +"\"><span>" + "Guest" + "</span></a>";
						}
						chatSessionMsgCounter[key] = 0;
					}
					
					lastRoom = (message_nodes[i].getAttribute('id'));
				}
			}
			
			if(isNewMsg) {
				document.getElementById('soundNotification').play();
			}
			
			room_div.innerHTML = roomHTML;
			room_div.scrollTop = room_div.scrollHeight;
			
			if(mTimerRooms != null) {
				clearTimeout(mTimerRooms);
			}
			mTimerRooms = setTimeout('getChatRoom();',2000); //Refresh our chat in 2 s\econds
		}
	}

	function smile(code){				
		this.code = code;
		document.getElementById('txt_message').value += code
	}
	
	function setTemplateMessage(id){				
		document.getElementById('txt_message').value = predefinedMsg[id]
		document.getElementById('txt_message').focus();
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
			
	function onEnter(event) {
		var key = event.keyCode;
		if (key == 13) {
			blockSubmit();
			return false;
		}
	}

	//This cleans out the database so we can start a new chat session.
	function finishChat() {
		if (sendReq.readyState == 4 || sendReq.readyState == 0) {
			sendReq.open("POST", '<?php echo $requestURL;?>', true);
			sendReq.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
			sendReq.onreadystatechange = handleFinishChat; 
			var param = 'action=jms_livechat';
			param += '&chat=' + id;
			param += '&task=finish-by-agent';
			sendReq.send(param);
			
			if(currentCustomerObj != null) {
				var oldcustomerObj = document.getElementById(currentCustomerObj);
				if(oldcustomerObj != null) {
					oldcustomerObj.className = "list-group-item not-active";
				}
			}
			
			document.getElementById('txt_message').value = '';
			document.getElementById('div_chat').innerHTML = '';
			
			var indexOfSession = chatSessionList.indexOf(id.toString());
			chatSessionList.splice(indexOfSession,1);
		}							
	}
	
	//This function handles the response after the page has been refreshed.
	function handleFinishChat() {
	}	
</script>

<legend><?php echo __('Live Support Agent','jms-live-chat'); ?></legend>
<p id="p_status"><img src="<?php echo $pluginURL."res/user.png"; ?>">
</p>

<div class="row">
	
	<div id="div-chat-room" class="list-group col-md-4">
	</div>

	<div class="col-md-8">
		<div id="div_chat" class="panel panel-default">
			<div><b></b></div>
		</div>
		
		<form id="frmmain" name="frmmain" onsubmit="return blockSubmit();">
			<div class="form-group">
				<div><label for="exampleInputEmail1"><?php echo __('Agent Name','jms-live-chat'); ?></label></div>
				<input disable type="text" class="form-control" id="txt_name" name="txt_name" value="<?php echo $username;?>">
			</div>
			
			<div class="form-group">
				<div><label for="exampleInputPassword1"><?php echo __('Message','jms-live-chat'); ?></label></div>
				<textarea style="width: 100%" class="form-control" rows="3" id="txt_message" name="txt_message" onkeyup="onEnter(event);"></textarea>
			</div>

			<button type="submit" class="btn btn-primary"><?php echo __('Submit','jms-live-chat'); ?></button>
			<div style="margin: 0 0 0 10px; display: inline-block; padding: 1px 5px; cursor: pointer;">
				<a onclick="finishChat()" class="btn btn-default"><?php echo __('Finish Chat','jms-live-chat'); ?></a>
			</div>
		</form>
	</div>
</div>
</div>
<audio id="soundNotification">
  <source src="<?php echo $pluginURL."res/notify.mp3"; ?>" type="audio/mpeg">
</audio>