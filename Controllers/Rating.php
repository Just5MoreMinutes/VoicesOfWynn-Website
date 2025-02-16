<?php

namespace VoicesOfWynn\Controllers;

use VoicesOfWynn\Models\Recording;
use VoicesOfWynn\Models\UserException;

class Rating extends Controller
{
	
	/**
	 * @inheritDoc
	 */
	public function process(array $args): bool
	{
		$recording = new Recording(array('id' => array_shift($args)));
		$action = array_shift($args);
		
		switch ($action) {
			case '+':
				if (isset($_COOKIE['votedFor'.$recording->id])) {
					header('HTTP/1.1 401 Unauthorized');
					exit();
				}
				$recording->upvote();
				header('HTTP/1.1 204 No Content');
				exit();
			case '-':
				if (isset($_COOKIE['votedFor'.$recording->id])) {
					header('HTTP/1.1 401 Unauthorized');
					exit();
				}
				$recording->downvote();
				header('HTTP/1.1 204 No Content');
				exit();
			case 'c':
				try {
					$recording->comment($_POST['name'], $_POST['email'], $_POST['content'], $_SESSION['antispam'],
						$_POST['antispam']);
					header('HTTP/1.1 204 No Content');
					exit();
				} catch (UserException $e) {
					header('HTTP/1.1 418 '.$e->getMessage());
					exit();
				}
			default:
				header('HTTP/1.1 400 Bad Request');
				exit();
		}
	}
}

