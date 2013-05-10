<?php
// ----------------------------------------------------------------
// sitecon.php AKA crudbud
// Created by: aarondllc@gmail.com
// ----------------------------------------------------------------

$db = array('user' => 'root', 'pass' => 'root', 'host' => 'localhost', 'database' => 'aaron_bda');

/**
 * DO NOT EDIT BELOW THIS LINE UNLESS YOU ARE EXTENDING FUNCTIONALITY
 */

$help = 'Script to manage all CRUD operations for a MySQL database.

Requirements
 -must have a database field called id that is a primary key and auto_increment
 -setup one database user with ONLY insert, update and delete record privs (no table alter)
 -to use on/off or yes/no use the tinyint mysql field type with flag in the name somewhere like weekday_flag. 0=off/no 1=on/yes
 -date field content type uses YYYY-MM-DD
 
Installation
 -place this file into a folder like /admin
 -update the $db var with proper user/pass/host/database 
 -create an .htaccess file with authentication
   $ mkdir passworddir
   $ htpasswd -c /path/to/passworddir/newpasswordfile new-user-name
   // make sure chmod 0660 newpasswordfile, and chown as needed
   // copy/paste this to new .htaccess in sitecon.php dir, replace vars 
    AuthType Basic
    AuthName "Restricted Access"
    AuthUserFile /path/to/passworddir/newpasswordfile
    Require user new-user-name
 
Usage
 -for small, simple sites, just point to a database and this will list out all tables
 -design a simple database around concept of using an order id, title/body type fields without any lookups';

