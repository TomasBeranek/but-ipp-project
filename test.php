<?php

/* ******************************* test.php ****************************
 *  Course: Principles of Programming languages (IPP) - FIT BUT
 *  Project name: Testing framework for parser and interpreter (IPPcode20)
 *  Author: Beranek Tomas (xberan46)
 *  Date: 3.3.2020
 * ************************************************************************** */

/* Desciption:
 *  function prints help on stdout
 */
function print_help(){
echo
"Testovací rámec (test.php)

Skript (test.php v jazyce PHP 7.4) slouzi pro automaticke testovani postupne aplikace
parse.php a interpret.py. Skript projde zadany adresar s testy a vyuzije je pro automaticke
otestovani spravne funkcnosti obou predchozich programu vcetne vygenerovani souhrnu
v HTML 5 do standardniho vystupu.

Parametry:
  --help            -zobrazi tuto napovedu
  --directory=path  -testy bude hledat v zadanem adresari (chybí-li tento parametr, tak skript
                    prochazi aktualni adresar
  --recursive       -testy bude hledat nejen v zadanem adresari, ale i rekurzivne ve vsech jeho
                    podadresarich
  --parse-script=file -soubor se skriptem v PHP 7.4 pro analyzu zdrojoveho kodu v IPPcode20
                      (chybi-li tento parametr, tak implicitni hodnotou je parse.php ulozeny
                      v aktualnim adresari)
  --int-script=file -soubor se skriptem v Python 3.8 pro interpret XML reprezentace kodu
                    v IPPcode20 (chybi-li tento parametr, tak implicitní hodnotou je interpret.py
                    ulozeny v aktualnim adresari)
  --parse-only      -bude testovan pouze skript pro analyzu zdrojového kodu v IPPcode20 (tento
                    parametr se nesmi kombinovat s parametry --int-only a --int-script)
  --int-only        -bude testovan pouze skript pro interpret XML reprezentace kodu v IPPcode20
                    (tento parametr se nesmí kombinovat s parametry --parse-only a --parse-script)
  --jexamxml=file   -soubor s JAR balickem s nastrojem A7Soft JExamXML. Je-li parametr vynechan
                    uvazuje se implicitni umisteni /pub/courses/ipp/jexamxml/jexamxml.jar na
                    serveru Merlin

Kazdy test je tvoren az 4 soubory stejneho jmena s priponami src, in, out a rc (ve stejnem
adresari). Soubor s priponou src obsahuje zdrojovy kod v jazyce IPPcode20 (prip. jeho XML
reprezentaci). Soubory s priponami in, out a rc obsahuji vstup a ocekavany/referencni vystup a
ocekavany prvni chybovy navratovy kod analyzy resp. interpretace nebo bezchybovy navratovy kod 0.\n";
}

/* Desciption:
 *  function finds unique name for a file
 * Return value:
 *  string with the name
 */
function get_unique_file_name(){
  $cnt = 0;
  while (file_exists($cnt.".myout"))
    $cnt++;

  return $cnt.".myout";
}

/******* MAIN *******/
  ini_set('display_errors', 'stderr');

  //setting default values
  $params = array(
    "help"          => false,
    "path"          => "./",
    "recursive"     => false,
    "parse_script"  => "parse.php",
    "int_script"    => "interpret.py",
    "parse_only"    => false,
    "int_only"      => false,
    "jexamxml"      => "/pub/courses/ipp/jexamxml/jexamxml.jar",
  );

  //parameters processing
  array_shift($argv); //delete name of script from argv

  foreach ($argv as $arg) {
    if ($arg == "--help")
      $params["help"] = true;

    elseif ($arg == "--recursive")
      $params["recursive"] = true;

    elseif ($arg == "--parse-only")
      $params["parse_only"] = true;

    elseif ($arg == "--int-only")
      $params["int_only"] = true;

    elseif (preg_match('/(--parse-script=.*)/', $arg, $found) && $found[0] == $arg){
        $tmp = preg_split('/--parse-script=/', $arg);
        $params["parse_script"] = $tmp[1];
    }

    elseif (preg_match('/(--int-script=.*)/', $arg, $found) && $found[0] == $arg){
        $tmp = preg_split('/--int-script=/', $arg);
        $params["int-script"] = $tmp[1];
    }

    elseif (preg_match('/(--directory=.*)/', $arg, $found) && $found[0] == $arg){
        $tmp = preg_split('/--directory=/', $arg);
        $params["path"] = $tmp[1];
    }

    elseif (preg_match('/(--jexamxml=.*)/', $arg, $found) && $found[0] == $arg){
        $tmp = preg_split('/--jexamxml=/', $arg);
        $params["jexamxml"] = $tmp[1];
    }

    else {
      exit(10); //unknown parameter
    }
  }

  //check for not allowed combinations of parameters
  if ($params["help"] && count($argv) > 1)
    exit(10);
  if ($params["parse_only"] && ($params["int_only"] || $params["int_script"] != "interpret.py"))
    exit(10);
  if ($params["int_only"] && ($params["parse_only"] || $params["parse_script"] != "parse.php"))
    exit(10);

  if ($params["help"]){
    print_help();
    exit(0);
  }

  //check if given directory exists
  if (!file_exists($params["path"]))
    exit(11);

  //get array of source files names, array is stored in $files
  if ($params["recursive"])
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($params["path"]));
  else
    $iterator = scandir($params["path"]);

  $files = array();

  foreach ($iterator as $file) {
    if (is_dir($file))
      continue;
    if (preg_match('/.*\.src/', $file, $found) && $found[0] == $file)
      if ($params["recursive"]) {
        $files[] = $file->getPathname();
      } else {
        $files[] = $params["path"].$file;
      }
  }


  $file_status = array(); //array to store files with test status
  $dir_status = array(); //array to store statuses of tested directories
  $pf = array( //tmp array to create two-dimensional array $dir_status
    "PASSED" => 0,
    "FAILED" => 0,
  );
  if ($params["parse_only"]) {
    foreach ($files as $file_src) {
      //replace .src
      $file_out = substr($file_src, 0, -4) . '.out';
      $file_rc = substr($file_src, 0, -4) . '.rc';
      $file_in = substr($file_src, 0, -4) . '.in';
      $tmp = preg_split('/[^\/]*\.src/', $file_src); //get path from path/file.src
      $curr_dir = $tmp[0];
      if ($curr_dir == "")
        $curr_dir = "./";
      $out = ""; //clear variable
      $rc_given = 0;

      //create new record in $dir_status, if needed
      if (!array_key_exists($curr_dir, $dir_status))
        $dir_status[$curr_dir] = $pf;

      //run parser.php on $file_src and output save to $out
      //output cannot be saved directly, because problem with user rights will not be detectable
      //also output cannot be redirected to diff, because we need to check RC of interpreter
      exec('php7.4 ' . $params["parse_script"] . " < " . $file_src, $out, $rc);

      //if file .out doesnt exist, create empty file
      if (!file_exists($file_out)){
        if (fopen($file_out, 'w') === FALSE)
          exit(12);
      }

      //if file .in doesnt exist, create empty file
      if (!file_exists($file_in)){
        if (fopen($file_in, 'w') === FALSE)
          exit(12);
      }

      //if .rc file doesnt exist, create it with 0 value
      if (!file_exists($file_rc)) {
        if (!file_put_contents($file_rc, '0'))
          exit(12);
      } else {
        if (($rc_given = file_get_contents($file_rc)) === FALSE)
          exit(11);

        //convert rc from string to int with check
        $tmp = intval($rc_given);
        if ($tmp != $rc_given)
          exit(11); //invalid value in file.rc
        $rc_given = $tmp;
      }

      //testing return code
      if ($rc_given != $rc){
        $file_status[$file_src] = "FAILED - Expected RC=" . $rc_given . " got " . $rc;
        $dir_status[$curr_dir]["FAILED"]++;
        continue; //skip to next file
      }
      elseif ($rc != 0) {
        $file_status[$file_src] = "SUCCESSFULL";
        $dir_status[$curr_dir]["PASSED"]++;
        continue; //skip to next file
      }

      //save parser output to temporary file wih unique name, file is created in same directory as test.php
      $tmp_file = get_unique_file_name();
      if (!file_put_contents($tmp_file, $out))
        exit(12);

      //get path to options from jexamxml.jar
      $options = substr($params["jexamxml"], 0, -12) . 'options';
      exec('java -jar ' . $params["jexamxml"] . " " . $tmp_file . " " . $file_out . " " . $options, $out, $rc_jexamxml);
      if ($rc_jexamxml != 0){
        $file_status[$file_src] = "FAILED - XML outputs are different";
        $dir_status[$curr_dir]["FAILED"]++;
        exec('rm -f ' . $tmp_file . ".log"); //delete .log file
      }
      else{
        $file_status[$file_src] = "SUCCESSFULL";
        $dir_status[$curr_dir]["PASSED"]++;
      }

      exec('rm -f ' . $tmp_file);
    }
  }
  elseif ($params["int_only"]) {
    foreach ($files as $file_src) {
      //replace .src
      $file_out = substr($file_src, 0, -4) . '.out';
      $file_rc = substr($file_src, 0, -4) . '.rc';
      $file_in = substr($file_src, 0, -4) . '.in';
      $tmp = preg_split('/[^\/]*\.src/', $file_src); //get path from path/file.src
      $curr_dir = $tmp[0];
      if ($curr_dir == "")
        $curr_dir = "./";
      $out = ""; //clear variable
      $rc_given = 0;

      //create new record in $dir_status, if needed
      if (!array_key_exists($curr_dir, $dir_status))
        $dir_status[$curr_dir] = $pf;

      //if file .in doesnt exist, create empty file
      if (!file_exists($file_in)){
        if (fopen($file_in, 'w') === FALSE)
          exit(12);
      }

      //run interpret.py on $file_src with $file_in and output save to $out
      //output cannot be saved directly, because problem with user rights will not be detectable
      exec('python3.8 ' . $params["int_script"] . " --input=" . $file_in . " < " . $file_src, $out, $rc);

      //if file .out doesnt exist, create empty file
      if (!file_exists($file_out)){
        if (fopen($file_out, 'w') === FALSE)
          exit(12);
      }

      //if .rc file doesnt exist, create it with 0 value
      if (!file_exists($file_rc)) {
        if (!file_put_contents($file_rc, '0'))
          exit(12);
      } else {
        if (($rc_given = file_get_contents($file_rc)) === FALSE)
          exit(11);

        //convert rc from string to int with check
        $tmp = intval($rc_given);
        if ($tmp != $rc_given)
          exit(11); //invalid value in file.rc
        $rc_given = $tmp;
      }

      //testing return code
      if ($rc_given != $rc){
        $file_status[$file_src] = "FAILED - Expected RC=" . $rc_given . " got " . $rc;
        $dir_status[$curr_dir]["FAILED"]++;
        continue; //skip to next file
      }
      elseif ($rc != 0) {
        $file_status[$file_src] = "SUCCESSFULL";
        $dir_status[$curr_dir]["PASSED"]++;
        continue; //skip to next file
      }

      //save interpret output to temporary file wih unique name, file is created in same directory as test.php
      $tmp_file = get_unique_file_name();
      if (!file_put_contents($tmp_file, $out))
        exit(12);

      //test outputs with diff
      exec('diff ' . $tmp_file . " " . $file_out, $out, $rc_diff);
      if ($rc_diff != 0){
        $file_status[$file_src] = "FAILED - interpret outputs are different";
        $dir_status[$curr_dir]["FAILED"]++;
      }
      else{
        $file_status[$file_src] = "SUCCESSFULL";
        $dir_status[$curr_dir]["PASSED"]++;
      }

      exec('rm -f ' . $tmp_file);
    }
  }
  else { //parser and interpreter
    // code...
  }


