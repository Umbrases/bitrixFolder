<?php

require_once (__DIR__ .'/crest.php');
require_once (__DIR__ .'/getQuery.php');

function getFolder($folderId, $folderName)
{
    return getQuery('disk.folder.getchildren', [
        'id' => $folderId,
        'filter' =>[
            'NAME' => $folderName,
        ]])['result'][0];
}

function getFolderAll($folderId, $start = 0)
{
    return getQuery('disk.folder.getchildren', [
        'id' => $folderId,
        'start'=>$start,
        ]);
}

function comparison($contentArchive, $contentFolder, $folderId)
{
    foreach ($contentArchive as $keyArchive => $valueArchive) {
        foreach ($contentFolder as $keyFolder => $valueFolder) {
            if ($valueArchive['NAME'] == $valueFolder['NAME']) {
                if ($valueArchive['TYPE'] == 'folder') {
                    $contentArc = getFolderAll($valueArchive['ID']);
                    $contentFol = getFolderAll($valueFolder['ID']);

                    if (!empty($contentFol['result'])) {
                        comparison($contentArc['result'], $contentFol['result'], $valueFolder['ID']);
                    } else {
                        foreach ($contentArc['result'] as $key => $value) {
                            if ($value['TYPE'] == 'folder') {
                                setFolder($valueFolder['ID'], $value['NAME']);
                            }
                        }
                    }
                }
            } else {
                if ($valueArchive['TYPE'] == 'folder') {
                    setFolder($folderId, $valueArchive['NAME']);
                } elseif ($valueArchive['TYPE'] == 'file') {
                    setFile($valueArchive['ID'], $folderId);
                }
            }
        }
    }
}

function setFolder($folderId, $folderName)
{
    return getQuery('disk.folder.addsubfolder', [
        'id' => $folderId,
        'data' =>[
            'NAME' => $folderName,
        ]]);
}

function setFile($fileId, $folderId)
{
    getQuery('disk.file.copyto', [
        'id' => $fileId,
        'targetFolderId' => $folderId,
    ]);
}

function preprint($data){
    echo '<pre>';
    print_r($data);
    echo '</pre>';
}

//926575 - id папки клиенты
//403 - id папки Kliyenty. Bankrotstvo

$result = getFolderAll(926575);

if(!empty($result['next'])) $next = $result['next'];

foreach ($result['result'] as $key => $value) {
    $folderArr = getFolderAll($value['ID']);

    foreach ($folderArr['result'] as $folderKey => $folderValue) {
        $folder = getFolder(403, $folderValue['NAME']);

        $contentFolder = getFolderAll($folder['ID']);
        $contentArchive = getFolderAll($folderValue['ID']);

        if (!empty($contentFolder)){
            comparison($contentArchive['result'], $contentFolder['result'], $folder['ID']);
        }else{
            setFolder($folder['ID'], $contentArchive['NAME']);
        }
    }
}
