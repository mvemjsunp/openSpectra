<?php


//require_once('connections/spectra.php');
require_once('user_manager.php');
require_once('page.php');


//mysql_select_db($database_openSpectra, $openSpectra);

//check for file uploads

if ($_FILES["origfile_url"]["error"] > 0) {
  echo "Error: " . $_FILES["origfile_url"]["error"] . "<br />";
} else {
  	//check the database for the next origfile and origcalib autoinc values
	$query = mysqli_query($conn, "SHOW TABLE STATUS LIKE 'DATASETS'"); 
	$row = mysqli_fetch_assoc($query);
	$next_inc_value = $row['Auto_increment'];
	 
	//this is the new name of the raw data file -- keep consistent across the raw and xml buckets
	$origfile_url_destination = "data-" . $next_inc_value . "-" . $_FILES['origfile_url']['name'];
  	$origfile_url_destination_file = $_FILES['origfile_url']['tmp_name'];
  	
  	//create a new bucket  
	$s3->putBucket("spectraview-rawfiles-data", S3::ACL_PUBLIC_READ);
	  
	//move the file
	if ($s3->putObjectFile($origfile_url_destination_file, "spectraview-rawfiles-data", $origfile_url_destination, S3::ACL_PUBLIC_READ)) {  
	    echo "We successfully uploaded your file.";  
	} else{  
	    echo "Something went wrong while uploading your file... sorry.";  
	}
	
	//if calibration set added, upload it
	if ($_FILES['origcalib_url']["error"] == 4) {
		//do nothing
	} else if ($_FILES['origcalib_url']["error"] > 0) {
	  echo "Error: " . $_FILES["origcalib_url"]["error"] . "<br />";
	} else {
		//this is the new name of the calibration file -- keep consistent across the raw and xml buckets
	  	$origcalib_url_destination = "calib-" . $next_inc_value . "-" . $_FILES['origcalib_url']['name'];
	  	$origcalib_url_destination_file = $_FILES['origcalib_url']['tmp_name'];
	  	
		//create a new bucket  
		$s3->putBucket("spectraview-rawfiles-calib", S3::ACL_PUBLIC_READ);
		  
		//move the file  
		if ($s3->putObjectFile($origcalib_url_destination_file, "spectraview-rawfiles-calib", $origcalib_url_destination, S3::ACL_PUBLIC_READ)) {  
		    echo "We successfully uploaded your file.";  
		} else{  
		    echo "Something went wrong while uploading your file... sorry.";  
		}		
	} 
} 

//echo "got through file upload";


//prep the form inputs for entry into database
$FNAME = isset($_POST['fname']) ? $_POST['fname'] : '';			//required
$MATERIAL = isset($_POST['material']) ? $_POST['material'] : '';			//required
$MOLECULARFORMULA = isset($_POST['formula']) ? $_POST['formula'] : '';			//required
$ISOTOPE = isset($_POST['isotope']) ? $_POST['isotope'] : '';			//required
$PUBLIC = isset($_POST['public']) ? $_POST['public'] : '1';			//required
$DATE_COLLECTED = isset($_POST['date_collected']) ? $_POST['date_collected'] : '';			//required
$DESCRIPTION = isset($_POST['description']) ? $_POST['description'] : '';			//required
$ORIGFILE_URL = isset($origfile_url_destination) ? $origfile_url_destination : '';  	//change to URL for item photo
$ORIGCALIB_URL = isset($origcalib_url_destination) ? $origcalib_url_destination : '';  	//change to URL for item photo

//add variable that you want to use for the inc value and set it equal to $argv[2]

$queryGetID = "SELECT * FROM USER WHERE USERNAME='" . $FNAME . "' LIMIT 0, 1";
$selectUserID = mysqli_query($conn, $queryGetID) or die(mysqli_error($conn));
$row_GetID = mysqli_fetch_assoc($selectUserID);
$USER_ID = $row_GetID['USER_ID'];
$FNAME = $USER_ID;



//add the new dataset to database

$qstr = "INSERT INTO DATASETS (USER_ID, MATERIAL, MOLECULARFORMULA, ISOTOPE, PUBLIC, DATE_COLLECTED, DESCRIPTION, ORIGFILE_URL, ORIGCALIB_URL) VALUES ('" . $FNAME . "', '" . $MATERIAL . "', '" . $MOLECULARFORMULA . "', '" . $ISOTOPE . "', '" . $PUBLIC . "', '" . $DATE_COLLECTED . "', '" . $DESCRIPTION . "', '" . $ORIGFILE_URL . "', '" . $ORIGCALIB_URL . "')";

$addNewDataset = mysqli_query($conn, $qstr) or die(mysqli_error($conn));
//echo "new row should be added!";


$python_exec_script = exec("python ../../scripts/processData.py .$origfile_url_destination .$next_inc_value");



?>