//generating HTML to stdout
echo "<!DOCTYPE html>\n<html>\n<body>\n\n";
if ($params["parse_only"]){
  echo "<h1>Vysledky testu souboru parser.php</h1>\n";
}
elseif ($params["int_only"]) {
  echo "<h1>Vysledky testu souboru interpret.py</h1>\n";
}
else {
  echo "<h1>Vysledky testu souboru parser.php a interpret.py</h1>\n";
}

echo "<h3>Prehled vysledku po jednotlivych adresarich:</h3>\n";
foreach ($dir_status as $key => $dir) {
echo "Slozka \"" . $key . "\"\n";
echo "<font color=\"green\">PASSED:</font> " . $dir["PASSED"] . "\n";
if ($dir["FAILED"] > 0)
  echo "<font color=\"red\">FAILED:</font> " . $dir["FAILED"] . "<br>\n\n";
else
  echo "<br>\n";
}

echo "<h3>Vysledky jednotlivych testu:</h3>\n";
foreach ($file_status as $key => $status) {
  if ($status == "SUCCESSFULL")
    echo $key . " <font color=\"green\">PASSED</font><br>\n";
  else
    echo $key . " <font color=\"red\">" . $status . "</font><br>\n";
}

echo "</body>
</html>";
exit(0);
  /* end of file parse.php */
?>
