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

if (isset($_REQUEST['fID'])) {
	// we are replacing a file
	$fr = File::getByID($_REQUEST['fID']);
	$file_permissions = new Permissions($fr);
	if (!$file_permissions->canEditFileContents()) {
		die(t("Unable to add files."));
	}
} else {
	$fr = false;
}

Loader::library("file/importer");
Loader::library('3rdparty/Zend/Http/Client');
Loader::library('3rdparty/Zend/Uri/Http');
Loader::helper('mime');
$valt = Loader::helper('validation/token');
$file = Loader::helper('file');

if ($valt->validate('import_remote')) {
	//This section parses and validates the URLs from the form
	//to ensure that they'd be valid locations (in theory)
	$incoming_urls = array();
	if (!function_exists('iconv_get_encoding')) {
		$error = t('Remote URL import requires the iconv extension enabled on your server.');
	} else {
		for ($i = 1; $i < 6; $i++) {
			$this_url = trim($_REQUEST['url_upload_' .$i]);

			// did we get anything?
			if (!strlen($this_url)) continue;

			// validate URL
			if (Zend_Uri_Http::check($this_url)) {
				// URL appears to be good... add it
				$incoming_urls[] = $this_url;
			} else {
				$error = Loader::helper('text')->specialchars($this_url) . t(' is not a valid URL.');
				$statusCode = '400 Bad Request';
				break;
			}
		}

		if (count($incoming_urls) == 0 && strlen($error) == 0) {
			$error = t('You must specify at least one valid URL.');
			$statusCode = '400 Bad Request';
		}
	}
} else {
	$error = $valt->getErrorMessage();
	$statusCode = '401 Unauthorized';
}

// if we haven't gotten any errors yet then try to process the form
$fnames = array();
$import_responses = array();
if (strlen($error) == 0) {
	// iterate over each incoming URL adding if relevant
	foreach($incoming_urls as $this_url) {
		// try to D/L the provided file
		$client = new Zend_Http_Client($this_url);
		$response = $client->request();

		if ($response->isSuccessful()) {
			$uri = Zend_Uri_Http::fromString($this_url);
			$fname = '';
			$fpath = $file->getTemporaryDirectory();

			// figure out a filename based on filename, mimetype, ???
			if (preg_match('/^.+?[\\/]([-\w%]+\.[-\w%]+)$/', $uri->getPath(), $matches)) {
				// got a filename (with extension)... use it
				$fname = $matches[1];
			} else if (! is_null($response->getHeader('Content-Type'))) {
				// use mimetype from http response
				$fextension = MimeHelper::mimeToExtension($response->getHeader('Content-Type'));
				if ($fextension === false) {
					$error = t('Error for file at '.$uri.' - Unknown or unsupported file type: ') . $response->getHeader('Content-Type') . ' (No files were uploaded)';
					$statusCode = '400 Bad Request';
					break;
				} else {
					$dh = Loader::helper('date');
					/* @var $dh DateHelper */
					// make sure we're coming up with a unique filename
					do {
						// make up a filename based on the current date/time, a random int, and the extension from the mime-type
						$fname = $dh->formatSpecial('FILENAME') . mt_rand(100, 999) . '.' . $fextension;
					} while (file_exists($fpath.'/'.$fname));
				}
			} //else {
				// if we can't get the filename from the file itself OR from the mime-type I'm not sure there's much else we can do
			//}

			//Add these into an array and stash them in a tmp directory
			if (strlen($fname)) {
				$fnames[] = $fname;
				$handle = fopen($fpath.'/'.$fname, "w");
				fwrite($handle, $response->getBody());
				fclose($handle);
			} else {
				// could not figure out a file name
				$error = t('Could not determine the name of the file at ') . $this_url;
				$statusCode = '400 Bad Request';
			}
		} else {
			// warn that we couldn't download the file
			$error = t('There was an error downloading ') . $this_url;
			$statusCode = '400 Bad Request';
		}
	}

	//if there were no errors getting any of the files, import them into Concrete5
	if(strlen($error) == 0){
		foreach($fnames as $fname){
			if ($fp->canAddFileType($cf->getExtension($fname))) {
				$fi = new FileImporter();
				$resp = $fi->import($fpath.'/'.$fname, $fname, $fr);
			} else {
				$resp = FileImporter::E_FILE_INVALID_EXTENSION;
			}
			if (!($resp instanceof FileVersion)) {
				$error = $fname . ': ' . FileImporter::getErrorMessage($resp) . "\n";
				$statusCode = '400 Bad Request';
				break;
			} else {
				$import_responses[] = $resp;

				if (!is_object($fr)) {
					// we check $fr because we don't want to set it if we are replacing an existing file
					$respf = $resp->getFile();
					$respf->setOriginalPage($_POST['ocID']);
				}

			}

			// clean up the file
			unlink($fpath.'/'.$fname);
		}
	}
}

if (strlen($error) > 0) {
	$info = array('error' => $error);
	if(count($import_responses) > 0){
		$info['error'] .= " (NOTE: ".count($import_responses)." file(s) were uploaded successfully before this error occurred)";
		foreach($import_responses as $resp){
			$info['files'][] = $resp->getFileID();
		}
	}
} else {
	foreach($import_responses as $resp){
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
