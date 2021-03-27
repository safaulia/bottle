<?php
namespace RepostInstagram;

require __DIR__ . '/vendor/autoload.php';


/**
 * Class RepostInstagram
 * @package RepostInstagram
 */
class MasterBot{
    public const BOT_LIKE_MODE = "BOT_LIKE_MODE";
    public const BOT_FOLLOW_MODE = 'BOT_FOLLOW_MODE';
    public const BOT_DM_MODE = 'BOT_DM_MODE';
    protected $ig;
    protected $username;
    protected $password;
    protected $selfTargetingMode = false;
    protected $targets = [];
    protected $comments = [];
    protected $messages = [];
    protected $isLogedIn = false;
    protected $lastFollowingTime;
    protected $lastDirrectMessageTime;
    protected $lastLikeTime;
    protected $lastCommentTime;
    protected $totalAction;
    public function __construct(){
        \InstagramAPI\Instagram::$allowDangerousWebUsageAtMyOwnRisk = true;
        $this->ig = new \InstagramAPI\Instagram();
        $totalAction = 0;
    }

    public function getRankToken()
    {
        return \InstagramAPI\Signatures::generateUUID();
    }

    public function setComments($comments)
    {
        if(is_array($comments)){
            $this->comments = $comments;
        }else{
            $this->comments[] = $comments;
        }
        return $this;
    }

    public function setMessages($messages)
    {
        if(is_array($messages)){
            $this->messages = $messages;
        }else{
            $this->messages[] = $messages;
        }
        return $this;
    }

    public function chooseRandomMessage()
    {
        return $this->messages[array_rand($this->messages,1)];
    }

    public function setCredentials($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
        return $this;
    }

    public function getMediaLikersIds($mediaId)
    {
        $result = [];
        $users = $this->ig->media->getLikers($mediaId)->getUsers();
        foreach ($users as $user){
            $result[] = $user->getPk();
        }
        return $result;
    }

    public function sendMessages($pk, $messages)
    {
        if (!is_array($pk)){
            $pk = array($pk);
        }
        $this->lastDirrectMessageTime = microtime(true);
        return $this->ig->direct->sendText(["users" => $pk], $messages);
    }

    public function getUserPostIds($userId)
    {
        $result = [];
        $timelinePost = $this->ig->timeline->getUserFeed($userId)->getItems();
        foreach ($timelinePost as $item){
            $result[] = $item->getId();
        }
        return $result;
    }

    public function login()
    {
        try {
            $this->ig->login($this->username, $this->password);
            $this->isLogedIn = true;
        }catch (Exception $e){
            print($e->getMessage());
        }
        return $this;
    }

    public function setSelftargeting($selfTargetingMode = False)
    {
        $this->selfTargetingMode = $selfTargetingMode;
    }

    public function setUsernameTarget($usernames)
    {
        if (is_array($usernames)){
            foreach ($usernames as $username){
                $this->targets[] = $username;
            }
            return $this;
        }
        $this->targets[] = $usernames;
        return $this;
    }

    public  function getUserId($username)
    {
        if (!$this->isLogedIn){
            throw new Exception('login required');
        }
        return $this->ig->people->getUserIdForName($username);
    }

    public function getInstagram()
    {
        return $this->ig;
    }

    public function getUserFollowersIds($target){
        $targetId = $this->getUserId($target);
        return array_map(function($user){
            return $user->getPk();
        }, $this->ig->people->getFollowers($targetId, $this->getRankToken())->getUsers()
        );
    }

    public function followUser($id){
        $this->lastFollowingTime = microtime(true);
        return $this->ig->people->follow($id);
    }

    public function likeMedia($id){
        $this->lastLikeTime = microtime(true);
        return $this->ig->media->like($id)->getStatus();
    }

    public function commentMedia($text, $id){
        $this->lastCommentTime = microtime(true);
        return $this->ig->media->comment($id, $text)->getStatus();
    }

