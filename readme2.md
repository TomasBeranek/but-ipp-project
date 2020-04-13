Implementační dokumentace k 2. úloze do IPP 2019/2020  
Jméno a příjmení: Tomáš Beránek  
Login: xberan46

## Popis implementace interpretu pro jazyk IPPcode20
Vstupem skriptu interpret.py je XML reprezentace zdrojového kódu v jazyce IPPcode20 a vstupni data pro samotnou interpretaci. Výstup interpretu je vypsán na standardní výstup (stdout).

Pro zpracování dat v XML formátu je použita funkce `ET.parse` z knihovny `xml.etree.ElementTree`. Výstupem této funkce je objekt reprezentující XML strom. Pomocí metody `getroot` je získán kořen stromu (kořenový element), ten je reprezentován jako list elementů, kde každý element je kořenem svého vlastního podstromu. Iterací přes elementy hlavního kořene se přistupuje k elementům představující jednotlivé instrukce. Analogicky poté k argumentům jednotlivých instrukcí.

Instrukce jsou seřazeny pomocí funkce `sorted`, která pro získání hodnoty elementu instrukce využívá funkci `get_order_attr`, která vrací celočíselnou kladnou hodnotu atributu `order`, podle které proběhne přeuspořádání instrukcí. Před zpracováním jednolivých elementů (`program`, `instruction`, `argN`) jsou zkontrolovány jejich názvy, atributy a případně počty vnořených elementů.

Program je zpracováván dvěma průchody. V prvním, jsou zpracovány pouze instrukce definující návěští a v druhém průchodu jsou zpracovány všechny instrukce (i s instrukcemi definující návěští).

Na právě zpracovávanou instrukci je zavolána funkce `process_instruction`, která funguje jako přepínač a podle atributu `opcode` vybírá funkci, která obslouží danou instrukci. Obslužné funkce jsou formátu `i_*`, kde `*` je název operačního kódu instrukce.

V každé funkci zpracovávající instrukci jsou nejdříve zkontrolovány typy argumentů pomocí funkce `check_args`, která jako první parametr přijímá element instrukce a dále libovolný počet řetězců, které reprezentují typy argumentů - `nil`, `var`, `int`, `string`, `label`, `bool`, `type` nebo `symb`. Poté je zpracována samotná instrukce. Zde je využito funkce `get_val`, která vrátí dvojici hodnot (typ hodnoty, hodnota) ze zadaného elementu argumentu (pokud argument obsahuje proměnnou je vracena její hodnota) a funkce `insert_value_to_var`, která vloží hodnotu (druhý argument) do zadané proměnné (první argument).

Pokud instrukce může ovlivit řízení toku programu, tak může vracet kladnou celočíselnou hodnotu, která představuje pořadí instrukce, na kterou se skočí (nejedná se o atribut `order`). Pokud pořadí instrukce `instruction_cnt` je větší než počet instrukcí, je program korektně ukončen (návratová hodnota 0).

### Rozšíření STACK
Veškeré instrukce z rozšíření STACK jsou z důvodu lepší čitelnosti a lepšího oddělení od ostatních instrukcí implementovány jako samostatné funkce. Jelikož v zadání není uvedeno, jakým zpusobem se má zpracovat výsledek dané instrukce (např. ADDS, EQS, ...), tak se předpokládá, že první argument (`var` nebo `label`) těchto instrukcí (kromě CLEARS) je předám normálně (uveden v XML) a zbylé operandy jsou získány ze zásobníku.

### Rozšíření STATI
Statistiky o kódu jsou sbírány nezávisle na zadání vstupních parametrů. Parametry `--vars` a `--insts` jsou ukládány do listu `stats_to_print` (kvůli uchování pořadí). Pokud je spolu s těmito parametry zadán také parametr `--stats=file`, tak se na konci programu zpracuje list `stats_to_print` a v zadaném pořadí se vypíší statistiky do souboru `file`. Jelikož v zadání není dostatečně upřesněna funkcionalita parametru `--vars`, tak byla zvolena následovně: výsledkem je maximální počet inicializovaných proměnných v jednu konkrétní chvíli interpretace, tzn. po každé instrukci je zjištěn počet inicializovaných proměnných a pokud je toto číslo větší než dosavadní maximální, tak se maximální hodnota aktualizuje.

## Popis implementace testovacího rámce
Skript test.php slouží jako testovací rámec pro skripty parser.php a interpret.py. Výsledky testů jsou vypsány na standardní výstup (stdout) ve formě HTML. Skript test.php není implementován objektově, vyjimkou je využití objektů a metod pro iteraci přes soubory ve složce - `RecursiveDirectoryIterator`, `RecursiveIteratorIterator` a metoda `getPathname`. Skripty lze testovat současně nebo zvlášť. Pro lepší čitelnost kódu a lepší invarianci jsou tyto tři části implementovány odděleně. Výstup skriptů je vždy před porovnáním uložen do souboru s unikátním jménem, aby nedocházelo k přepsání již existujících souborů. K vygenerování unikátního jména je využita funkce `get_unique_file_name`, která generuje názvy souborů jako kladná celá čísla s příponou `.myout`. V případě kolize se vygeneruje další možný název. Soubor je ihned po porovnání vymazán.
