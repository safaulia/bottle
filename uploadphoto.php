<?php
require __DIR__ . '/vendor/autoload.php';
$username = 'dakwahchanels';
$password = 'makestories';
$debug = false;
$truncatedDebug = false;
$ig = new \InstagramAPI\Instagram($debug, $truncatedDebug);
$self_following_target = true;
$target = ['xagusart'] ;


try {
    $ig->login($username, $password);
} catch (\Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
    exit(0);
}
$user = $ig->people->getInfoByName('nsrinanp');

print_r($user);


//while (True):
//try {
//
//    if($self_following_target){
//        $following_self = $ig->people->getSelfFollowing(\InstagramAPI\Signatures::generateUUID())->getUsers();
//
//        foreach ($following_self as $user) {
//            $username_target[] = $user->getUsername();
//        }
//    }else{
//        $username_target = $target;
//    }
//
//
//    foreach ($username_target as $username) {
//        echo " get post from " . $username . ' :D' . PHP_EOL;
//        $userId = $ig->people->getUserIdForName($username);
//        $items = $ig->timeline->getUserFeed($userId, null)->getItems();
//        foreach ($items as $item) {
//            $old_ids = getOldIds();
//            if (!in_array($item->getId(), $old_ids)) {
//                uploads($ig, $item, $item->getMediaType());
//                break;
//            }
//        }
//    }
//
//} catch (\Exception $e) {
//    echo 'Something went wrong: '.$e->getMessage()."\n";
//}
//endwhile;

function hasPhoneNumber($text){
    $pattern = '~\b\d[- /\d]*\d\b~';
    preg_match_all($pattern,$text,$out);
    $phone_num = [];
    foreach($out[0] as $num){
        if(strlen($num) > 5){
            array_push($phone_num, $num);
        }

    }
    return count($phone_num) > 0;
}
function hasPromotionWord($text){
    $list_promotion_word = ['promo', 'order', 'giveaway', 'cod', 'gratis', 'shopee', 'wa', 'sukses',
        'viral', 'bisnis', 'rekomendasi'
    ];
    $has_promotion_word = false;
    $arr_word = explode(' ',strtolower($text));
    foreach($arr_word as $w){
        if (in_array(trim($w), $list_promotion_word)){
            $has_promotion_word = true;
        }
    }
    return $has_promotion_word;

}
function escapeUsername($text){
    return  preg_replace("[\@.*?(\ |\n|\.)]", '', $text);
}

function isPromotionPost($text){
    if($text == null) $text = '';
    return hasPhoneNumber($text) || hasPromotionWord($text);
}
function getOldIds(){
    return explode(PHP_EOL,file_get_contents('ids.txt'));
}
function uploads($ig, $item, $type = 1){
    if(!in_array($type,[1,2])) return;
    $url = $type == 1 ? $item->getImageVersions2()->getCandidates()[0]->getUrl()
        : $item->getVideoVersions()[0]->getUrl();
    $file_name = $type == 1 ? 'media.jpg' :'media.mp4';
    file_put_contents($file_name, file_get_contents($url));
    fwrite(fopen('ids.txt','a+'), $item->getId().PHP_EOL);
    if($item->getCaption() != null){

       $capt =  "Repost by @".$item->getUser()->getUsername().PHP_EOL.$item->getCaption()->getText();
    }else{
        $capt = '';
    }
    if(!isPromotionPost($capt)){
        if($type == 1):
            $photo = new \InstagramAPI\Media\Photo\InstagramPhoto($file_name);
            $ig->timeline->uploadPhoto($photo->getFile(), ['caption' => escapeUsername($capt)]);
            echo shell_exec('./tweet '.$file_name);
        else:
            $video = new \InstagramAPI\Media\Video\InstagramVideo($file_name);
            $ig->timeline->uploadVideo($video->getFile(), ['caption' => escapeUsername($capt), 'share_facebook' => 1]);
        endif;
        echo "sleeping for next post :D".PHP_EOL;
        for($i=0;$i<40; $i++) {
            sleep(rand(45, 90));
            echo "=";
        }
    }else{
        echo 'skiping promotion post !!'.PHP_EOL;
    }
    echo PHP_EOL;
}


