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

  //arguments processing
  array_shift($argv); //delete name of script from argv

  foreach ($argv as $arg) {
    if ($arg == "--help")
      $params["help"] = true;

    if ($arg == "--recursive")
      $params["recursive"] = true;

    if ($arg == "--parse-only")
      $params["parse_only"] = true;

    if ($arg == "--int-only")
      $params["int_only"] = true;

    if (preg_match('/(--parse-script=.*)/', $arg, $found) && $found[0] == $arg){
        $tmp = preg_split('/--parse-script=/', $arg);
        $params["parse_script"] = $tmp[1];
    }

    if (preg_match('/(--int-script=.*)/', $arg, $found) && $found[0] == $arg){
        $tmp = preg_split('/--int-script=/', $arg);
        $params["int-script"] = $tmp[1];
    }

    if (preg_match('/(--directory=.*)/', $arg, $found) && $found[0] == $arg){
        $tmp = preg_split('/--directory=/', $arg);
        $params["path"] = $tmp[1];
    }

    if (preg_match('/(--jexamxml=.*)/', $arg, $found) && $found[0] == $arg){
        $tmp = preg_split('/--jexamxml=/', $arg);
        $params["jexamxml"] = $tmp[1];
    }
  }

  //check for not allowed combinations of parameters
  if ($params["help"] && count($argv) > 1)
    exit(10);
  if ($params["parse_only"] && ($params["int_only"] || $params["int_script"] != "interpret.py"))
    exit(10);
  if ($params["int_only"] && ($params["parse_only"] || $params["parse_script"] != "parse.php"))
    exit(10);

  //check if given directory exists
  if (!file_exists($params["path"]))
    exit(11);

  //get array of source files names, array is stored in $files
  if ($params["recursive"])
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($params["path"]));
  else
    $iterator = scandir($params["path"]);

  $files = array();
  $found = array();

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

  $tmp_file = get_unique_file_name();

exit(0);
  /* end of file parse.php */
?>
