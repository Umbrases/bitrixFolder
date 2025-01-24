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
    foreach ($contentArchive as $keyArc => $valueArc) {
        if (!empty($contentFolder)) {
            foreach ($contentFolder as $keyFol => $valueFol) {
                if ($valueArc['TYPE'] == 'folder') {
                    if ($valueArc['NAME'] == $valueFol['NAME']) {
                        setFolderChildren($valueArc['ID'], $valueFol['ID']);
                    } else {
                        $folder = getFolder($folderId, $valueArc['NAME']);

                        if (empty($folder)) {
                            $folderAdd = setFolder($folderId, $valueArc['NAME']);

                            setFolderChildren($valueArc['ID'], $folderAdd['result']['ID']);
                        }
                    }
                } else {
                    if ($valueArc['NAME'] != $valueFol['NAME']) {
                        setFile($valueArc['ID'], $folderId);
                    }
                }
            }
        } else {
            if ($valueArc['TYPE'] == 'folder') {
                $folderAdd = setFolder($folderId, $valueArc['NAME']);

                setFolderChildren($valueArc['ID'], $folderAdd['result']['ID']);
            } else {
                setFile($valueArc['ID'], $folderId);
            }
        }
    }
}

function setFolderChildren($ArcId, $folderId)
{
    $contentArcChildren = getFolderAll($ArcId);
    $contentFolChildren = getFolderAll($folderId);

    comparison($contentArcChildren['result'], $contentFolChildren['result'], $folderId);
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

//926575 - id папки клиенты
//403 - id папки Kliyenty. Bankrotstvo

function start($start = 0)
{
    $log = "\n------------------------\n";
    $log .= print_r($start, 1);
    file_put_contents(getcwd() . '/hook.log', $log, FILE_APPEND);

    $result = getFolderAll(926575, $start);

    foreach ($result['result'] as $key => $value) {
        $folderArr = getFolderAll($value['ID']);

        foreach ($folderArr['result'] as $folderKey => $folderValue) {


            $folder = getFolder(403, $folderValue['NAME']);

            $contentFolder = getFolderAll($folder['ID']);
            $contentArchive = getFolderAll($folderValue['ID']);

            if (empty($contentFolder)) {
                $folderAdd = setFolder($folder['ID'], $contentArchive['NAME']);
                $contentFolder = getFolderAll($folderAdd['result']['id']);
            }

            comparison($contentArchive['result'], $contentFolder['result'], $folder['ID']);
        }
    }

    if(!empty($result['next'])) start($result['next']);
}

start();
