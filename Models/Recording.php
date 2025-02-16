<?php

namespace VoicesOfWynn\Models;

class Recording
{
	public const IDEAL_COLORS = array(
		'red' => "#CC3333",
		'yellow' => '#CCCC33',
		'green' => '#33CC33',
		'blue' => '#3333CC',
		'purple' => '#CC33CC'
	);
	private const ANTISPAM_TOLLERANCE = 20; //In % out of 256
	
	private int $id = 0;
	private int $npcId = 0;
	private int $questId = 0;
	private int $line = 0;
	private string $file = '';
	private int $upvotes = 0;
	private int $downvotes = 0;
	private int $comments = 0;
	
	/**
	 * @param array $data Data returned from database, invalid items are skipped, multiple key names are supported for
	 * each attribute
	 */
	public function __construct(array $data)
	{
		foreach ($data as $key => $value) {
			switch ($key) {
				case 'id':
				case 'recording_id':
					$this->id = $value;
					break;
				case 'npcId':
				case 'npc_id':
				case 'npc':
					$this->npcId = $value;
					break;
				case 'questId':
				case 'quest_id':
				case 'quest':
					$this->questId = $value;
					break;
				case 'line':
				case 'number':
				case 'line_number':
					$this->line = $value;
					break;
				case 'file':
				case 'filename':
				case 'fileName':
				case 'recording':
				case 'audio':
					$this->file = $value;
					break;
				case 'upvotes':
				case 'likes':
					$this->upvotes = $value;
					break;
				case 'downvotes':
				case 'dislikes':
					$this->downvotes = $value;
					break;
				case 'comments':
				case 'comment_count':
				case 'commentCount':
					$this->comments = $value;
					break;
			}
		}
	}
	
	/**
	 * Generic getter
	 * @param $attr
	 * @return mixed
	 */
	public function __get($attr)
	{
		if (isset($this->$attr)) {
			return $this->$attr;
		}
		return null;
	}
	
	/**
	 * Upvotes this recording and sets the cookie preventing duplicate votes
	 * @return bool
	 * @throws \Exception
	 */
	public function upvote(): bool
	{
		setcookie('votedFor'.$this->id, 1, time() + 31536000, '/');
		return Db::executeQuery('UPDATE recording SET upvotes = upvotes + 1 WHERE recording_id = ?;', array($this->id));
	}
	
	/**
	 * Downvotes this recording and sets the cookie preventing duplicate votes
	 * @return bool
	 * @throws \Exception
	 */
	public function downvote(): bool
	{
		setcookie('votedFor'.$this->id, 1, time() + 31536000, '/');
		return Db::executeQuery('UPDATE recording SET downvotes = downvotes + 1 WHERE recording_id = ?;',
			array($this->id));
	}
	
	/**
	 * Adds a new comment to this recording
	 * @param $author
	 * @param $email
	 * @param $content
	 * @param $antispam
	 * @return bool
	 * @throws \Exception
	 */
	public function comment($author, $email, $content, $antispamQuestion, $antispamAnswer)
	{
		$idealColor = self::IDEAL_COLORS[$antispamQuestion];
		$redPart = hexdec(substr($idealColor, 1, 2));
		$greenPart = hexdec(substr($idealColor, 3, 2));
		$bluePart = hexdec(substr($idealColor, 5, 2));
		$absoluteTollerance = round(256 * self::ANTISPAM_TOLLERANCE / 100);
		
		$redPartAnswer = hexdec(substr($antispamAnswer, 1, 2));
		$greenPartAnswer = hexdec(substr($antispamAnswer, 3, 2));
		$bluePartAnswer = hexdec(substr($antispamAnswer, 5, 2));
		
		if (
			$redPartAnswer + $absoluteTollerance < $redPart || $redPartAnswer - $absoluteTollerance > $redPart ||
			$greenPartAnswer + $absoluteTollerance < $greenPart || $greenPartAnswer - $absoluteTollerance > $greenPart ||
			$bluePartAnswer + $absoluteTollerance < $bluePart || $bluePartAnswer - $absoluteTollerance > $bluePart
		) {
			throw new UserException('The colour you picked was too distinct from '.$antispamQuestion.'. Try again please.');
		}
		
		$author = trim($author);
		$email = trim($email);
		$content = trim($content);
		
		if (empty($author)) {
			$author = 'Anonymous';
		}
		if (empty($email)) {
			$email = 'nobody@nowhere.net';
		}
		if (empty($content)) {
			throw new UserException('No content submitted');
		}
		if (mb_strlen($author) > 31) {
			throw new UserException('Name is too long, 31 characters is the limit.');
		}
		if (mb_strlen($email) > 255) {
			throw new UserException('E-mail is too long, 255 characters is the limit.');
		}
		if (mb_strlen($content) > 65535) {
			throw new UserException('Comment is too long, 65,535 characters is the limit.');
		}
		
		$badwords = file('Models/BadWords.txt');
		foreach ($badwords as $badword) {
			if (mb_stripos($content, trim($badword)) !== false) {
				throw new UserException('The comment contains a bad word: "'.trim($badword).'". If you believe that it\'s not used as a profanity, join our Discord (link in the footer) and ping Shady#2948.');
			}
		}
		
		//Check e-mail format (might not allow some exotic but valid e-mail domains)
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			throw new UserException('E-mail address doesn\'t seem to be in the correct format. If you are sure that you entered your e-mail address properly, ping Shady#2948 on Discord.');
		}
		
		return Db::executeQuery('INSERT INTO comment (name,email,content,recording_id) VALUES (?,?,?,?);', array(
			$author,
			$email,
			$content,
			$this->id
		));
	}
}

