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
define("database", "test"); // your database 
define("TBL_USERS","user"); // your users database table
define("TBL_MESSAGES", "messages"); // your messages database table
define("MY_WEBSITE_EMAIL", "you@example.com"); // your website email address

class Private_messaging_system{
	
	public function __construct()
	{
			$conn = new mysqli(server, user, password,database);
			if ($conn->connect_errno($conn)) {
			echo "Error: " . $conn->connect_error() ." - Fail to connect db ". NOW();
			sleep(3);
			} 


		//mysqli_connect(server,user,password) or die ("Mysql was unable to connect to a database using provided information!");
		//mysqli_select_db(database) or die ("Mysql was unable to select " . database . " database!");
	}
	/**
	*	$TO = username for which the message is meant
	* 	$MESSAGE = message that is sent to a user
	*	$SUBJECT = subject of a message
	*	$RESPOND = ID of conversation to which this message is a part of
	**/
	public function send_message($to, $message, $subject, $respond = 0){
		global $conn;
		$from = $_SESSION['username']; // ID of a user sending a message

		$message = $this->_validate_message($message); // validate message to see if it safe, to be passed to the database

		if($respond == 0){
			$query = "INSERT INTO `". TBL_MESSAGES ."` (`user_to`, `user_from`, `subject`, `message`) VALUES('" . $to . "', '" . $from . "', '" . $subject . "', '" . $message . "')";
		}else{
			$query = "INSERT INTO `" . TBL_MESSAGES . "` (`user_to`, `user_from`, `subject`, `message`, `respond`) VALUES(" . $to . ", " . $from . ", '" . $subject . "', '" . $message . "'," . $respond . ")";
		}

		if($this->_validate_message($message)){
			echo $query;
			$conn->query($query);
			//	$conn->query($query);
			// uncomment this function out if you want to email a user of a new message
			//$this->_email_user_of_new_message($to,$from,$subject);
			return TRUE;
		}else{
			return FALSE;
		}
	} // END send_message
	
	public function get_number_of_unread_messages(){
		global $conn;
		$id = $_SESSION['username'];
		$query = "SELECT COUNT(*) AS unread FROM " . TBL_MESSAGES . " WHERE user_to = '" . $id . "' AND respond = '0'";
		$result = $conn->query($query);
		$row = $conn->fetch_assoc($result);
		return $row['unread'];
	} // END get_number_of_unread_messages

	public function get_all_messages(){
		global $conn;
		$role = "sender_delete";
		$id = $_SESSION['username'];
		$query = $conn->query("SELECT user_to FROM " . TBL_MESSAGES . " ");
		while($data = mysqli_fetch_object($query)){
			if($data->user_to != $id){
				$role = "receiver_delete";
			}			
		}
		$query = "SELECT * FROM " . TBL_MESSAGES . " WHERE user_to = '" . $id . "' OR user_from = '" . $id . "' AND respond = 0 AND " . $role . " != 'n'";
		 $result = $conn->query($query);
		 $t ="";
		 while($row = $conn->fetch_assoc($result)) {
		 	$t = $t . "De: ".$row['user_to']." para ".$row['user_from']." <br> ".$row['subject']." <br> ".$row['message']."<br>";
		 }

		 return htmlspecialchars(trim($t));
	} // END get_all_messages

	public function get_message($message_id){
		global $conn;
		$role = "sender_delete";
		$id = $_SESSION['username'];
		$query = $conn->query("SELECT user_to FROM " . TBL_MESSAGES . " WHERE id = '" . $message_id . "'");
		while($data = $conn->fetch_object($query)){
			if($data->user_to != $id){
				$role = "receiver_delete";
			}			
		}
		$query = $conn->query("SELECT * FROM " . TBL_MESSAGES . " WHERE id = '" . $message_id . "' AND (user_to = '" . $id . "' OR user_from = '" . $id . "') OR respond = '" . $message_id . "' AND " . $role . " != 'n'");
		return htmlspecialchars(trim($query));
	} // END get_message

	public function delete_message($message_id){
		global $conn;
		$role = "sender_delete";
		$id = $_SESSION['username'];
		$query = $conn->query("SELECT user_to FROM " . TBL_MESSAGES . " WHERE id = '" . $message_id . "' OR respond = '" . $message_id . "'");
		while($data = $conn->fetch_object($query)){
			if($data->user_to != $id){
				$role = "receiver_delete";
			}			
		}

		$query1 = $conn->query("UPDATE " . TBL_MESSAGES . " SET " . $role . " = 'y' WHERE id = '" . $message_id . "'");
		$this->_check_for_deleted_messages();
	} // END delete_message

	public function delete_conversation($conversation_id){
		global $conn;
		$role = "sender_delete";
		$id = $_SESSION['username'];
		$query = $conn->query("SELECT user_to FROM " . TBL_MESSAGES . " WHERE id = '" . $message_id . "'");
		while($data = $conn->fetch_object($query)){
			if($data->user_to != $id){
				$role = "receiver_delete";
			}			
		}
		$conn->query("UPDATE " . TBL_MESSAGES . " SET " . $role . " = 'y' WHERE id = '" . $conversation_id . "'");
	}

	private function _check_for_deleted_messages(){
		global $conn;
		$conn->query("DELETE FROM " . TBL_MESSAGES . " WHERE sender_delete = 'y' AND receiver_delete 'y'"); // removes messages from DB if both sender and receiver have deleted it.
	} // END _check_for_deleted_messages

	/**
	*	$to = ID of a user who will receive message
	*	$from = ID of a user who is sending message
	*	$subject = subject of a message
	**/
	private function _email_user_of_new_message($to,$from,$subject){
		global $conn;
		$r = $conn->fetch_object($conn->query("SELECT first_name,last_name,email FROM " . TBL_USERS . " WHERE id = '" . $to . "'"));
		$u = $conn->fetch_object($conn->query("SELECT first_name,last_name FROM " . TBL_USERS . " WHERE id = '" . $from . "'"));
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
