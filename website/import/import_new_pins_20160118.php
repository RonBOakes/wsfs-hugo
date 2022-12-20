<?PHP

error_reporting(E_ALL);
ini_set('display_errors', '1');
echo getcwd()."\n";

// Sample import program
chdir('..');
echo getcwd()."\n";
require_once('database.php');
chdir('import');
echo getcwd()."\n";

$db = new Database();

$fptr = fopen('NewPins20150730.csv','r');

if($fptr)
{
  $readData = fgetcsv($fptr,255,',','"','\\');   // Eat the header

  while ($readData = fgetcsv($fptr,255,',','"','\\'))
  {
    $source = 'CURRENT';

    // Member,PIN,Last,First,email
    $db->addUpdatePinEmailRecord($readData[3],$readData[2],$readData[0],$readData[4],$readData[1],$source);
  } //     function addPinEmailRecord($first_name, $second_name, $member_id, $email, $pin, $source)
}

?>
