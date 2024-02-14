<?php
#     Zdenek Dobes   #
#       xdobes21     #
# php parse.php
# @input - Kod v jazyce IPPcode21 ze standartniho vstupu
# @output - Pri syntakticke a lexikalni korektnosti kod v jazyce XML, jinak chybu

if(count($argv) == 1){
        $file = fopen('php://stdin', 'r');
        $xml = null;                    // globalni pole na ulozeni xml kodu, ktery se na konci vsechen vygeneruje 
        $input = fgets($file);          // nacitani 1. radku vstupu
        $Line = explode(" ", $input);   // rozdeleni vstupu na jednotlive instrukce/argumenty
        $firstLine = CheckinCheck(SpaceEater($Line)); // odstraneni mezer a koncu radku za komentari
        while($firstLine[0] == "#" or $firstLine[0] == "\r" or $firstLine[0] == "\n" or $firstLine[0] == "\r\n"){
            // odstraneni prazdnych radku a komentaru pred hlavickou
            $input = fgets($file);         
            $Line = explode(" ", $input);       
            $firstLine = CheckinCheck(SpaceEater($Line));
        }
        if(strtoupper($firstLine) != ".IPPCODE21"){ // KONTROLA HLAVICKY
            exit(21);
        }
        $instructionNumber = 0; // poradi instrukce
        genXMLstart();          // generovani hlavicky
        while(!feof($file)){    // KONTROLA TELA KODU
            $instructionNumber = $instructionNumber + 1;
            $input = fgets($file);          // nacitani 1. radku vstupu
            $Line = explode(" ", $input);   // rozdeleni vstupu na jednotlive instrukce/argumenty
            $Line[0] = CheckinCheck($Line[0]);
            switch (strtoupper($Line[0])) { // KONTROLA NAZVU INSTRUKCI 
                case "RETURN":
                case "POPFRAME":
                case "PUSHFRAME":
                case "CREATEFRAME":
                case "BREAK":
                    genXMLinstruction0($instructionNumber, $Line[0]);
                    break;
                case "POPS":
                case "DEFVAR":  
                    if(variableCheck($Line[1]) == false){
                        exit(23);
                    }
                    genXMLinstruction1($instructionNumber, $Line[0], $Line[1]);
                    break;
                case "INT2CHAR":
                case "MOVE":
                case "STRLEN":
                case "NOT":
                case "TYPE":
                    if(variableCheck($Line[1]) == false){
                        exit(23);
                    }
                    if(symbolCheck($Line[2]) == false){
                        if(variableCheck($Line[2]) == false){
                            exit(23);
                        }
                    }
                    genXMLinstruction2($instructionNumber, $Line[0], $Line[1], $Line[2]);
                    break;
                case "READ":
                    if(variableCheck($Line[1]) == false){
                        exit(23);
                    }
                    if(typeCheck($Line[2]) == false){
                        exit(23);
                    }
                    genXMLinstruction2($instructionNumber, $Line[0], $Line[1], $Line[2]);
                    break;
                case "CALL":
                case "LABEL":
                case "JUMP":
                    if(labelCheck($Line[1]) == false){
                        exit(23);
                    }
                    genXMLinstruction1($instructionNumber, $Line[0], $Line[1]);
                    break;
                case "PUSHS":
                case "EXIT":
                case "DPRINT":
                case "WRITE":
                    if(symbolCheck($Line[1]) == false){
                        if(variableCheck($Line[1]) == false){
                            exit(23);
                        }
                    }
                    genXMLinstruction1($instructionNumber, $Line[0], $Line[1]);
                    break;
                case "STRI2INT":
                case "AND":
                case "OR":
                case "LT":
                case "EQ":
                case "GT":
                case "ADD":
                case "SUB":
                case "IDIV":
                case "MUL":
                case "CONCAT":
                case "GETCHAR":
                case "SETCHAR":
                    if(variableCheck($Line[1]) == false){
                        exit(23);
                    }
                    if(symbolCheck($Line[2]) == false){
                        if(variableCheck($Line[2]) == false){
                            exit(23);
                        }
                    }
                    if(symbolCheck($Line[3]) == false){
                        if(variableCheck($Line[3]) == false){
                            exit(23);
                        }
                    }
                    genXMLinstruction3($instructionNumber, $Line[0], $Line[1], $Line[2], $Line[3]);
                    break;
                case "JUMPIFNEQ":
                case "JUMPIFEQ":
                    if(labelCheck($Line[1]) == false){
                        exit(23);
                    }
                    if(symbolCheck($Line[2]) == false){
                        if(variableCheck($Line[2]) == false){
                            exit(23);
                        }
                    }
                    if(symbolCheck($Line[3]) == false){
                        if(variableCheck($Line[3]) == false){
                            exit(23);
                        }
                    }
                    genXMLinstruction3($instructionNumber, $Line[0], $Line[1], $Line[2], $Line[3]);
                    break;
                default:
                if(strlen($Line[0]) != 0){
                    if($Line[0][0] != '#'){ // kontrola komentaru
                        if($Line[0] != "\r" and $Line[0] != "\n" and $Line[0] != "\r\n"){ //kontrola prazdnych radku a carriage return
                            exit(22);
                        }
                    }
                }
                $instructionNumber = $instructionNumber - 1;
                break;
            }
        }
        genXMLend(); // generovani konce programu
        fclose($file);
        echo $xml; // generovani celeho xml kodu
        exit(0);

    }
    if($argv[1] == '--help' and $argc == 2){
        echo "Zadejte na standartni vstup zdrojovy kod IPPcode21.";
        exit(0);
    }
    else {      // neplatne argumenty programu
        exit(10);
    }
    //      Generovani instrukci s 0 argumenty       \\
    // @param int $instrNum - poradi instrukce 
    // @param string $instr - nazev instrukce
    function genXMLinstruction0($instrNum, $instr){
        global $xml;
        $xml .= ' <instruction order="';
        $xml .= $instrNum;
        $xml .= '" opcode="';
        $xml .= $instr;
        $xml .= '">';
        $xml .= "\n";
        $xml .= " </instruction>\n";
    }
    //      Generovani instrukci s 1 argumentem       \\
    // @param int $instrNum - poradi instrukce 
    // @param string $instr - nazev instrukce
    // @param string $arg1 - argument 1
    function genXMLinstruction1($instrNum, $instr, $arg1){
        global $xml;
        $arg1 = CheckinCheck($arg1);
        $xml .= ' <instruction order="';
        $xml .= $instrNum;
        $xml .= '" opcode="';
        $xml .= $instr;
        $xml .= '">';
        $xml .= "\n";
        genArgument($arg1, 1);
        $xml .= " </instruction>\n";
    }
    //      Generovani instrukci s 2 argumenty       \\
    // @param int $instrNum - poradi instrukce 
    // @param string $instr - nazev instrukce
    // @param string $arg1 - argument 1
    // @param string $arg1 - argument 2
    function genXMLinstruction2($instrNum, $instr, $arg1, $arg2){
        global $xml;
        $arg1 = CheckinCheck($arg1);
        $arg2 = CheckinCheck($arg2);
        $xml .= ' <instruction order="';
        $xml .= $instrNum;
        $xml .= '" opcode="';
        $xml .= $instr;
        $xml .= '">';
        $xml .= "\n";
        genArgument($arg1, 1);
        genArgument($arg2, 2);
        $xml .= " </instruction>\n";
    }
    //      Generovani instrukci s 3 argumenty       \\
    // @param int $instrNum - poradi instrukce 
    // @param string $instr - nazev instrukce
    // @param string $arg1 - argument 1
    // @param string $arg1 - argument 2
    // @param string $arg3 - argument 3
    function genXMLinstruction3($instrNum, $instr, $arg1, $arg2, $arg3){
        global $xml;
        $arg1 = CheckinCheck($arg1);
        $arg2 = CheckinCheck($arg2);
        $arg3 = CheckinCheck($arg3);
        $xml .= ' <instruction order="';
        $xml .= $instrNum;
        $xml .= '" opcode="';
        $xml .= $instr;
        $xml .= '">';
        $xml .= "\n";
        genArgument($arg1, 1);
        genArgument($arg2, 2);
        genArgument($arg3, 3);
        $xml .= " </instruction>\n";
    }
    //      Generovani argumentu       \\
    // @param string $arg - argument
    // @param int $argnum - poradi instrukce
    function genArgument($arg, $argnum){
        global $xml;
        $xml .= "  <arg";
        $xml .= $argnum;
        $xml .= ' type="';
        $arg1length = lengthOfFirstPart($arg);
        if($arg1length == 0){
            if($arg == "bool" or $arg == "int" or $arg == "string"){
            $xml .= "type";
            }
            else{
                $xml .= "label";
            }
        }
        else if(substr($arg, 0, 3) == "GF@" or substr($arg, 0, 3) == "LF@" or substr($arg, 0, 3) == "TF@"){
            $xml .= "var";
            $arg1length = 0;
        }
        else if(substr($arg, 0, 7) == "string@" or substr($arg, 0, 4) == "int@" or substr($arg, 0, 5) == "bool@" or substr($arg, 0, 4) == "nil@"){
            $arg1length = $arg1length - 1;
            $xml .= implode(str_split(substr($arg, 0, $arg1length)));
            $arg1length = $arg1length + 1;
        }
        else{
            $xml .= implode(str_split(substr($arg, 0, $arg1length)));
        }
        $xml .= '">';
        $xml .= implode(str_split(substr($arg, $arg1length, strlen($arg)-$arg1length)));
        $xml .= "</arg";
        $xml .= $argnum;
        $xml .= '>';
        $xml .= "\n";
    }
    //      Generovani hlavicky       \\
    function genXMLstart(){
        global $xml;
        $xml .= '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= "\n";
        $xml .= '<program language="IPPcode21">';
        $xml .= "\n";
    }
    //      Generovani konce programu       \\
    function genXMLend(){
        global $xml;
        $xml .= '</program>';
    }
    //      Pomocna funkce ke generovani       \\
    // @param string $variable - argument
    // @return int - delka prefixu argument
    function lengthOfFirstPart($variable){
        if(substr($variable, 0, 3) == "GF@" or substr($variable, 0, 3) == "LF@" or substr($variable, 0, 3) == "TF@"){
            $var = 3;
        }
        else if(substr($variable, 0, 4) == "nil@" or substr($variable, 0, 4) == "int@"){
            $var = 4;
        }
        else if(substr($variable, 0, 5) == "bool@"){
            $var = 5;
        }
        else if(substr($variable, 0, 7) == "string@"){
            $var = 7;
        }
        else{
            $var = 0;
        }
        return $var;
    }
    //      Syntakticka a lexikalni kontrola promennych       \\
    // @param string $variable - argument/promenna
    // @return bool true = spravne napsana promenna
    // @return bool false = spatne napsana promenna nebo jiny typ argumentu
    function variableCheck($variable){
        if(substr($variable, 0, 3) == "GF@" or substr($variable, 0, 3) == "LF@" or substr($variable, 0, 3) == "TF@"){ // kontrola prefixu
            $variable = CheckinCheck($variable);
            $var = str_split(substr($variable, 3, strlen($variable)-3));  
            $first_iteration = true;
            foreach($var as $letter){ //kontrola nazvu promenne pomoci hodnoty z ascii
                $ascii = ord($letter); 
                if($first_iteration == true){ // kontrola prvniho znaku promenne
                    $first_iteration = false;
                    if(($ascii >= 65 and $ascii <= 90) or ($ascii >= 97 and $ascii <= 122) or $ascii == 63 or $ascii == 33 or ($ascii >= 36 and $ascii <= 38) or $ascii == 42 or $ascii == 45 or $ascii == 95){
                    }
                    else{
                        return false;
                    }
                }
                else{                       // kontrola ostatnich znaku promenne 
                    if(($ascii >= 48 and $ascii <= 57) or ($ascii >= 65 and $ascii <= 90) or ($ascii >= 97 and $ascii <= 122) or $ascii == 63 or $ascii == 33 or ($ascii >= 36 and $ascii <= 38) or $ascii == 42 or $ascii == 45 or $ascii == 95){
                    }
                    else{
                        return false;
                    }
                }
            }
            return true;
        }
        else{
            return false;
        }
    }
    //      Syntakticka a lexikalni kontrola hodnot s datovymi typy       \\
    // @param string $variable - argument/hodnota
    // @return bool true = spravne napsana hodnota
    // @return bool false = spatne napsana hodnota nebo jiny typ argumentu
    function symbolCheck($variable){
        $variable = CheckinCheck($variable);
        if(substr($variable, 0, 4) == "nil@"){ // kontrola prefixu datoveho typu nil
            $var = str_split(substr($variable, 3, strlen($variable)-3), strlen($variable)-3);
            if(substr(implode($var), 0, 4) != "@nil" or strlen(implode($var)) != 4){ // kontrola hodnoty nil
                return false;
            }
        }
        else if(substr($variable, 0, 5) == "bool@"){ // kontrola prefixu datoveho typu bool
            $var = str_split(substr($variable, 4, strlen($variable)-4), strlen($variable)-4);
            if(substr(implode($var), 0, 6) == "@false" and strlen(implode($var)) == 6){ // kontrola hodnoty false
            }
            else if((substr(implode($var), 0, 5) == "@true" and strlen(implode($var)) == 5)){ // kontrola hodnoty true
            }
            else{
                return false;
            }
        }
        else if(substr($variable, 0, 4) == "int@"){ // kontrola prefixu datoveho typu int
            $var = str_split(substr($variable, 3, strlen($variable)-3));  
            $first_iteration = true;
            $second_iteration = false;
            foreach($var as $number){ // kontrola ciselne hodnoty
                $ascii = ord($number);
                if($second_iteration == true){
                    $second_iteration = false;
                    if(($ascii >= 48 and $ascii <= 57) or $ascii == 45){ // kontrola znamenka, ascii 45 = '-'
                    }
                    else{
                        return false;
                    }
                }
                if($first_iteration == true){ // kontrola @, ascii 64 = '@'
                    $first_iteration = false;
                    $second_iteration = true;
                    if($ascii != 64){
                        return false;
                    }
                }
                else{
                    if($ascii >= 48 and $ascii <= 57){ // kontrola cisel 
                    }
                    else{
                        return false;
                    }
                }
            }
        }
        else if(substr($variable, 0, 7) == "string@"){
            $var = str_split(substr($variable, 6, strlen($variable)-6));
            $first_iteration = true;
            $escape = 3;
            foreach ($var as $word){  
                $ascii = ord($word);
                if($first_iteration == true){
                    $first_iteration = false;
                    if($ascii != 64){
                        return false;
                    }
                }
                if($escape < 3){
                    if($ascii >= 48 and $ascii <= 57){
                    }
                    else{
                        return false;
                    }
                    $escape = $escape + 1;
                }
                if($ascii == 92){
                    $escape = 0;
                }
            } 
        }
        else{
            return false;
        }
        return true;
    }
    //      Syntakticka a lexikalni kontrola navesti      \\
    // @param string $variable - argument/navesti
    // @return bool true = spravne napsana navesti
    // @return bool false = spatne napsana navesti nebo jiny typ argumentu
    function labelCheck($variable){
        $variable = CheckinCheck($variable);
        $var = str_split($variable); // konverze datoveho typu
        $first_iteration = true;
        foreach($var as $letter){ // kontrola jednotlivych znaku navesti
            $ascii = ord($letter);
            if($first_iteration == true){ // kontrola prvniho znaku retezce
                $first_iteration = false;
                if(($ascii >= 65 and $ascii <= 90) or ($ascii >= 97 and $ascii <= 122) or $ascii == 63 or $ascii == 33 or ($ascii >= 36 and $ascii <= 38) or $ascii == 42 or $ascii == 45 or $ascii == 95){
                }
                else{
                    return false;
                }
            }
            else{                         // kontrola ostatnich znaku retezce
                if(($ascii >= 48 and $ascii <= 57) or ($ascii >= 65 and $ascii <= 90) or ($ascii >= 97 and $ascii <= 122) or $ascii == 63 or $ascii == 33 or ($ascii >= 36 and $ascii <= 38) or $ascii == 42 or $ascii == 45 or $ascii == 95){
                }
                else{
                    return false;
                }
            }
        }
        return true;
    }
    //      Syntakticka a lexikalni kontrola datoveho typu       \\
    // @param string $variable - argument/datovy typ
    // @return bool true = spravne napsana datoveho typu
    // @return bool false = spatne napsana datoveho typu nebo jiny typ argumentu
    function typeCheck($variable){
        $variable = CheckinCheck($variable);
        if($variable == "bool" or $variable == "int" or $variable == "string"){ // kontrola vsech legalnich datovych typu
            return true;
        }
        else{
            return false;
        }
    }
    //      Odstraneni koncu radku a radkovych komentaru       \\
    // @param string $variable - libovolny retezec ke kontrole
    // @return string - zkraceny retezec o konce radku a komentare
    function CheckinCheck($variable){
        if(strpos($variable, "\n")){ // kontrola konce radku
            $variable = substr($variable, 0, strlen($variable)-1);
            if(substr($variable, strlen($variable)-1, 1) == chr(92)){ // odstraneni konce radku kvuli formatum, kde '\n' je 1 znak 
                $variable = substr($variable, 0, strlen($variable)-1);
            }
        }
        if(strpos($variable, "\r")){ // kontrola carriage return
            $variable = substr($variable, 0, strlen($variable)-1);
            if(substr($variable, strlen($variable)-1, 1) == chr(92)){ // kontrola carriage return kvuli formaty, kde '\n' je 1 znak
                $variable = substr($variable, 0, strlen($variable)-1);
            }
        }
        if(strpos($variable, "#")){ // kontrola komentaru 
            $helper = strpos($variable, "#");
            $variable = substr($variable, 0, $helper); // odstraneni komentaru
        }
        return $variable;
    }
    //              Odstraneni mezer pred instrukci              \\
    // @param string array $variable - nacteny radek rozdeleny v poli po mezerach
    // @return string - prvni instrukce
    function SpaceEater($variable){
        $x = 0;
        while(strlen($variable[$x]) == 0){
            $x = $x + 1;
        }
        return $variable[$x];
    }
?>