    public function isLikeAvailable()
    {
        if($this->lastLikeTime == null) return true;
        return (microtime(true) - $this->lastLikeTime) > rand(180,280);
    }

    public function isCommentAvailable()
    {
        if($this->lastCommentTime == null) return true;
        return (microtime(true) - $this->lastCommentTime) > rand(180,280);
    }
    public function isFollowAvailable()
    {
        if($this->lastFollowingTime == null) return true;
        return (microtime(true) - $this->lastFollowingTime) > rand(420,450);
    }
    public function isDirrectMessageAvailable()
    {
        if($this->lastDirrectMessageTime == null) return true;
        return (microtime(true) - $this->lastDirrectMessageTime) > rand(60,2*60);
    }
    public function run($target, $mode = [])
    {
        if ($target == null){
            throw new \Exception('Null target');
        }
        if (is_array($target)){
            $this->targets = $target;
        }else{
            $this->targets[] = $target;
        }

        while (true):
            if($this->isFollowAvailable() && in_array(MasterBot::BOT_FOLLOW_MODE, $mode)){
                $this->runFollowTask();
            }
            if($this->isDirrectMessageAvailable() && in_array(MasterBot::BOT_DM_MODE, $mode)){
                $this->runDirectTask();
            }
            if($this->isLikeAvailable() && in_array(MasterBot::BOT_LIKE_MODE, $mode)){
                $this->runLikeTask();
            }
            sleep(1);
        endwhile;
    }

    private function chooseSingleTarget(){
        return $this->targets[array_rand($this->targets)];
    }

    private function runFollowTask()
    {
        $followersIds = $this->getUserFollowersIds($this->chooseSingleTarget());
        $status = $this->followUser($followersIds[0])->getFriendshipStatus()->getFollowing() ? 'success' : 'fail';
        $this->incrementActionCount($status);
        echo $this->username." : ".$status." follow ". $followersIds[0]." total actions -> ".$this->totalAction.PHP_EOL;
    }

    private function runDirectTask()
    {
        $followersIds = $this->getUserFollowersIds($this->chooseSingleTarget());
        $status = $this->sendMessages($this->chooseRandomIds($followersIds), 'follower gratisnya kak cek bio :D')->getStatus();
        $this->incrementActionCount($status);
        echo $this->username." : ".$status." dm ". $followersIds[0]." total actions -> ".$this->totalAction.PHP_EOL;
    }

    private function runLikeTask()
    {
        $followersIds = $this->getUserFollowersIds($this->chooseSingleTarget());
        $targetId = $this->chooseRandomIds($followersIds);


        if ($targetId != null){
            $items = $this->ig->timeline->getUserFeed($targetId, null)->getItems();
            $mediaIds = [];
            foreach ($items as $item){
                $mediaIds[] = $item->getId();
            }
            if(count($mediaIds) == 0){
                echo "empty media from user, skipping". PHP_EOL;
                return;
            }
            $status = $this->likeMedia($mediaIds[0]);
            $this->incrementActionCount($status);
            echo $this->username." : ".$status." like ". $followersIds[0]." total actions -> ".$this->totalAction.PHP_EOL;        }
    }

    private function incrementActionCount(string $status)
    {
        if($status != 'fail'){
            $this->totalAction +=1;
        }
    }

    private function chooseRandomIds(array $followersIds)
    {
        $targetId = [];
        $logIds = $this->getLogIds('log.txt');
        foreach ($followersIds as $id){
            if(! $this->ig->people->getInfoById($id)->getUser()->getIsPrivate() && !in_array($id, $logIds)){
                $targetId []= $id;
            }
        }
        $target = array_rand($targetId,1);
        $this->writeLogIds($target, 'log.txt');
        return $target;
    }

    function getLogIds($fileName){
        return explode(PHP_EOL,file_get_contents($fileName));
    }

    function writeLogIds($id, $fileName){
        fwrite(fopen($fileName,'a+'), $id.PHP_EOL);
    }
}
