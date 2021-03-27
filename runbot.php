<?php
require __DIR__ .'/MasterBot.php';
$reposter = new RepostInstagram\MasterBot();
$reposter->setCredentials('followrsgratiscekbio001', 'sayangrina')
    ->login()
    ->run([ 'naisaalifiayuriza',
            'brisiajodie96',
            'rizkyfbian',
            'awkarin',
            'syifahadjureal',
            'fbyputrinc',
            'fiersabesari',
            'ochi24',
            'vaneshaass',
            'salshabillaadr',
            'radenrauf',
            'natasharizkynew',
            'rachelvennya',
            'okintph'],
        [
            RepostInstagram\MasterBot::BOT_FOLLOW_MODE
        ]
    );



