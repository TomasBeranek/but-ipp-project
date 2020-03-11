<?php

/* ******************************* parse.php ****************************
 *  Course: Principles of Programming languages (IPP) - FIT BUT
 *  Project name: IPPcode20 code parser in PHP
 *  Author: Beranek Tomas (xberan46)
 *  Date: 2.3.2020
 * ************************************************************************** */

  /* Desciption:
   *  function prints help on stdout
   */
  function print_help(){
    echo
"Analyzator kodu v jazyce IPPcode20
  Skript typu filtr (parse.php jazyce PHP 7.4) nacte ze standardniho vstupu zdrojovy kod v
  IPP-code20, zkontroluje lexikalni a syntaktickou spravnost kodu a vypise na standardniho
  vystup XML reprezentaci programu.

  Parametry:
    --help  -zobrazeni teto napovedi

  Chybové návratové kódy specifické pro analyzátor:
    21 - chybná nebo chybějící hlavička ve zdrojovém kódu zapsaném v IPPcode20
    22 - neznámý nebo chybný operační kód ve zdrojovém kódu zapsaném v IPPcode20
    23 - jiná lexikální nebo syntaktická chyba zdrojového kódu zapsaného v IPPcode20\n";
  }

  /* Desciption:
   *  function checks, if string $s is variable in IPPcode20
   */
  function is_var($s){
    if (preg_match('/[LTG]F@([_\-$&%*!?]|[a-zA-Z])(\d|[a-zA-Z]|[_\-$&%*!?])*/', $s, $found) && $found[0] == $s)
      return true;
    else
      return false;
  }

  /* Desciption:
   *  function checks, if string $s is symbol in IPPcode20
   */
  function is_symb($s){
    if (is_var($s)) //variable
      return true;
    else if (preg_match('/nil@nil/', $s, $found) && $found[0] == $s) //nil constant
      return true;
    else if (preg_match('/bool@(true|false)/', $s, $found) && $found[0] == $s) //bool constant
      return true;
    else if (preg_match('/int@(\+|\-)?\d+/', $s, $found) && $found[0] == $s) //int constant
      return true;
    else if (preg_match('/string@([^\s\\\\#]|\\\\\d{3})*/', $s, $found) && $found[0] == $s) //string constant
      return true;
    else
      return false;
  }

  /* Desciption:
   *  function checks, if string $s is label in IPPcode20
   */
  function is_label($s){
    if (preg_match('/([_\-$&%*!?]|[a-zA-Z])(\d|[a-zA-Z]|[_\-$&%*!?])*/', $s, $found) && $found[0] == $s)
      return true;
    else
      return false;
  }

  /* Desciption:
   *  function checks, if string $s is type in IPPcode20
   */
  function is_type($s){
    if (preg_match('/(int|string|bool)/', $s, $found) && $found[0] == $s)
      return true;
    else
      return false;
  }

  /* Desciption:
   *  function creates XML representation of instruction on given line $line
   * Global variables:
   *  $program, $doc - main entities in XML, used to append subentities
   *  $order - instruction counter, used to print XML atributes
   *  $stats_values, $unique_labels - arrays used to store code statistics
   */
  function print_xml_instruction($line){
    global $program, $doc, $order, $stats_values, $unique_labels;

    //split line into words separated by isspace characters
    $word = preg_split('/\s+/', $line);
    $word[0] = strtoupper($word[0]);

    //delete empty items from beginning
    while (empty($word[0]) && (count($word) > 1))
      array_shift($word);

    //checking for comments
    $words_cnt = 0; //number of valid words (before comment)
    foreach ($word as $s) {
      if (empty($s))
        break;

      if ($s[0] == '#'){
        $stats_values["comments"]++; //--comments
        break;
      }

      if (preg_match('/.\#.*/', $s)){
        $stats_values["comments"]++; //--comments
        $tmp = preg_split('/#/', $s);
        $word[$words_cnt] = $tmp[0];
        $words_cnt++;
        break;
      }
      $words_cnt++;
    }

    //if its a only comment or empty line
    if ($words_cnt == 0){
      $order--;
      return;
    }

    //header check
    if ($order == 0){
      if (rtrim(strtolower($word[0]) != ".ippcode20"))
        exit(21);
      return;
    }

    //--jumps
    if (preg_match('/(CALL|RETURN|JUMP)/', $word[0]))
      $stats_values["jumps"]++;
    //--loc
    $stats_values["loc"]++;

    //create XML instruction element
    $instruction = $doc->createElement('instruction');
    $program->appendChild($instruction);
    $instruction->setAttributeNode(new DOMAttr('order', strval($order)));
    $instruction->setAttributeNode(new DOMAttr('opcode', $word[0]));

    switch ($word[0]) {
      //0 args
      case "CREATEFRAME":
      case "PUSHFRAME":
      case "POPFRAME":
      case "RETURN":
      case "BREAK":
        if ($words_cnt != 1)
          exit(23);
        break;

      //<var>
      case "DEFVAR":
      case "POPS":
        if ($words_cnt != 2 || !is_var($word[1]))
          exit(23);

        $arg1 = $doc->createElement('arg1');
        $text = $doc->createTextNode($word[1]);
        $instruction->appendChild($arg1);
        $arg1->appendChild($text);
        $arg1->setAttributeNode(new DOMAttr('type', 'var'));
        break;

      //<label>
      case "CALL":
      case "LABEL":
      case "JUMP":
        if ($words_cnt != 2 || !is_label($word[1]))
          exit(23);

        //--labels
        if ($word[0] == "LABEL"){
          $unique_labels[$word[1]] = true;
        }

        $arg1 = $doc->createElement('arg1');
        $text = $doc->createTextNode($word[1]);
        $instruction->appendChild($arg1);
        $arg1->appendChild($text);
        $arg1->setAttributeNode(new DOMAttr('type', 'label'));
        break;

      //<symb>
      case "PUSHS":
      case "WRITE":
      case "EXIT":
      case "DPRINT":
        if ($words_cnt != 2 || !is_symb($word[1]))
          exit(23);

        $arg1 = $doc->createElement('arg1');
        if (is_var($word[1])){
          $text = $doc->createTextNode($word[1]);
          $arg1->setAttributeNode(new DOMAttr('type', 'var'));
        }
        else {
          $const = preg_split('/@/', $word[1]);
          $text = $doc->createTextNode($const[1]);
          $arg1->setAttributeNode(new DOMAttr('type', $const[0]));
        }
        $instruction->appendChild($arg1);
        $arg1->appendChild($text);
        break;

      //<var> <symb>
      case "MOVE":
      case "INT2CHAR":
      case "NOT":
      case "STRLEN":
      case "TYPE":
        if ($words_cnt != 3 || !is_var($word[1]) || !is_symb($word[2]))
          exit(23);

        //var
        $arg1 = $doc->createElement('arg1');
        $text = $doc->createTextNode($word[1]);
        $instruction->appendChild($arg1);
        $arg1->appendChild($text);
        $arg1->setAttributeNode(new DOMAttr('type', 'var'));

        //symb
        $arg2 = $doc->createElement('arg2');
        if (is_var($word[2])){
          $text2 = $doc->createTextNode($word[2]);
          $arg2->setAttributeNode(new DOMAttr('type', 'var'));
        }
        else {
          $const = preg_split('/@/', $word[2]);
          $text2 = $doc->createTextNode($const[1]);
          $arg2->setAttributeNode(new DOMAttr('type', $const[0]));
        }
        $instruction->appendChild($arg2);
        $arg2->appendChild($text2);
        break;

      //<var> <symb1> <symb2>
      case "ADD":
      case "SUB":
      case "MUL":
      case "IDIV":
      case "LT":
      case "GT":
      case "EQ":
      case "AND":
      case "OR":
      case "STRI2INT":
      case "CONCAT":
      case "GETCHAR":
      case "SETCHAR":
        if ($words_cnt != 4 || !is_var($word[1]) || !is_symb($word[2]) || !is_symb($word[3]))
          exit(23);

        //var
        $arg1 = $doc->createElement('arg1');
        $text = $doc->createTextNode($word[1]);
        $instruction->appendChild($arg1);
        $arg1->appendChild($text);
        $arg1->setAttributeNode(new DOMAttr('type', 'var'));

        //symb1
        $arg2 = $doc->createElement('arg2');
        if (is_var($word[2])){
          $text2 = $doc->createTextNode($word[2]);
          $arg2->setAttributeNode(new DOMAttr('type', 'var'));
        }
        else {
          $const = preg_split('/@/', $word[2]);
          $text2 = $doc->createTextNode($const[1]);
          $arg2->setAttributeNode(new DOMAttr('type', $const[0]));
        }
        $instruction->appendChild($arg2);
        $arg2->appendChild($text2);

        //symb2
        $arg3 = $doc->createElement('arg3');
        if (is_var($word[3])){
          $text3 = $doc->createTextNode($word[3]);
          $arg3->setAttributeNode(new DOMAttr('type', 'var'));
        }
        else {
          $const = preg_split('/@/', $word[3]);
          $text3 = $doc->createTextNode($const[1]);
          $arg3->setAttributeNode(new DOMAttr('type', $const[0]));
        }
        $instruction->appendChild($arg3);
        $arg3->appendChild($text3);
        break;

      //<var> <type>
      case "READ":
        if ($words_cnt != 3 || !is_var($word[1]) || !is_type($word[2]))
          exit(23);

        //var
        $arg1 = $doc->createElement('arg1');
        $text = $doc->createTextNode($word[1]);
        $instruction->appendChild($arg1);
        $arg1->appendChild($text);
        $arg1->setAttributeNode(new DOMAttr('type', 'var'));

        //var
        $arg2 = $doc->createElement('arg2');
        $text2 = $doc->createTextNode($word[2]);
        $instruction->appendChild($arg2);
        $arg2->appendChild($text2);
        $arg2->setAttributeNode(new DOMAttr('type', 'type'));
        break;

      //<label> <symb1> <symb2>
      case "JUMPIFEQ":
      case "JUMPIFNEQ":
        if ($words_cnt != 4 || !is_label($word[1]) || !is_symb($word[2]) || !is_symb($word[3]))
          exit(23);

        //label
        $arg1 = $doc->createElement('arg1');
        $text = $doc->createTextNode($word[1]);
        $instruction->appendChild($arg1);
        $arg1->appendChild($text);
        $arg1->setAttributeNode(new DOMAttr('type', 'label'));

        //symb1
        $arg2 = $doc->createElement('arg2');
        if (is_var($word[2])){
          $text2 = $doc->createTextNode($word[2]);
          $arg2->setAttributeNode(new DOMAttr('type', 'var'));
        }
        else {
          $const = preg_split('/@/', $word[2]);
          $text2 = $doc->createTextNode($const[1]);
          $arg2->setAttributeNode(new DOMAttr('type', $const[0]));
        }
        $instruction->appendChild($arg2);
        $arg2->appendChild($text2);

        //symb2
        $arg3 = $doc->createElement('arg3');
        if (is_var($word[3])){
          $text3 = $doc->createTextNode($word[3]);
          $arg3->setAttributeNode(new DOMAttr('type', 'var'));
        }
        else {
          $const = preg_split('/@/', $word[3]);
          $text3 = $doc->createTextNode($const[1]);
          $arg3->setAttributeNode(new DOMAttr('type', $const[0]));
        }
        $instruction->appendChild($arg3);
        $arg3->appendChild($text3);
        break;

      //unknown opcode
      default:
        exit(22);
    }
  }

  /******* MAIN *******/
  ini_set('display_errors', 'stderr');

  $stats_enabled = false;
  $stats_param_passed = false;
  $stats = array();

  array_shift($argv); //delete name of script from argv

  foreach ($argv as $param){
    if (preg_match('/(--stats=.*)/', $param, $found) && $found[0] == $param){
      $stats_enabled = true;
      //get file name from parameter
      $tmp = preg_split('/--stats=/', $param);
      $output_file_name = $tmp[1];
    }
    else if (preg_match('/(--loc|--comments|--labels|--jumps)/', $param, $found) && $found[0] == $param){
      $stats_param_passed = true;
      array_push($stats, $param);
    }
    else if ($param == "--help" && (count($argv) == 1)){
      print_help();
      exit(0);
    }
    else {
      exit(10);
    }
  }

  //if --stats=file wasnt passed, but other stat param was passed
  if ($stats_param_passed && !$stats_enabled)
    exit(10);

  $stats_values = array(
    "loc" => 0,
    "comments" => 0,
    "labels" => 0,
    "jumps" => 0,
  );
  $unique_labels = array();

  $doc = new DOMDocument('1.0', 'UTF-8');
  $doc->formatOutput = true;
  $program = $doc->createElement('program');
  $doc->appendChild($program);
  $program->setAttributeNode(new DOMAttr('language', 'IPPcode20'));

  if (!($input = fopen('php://stdin', 'r')))
    exit(11);
  $order = 0;

  while ($line = fgets($input)){
    print_xml_instruction($line, $order);
    $order++;
  }

  //printing statistics
  if ($stats_enabled){
    if (!($output = fopen($output_file_name, 'w')))
      error(12);
    foreach ($stats as $param) {
      switch ($param) {
        case "--loc":
          fwrite($output, $stats_values["loc"]);
          break;
        case "--comments":
          fwrite($output, $stats_values["comments"]);
          break;
        case "--labels":
          fwrite($output, count($unique_labels));
          break;
        case "--jumps":
          fwrite($output, $stats_values["jumps"]);
          break;
        //this should not happen
        default:
          exit(99);
          break;
      }
      fwrite($output, "\n");
    }
    fclose($output);
  }
  fclose($input);
  echo $doc->saveXML();
  exit(0);
  /* end of file parse.php */
?>
