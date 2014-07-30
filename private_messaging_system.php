<?php

/**
*	author: Igor Ilic
*	email: mr.gigiliIII@gmail.com
*	website: http://github.com/gigili/Private-messaging-system
* 	version: 1.0
*	licence: MIT	
**/

define("server", "localhost");  // your database server
define("user", "root"); // your database username 
define("password", ""); // your database password
define("database", "messages"); // your database 
define("TBL_USERS","users"); // your users database table
define("TBL_MESSAGES", "messages"); // your messages database table
define("MY_WEBSITE_EMAIL", "you@example.com"); // your website email address

class Private_messaging_system{
	
	public function __construct()
	{
		mysql_connect(server,user,password) or die ("Mysql was unable to connect to a database using provided information!");
		mysql_select_db(database) or die ("Mysql was unable to select " . database . " database!");
	}

	/**
	*	$TO = USER_ID for which the message is meant
	* 	$MESSAGE = message that is sent to a user
	*	$SUBJECT = subject of a message
	*	$RESPOND = ID of conversation to which this message is a part of
	**/
	public function send_message($to, $message, $subject, $respond = 0){
		$from = $_SESSION['user_id']; // ID of a user sending a message

		$message = $this->_validate_message($message); // validate message to see if it safe, to be passed to the database

		if($respond == 0){
			$query = "INSERT INTO " . TBL_MESSAGES . " (user_to, user_from, subject, message) VALUES(" . $to . ", " . $from . ", '" . $subject . "', '" . $message . "')";
		}else{
			$query = "INSERT INTO " . TBL_MESSAGES . " (user_to, useer_from, subject, message, respond) VALUES(" . $to . ", " . $from . ", '" . $subject . "', '" . $message . "'," . $respond . ")";
		}
		if($this->validate_message($message)){
			mysql_query($query);
			// uncomment this function out if you want to email a user of a new message
			//$this->_email_user_of_new_message($to,$from,$subject);
			return TRUE;
		}else{
			return FALSE;
		}
	} // END send_message
	
	public function get_number_of_unread_messages(){
		$id = $_SESSION['user_id'];
		$query = "SELECT COUNT(opened) AS unread FROM " . TBL_MESSAGES . " WHERE user_to = '" . $id . "' AND respond = '0'";
		return mysql_query($query);
	} // END get_number_of_unread_messages

	public function get_all_messages(){
		$role = "sender_delete";
		$id = $_SESSION['user_id'];
		$query = mysql_query("SELECT user_to FROM " . TBL_MESSAGES . " WHERE id = '" . $message_id . "'");
		while($data = mysql_fetch_object($query)){
			if($data->user_to != $id){
				$role = "receiver_delete";
			}			
		}
		$query = "SELECT * FROM " . TBL_MESSAGES . " WHERE user_to = '" . $id . "' OR user_from = '" . $id . "' AND respond = 0 AND " . $role . " != 'n'";
		return mysql_query($query);
	} // END get_all_messages

	public function get_message($message_id){
		$role = "sender_delete";
		$id = $_SESSION['user_id'];
		$query = mysql_query("SELECT user_to FROM " . TBL_MESSAGES . " WHERE id = '" . $message_id . "'");
		while($data = mysql_fetch_object($query)){
			if($data->user_to != $id){
				$role = "receiver_delete";
			}			
		}
		$query = mysql_query("SELECT * FROM " . TBL_MESSAGES . " WHERE id = '" . $message_id . "' AND (user_to = '" . $id . "' OR user_from = '" . $id . "') OR respond = '" . $message_id . "' AND " . $role . " != 'n'");
		return $query;
	} // END get_message

	public function delete_message($message_id){
		$role = "sender_delete";
		$id = $_SESSION['user_id'];
		$query = mysql_query("SELECT user_to FROM " . TBL_MESSAGES . " WHERE id = '" . $message_id . "' OR respond = '" . $message_id . "'");
		while($data = mysql_fetch_object($query)){
			if($data->user_to != $id){
				$role = "receiver_delete";
			}			
		}

		$query1 = mysql_query("UPDATE " . TBL_MESSAGES . " SET " . $role . " = 'y' WHERE id = '" . $message_id . "'");
		$this->_check_for_deleted_messages();
	} // END delete_message

	public function delete_conversation($conversation_id){
		$role = "sender_delete";
		$id = $_SESSION['user_id'];
		$query = mysql_query("SELECT user_to FROM " . TBL_MESSAGES . " WHERE id = '" . $message_id . "'");
		while($data = mysql_fetch_object($query)){
			if($data->user_to != $id){
				$role = "receiver_delete";
			}			
		}
		mysql_query("UPDATE " . TBL_MESSAGES . " SET " . $role . " = 'y' WHERE id = '" . $conversation_id . "'");
	}

	private function _check_for_deleted_messages(){
		mysql_query("DELETE FROM " . TBL_MESSAGES . " WHERE sender_delete = 'y' AND receiver_delete 'y'"); // removes messages from DB if both sender and receiver have deleted it.
	} // END _check_for_deleted_messages

	/**
	*	$to = ID of a user who will receive message
	*	$from = ID of a user who is sending message
	*	$subject = subject of a message
	**/
	private function _email_user_of_new_message($to,$from,$subject){
		$r = mysql_fetch_object(mysql_query("SELECT first_name,last_name,email FROM " . TBL_USERS . " WHERE id = '" . $to . "'"));
		$u = mysql_fetch_object(mysql_query("SELECT first_name,last_name FROM " . TBL_USERS . " WHERE id = '" . $from . "'"));
		$name = $r->first_name . " " . $r->last_name;
		$uname = $u->first_name . " " . $u->last_name;
		$to_email = $r->email;
		//This message is optional, you can put what ever you want
		$body = "Dear " . $name . ",\n";
		$body .= "you have received a new message on our system.\n\n";
		$body .= "<table border='1'><tr>";
		$body .= "<th>From: </th>";
		$body .= "<th>Subject:</th>";
		$body .= "</tr><tr>";
		$body .= "<td>&nbsp;" . $uname . "&nbsp;</td>";
		$body .= "<td>&nbsp;" . $subject . "&nbsp;</td>";
		$body .= "</tr></table>\n";
		$body .= "Best regards,\n";
		$body .= "MyWebSite.com team";		
		
		mail($to_email,MY_WEBSITE_EMAIL,"New message: " . $subject,$body);
	} // END _email_user_of_new_message

	/**
	*	$message = message that will be validate for security purposes
	**/
	private function _validate_message($message){
		$return = trim($message); // trims all the white space at the beginning and the end of string
		$return = filter_var($message, FILTER_SANITIZE_STRING); // strips tags
		$return = filter_var($message, FILTER_SANITIZE_FULL_SPECIAL_CHARS); // Equivalent to calling htmlspecialchars()
		return $return;
	} // END _validate_message

} // END class

?>