$css = 'body { font: 12px/14px sans-serif; margin: auto 40px; padding-top:40px; }
  hr { height: 2px; margin: 12px 12px 12px 0; }
  label { display: block; float: left; text-transform: uppercase; width: 150px; }
  input { border: 1px solid #999999; }
  table.results { border: 1px solid #999999; background-color:#ccc; padding:0; width:100%; }
  table.results tr.odd { background-color:#eee; }
  thead { background-color: #888888; color: #EEEEEE; font-size: small; text-align: left; text-transform: uppercase; }
  #help { float:right; padding:12px; }
  #help textarea { width:500px; height:200px; padding:4px; font-size:small; border:1px solid #ccc; }
  pre { margin:0; }';

// open up connection
$mysqli = new mysqli($db['host'], $db['user'], $db['pass'], $db['database']);
// check connection 
if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}

/**
 * helper functions
 */
// returns object of column data 
function getColumns($table){
  global $mysqli;
  $table = strip_tags(trim($table));
  // make sure passed table name exists before running query
  $tables = getTables();
  if(in_array($table, $tables)){
    $table_columns = $mysqli->query("SHOW COLUMNS FROM ".$table);
    if (!$table_columns) {
      return false;  
    }
    else {
      return $table_columns;
    }
  } else {
    return false; // passed table name is not in array, add error msg here?
  }
}

// returns array of table names
function getTables(){
  global $db, $mysqli;
  // get list of tables in database
  $result = $mysqli->query("SHOW TABLES FROM ".$db['database']);
  $arr = array();
  while ($row = $result->fetch_array(MYSQLI_NUM)) {
    $arr[] = $row[0];
  }
  return $arr;
}

/**
 * INSERT / UPDATE
 */
if(isset($_POST['op'])){
  //print_r($_POST); exit; 
  $table = strip_tags(trim($_POST['table']));
  $fields = array();
  
  // get table columns
  $table_columns = getColumns($table, $mysqli);  
  while ($row = $table_columns->fetch_array(MYSQLI_ASSOC)) {
    $fields[]="`".$row['Field']."`";
  }
  
  // prep values, remove last two (op and table) and add wrapping quotes
  $values = array_slice($_POST, 0, -2);
  $values_sql = array();
  foreach($values as $value){
    $values_sql[]= "'".$mysqli->real_escape_string($value)."'";
  }  
  
  if(trim($_POST['op'])=='insert'){
    // id is first since it won't be passed in for insert records
    $sql = 'INSERT INTO '.$table.' ('.implode(',', $fields).') VALUES (\'\','.implode(',', $values_sql).')';
    $result = $mysqli->query($sql);
    if($result==true){
      print 'Your record has been added.';
    } else {
      print 'There was an error adding your record.<BR>';
      printf("Error: %s\n", mysqli_error($mysqli)." ".$sql);
    }
    
  } else if (trim($_POST['op'])=='update') {
    
    $id = strip_tags(trim($_POST['id']));
    // prep update col=val pairs
    $values = array_slice($_POST, 0, -3); // remove id, op and table vars from end
    array_shift($fields); // remove id from front
    $value_pairs = array_combine($fields, $values); // combine to field=>value array
    // format into string for sql
    $sql_fields = array();
    foreach($value_pairs as $key=> $value){
      $sql_fields[]= $key."='".$mysqli->real_escape_string($value)."'";
    }
    
    // id is first since it won't be passed in for insert records
    $sql = 'UPDATE '.$table.' SET '.implode(', ', $sql_fields).' WHERE id='.$id;
    $result = $mysqli->query($sql);
    if($result==true){
      print 'Your record has been updated.';
    } else {
      print 'There was an error updating your record.<BR><BR>';  
      printf("Error: %s\n", mysqli_error($mysqli)." ".$sql);
    }
  }
  print '<HR><a href="sitecon.php">Back to Manage Page</a>';
  exit;
}


/**
 * DELETE
 */
if(isset($_GET['op']) and trim($_GET['op'])=='delete'){
  $table = strip_tags(trim($_GET['t']));  
  $id = strip_tags(trim($_GET['id']));  
  $result = $mysqli->query('DELETE FROM '.$table.' WHERE id="'.$id.'"');
  if($result==true)
    print 'Your record has been deleted.';
  else
    print 'There was an error deleting your record';  
  print '<HR><a href="sitecon.php">Back to Manage Page</a>';
  exit;
}
?>

<html><head><style><?php print $css; ?></style></head><body>
<div id="help"><textarea><?php print $help; ?></textarea></div>
<h2>Manage Site Content</h2><hr>
    
<?php
// list tables in database
print '<h3>Tables</h3><ul>';
foreach(getTables() as $tablename) {
    print '<li><a href="?t='.$tablename.'">'.$tablename.'</a>';
}
print '</ul>';
?>

<hr><form action="sitecon.php" method="POST"> 

<?php
// Get table name from url
if(!isset($_GET['t'])){
  print 'Click on a table to manage.';
  exit;
} else {
  $table = strip_tags(trim($_GET['t']));
}

/**
 * PRINT ADD/EDIT FORM
 */
// get fields in table
$table_columns = getColumns($_GET['t']);
if (!$table_columns) {
    echo 'Could not run query, error in table name passed.';
    exit;
}

// if id is passed in url, prepare edit form with values array
$edit_values = array();
if(isset($_GET['id']) and isset($_GET['op'])) {
  $id = strip_tags(trim($_GET['id']));
  $op = strip_tags(trim($_GET['op']));
  if($op=='delete'){ 
    // run delete sql
  } else if($op=='edit') {
    // run edit sql
    $edit_record = $mysqli->query("SELECT * FROM ".$table." WHERE id='".$id."' LIMIT 1");
    $edit_values = $edit_record->fetch_array(MYSQLI_ASSOC);
    }
}

// save columns for use later
$column_names = array();
$value = '';

if ($table_columns->num_rows > 0) {
  while ($row = $table_columns->fetch_array(MYSQLI_ASSOC)) {
    $column_names[] = $row['Field'];
    // skip if auto increment value
    if($row['Extra']!='auto_increment'){
      // split field type name by '(' to get actual name not int(10)
      $type = explode('(',$row['Type']);
      // match to values if exist
      if(isset($edit_values[$row['Field']]))
        $value = $edit_values[$row['Field']];
      else{
        $value = '';
      }
      
      // match fields to html types
      $option = '';
      switch($type[0]){
        case 'int':
        case 'varchar':
          $option = '<label>'.$row['Field'].'</label><input type="text" name="'.$row['Field'].'" value="'.$value.'">';
          break;
        case 'text':
          $option = '<label>'.$row['Field'].'</label><textarea name="'.$row['Field'].'">'.$value.'</textarea>';
          break;
        case 'date';
          $option = '<label>'.$row['Field'].'(YYYY-MM-DD)</label><input type="text" name="'.$row['Field'].'" value="'.$value.'">';
          break;
        case 'tinyint';
          $sel0='';
          $sel1='';
          if($value==1)
            $sel1 = 'checked';
          else
            $sel0 = 'checked';
          $option = '<label>'.$row['Field'].'</label>
                    <input type="radio" name="'.$row['Field'].'" value="1" '.$sel1.'>Yes 
                    <input type="radio" name="'.$row['Field'].'" value="0" '.$sel0.'>No ';
          break;
        default;
          $option = '<label>'.$row['Field'].'</label> This field type has not been defined yet in the script.';
      }
      print $option.'<br><br>';
    }
  }
}

/**
 * SET HIDDEN VALUE FOR INSERT/UPDATE ACTION
 */
if(!isset($_POST['op']) and !isset($_GET['op'])){
  // and print hidden action for insert 
  print '<input type="hidden" name="op" value="insert">';
} else if ( isset($_GET['op'])) {
  print '<input type="hidden" name="id" value="'.$id.'">';
  print '<input type="hidden" name="op" value="update">';
}

  print '<input type="hidden" name="table" value="'.$table.'">';
?>    
      
<label>&nbsp;</label><input type="submit" value="Save"></form><HR>

<?php
/**
 * SHOW RECORDS FROM CURRENT TABLE
 */
print '<h3>'.$table.'</h3>';

// show all records in table with edit link to query var
// temp cap at 200 items to prevent script timeout
$table_records = $mysqli->query("SELECT * FROM ".$table);
if($table_records->num_rows<200){
  print '<table class="results"><thead>';
  foreach($column_names as $column){
    print '<th>'.$column.'</th>';
  }
  print '<th>Edit</th><th>Delete</th></thead></tr>';

  $c = 0; 
  while ($row = $table_records->fetch_array(MYSQLI_ASSOC)) {
    $rowcss = ($c++%2==1) ? "odd" : NULL;
    print '<tr class="'.$rowcss.'">'; // add 'odd' css classname
    foreach($column_names as $column){
      // print yes/no for columns with 'flag' in name
      if(substr_count($column, 'flag')>0){
        $onoff = ($row[$column]==1) ? 'yes' : 'no';
        print '<td>'.$onoff.'</td>';
      } else {
        print '<td>'.$row[$column].' &nbsp;</td>';
      }
      // only print edit/delete links from id
      if($column=='id'){
        $link_edit = '<a href="?t='.$table.'&id='.$row['id'].'&op=edit">Edit</a>';
        $link_delete = '<a href="?t='.$table.'&id='.$row['id'].'&op=delete" onclick="return confirm(\'Are you sure you want to delete id: '.$row[$column].' ?\');">Delete</a>';
      }
    } 
    print '<td>'.$link_edit.'</td><td>'.$link_delete.'</td></tr>';
  }
  print '</table><BR><BR><BR><BR>';
}
$mysqli->close();
?>
</body></html>