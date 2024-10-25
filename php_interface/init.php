<?php

use Dev\Site\Handlers\Iblock;

CModule::IncludeModule('dev.site');

dev_site_autoload(Iblock::class);

AddEventHandler('iblock', 'OnAfterIBlockElementAdd', Array(Iblock::class, 'addLog'));
AddEventHandler('iblock', 'OnAfterIBlockElementUpdate', Array(Iblock::class, 'addLog'));

$task = CAgent::GetList(
    [
        'NEXT_EXEC' => 'DESC'
    ],
    [
        'NAME' => '%clearOldLogs%',
        'MODULE_ID' => 'dev.site',
    ]
)->Fetch();

$taskTimeStamp = $task
    ? DateTime::createFromFormat('d.m.Y H:i:s', $task['NEXT_EXEC'])->getTimestamp()
    : strtotime('-1 day', time());

if ($taskTimeStamp < time()) {
    CAgent::AddAgent(
        'Dev\Site\Agents\Iblock::clearOldLogs();',
        'dev.site',
        'N',
        3600
    );
}
