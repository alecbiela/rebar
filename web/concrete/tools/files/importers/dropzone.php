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

//Check the C5 token
if ($valt->validate('upload')) {
	//Check for good file
	if (isset($_FILES['file']) && (is_uploaded_file($_FILES['file']['tmp_name']))) {
		//Check if uploading files of this extension is allowed
		if (!$fp->canAddFileType($cf->getExtension($_FILES['file']['name']))) {
			$resp = FileImporter::E_FILE_INVALID_EXTENSION;
			$statusCode = '403 Forbidden';
		} else {
			//Attempt to upload the file
			$fi = new FileImporter();
			$resp = $fi->import($_FILES['file']['tmp_name'], $_FILES['file']['name'], $fr);
		}
		if (!($resp instanceof FileVersion)) {
			$errorCode = $resp;
			$statusCode = '500 Internal Server Error';
		} else if (!is_object($fr)) {
			// we check $fr because we don't want to set it if we are replacing an existing file
			$respf = $resp->getFile();
			$respf->setOriginalPage($_POST['ocID']);
		}
	} else {
		//A server error with the file
		$errorCode = $_FILES['file']['error'];
		$statusCode = '400 Bad Request';
	}
} else if (isset($_FILES['file'])) {
	$error = $valt->getErrorMessage();
	$statusCode = '401 Unauthorized';
} else {
	$errorCode = FileImporter::E_PHP_FILE_ERROR_DEFAULT;
	$statusCode = '400 Bad Request';
}

if ($errorCode > -1 && $error == '') {
	$error = FileImporter::getErrorMessage($errorCode);
}

if (strlen($error) > 0) {
	$info = array('error' => $error);
} else {
	$id = $resp->getFileID();
	$info['message'] = t('Upload Complete.');
	$info['error'] = false;
	$info['id']		 = $resp->getFileID();
}

header($_SERVER['SERVER_PROTOCOL'] . ' ' . $statusCode);
header('Content-Type: application/json');
echo json_encode($info);
exit;
