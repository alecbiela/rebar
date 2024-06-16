<?php defined('C5_EXECUTE') or die("Access Denied.");

$cf = Loader::helper("file");
$valt = Loader::helper('validation/token');
Loader::library("file/importer");
$fp = FilePermissions::getGlobal();

//Check if uploading files is allowed
if (!$fp->canAddFiles()) {
	$error = FileImporter::getErrorMessage(FileImporter::E_PHP_FILE_ERROR_DEFAULT);
	$info = array('message'=>$error, 'error' => true);
	header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
	header('Content-Type: application/json');
	echo json_encode($info);
	exit;
}

$u = new User();
$error = "";
$errorCode = -1;
$statusCode = '200 OK';

$files = array();
if ($valt->validate('import_incoming')) {
	if( !empty($_REQUEST) ) {
		$fi = new FileImporter();
		foreach($_REQUEST as $k=>$name) {
			if(preg_match("#^send_file#", $k)) {

				if (!$fp->canAddFileType($cf->getExtension($name))) {
					$resp = FileImporter::E_FILE_INVALID_EXTENSION;
					$statusCode = '403 Forbidden';
				} else {
					$resp = $fi->import(DIR_FILES_INCOMING .'/'. $name, $name, $fr);
				}

				if (!($resp instanceof FileVersion)) {
					$errorCode = $resp;
					$statusCode = '500 Internal Server Error';
				} else {
					$files[] = $resp;
					//If remove files from incoming directory is set, remove this file
					if ($_REQUEST['removeFilesAfterPost'] == 1) {
						unlink(DIR_FILES_INCOMING .'/'. $name);
					}
					if (!is_object($fr)) {
						// we check $fr because we don't want to set it if we are replacing an existing file
						$respf = $resp->getFile();
						$respf->setOriginalPage($_REQUEST['ocID']);
					}
				}
			}
		}
	}

	if (count($files) == 0) {
		$error = t('You must select at least one file.');
		$statusCode = '400 Bad Request';
	}

} else {
	$error = $valt->getErrorMessage();
	$statusCode = '401 Unauthorized';
}

if ($errorCode > -1 && $error == '') {
	$error = FileImporter::getErrorMessage($errorCode);
}

if (strlen($error) > 0) {
	$info = array('error' => $error);
} else {
	foreach($files as $resp){
		$id = $resp->getFileID();
		$info['message'] = t('Upload Complete.');
		$info['error'] = false;
		$info['files'][] = $resp->getFileID();
	}
}

header($_SERVER['SERVER_PROTOCOL'] . ' ' . $statusCode);
header('Content-Type: application/json');
echo json_encode($info);
exit;
?>