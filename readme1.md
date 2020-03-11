Implementační dokumentace k 1. úloze do IPP 2019/2020
Jméno a příjmení: Tomáš Beránek
Login: xberan46

##Popis implementace parsovacího skriptu parse.php pro jazyk IPPcode20
Implementace je založena na konečném stavovém řízení (rozlišování instrukcí) v
kombinaci s regulárními výrazy (zejména pro lexikální analýzu). Skript není
implementován objektově, vyjimkou je třída DOMDocument sloužící pro vytvoření
výstupního XML formátu. Výstup v XML formátu je vypsán na stdout, pouze pokud
skript byl ukončen bez chyby (RC 0).

Skript parse.php zpracová vstup ze stdin po řadcích. Na každý rádek je volána
funkce `print_xml_instruction`, která vytvoří a přípojí element reprezentující
danou instrukci. Uvnitř funkce je implementováno stavové řízení. Jednotlivé
stavy reprezentují množiny příkazů z IPPcode20, rozdělené podle počtu a typu
operandů např. instrukce DEFVAR a POPS akceptují jediný operand typu proměnná
(na úrovní parseru se tudíž k nim můžeme při kontrole chovat stejně). Stavový
řízení zajišťuje syntaktickou (počty a typy operandů) a lexikální analýzu
(názvy instrukcí).

Dále byly vytvořeny funkce `is_var`, `is_symb`, `is_type` a `is_label`, které
zajišťují lexikální analýzu jednotlivých typů operandů.

Nápověda je realizována pomocí funkce `print_help`, která po zpracování
vstupních parametrů skriptu vypíše při zadaní parametru `--help` daný text.
