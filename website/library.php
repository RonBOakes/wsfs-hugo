<?PHP
/* Written by Ronald B. Oakes, copyright  2015
   Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
   For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
   All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
*/

require_once('database.php');

// Library routines
class library
{
  static function verify_membership($firstname,$lastname,$city,$postalCode,$email,$membershipCode,$pin)
  {
    // TODO: Actually implement the verification - return a false value if not verified
    // Actual verification should ensure that firstname, lastname and e-mail are populated even of code was all
    // that we received
    if($pin == '')
    {
      return '';
    }

    // Turn the first and last name into a unique key
    $preKey = strtoupper($firstname.$lastname);
    $preKey = preg_replace('/\\s+/','',$preKey);  // Strip of spaces

    $key = sha1($preKey);

    $returnValue = array('key'=>$key,'email'=>$email);

    return $returnValue;
  }
}