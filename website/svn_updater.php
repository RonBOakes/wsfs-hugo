<HEAD>
  <TITLE>Hugo Web Subversion Update</TITLE>
</HEAD>
<BODY>
  <?PHP
/* Written by Ronald B. Oakes, copyright  2015, 2016
   Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
   For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
   All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
*/

    $svnLoc = trim(`which svn`);

    if($svnLoc == '')
    {
      $results = "The svn command cannot be located.  Aborting";
    }
    else
    {
      $dirList = scandir('.');
      if(array_search('.svn',$dirList))
      {
        // Update the current subversion installation
        $results  = "<pre>svn revert -R<BR/>\n";
        $results .= `svn revert -R`;
        $results .= "\n</pre>\n";
        $results .= "<pre>svn cleanup<br/>\n";
        $results .= `svn cleanup`;
        $results .= "\n</pre>\n";
        $results .= "<pre>svn update<BR/>\n";
        $results .= `svn update`;
        $results .= "\n</pre>";
      }
      else
      {
        // Do a new subversion checkout
        $results  = "svn co --force --username &lt;Redacted&gt; --password &lt;Redacted&gt; http://wwww.ron-oakes.com/svn/hugo_web/midamericon2 .<BR/>\n";
        $results .= `yes | svn co --force --username hugo --password G3rn5Beck http://www.ron-oakes.com/svn/hugo_web/midamericon2 . |& tee svn_output.txt`;
      }
    }
  ?>
  <P>
    The results of the operation to bring this directory up to the current subversion revision are<BR/>
    <?PHP print($results); ?>
  </P>
  <P>
      <pre>
        <?PHP print(`svn commit --message 'test'`); ?>
        <?PHP print(`svn status`); ?>
      </pre>
  </P>
</BODY>

