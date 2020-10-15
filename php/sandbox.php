<?php
//========== Global Parameters ==========

$msgIndex = 0;

$targetDB = '';
$querytype = 'sql';
$inputQuery = '';

$tableName = '';
$selection = '';

$errorMsg = array('');
$successMsg = array('');
$defaultTables = ['information_schema', 'mysql', 'performance_schema', 'sakila', 'sys', 'world'];

$search_result = null;

//========== Database Connection ==========

$servername = "localhost";
$username = "root";
$password = "<your password>";
$dbname = "information_schema";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn -> connect_error) {
  die("Connection failed: ".$conn -> connect_error);
}

//========== Button Actions ==========

if (isset($_POST['submit'])) {
  $selection = $_POST['sqldblist'];

  if ($selection !== 'Select Database') { $targetDB = $selection; }

  // Create connection
  $conn = new mysqli($servername, $username, $password, $targetDB);
  // Check connection
  if ($conn -> connect_error) {
    die($conn -> connect_error);
  }

  $search_result = null;

  $inputQuery = trim($_POST['inputQuery']);

  if (strpos(strtolower('###'.$inputQuery), 'create database')) {
    //updateMessages('error', 'Database creation not allowed on this platform.');
  }
  else if (strpos(strtolower('###'.$inputQuery), 'drop database')) // prefixing with ### 
  {
    //updateMessages('error', 'Database deletion not allowed on this platform.');
  }
  // else
  {
    if (0)//(mysqli_multi_query($conn, str_replace('<br>', '', $inputQuery)))
    {
      do {
        //check first result
        if ($result = mysqli_store_result()) {
          $search_result = $result; echo $inputQuery;
          //free the result and move on to next query
          mysqli_free_result($result);
        }
        else {
          updateMessages('error', $conn -> error);
        }

        $success = mysqli_next_result($conn); echo $success;
        if (!$success) {
          updateMessages('error', $conn -> error);
        }
        else {
          $search_result = mysqli_store_result($conn);
        }
      }
      while ($success);
    }

    //$search_result = mysqli_store_result($conn);
    $search_result = $conn -> query($inputQuery);
    if (is_bool($search_result) and $search_result)
    {
      $operation = substr($inputQuery, 0, strpos($inputQuery, ' '));
      updateMessages('success', ucfirst($operation).' operation successfully executed.');
    }
  }

  //retrieve column names to display in output table
  $col_names = '';
  if (strpos(strtolower('###'.substr(trim($inputQuery), 0, 7)), 'select')) {
    preg_match('/(?<=select )(.*)(?= from)/', $inputQuery, $regexResults);
    $col_names = $regexResults[0];
  }
  if (strpos(strtolower('###'.substr(trim($inputQuery), 0, 7)), 'show')) {
    $col_names = 'show';
  }

  if ($col_names == '*' or strtolower($col_names) == 'show')
  {

    if (strtolower($col_names) == 'show') { $q = rtrim($inputQuery, ';'); }
    else {
      $q = $inputQuery;
      if (strpos($q, 'limit')) # remove any occurence of 'limit'
      {
        $q = substr($q, 0, strpos($q, 'limit'));
      }

      $q = rtrim($q, ';').' limit 1';
    }

    $col_names = '';
    if ($result = mysqli_query($conn, $q)) {
      // Get field information for all fields
      while ($fieldinfo = mysqli_fetch_field($result)) {
        $col_names.= $fieldinfo -> name.' ';
      }
      // Free result set
      mysqli_free_result($result);
    }
    else {
      updateMessages('error', $conn -> error);
    }
  }

  $columns = explode(" ", trim($col_names));
}

//========== Functions ==========

function updateMessages($msgStatus, $msg) {
  GLOBAL $msgIndex;
  GLOBAL $successMsg;
  GLOBAL $errorMsg;

  if ($msg != '') {
    $msgIndex += 1;
    if ($msgStatus == 'success') { array_push($successMsg, $msgIndex.'. '.$msg); }
    else { array_push($errorMsg, $msgIndex.'. '.$msg); }
  }
}
?>