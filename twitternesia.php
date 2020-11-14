<?php
require __DIR__ . '/vendor/autoload.php';
$username = 'ind.travels';
$password = 'sayangrina';
$debug = false;
$truncatedDebug = false;
$ig = new \InstagramAPI\Instagram($debug, $truncatedDebug);

try {
    $ig->login($username, $password);
} catch (\Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
    exit(0);
}

while (True):
    if(shell_exec("python3.8 getimage.py")) {
        try{
            $photo = new \InstagramAPI\Media\Photo\InstagramPhoto('file.png');
            $ig->timeline->uploadPhoto($photo->getFile(), ['caption' => '#twitternesia']);
        } catch (Exception $exception){
            die($exception->getMessage());
        }
        print "succes sleep for 3 hours \n";
        sleep(60 * 60 * 4);
    }else{
        print "failed wait 1 hours \n";
        sleep(60*60*1);
    }
    endwhile;