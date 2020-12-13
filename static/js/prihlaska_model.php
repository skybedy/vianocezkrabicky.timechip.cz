
<?php
class Prihlaska_Model extends Model{
        private $vychozi_vs = 202000001;
	public $sqlzavody;
	public $sqlprihlasky;
	public  $sqlpodzavody;
	public $NazevZavodu;
	public $kod_zavodu;
	public $IdZavodu;
	private $cislo_uctu;
	private $iban;
	private $swift;
	private $nazev_banky;
	public $udaje;
	private $vs;
	private $startovne_z_db;
	private $startovne_z_db_kc;
	private $startovne_z_db_eu;
	private $startovne_eu;
	private $startovne_kc;
	private $startovne;
	private $startovne_2;
	private $konec_prihlasek;
	private $mena;
	private $poradatel;
	private $telefon_na_poradatele;
	private $mail_na_poradatele;
	public  $mail_na_zavodnika;
	private $kategorie;
	private $kod_kategorie;
	private $poradi_podzavodu;
	private $kategorie_2;
	private $kod_kategorie_2;
	private $poradi_podzavodu_2;
	public $RokZavodu;
	private $vek;
	private $typ_prihlasky;
	private $pohlavi;
	private $pocet_podzavodu;
	private $sqlprihlaskyjednotlivci;
	private $sqlprihlaskytymy;
	private $sqlkategorie;
	private $sqlzavod;
	private $vychozi_startovne;
        private $cena_tricka;
        private $cena_tricka_z_db;
	
	
	//select bez hodnoty je false, ale je definovaný, checbox není ani definovaný, musí se použít isset
	
	function __construct() {
	    parent::__construct();
	    $this->IdZavodu = Session::get('race_id');
	    if(Session::get('poradi_podzavodu')){
		$this->poradi_podzavodu = Session::get('poradi_podzavodu'); 	
	    }
	    $data = false;
	    $this->sqlzavody = 'zavody_'.YEAR;
	    $sql1 = "SELECT *,DATE_FORMAT(datum_zavodu,'%Y') AS rok_zavodu FROM $this->sqlzavody WHERE id_zavodu = :id_zavodu";
	    $sth = $this->db->prepare($sql1);
	    $sth->execute(array(':id_zavodu' => $this->IdZavodu));
	    if($sth->rowCount()){
		$data =  $sth->fetchAll();
		foreach($data as $val){
		    $this->NazevZavodu = $val['nazev_zavodu'];
		    $this->kod_zavodu = $val['kod_zavodu'];
		   // $this->RokZavodu = $val['rok_zavodu'];
		    $this->RokZavodu = YEAR;
		    if($this->IdZavodu == 800 AND $this->RokZavodu == 2017){
                        $this->RokZavodu = 2018;
                    }
                    
                    $this->pocet_podzavodu = $val['pocet_podzavodu']; 
		    $this->kod_zavodu = $val['kod_zavodu']; 
		}
		$this->sqlprihlasky = 'prihlasky_'.$this->kod_zavodu.'_'.YEAR;
		$this->sqlprihlaskyjednotlivci = 'prihlasky_jednotlivci_'.YEAR;
		$this->sqlprihlaskytymy = 'prihlasky_tymy_'.YEAR;
		$this->sqlkategorie = 'kategorie_'.YEAR;
		$this->sqlpodzavody = 'podzavody_'.YEAR;
		$this->sqlzavod = 'zavod_'.$this->kod_zavodu.'_'.YEAR;
	    }
	    $this->Pohlavi();
            require "./libs/phpmailer/phpmailer/PHPMailerAutoload.php";

	}
	
	
	public function xhrFinish(){
	    //tady ještě zkontrolovat, jestli se ty SESSIONS nedají udělat nějak jinak
	    $str = '';
	    if(Session::get('token') == true){
		(Session::get('udaje') == true) ? ($this->udaje = unserialize(Session::get('udaje'))) : ($this->udaje = false);
		if($this->udaje['typ_prihlasky'] == 1 OR $this->udaje['typ_prihlasky'] == 4 OR $this->udaje['typ_prihlasky'] == 5){
		    $this->mail_na_zavodnika = $this->udaje['mail'];
		}
		if($this->udaje['typ_prihlasky'] == 2){
		    $this->mail_na_zavodnika = $this->udaje['mail_tym'];
		}
                $str = false;
		$sql1 = "SELECT *,DATE_FORMAT(konec_prihlasek,'%e.%c.%Y') AS konec_prihlasek FROM prihlasky_".YEAR." WHERE id_zavodu = '$this->IdZavodu'";
		//echo $sql1;
		$sth1 = $this->db->prepare($sql1);
		$sth1->execute(array(':id_zavodu' => $this->IdZavodu));
		if($sth1->rowCount()){
		    $data1 =  $sth1->fetchObject();
		    $this->cislo_uctu = $data1->cislo_uctu;
		    $this->iban = $data1->iban;
		    $this->swift = $data1->swift;
		    $this->nazev_banky = $data1->nazev_banky;
		    $this->poradatel = $data1->poradatel;
		    $this->telefon_na_poradatele = $data1->telefon;
		    $this->mail_na_poradatele = $data1->mail;
		    $this->konec_prihlasek = $data1->konec_prihlasek;
		}
		$this->ZiskaniVS();
		//$this->VyberStartovneho();
		//$this->VypocetStartovneho();
		$this->StartovneZDB();
		$this->VyberKategorie();
		$this->Startovne();
		$this->Vek();
		$this->TypPrihlasky();
		$this->xhrSaveToDB($this->typ_prihlasky);
		return true;
	    }
	    else{
		return false;
	    }
	}

	
    private function TypPrihlasky(){
	switch($this->udaje['typ_prihlasky']){
	    case 1:
		$this->typ_prihlasky = 'jednotlivci';
	    break;
	    case 2:
		$this->typ_prihlasky = 'tymy';
	    break;
	    case 4: //enduro hobby
		$this->typ_prihlasky = 'jednotlivci';
	    break;
	    case 5: //koruna beskyd
		$this->typ_prihlasky = 'jednotlivci';
	    break;
	    case 6: //ctyrkolky sikl
		$this->typ_prihlasky = 'tymy';
	    break;
	}
    }
	
    
    //v případě 1 a 2 se bere typ z metody TpPrihlasky, která je volána xhr finisf, v případě 3 je typ_prihlášky  dodáván ručně v kontroleru
    public function xhrSaveToDB($typ_prihlasky){
	if($this->IdZavodu == 13 || $this->IdZavodu == 19 || $this->IdZavodu == 39 || $this->IdZavodu == 46){
	    $tp = $typ_prihlasky;
	}
	else{
	   $tp = $this->udaje['typ_prihlasky']; 
	}
	
	switch($tp){
	    case 1:
		$this->xhrSaveToDBJednotlivci();
	    break;
	    case 2:
		$this->xhrSaveToDBTymy();
	    break;
	    case 3:
		$this->xhrSaveToDBEnduro();
	    break;
	    case 4: //Enduro hobby
		$this->xhrSaveToDBJednotlivci();
	    break;
	    case 5: //KORUNA Beskyd
		$this->xhrSaveToDBKorunaBeskyd();
	    break;
	    case 6: //Ctyrkolky šikl
		$this->xhrSaveToDBTymyCtyrkolkySikl();
	    break;
	}
    }
    
    public function xhrOvereni(){
	switch($_POST['typ_prihlasky']){
	    case 1:
		return $this->xhrOvereniJednotlivci();
	    break;
	    case 2:
		return $this->xhrOvereniTymy();
	    break;
	    case 4:
		return $this->xhrOvereniHobbyEnduro();
	    break;
	    case 5:
		return $this->xhrOvereniJednotlivci();
	    break;
	    case 6:
		return $this->xhrOvereniTymy();
	    break;
	}
    }

    private function Pohlavi(){
	$this->pohlavi = Array("M" => "Muž","Z" => "Žena","0" => "0");
    }

   /*
    * Ať to zas příště nehledám jako kokot, tak v případě typu závodu jako je Žilina, (tzn. různé typy formulářů na různé podzávody) se číslo podzávodu ukládá do Session a je posléze
    * přístupné přes $this->poradí_podzavodu... v pripade, kdy se jedna  stejne formulare, tak je poradi podzavodu pristupne pres příchozí form a je serializovaný do $this->udaje['poradi_podzavodu']
    */

    
    
	public function xhrOvereniJednotlivci(){
	    Session::set('udaje',serialize($_POST));
	    $str = false;
	    $str .= '<div class="container">';
	    $str .= '<table class="table table-hover">';
	    $str .= (isset($_POST['jmeno_1']) && isset($_POST['prijmeni_1'])) ? ('<tr><td class="align_left">Jméno a příjmení</td><td class="align_right">'.$_POST['jmeno_1'].' '.$_POST['prijmeni_1'].'</td></tr>') : ('');
	    $str .= (isset($_POST['pohlavi'])) ? ('<tr><td class="align_left">Pohlaví</td><td class="align_right">'.$this->pohlavi[$_POST['pohlavi']].'</td></tr>') : ('');
	    $str .= (isset($_POST['den_narozeni']) && isset($_POST['mesic_narozeni']) && isset($_POST['rok_narozeni'])) ? ('<tr><td class="align_left">Datum narození</td><td class="align_right">'.$_POST['den_narozeni'].'.'.$_POST['mesic_narozeni'].'.'.$_POST['rok_narozeni'].'</td></tr>') : ('');
	    $str .= (isset($_POST['prislusnost'])) ? ('<tr><td class="align_left">Tým nebo bydliště</td><td class="align_right">'.$_POST['prislusnost'].'</td></tr>') : ('');
	    $str .= (isset($_POST['stat'])) ? ('<tr><td class="align_left">Stát</td><td class="align_right">'.$_POST['stat'].'</td></tr>') : ('');
	    $str .= (isset($_POST['mail'])) ? ('<tr><td class="align_left">E-mail</td><td class="align_right">'.$_POST['mail'].'</td></tr>') : ('');
	    $str .= (isset($_POST['telefon_1'])) ? ('<tr><td class="align_left">Telefon</td><td class="align_right">'.$_POST['telefon_1'].'</td></tr>') : ('');
	    $str .= (!empty($_POST['telefon_2'])) ? ('<tr><td class="align_left">Alternativní telefon</td><td class="align_right">'.$_POST['telefon_2'].'</td></tr>') : ('');
	    $str .= (!empty($_POST['tricko'])) ? ('<tr><td class="align_left">Velikost trička</td><td class="align_right">'.$_POST['tricko'].'</td></tr>') : ('');
	    $str .= (!empty($_POST['ponozky'])) ? ('<tr><td class="align_left">Velikost ponožek</td><td class="align_right">'.$_POST['ponozky'].'</td></tr>') : ('');
	    $str .= (!empty($_POST['vzkaz_poradateli'])) ? ('<tr><td class="align_left">Vzkaz pořadateli</td><td class="align_right">'.$_POST['vzkaz_poradateli'].'</td></tr>') : ('');
	    $str .= '</table>';
	    $str .= $this->xhrOvereniForm();
	    return $str;
	}
	
	
    public function xhrOvereniHobbyEnduro(){
	    
	Session::set('udaje',serialize($_POST));
	$str = false;
	$str .= '<div class="container">';
	$str .= '<table class="table table-hover">';
	$str .= (isset($_POST['ids'])) ? ('<tr><td class="align_left">Startovní číslo</td><td class="align_right">'.$_POST['ids'].'</td></tr>') : ('');
	$str .= (isset($_POST['jmeno_1']) && isset($_POST['prijmeni_1'])) ? ('<tr><td class="align_left">Jméno a příjmení</td><td class="align_right">'.$_POST['jmeno_1'].' '.$_POST['prijmeni_1'].'</td></tr>') : ('');
	$str .= (isset($_POST['pohlavi'])) ? ('<tr><td class="align_left">Pohlaví</td><td class="align_right">'.$this->pohlavi[$_POST['pohlavi']].'</td></tr>') : ('');
	$str .= (isset($_POST['den_narozeni']) && isset($_POST['mesic_narozeni']) && isset($_POST['rok_narozeni'])) ? ('<tr><td class="align_left">Datum narození</td><td class="align_right">'.$_POST['den_narozeni'].'.'.$_POST['mesic_narozeni'].'.'.$_POST['rok_narozeni'].'</td></tr>') : ('');
	$str .= (isset($_POST['prislusnost'])) ? ('<tr><td class="align_left">Tým nebo bydliště</td><td class="align_right">'.$_POST['prislusnost'].'</td></tr>') : ('');
	$str .= (isset($_POST['stat'])) ? ('<tr><td class="align_left">Stát</td><td class="align_right">'.$_POST['stat'].'</td></tr>') : ('');
	$str .= (isset($_POST['mail'])) ? ('<tr><td class="align_left">E-mail</td><td class="align_right">'.$_POST['mail'].'</td></tr>') : ('');
	$str .= (isset($_POST['telefon_1'])) ? ('<tr><td class="align_left">Telefon</td><td class="align_right">'.$_POST['telefon_1'].'</td></tr>') : ('');
	$str .= (!empty($_POST['telefon_2'])) ? ('<tr><td class="align_left">Alternativní telefon</td><td class="align_right">'.$_POST['telefon_2'].'</td></tr>') : ('');
	$str .= (!empty($_POST['tricko'])) ? ('<tr><td class="align_left">Velikost trička</td><td class="align_right">'.$_POST['tricko'].'</td></tr>') : ('');
	$str .= (!empty($_POST['ponozky'])) ? ('<tr><td class="align_left">Velikost ponožek</td><td class="align_right">'.$_POST['ponozky'].'</td></tr>') : ('');
	$str .= (!empty($_POST['vzkaz_poradateli'])) ? ('<tr><td class="align_left">Vzkaz pořadateli</td><td class="align_right">'.$_POST['vzkaz_poradateli'].'</td></tr>') : ('');
	$str .= '</table>';
	
	$str .= '<div class="panel contact">';
	$str .= '<div class="panel-body padding-bottom-none">';    
	$str .= '<ul class="list-unstyl">';
	$str .= '<li>zkontrolujte si hlavně e-mail, pokud bude uveden špatně, nepřijdou vám platební informace</li>';
	$str .= '<li>neuvádějte zdrobnělé nebo jiné tvary svého jména než máte v osobních dokladech, v opačném případě může být přihláška smazána</li>';
	$str .= '<li>v případě, že neuvádíte název týmu, uveďte pouze místo bydliště (např. Ostrava), neuvádějte ulici, ani č.p, ani psč</li>';
	$str .= '<li>telefonní číslo uvádějte pokud možno s předčíslím, tzn. např. +420, +421, atd.</li>';
	$str .= '</ul>';
	$str .= '</div></div>';
	$sql1 = "SELECT ids FROM $this->sqlprihlaskyjednotlivci WHERE id_zavodu = :id_zavodu AND ids = :ids";
	$sth1 = $this->db->prepare($sql1);
	$sth1->execute(Array('id_zavodu' => $this->IdZavodu,'ids' => $_POST['ids']));
	if($sth1->rowCount()){
	    $dbdata1 = $sth1->fetchObject();
	    $ids = $dbdata1->ids;
	    $str .= '<div class="panel contact"><div class="panel-body">';
	    $str .= 'Startovní číslo <b>'.$ids.'</b> už je <a style="text-decoration:underline" target="_blank" href="'.URL.'prihlaska/vypis-prihlasek">obsazené</a>, je třeba si vybrat jiné.';
	    $str .= '</div></div>';
	    $str .= '<form action="'.URL.'prihlaska/xhrRepair" method="post" id="opravit_udaje" class="kontrolni_tabulka">';
	    $str .= '<div class="form-group">'; 
	    $str .= '<button type="submit" class="form-control btn btn-danger">Opravit údaje - zvolte v případě, že údaje potřebujete opravit</button>';
	    $str .= '</div>';
	    $str .= '</form>';

	}
	else{
	    $str .= '<form action="'.URL.'prihlaska/xhrRepair" method="post" id="opravit_udaje" class="kontrolni_tabulka">';
	    $str .= '<div class="form-group">'; 
	    $str .= '<button type="submit" class="form-control btn btn-danger">Opravit údaje - zvolte v případě, že údaje potřebujete opravit</button>';
	    $str .= '</div>';
	    $str .= '</form>';
	    $str .= '<form action="'.URL.'prihlaska/xhrFinish/'.$this->IdZavodu.'" method="post" id="odeslat_prihlasku" class="form">';
	    $str .= '<div class="form-group">'; 
	    $str .= '<button type="submit" class="form-control btn btn-success">Odeslat přihlášku - zvolte pouze v případě, že údaje jsou OK a můžete je odeslat</button>';
	    $str .= '</div>';
	    $str .= '</form>';
	}
	$str .= '</div>';
	return $str;
    }
	
		
	
    public function xhrOvereniTymy(){
        Session::set('udaje',serialize($_POST));
        $str = false;
       // print_r($_POST);
       // echo $_POST['id_kategorie'];
        $str .= '<div class="container">';
        $str .= '<table class="table table-hover">';
        $str .= '<thead><th colspan="2">Tým</th></thead>';
        $str .= (isset($_POST['nazev_tymu'])) ? ('<tr><td class="align_left">Název týmu</td><td class="align_right">'.$_POST['nazev_tymu'].'</td></tr>') : ('');
        $str .= (isset($_POST['stat_tym'])) ? ('<tr><td class="align_left">Stát</td><td class="align_right">'.$_POST['stat_tym'].'</td></tr>') : ('');
        $str .= (isset($_POST['mail_tym'])) ? ('<tr><td class="align_left">E-mail:</td><td class="align_right">'.$_POST['mail_tym'].'</td></tr>') : ('');
        $str .= (isset($_POST['telefon_1_tym'])) ? ('<tr><td class="align_left">Telefon:</td><td class="align_right">'.$_POST['telefon_1_tym'].'</td></tr>') : ('');
        $str .= (isset($_POST['telefon_2_tym'])) ? ('<tr><td class="align_left">Alternativní telefon :</td><td class="align_right">'.$_POST['telefon_2_tym'].'</td></tr>') : ('');
        $str .= (isset($_POST['id_kategorie'])) ? ('<tr><td class="align_left">Kategorie</td><td class="align_right">'.$this->VyberKategoriePodleId($_POST['id_kategorie']).'</td></tr>') : ('');
        for($i=1;$i<=$_POST['pocet_clenu'];$i++){
            $str .= '<thead><th colspan="2">Závodník '.$i.'</th></thead>';
            $str .= (isset($_POST['jmeno_1_'.$i]) && isset($_POST['prijmeni_1_'.$i])) ? ('<tr><td class="align_left">Jméno a příjmení</td><td class="align_right">'.$_POST['jmeno_1_'.$i].' '.$_POST['prijmeni_1_'.$i].'</td></tr>') : ('');
            $str .= (isset($_POST['pohlavi_'.$i])) ? ('<tr><td class="align_left">Pohlaví</td><td class="align_right">'.$this->pohlavi[$_POST['pohlavi_'.$i]].'</td></tr>') : ('');
            $str .= (isset($_POST['den_narozeni_'.$i]) && isset($_POST['mesic_narozeni_'.$i]) && isset($_POST['rok_narozeni_'.$i])) ? ('<tr><td class="align_left">Datum narození</td><td class="align_right">'.$_POST['den_narozeni_'.$i].'.'.$_POST['mesic_narozeni_'.$i].'.'.$_POST['rok_narozeni_'.$i].'</td></tr>') : ('');
            $str .= (isset($_POST['prislusnost_'.$i])) ? ('<tr><td class="align_left">Tým/bydliště</td><td class="align_right">'.$_POST['prislusnost_'.$i].'</td></tr>') : ('');
            $str .= (isset($_POST['stat_'.$i])) ? ('<tr><td class="align_left">Stát</td><td class="align_right">'.$_POST['stat_'.$i].'</td></tr>') : ('');
            $str .= (isset($_POST['mail_'.$i])) ? ('<tr><td class="align_left">E-mail</td><td class="align_right">'.$_POST['mail_'.$i].'</td></tr>') : ('');
            $str .= (isset($_POST['telefon_1_'.$i])) ? ('<tr><td class="align_left">Telefon</td><td class="align_right">'.$_POST['telefon_1_'.$i].'</td></tr>') : ('');
            $str .= (!empty($_POST['telefon_2_'.$i])) ? ('<tr><td class="align_left">Alternativní telefon</td><td class="align_right">'.$_POST['telefon_2_'.$i].'</td></tr>') : ('');
            $str .= (!empty($_POST['tricko_'.$i])) ? ('<tr><td class="align_left">Velikost trička</td><td class="align_right">'.$_POST['tricko_'.$i].'</td></tr>') : ('');
            $str .= (!empty($_POST['ponozky_'.$i])) ? ('<tr><td class="align_left">Velikost ponožek</td><td class="align_right">'.$_POST['ponozky_'.$i].'</td></tr>') : ('');
        }
        $str .= '</table>';
        $str .= $this->xhrOvereniForm();

        return $str;
    }
	
	
	
    private function xhrOvereniForm(){
	$str = '';
	$str .= '<div class="panel contact">';
	$str .= '<div class="panel-body padding-bottom-none">';    
	$str .= '<ul class="list-unstyl">';
	$str .= '<li>zkontrolujte si hlavně e-mail, pokud bude uveden špatně, nepřijdou vám platební informace</li>';
	$str .= '<li>neuvádějte zdrobnělé nebo jiné tvary svého jména než máte v osobních dokladech, v opačném případě může být přihláška smazána</li>';
	$str .= '<li>v případě, že neuvádíte název týmu, uveďte pouze místo bydliště (např. Ostrava), neuvádějte ulici, ani č.p, ani psč</li>';
	$str .= '<li>telefonní číslo uvádějte pokud možno s předčíslím, tzn. např. +420, +421, atd.</li>';
	$str .= '</ul>';
	$str .= '</div></div>';
	$str .= '<form action="'.URL.'prihlaska/xhrRepair" method="post" id="opravit_udaje" class="kontrolni_tabulka">';
	$str .= '<div class="form-group">'; 
	$str .= '<button type="submit" class="form-control btn btn-danger">Opravit údaje - zvolte v případě, že údaje potřebujete opravit</button>';
	$str .= '</div>';
	$str .= '</form>';
	$str .= '<form action="'.URL.'prihlaska/xhrFinish/'.$this->IdZavodu.'" method="post" id="odeslat_prihlasku" class="form">';
	$str .= '<div class="form-group">'; 
	$str .= '<button type="submit" class="form-control btn btn-success">Odeslat přihlášku - zvolte pouze v případě, že údaje jsou OK a můžete je odeslat</button>';
	$str .= '</div>';
	$str .= '</form>';
	$str .= '</div>';//konec containeru
	return $str;
    }
	
    
    public function VolneIdo(){
	$str = '';
	$prvni_cislo = 140;
	$sql1 = "SELECT MAX(ido) AS max_ido FROM osoby";
	$sth1 = $this->db->prepare($sql1);
	$sth1->execute();
	$dbdata1 = $sth1->fetchObject(); //zatím nepoužito
	$k = 1;
	for($i=$prvni_cislo;$i<=$dbdata1->max_ido;$i++){
	    $sql2 = "SELECT ido FROM osoby WHERE ido = :i";
	    $sth2 = $this->db->prepare($sql2);
	    $sth2->execute(Array(':i' => $i));
	    if(!$sth2->rowCount()){
		//$str .= ($i).',';
		$str .= ($i);
		//$k++;
		return $str;
		exit();
	    }
	}
	//$str .= $k;
    }
    


    private function Hlasky($typ_hlasky){
	$str = '';
	$dovetek = 'V případě nejasností nás kontaktujte prostřednictvím e-mailu na <a href="mailto://info@timechip.cz">info@timechip.cz</a>, nebo telefonicky na číslo +420 776 131313.<br />';
	$dovetek .= 'Zpět do formuláře se můžete vrátit <a id="navrat_do_formulare" href="#">zde.</a>'; 
	switch($typ_hlasky){
	    case 'osoba':
		$str .= 'Osoba se neuložila';
	    break;
	    case 'chybejici_etapa':
		$str .= 'Přihláška nemůže být uložena, nebyl zadán den závodění';
	    break;
	    case 'duplicitni_prihlaseni':
		$str .= 'Není možné se přihlásit v rámci jednoho závodu 2x do stejné kategorie.<br />';
		$str .= $dovetek;
	    break;
	    case 'uspesne_prihlaseni':
	    $str .= 'Děkujeme za přihlášení, na vaši e-mailovou adresu byla odeslána zpráva s dalšími informacemi.<br />
V případě, že vám e-mail nepřijde (zkontrolujte si i složku s nevyžádanou poštou), nenajdete se ve výpisu přihlášek, nebo narazíte na jiný problém, kontaktujte nás prosím buď prostřednictvím e-mailu na <a href="mailto:info@timechip.cz">info@timechip.cz</a>, nebo telefonicky
na +420 776131313.';
	    break;
	    
	
	}
	return $str;
    } 
    
    private function KontrolaDuplicityEnduro($etapa){
	$sql1 = "SELECT * FROM $this->sqlzavod WHERE ido = {$_GET['ido']} AND id_kategorie = {$_GET['id_kategorie']} AND id_etapy = $etapa";
	$sth1 = $this->db->prepare($sql1);
	$sth1->execute();
	if($sth1->rowCount()){
	    return $sth1->fetch();
	}
    }
    
    
    
    private function xhrSaveToDBEnduro(){
	$str = Array(); 
	$str['uspesne_prihlaseni'] = false;
	if(isset($_GET['etapa'])){
	    
	    
	    for($i=1;$i<=count($_GET['etapa']);$i++){
		if(count($_GET['etapa']) == 1){
		    $etapa = $_GET['etapa'];
		}
		else{
		    $etapa = $_GET['etapa'][$i-1]; 
		}
		
		if($this->KontrolaDuplicityEnduro($etapa)){
		    $str['hlaska'] =  $this->Hlasky('duplicitni_prihlaseni');
		}
		else{
		    ($_GET['ido'] == true) ? ($vlozeni['ido'] = $_GET['ido']) : ('');
		    ($_GET['race_number'] == true) ? ($vlozeni['ids'] = $_GET['race_number']) : ('');
		    ($_GET['race_number'] == true) ? ($vlozeni['ids_alias'] = $_GET['race_number']) : ('');
		    ($_GET['race_number'] == true) ? ($vlozeni['cip'] = $_GET['race_number']) : ('');
		    ($_GET['id_tymu'] == true) ? ($vlozeni['prislusnost'] = $_GET['id_tymu']) : ('');
		    ($_GET['id_tymu'] == true) ? ($vlozeni['id_tymu'] = $_GET['id_tymu']) : ('');
		    ($_GET['id_kategorie'] == true) ? ($vlozeni['id_kategorie'] = $_GET['id_kategorie']) : ('');
		    ($_GET['poradi_podzavodu'] == true) ? ($vlozeni['poradi_podzavodu'] = $_GET['poradi_podzavodu']) : ('');
		    ($_GET['zdravotni_pojistovna'] == true) ? ($vlozeni['zdravotni_pojistovna'] = $_GET['zdravotni_pojistovna']) : ('');
		    ($_GET['id_typu_licence'] == true) ? ($vlozeni['id_typu_licence'] = $_GET['id_typu_licence']) : ('');
		    ($_GET['cislo_licence'] == true) ? ($vlozeni['cislo_licence'] = $_GET['cislo_licence']) : ('');
		    ($_GET['id_2t4t'] == true) ? ($vlozeni['id_2t4t'] = $_GET['id_2t4t']) : ('');
		    ($_GET['id_motocyklu'] == true) ? ($vlozeni['id_motocyklu'] = $_GET['id_motocyklu']) : ('');
		    ($_GET['typ_motocyklu'] == true) ? ($vlozeni['typ_motocyklu'] = $_GET['typ_motocyklu']) : ('');
		    ($_GET['cislo_ramu'] == true) ? ($vlozeni['cislo_ramu'] = $_GET['cislo_ramu']) : ('');
		    ($_GET['objem_motoru'] == true) ? ($vlozeni['objem_motoru'] = $_GET['objem_motoru']) : ('');
		    ($_GET['pocet_valcu'] == true) ? ($vlozeni['pocet_valcu'] = $_GET['pocet_valcu']) : ('');
		    ($_GET['znacka_prilby'] == true) ? ($vlozeni['znacka_prilby'] = $_GET['znacka_prilby']) : ('');
		    ($_GET['homologace_prilby'] == true) ? ($vlozeni['homologace_prilby'] = $_GET['homologace_prilby']) : ('');
		    
		    $vlozeni['id_etapy'] = $etapa;
		    
		    
		    $sql1 = "INSERT INTO $this->sqlzavod (".implode(",",array_keys($vlozeni)).") VALUES ('".implode("','",$vlozeni)."')";
		    $sth1 = $this->db->prepare($sql1);
		    $sth1->execute();
		    if($sth1->rowCount()){
			$str['uspesne_prihlaseni'] = 1;
			$str['hlaska'] = $this->Hlasky('uspesne_prihlaseni');
			
		    }
		    else{
			//$str .= 'Nějaký problém s uložením - '.$sql1;
		    }

		}

	    }
	}
	else{
	    //$str .= $this->Hlasky('chybejici_etapa');
	}
	
	//mail  člověku přijde i když se třeba zkusí duplicitně přihlásít, což je špatně
	$this->MailZavodnikovi($_GET['mail']);
	$this->UpdateUdajuEnduro();
	$this->UpdateOsobyCiziZdroje();
	
	echo json_encode($str);
    }
    
    
    private function UpdateOsobyCiziZdroje(){
	
	($_GET['cislo_licence'] == '') ? ($cislo_licence = 'NULL') : ($cislo_licence = $_GET['cislo_licence']);
	($_GET['cislo_ramu'] == '') ? ($cislo_ramu = 'NULL') : ($cislo_ramu = $_GET['cislo_ramu']);

	$sql1 = "UPDATE osoby_cizi_zdroje SET kod_pojistovny =  '{$_GET['zdravotni_pojistovna']}',id_tymu = '{$_GET['id_tymu']}',id_typu_licence = '{$_GET['id_typu_licence']}',"
	. "cislo_licence = $cislo_licence,id_2t4t = '{$_GET['id_2t4t']}',id_motocyklu = '{$_GET['id_motocyklu']}',typ_motocyklu = '{$_GET['typ_motocyklu']}',"
	. "cislo_ramu = $cislo_ramu,objem_motoru = '{$_GET['objem_motoru']}',pocet_valcu = '{$_GET['pocet_valcu']}',znacka_prilby = '{$_GET['znacka_prilby']}',"
	. "homologace_prilby = '{$_GET['homologace_prilby']}' WHERE ido = '{$_GET['ido']}' AND id_serialu = 1 AND rok_serialu = $this->RokZavodu";
	$sth1 = $this->db->prepare($sql1);
	$sth1->execute();
	//echo $sql1;
    }
    
    
    
    
    private function UpdateUdajuEnduro(){
	$str = '';
	$den_update = false;
	$mesic_update = false;
	$ulice_update = false;
	$ulice_update = false;
	$obec_update = false;
	$zip_update = false;
	$stat_update = false;
	$telefon_update = false;
	$pohlavi_update = false;
	
	$sql2 = "UPDATE osoby SET ";
	$sql1 = "SELECT * FROM osoby WHERE ido = :ido";
	$sth1 = $this->db->prepare($sql1);
	$sth1->execute(Array(':ido' => $_GET['ido']));
	if($sth1->rowCount()){
	    $dbdata1 = $sth1->fetchObject();
	    if(!$dbdata1->den){
		$den_update = 'den = '.$_GET['den_narozeni'].',';
		$sql2 .= $den_update;
	    }
	    if(!$dbdata1->mesic){
		$mesic_update = 'mesic = '.$_GET['mesic_narozeni'].',';
		$sql2 .= $mesic_update;
	    }
	    
	    if(!$dbdata1->ulice){
		$ulice_update = "ulice = '".$_GET['ulice']."',";
		$sql2 .= $ulice_update;
	    }
	    if(!$dbdata1->obec){
		$obec_update = "obec = '".$_GET['obec']."',";
		$sql2 .= $obec_update;
	    }
	    if(!$dbdata1->zip){
		$zip_update = "zip = '".$_GET['zip']."',";
		$sql2 .= $zip_update;
	    }
	    if(!$dbdata1->psc){
		$stat_update = "psc = '".$_GET['stat']."',";
		$sql2 .= $stat_update;
	    }
	    if(!$dbdata1->telefon){
		$telefon_update = "telefon = '".$_GET['telefon']."',";
		$sql2 .= $telefon_update;
	    }
	    if(!$dbdata1->pohlavi){
		$pohlavi_update = "pohlavi = '".$_GET['pohlavi']."',";
		$sql2 .= $pohlavi_update;
	    }
	
	    if($den_update OR $mesic_update OR $ulice_update OR $obec_update OR $zip_update OR $stat_update OR $telefon_update OR $pohlavi_update){
		$sql2 .= "zdroj = 'update_prihlaseni_do_zavodu_cams' WHERE ido = {$_GET['ido']}";
	
		$sth2 = $this->db->prepare($sql2);
		$sth2->execute();
		if($sth2->rowCount()){
		    $str .= 'Úprava provedena '.$sql2;
		    //echo 'jo';
		}
		else{
		    $str .= 'Nejaký problem s UPDATE osoby - '.$sql2;
		    //echo 'ne';
		}
	    }
	}
	//echo $sql2; 
    }
	
    
    
    
    
    
    private function StartNumberInsert($ido){
	// ve chvíli programování použito číslo 1, což je jakoby Cross Country, což je provizorka
	//správně by mělo být k závodu v db přiřazeno id seriálu, pokud je nějakého seriálu součástí a s tím ID tady pracovat ... Maňana
	$id_serialu = 1;
	$sql1 = "SELECT * FROM startovni_cisla WHERE ido = $ido AND id_serialu = :id_serialu";
	$sth1 = $this->db->prepare($sql1);
	$sth1->execute(Array(':id_serialu' => $id_serialu));
	if($sth1->rowCount()){
	    return 'uz tam je';
	    //exit();
	}
	else{
	    $sql2 = "INSERT INTO startovni_cisla (ido,startovni_cislo,id_serialu) VALUES ('$ido','{$_GET['race_number']}','$id_serialu')";
	    $sth2 = $this->db->prepare($sql2);
	    $sth2->execute();
	    if($sth2->rowCount()){
		echo 'Startovní číslo vloženo do centrální DB';
	    }
	    else{
		echo 'Nějaký problém s vložením startovního čísla do centrální DB - '.$sql2;
	    }
	}

	
    }
    
    
    
    
    private function OsobaInsert(){
	$vlozeni['ido'] = $this->VolneIdo();
	($_GET['jmeno_1'] == true) ? ($vlozeni['jmeno'] = $_GET['jmeno_1']) : ('');
	($_GET['jmeno_1'] == true) ? ($vlozeni['jmeno_bez_diakritiky'] =  $this->RedukceDiakritiky($_GET['jmeno_1'])) : ('');
	($_GET['prijmeni_1'] == true) ? ($vlozeni['prijmeni'] = $_GET['prijmeni_1']) : ('');
	($_GET['prijmeni_1'] == true) ? ($vlozeni['prijmeni_bez_diakritiky'] =  $this->RedukceDiakritiky($_GET['prijmeni_1'])) : ('');
	($_GET['pohlavi'] == true) ? ($vlozeni['pohlavi'] = $_GET['pohlavi']) : ('');
	($_GET['den_narozeni'] == true) ? ($vlozeni['den'] = $_GET['den_narozeni']) : ('');
	($_GET['mesic_narozeni'] == true) ? ($vlozeni['mesic'] = $_GET['mesic_narozeni']) : ('');
	($_GET['rok_narozeni'] == true) ? ($vlozeni['rocnik'] = $_GET['rok_narozeni']) : ('');
	($_GET['ulice'] == true) ? ($vlozeni['ulice'] = $_GET['ulice']) : ('');
	($_GET['obec'] == true) ? ($vlozeni['obec'] = $_GET['obec']) : ('');
	($_GET['zip'] == true) ? ($vlozeni['zip'] = $_GET['zip']) : ('');
	($_GET['stat'] == true) ? ($vlozeni['psc'] = $_GET['stat']) : ('');
	($_GET['mail'] == true) ? ($vlozeni['mail'] = $_GET['mail']) : ('');
	($_GET['telefon'] == true) ? ($vlozeni['telefon'] = $_GET['telefon']) : ('');
	$sql1 = "INSERT INTO osoby (".implode(",",array_keys($vlozeni)).",datum_vlozeni) VALUES ('".implode("','",$vlozeni)."',NOW())";
	echo $sql1;
	$sth1 = $this->db->prepare($sql1);
	$sth1->execute();
	if($sth1->rowCount()){
	    return $this->db->lastInsertId();
	}
	else{
	    return false;
	}
    }
    

    private function xhrSaveToDBKorunaBeskyd(){
	$str = false;
	$vlozeni = Array();
        $pocet_zavodu = 1;
        if($this->udaje['poradi_podzavodu'] < 3){
            $pocet_zavodu = 2;
        }

        if($this->udaje['poradi_podzavodu'] < 4){
            $poradi_podzavodu = 1;
        }
	elseif($this->udaje['poradi_podzavodu'] == 4){
            $poradi_podzavodu = 2;
        }
        else{
            $poradi_podzavodu = $this->udaje['poradi_podzavodu'];
        }
        
    

	$vlozeni['id_zavodu'] = $this->IdZavodu;
	(isset($this->vychozi_startovne)) ? ($vlozeni['startovne'] = $this->vychozi_startovne) : ('');
	//(isset($this->mena)) ? ($vlozeni['mena'] = $this->mena) : ('');
	$vlozeni['mena'] = $this->mena;
	(isset($this->udaje['ids'])) ? ($vlozeni['ids'] = $this->udaje['ids']) : ('');
	(isset($this->udaje['jmeno_1'])) ? ($vlozeni['jmeno_1'] = $this->udaje['jmeno_1']) : ('');
	(isset($this->udaje['prijmeni_1'])) ? ($vlozeni['prijmeni_1'] = $this->udaje['prijmeni_1']) : ('');
	(isset($this->udaje['prislusnost'])) ? ($vlozeni['prislusnost'] = $this->udaje['prislusnost']) : ('');
	(isset($this->udaje['skola'])) ? ($vlozeni['skola'] = $this->udaje['skola']) : ('');
	(isset($this->udaje['pohlavi'])) ? ($vlozeni['pohlavi'] = $this->udaje['pohlavi']) : ('');
	(isset($this->udaje['stat'])) ? ($vlozeni['stat'] = $this->udaje['stat']) : ('');
	if(isset($this->udaje['den_narozeni']) && isset($this->udaje['mesic_narozeni']) && isset($this->udaje['rok_narozeni'])){
	    $vlozeni['datum_narozeni'] = $this->udaje['rok_narozeni'].'-'.$this->udaje['mesic_narozeni'].'-'.$this->udaje['den_narozeni'];
	}
	(isset($this->udaje['mail'])) ? ($vlozeni['mail'] = $this->udaje['mail']) : ('');
	(isset($this->udaje['telefon_1'])) ? ($vlozeni['telefon_1'] = $this->udaje['telefon_1']) : ('');	
	(isset($this->udaje['telefon_2'])) ? ($vlozeni['telefon_2'] = $this->udaje['telefon_2']) : ('');
        (isset($this->id_kategorie)) ? ($vlozeni['id_kategorie'] = $this->id_kategorie) : ('');
	(isset($this->udaje['tricko'])) ? ($vlozeni['tricko'] = $this->udaje['tricko']) : ('');	
	(isset($this->udaje['ponozky'])) ? ($vlozeni['ponozky'] = $this->udaje['ponozky']) : ('');	
	(isset($this->udaje['vzkaz'])) ? ($vlozeni['vzkaz'] = $this->udaje['vzkaz']) : ('');
	(isset($this->startovne)) ? ($vlozeni['startovne'] = $this->startovne) : ('');
	(isset($this->udaje['kategorie_2'])) ? ($vlozeni['kategorie_2'] = $this->udaje['kategorie_2']) : ('');
	(isset($this->udaje['jidlo'])) ? ($vlozeni['jidlo'] = $this->udaje['jidlo']) : ('');
	(isset($this->udaje['choice_1'])) ? ($vlozeni['choice_1'] = $this->udaje['choice_1']) : ('');
	(isset($this->udaje['choice_2'])) ? ($vlozeni['choice_2'] = $this->udaje['choice_2']) : ('');
	(isset($this->udaje['zaplaceno'])) ? ($vlozeni['zaplaceno'] = $this->udaje['zaplaceno']) : ('');
	(isset($this->udaje['vzkaz'])) ? ($vlozeni['vzkaz'] = $this->udaje['vzkaz']) : ('');
	(isset($this->udaje['dalsi_udaje_1'])) ? ($vlozeni['dalsi_udaje_1'] = $this->udaje['dalsi_udaje_1']) : ('');	
	(isset($this->udaje['dalsi_udaje_2'])) ? ($vlozeni['dalsi_udaje_2'] = $this->udaje['dalsi_udaje_2']) : ('');
	(isset($this->udaje['dalsi_udaje_3'])) ? ($vlozeni['dalsi_udaje_3'] = $this->udaje['dalsi_udaje_3']) : ('');
	(isset($this->udaje['dalsi_udaje_4'])) ? ($vlozeni['dalsi_udaje_4'] = $this->udaje['dalsi_udaje_4']) : ('');
	(isset($this->udaje['dalsi_udaje_5'])) ? ($vlozeni['dalsi_udaje_5'] = $this->udaje['dalsi_udaje_5']) : ('');
        
        for($i = 1;$i <= $pocet_zavodu;$i++){
            $vlozeni['poradi_podzavodu'] = $poradi_podzavodu * $i;
            $sql1 = "INSERT INTO prihlasky_{$this->typ_prihlasky}_$this->RokZavodu (".implode(",",array_keys($vlozeni)).",datum_prihlaseni) VALUES ('".implode("','",$vlozeni)."',NOW())";
            $sth1 = $this->db->prepare($sql1);
            if($sth1->execute()){
                $id_prihlasky = $this->db->lastInsertId();
                $sql2 = "INSERT INTO vs_{$this->RokZavodu} (id_zavodu,vs,id_prihlasky,typ_prihlasky) VALUES ('$this->IdZavodu','$this->vs','$id_prihlasky','{$this->udaje['typ_prihlasky']}')";
                //echo $sql2;
                $sth2 = $this->db->prepare($sql2);
                $sth2->execute();  
            }

        }
        $this->MailZavodnikovi('');
        unset($_POST);
    }

    private function xhrSaveToDBJednotlivci(){
	$str = false;
	$vlozeni = Array();
	$vlozeni['id_zavodu'] = $this->IdZavodu;
	(isset($this->vychozi_startovne)) ? ($vlozeni['startovne'] = $this->vychozi_startovne) : ('');
	//(isset($this->mena)) ? ($vlozeni['mena'] = $this->mena) : ('');
	$vlozeni['mena'] = $this->mena;
	(isset($this->udaje['ids'])) ? ($vlozeni['ids'] = $this->udaje['ids']) : ('');
	(isset($this->udaje['jmeno_1'])) ? ($vlozeni['jmeno_1'] = $this->udaje['jmeno_1']) : ('');
	(isset($this->udaje['prijmeni_1'])) ? ($vlozeni['prijmeni_1'] = $this->udaje['prijmeni_1']) : ('');
	(isset($this->udaje['prislusnost'])) ? ($vlozeni['prislusnost'] = $this->udaje['prislusnost']) : ('');
	(isset($this->udaje['skola'])) ? ($vlozeni['skola'] = $this->udaje['skola']) : ('');
	(isset($this->udaje['pohlavi'])) ? ($vlozeni['pohlavi'] = $this->udaje['pohlavi']) : ('');
	(isset($this->udaje['stat'])) ? ($vlozeni['stat'] = $this->udaje['stat']) : ('');
	if(isset($this->udaje['den_narozeni']) && isset($this->udaje['mesic_narozeni']) && isset($this->udaje['rok_narozeni'])){
	    $vlozeni['datum_narozeni'] = $this->udaje['rok_narozeni'].'-'.$this->udaje['mesic_narozeni'].'-'.$this->udaje['den_narozeni'];
	}
        else{
            $vlozeni['datum_narozeni'] = $this->udaje['rok_narozeni'].'-00-00';
        }
	(isset($this->udaje['mail'])) ? ($vlozeni['mail'] = $this->udaje['mail']) : ('');
	(isset($this->udaje['telefon_1'])) ? ($vlozeni['telefon_1'] = $this->udaje['telefon_1']) : ('');	
	(isset($this->udaje['telefon_2'])) ? ($vlozeni['telefon_2'] = $this->udaje['telefon_2']) : ('');
	
	
	//pokud je dáno poradi podzavodu staticky formuarem, tak se aplikuje první varanta, pokud ne, tak poradí podzávodu se vybírá dynamicky v kategoriích (prajzák) a pozužívá se druhá varanta
	(isset($this->udaje['poradi_podzavodu'])) ? ($vlozeni['poradi_podzavodu'] = $this->udaje['poradi_podzavodu']) : ('');
	
        
        if($this->IdZavodu != 27 && $this->RokZavodu == 2020){ //hybrid BBL 2018
            if($this->poradi_podzavodu){
                $vlozeni['poradi_podzavodu'] = $this->poradi_podzavodu;
            }
        }
	
	(isset($this->id_kategorie)) ? ($vlozeni['id_kategorie'] = $this->id_kategorie) : ('');
	(isset($this->udaje['tricko'])) ? ($vlozeni['tricko'] = $this->udaje['tricko']) : ('');	
	(isset($this->udaje['ponozky'])) ? ($vlozeni['ponozky'] = $this->udaje['ponozky']) : ('');	
	(isset($this->udaje['vzkaz'])) ? ($vlozeni['vzkaz'] = $this->udaje['vzkaz']) : ('');
	(isset($this->startovne)) ? ($vlozeni['startovne'] = $this->startovne) : ('');
	(isset($this->udaje['kategorie_2'])) ? ($vlozeni['kategorie_2'] = $this->udaje['kategorie_2']) : ('');
	(isset($this->udaje['jidlo'])) ? ($vlozeni['jidlo'] = $this->udaje['jidlo']) : ('');
	(isset($this->udaje['choice_1'])) ? ($vlozeni['choice_1'] = $this->udaje['choice_1']) : ('');
	(isset($this->udaje['choice_2'])) ? ($vlozeni['choice_2'] = $this->udaje['choice_2']) : ('');
	(isset($this->udaje['zaplaceno'])) ? ($vlozeni['zaplaceno'] = $this->udaje['zaplaceno']) : ('');
	(isset($this->udaje['vzkaz'])) ? ($vlozeni['vzkaz'] = $this->udaje['vzkaz']) : ('');
	(isset($this->udaje['dalsi_udaje_1'])) ? ($vlozeni['dalsi_udaje_1'] = $this->udaje['dalsi_udaje_1']) : ('');	
	(isset($this->udaje['dalsi_udaje_2'])) ? ($vlozeni['dalsi_udaje_2'] = $this->udaje['dalsi_udaje_2']) : ('');
	(isset($this->udaje['dalsi_udaje_3'])) ? ($vlozeni['dalsi_udaje_3'] = $this->udaje['dalsi_udaje_3']) : ('');
	(isset($this->udaje['dalsi_udaje_4'])) ? ($vlozeni['dalsi_udaje_4'] = $this->udaje['dalsi_udaje_4']) : ('');
	(isset($this->udaje['dalsi_udaje_5'])) ? ($vlozeni['dalsi_udaje_5'] = $this->udaje['dalsi_udaje_5']) : ('');

	$sql1 = "INSERT INTO prihlasky_{$this->typ_prihlasky}_$this->RokZavodu (".implode(",",array_keys($vlozeni)).",datum_prihlaseni) VALUES ('".implode("','",$vlozeni)."',NOW())";
	//echo $sql1;
	$sth1 = $this->db->prepare($sql1);
	if($sth1->execute()){
	    $id_prihlasky = $this->db->lastInsertId();
	    $sql2 = "INSERT INTO vs_{$this->RokZavodu} (id_zavodu,vs,id_prihlasky,typ_prihlasky) VALUES ('$this->IdZavodu','$this->vs','$id_prihlasky','{$this->udaje['typ_prihlasky']}')";
	    //echo $sql2;
	    $sth2 = $this->db->prepare($sql2);
	    if($sth2->execute()){
		$this->MailZavodnikovi('');
		unset($_POST);
	    }
	}
    }
    
    
    private function xhrSaveToDBTymy(){
	$str = false;
	$vlozeni = Array();
	$vlozeni['id_zavodu'] = $this->IdZavodu;
	(isset($this->udaje['nazev_tymu'])) ? ($vlozeni['nazev_tymu'] = $this->udaje['nazev_tymu']) : ('');
	(isset($this->udaje['stat_tym'])) ? ($vlozeni['stat'] = $this->udaje['stat_tym']) : ('');
	(isset($this->udaje['prislusnost'])) ? ($vlozeni['prislusnost'] = $this->udaje['prislusnost']) : ('');
	(isset($this->udaje['telefon_1_tym'])) ? ($vlozeni['telefon_1'] = $this->udaje['telefon_1_tym']) : ('');	
	(isset($this->udaje['telefon_2_tym'])) ? ($vlozeni['telefon_2'] = $this->udaje['telefon_2_tym']) : ('');
	(isset($this->udaje['mail_tym'])) ? ($vlozeni['mail'] = $this->udaje['mail_tym']) : ('');
	(isset($this->udaje['telefon_tym'])) ? ($vlozeni['telefon'] = $this->udaje['telefon_tym']) : ('');
	(isset($this->udaje['poradi_podzavodu'])) ? ($vlozeni['poradi_podzavodu'] = $this->udaje['poradi_podzavodu']) : ('');
	if(isset($this->udaje['id_kategorie'])){
	    $vlozeni['id_kategorie'] = $this->udaje['id_kategorie'];
	}
	elseif (isset($this->id_kategorie)) {
	    $vlozeni['id_kategorie'] = $this->id_kategorie;
	}
        
	(isset($this->udaje['zaplaceno'])) ? ($vlozeni['zaplaceno'] = $this->udaje['zaplaceno']) : ('');
	//(isset($this->startovne)) ? ($vlozeni['startovne'] = $this->startovne) : (''); //2015
	(isset($this->vychozi_startovne)) ? ($vlozeni['startovne'] = $this->vychozi_startovne) : (''); //2016
	(isset($this->mena)) ? ($vlozeni['mena'] = $this->mena) : ('');
	(isset($this->vs)) ? ($vlozeni['vs'] = $this->vs) : ('');
	(isset($this->udaje['vzkaz'])) ? ($vlozeni['vzkaz'] = $this->udaje['vzkaz']) : ('');
	(isset($this->udaje['dalsi_udaje_1'])) ? ($vlozeni['dalsi_udaje_1'] = $this->udaje['dalsi_udaje_1']) : ('');	
	(isset($this->udaje['dalsi_udaje_2'])) ? ($vlozeni['dalsi_udaje_2'] = $this->udaje['dalsi_udaje_2']) : ('');
	(isset($this->udaje['tym_dalsi_udaje_1'])) ? ($vlozeni['dalsi_udaje_1'] = $this->udaje['tym_dalsi_udaje_1']) : ('');	
	(isset($this->udaje['tym_dalsi_udaje_2'])) ? ($vlozeni['dalsi_udaje_2'] = $this->udaje['tym_dalsi_udaje_2']) : ('');
	(isset($this->udaje['tym_dalsi_udaje_3'])) ? ($vlozeni['dalsi_udaje_3'] = $this->udaje['tym_dalsi_udaje_3']) : ('');

	$sql1 = "INSERT INTO prihlasky_tymy_$this->RokZavodu (".implode(",",array_keys($vlozeni)).",datum_prihlaseni) VALUES ('".implode("','",$vlozeni)."',NOW())";
	$sth1 = $this->db->prepare($sql1);
	if($sth1->execute()){
	    $id_prihlasky = $this->db->lastInsertId(); //pro tabulku vs
	    $id_prihlasky_tymu = $id_prihlasky; //pro tabulku prihlasky_jednotlivci
	    $sql2 = "INSERT INTO vs_{$this->RokZavodu} (id_zavodu,vs,id_prihlasky,typ_prihlasky) VALUES ('$this->IdZavodu','$this->vs','$id_prihlasky','{$this->udaje['typ_prihlasky']}')";
	    $sth2 = $this->db->prepare($sql2);
	    if($sth2->execute()){
		for($i=1;$i<=$this->udaje['pocet_clenu'];$i++){
		    $vlozeni = Array();
		    $vlozeni['id_zavodu'] = $this->IdZavodu;
		    $vlozeni['id_prihlasky_tymu'] = $id_prihlasky_tymu;
		    (isset($this->udaje['jmeno_1_'.$i])) ? ($vlozeni['jmeno_1'] = $this->udaje['jmeno_1_'.$i]) : ('');
		    (isset($this->udaje['prijmeni_1_'.$i])) ? ($vlozeni['prijmeni_1'] = $this->udaje['prijmeni_1_'.$i]) : ('');
		    (isset($this->udaje['prislusnost_'.$i])) ? ($vlozeni['prislusnost'] = $this->udaje['prislusnost_'.$i]) : ('');
		    (isset($this->udaje['pohlavi_'.$i])) ? ($vlozeni['pohlavi'] = $this->udaje['pohlavi_'.$i]) : ('');
		    (isset($this->udaje['stat_'.$i])) ? ($vlozeni['stat'] = $this->udaje['stat_'.$i]) : ('');
		    if(isset($this->udaje['den_narozeni_'.$i]) && isset($this->udaje['mesic_narozeni_'.$i]) && isset($this->udaje['rok_narozeni_'.$i])){
			$vlozeni['datum_narozeni'] = $this->udaje['rok_narozeni_'.$i].'-'.$this->udaje['mesic_narozeni_'.$i].'-'.$this->udaje['den_narozeni_'.$i];
		    }
                    else{
			$vlozeni['datum_narozeni'] = $this->udaje['rok_narozeni_'.$i].'-00-00';
                    }
		    (isset($this->udaje['mail_'.$i])) ? ($vlozeni['mail'] = $this->udaje['mail_'.$i]) : ('');
		    (isset($this->udaje['telefon_1_'.$i])) ? ($vlozeni['telefon_1'] = $this->udaje['telefon_1_'.$i]) : ('');	
		    (isset($this->udaje['telefon_2_'.$i])) ? ($vlozeni['telefon_2'] = $this->udaje['telefon_2_'.$i]) : ('');
		    (isset($this->udaje['tricko_'.$i])) ? ($vlozeni['tricko'] = $this->udaje['tricko_'.$i]) : ('');	
		    (isset($this->udaje['ponozky_'.$i])) ? ($vlozeni['ponozky'] = $this->udaje['ponozky_'.$i]) : ('');	
		    (isset($this->udaje['dalsi_udaje_1_'.$i])) ? ($vlozeni['dalsi_udaje_1'] = $this->udaje['dalsi_udaje_1_'.$i]) : ('');	
		    (isset($this->udaje['dalsi_udaje_2_'.$i])) ? ($vlozeni['dalsi_udaje_2'] = $this->udaje['dalsi_udaje_2_'.$i]) : ('');
		    (isset($this->udaje['dalsi_udaje_3_'.$i])) ? ($vlozeni['dalsi_udaje_3'] = $this->udaje['dalsi_udaje_3_'.$i]) : ('');
                    
                    //kvuli winter hei run, aby se udaj o startu vlny dostal i ke clenum tymu
                    if(($this->IdZavodu == 48 && $this->RokZavodu == 2019) || ($this->IdZavodu == 101 && $this->RokZavodu == 2019) || ($this->IdZavodu == 68 && $this->RokZavodu == 2019) || ($this->IdZavodu == 65 && $this->RokZavodu == 2019)){
                        (isset($this->udaje['tym_dalsi_udaje_3'])) ? ($vlozeni['dalsi_udaje_1'] = $this->udaje['tym_dalsi_udaje_3']) : ('');
                    }

		    $sql1 = "INSERT INTO prihlasky_jednotlivci_$this->RokZavodu (".implode(",",array_keys($vlozeni)).",datum_prihlaseni) VALUES ('".implode("','",$vlozeni)."',NOW())";
		    //echo $sql1;
		    $sth1 = $this->db->prepare($sql1);
		    $sth1->execute();
		}
		$this->MailZavodnikovi('');
		unset($_POST);
	    }
	}
    }
    
    
    private function xhrSaveToDBTymyCtyrkolkySikl(){
	$str = false;
	$vlozeni = Array();
	$vlozeni['id_zavodu'] = $this->IdZavodu;
	(isset($this->udaje['nazev_tymu'])) ? ($vlozeni['nazev_tymu'] = $this->udaje['nazev_tymu']) : ('');
	(isset($this->udaje['stat_tym'])) ? ($vlozeni['stat'] = $this->udaje['stat_tym']) : ('');
	(isset($this->udaje['prislusnost'])) ? ($vlozeni['prislusnost'] = $this->udaje['prislusnost']) : ('');
	(isset($this->udaje['telefon_1_tym'])) ? ($vlozeni['telefon_1'] = $this->udaje['telefon_1_tym']) : ('');	
	(isset($this->udaje['telefon_2_tym'])) ? ($vlozeni['telefon_2'] = $this->udaje['telefon_2_tym']) : ('');
	(isset($this->udaje['mail_tym'])) ? ($vlozeni['mail'] = $this->udaje['mail_tym']) : ('');
	(isset($this->udaje['telefon_tym'])) ? ($vlozeni['telefon'] = $this->udaje['telefon_tym']) : ('');
	(isset($this->udaje['poradi_podzavodu'])) ? ($vlozeni['poradi_podzavodu'] = $this->udaje['poradi_podzavodu']) : ('');
	if(isset($this->udaje['id_kategorie'])){
	    $vlozeni['id_kategorie'] = $this->udaje['id_kategorie'];
	}
	elseif (isset($this->id_kategorie)) {
	    $vlozeni['id_kategorie'] = $this->id_kategorie;
	}
	(isset($this->udaje['zaplaceno'])) ? ($vlozeni['zaplaceno'] = $this->udaje['zaplaceno']) : ('');
	//(isset($this->startovne)) ? ($vlozeni['startovne'] = $this->startovne) : (''); //2015
	(isset($this->vychozi_startovne)) ? ($vlozeni['startovne'] = $this->vychozi_startovne) : (''); //2016
	(isset($this->mena)) ? ($vlozeni['mena'] = $this->mena) : ('');
	(isset($this->vs)) ? ($vlozeni['vs'] = $this->vs) : ('');
	(isset($this->udaje['vzkaz'])) ? ($vlozeni['vzkaz'] = $this->udaje['vzkaz']) : ('');
	(isset($this->udaje['dalsi_udaje_1'])) ? ($vlozeni['dalsi_udaje_1'] = $this->udaje['dalsi_udaje_1']) : ('');	
	(isset($this->udaje['dalsi_udaje_2'])) ? ($vlozeni['dalsi_udaje_2'] = $this->udaje['dalsi_udaje_2']) : ('');

	$sql1 = "INSERT INTO prihlasky_tymy_$this->RokZavodu (".implode(",",array_keys($vlozeni)).",datum_prihlaseni) VALUES ('".implode("','",$vlozeni)."',NOW())";
	$sth1 = $this->db->prepare($sql1);
	if($sth1->execute()){
	    $id_prihlasky = $this->db->lastInsertId(); //pro tabulku vs
	    $id_prihlasky_tymu = $id_prihlasky; //pro tabulku prihlasky_jednotlivci
	    $sql2 = "INSERT INTO vs_{$this->RokZavodu} (id_zavodu,vs,id_prihlasky,typ_prihlasky) VALUES ('$this->IdZavodu','$this->vs','$id_prihlasky','{$this->udaje['typ_prihlasky']}')";
	    $sth2 = $this->db->prepare($sql2);
	    if($sth2->execute()){
		for($i=1;$i<=$this->udaje['pocet_clenu'];$i++){
		    $vlozeni = Array();
		    $vlozeni['id_zavodu'] = $this->IdZavodu;
		    $vlozeni['id_prihlasky_tymu'] = $id_prihlasky_tymu;
		    (isset($this->udaje['jmeno_1_'.$i])) ? ($vlozeni['jmeno_1'] = $this->udaje['jmeno_1_'.$i]) : ('');
		    (isset($this->udaje['prijmeni_1_'.$i])) ? ($vlozeni['prijmeni_1'] = $this->udaje['prijmeni_1_'.$i]) : ('');
		    (isset($this->udaje['prislusnost_'.$i])) ? ($vlozeni['prislusnost'] = $this->udaje['prislusnost_'.$i]) : ('');
		    (isset($this->udaje['pohlavi_'.$i])) ? ($vlozeni['pohlavi'] = $this->udaje['pohlavi_'.$i]) : ('');
		    (isset($this->udaje['stat_'.$i])) ? ($vlozeni['stat'] = $this->udaje['stat_'.$i]) : ('');
		    if(isset($this->udaje['den_narozeni_'.$i]) && isset($this->udaje['mesic_narozeni_'.$i]) && isset($this->udaje['rok_narozeni_'.$i])){
			$vlozeni['datum_narozeni'] = $this->udaje['rok_narozeni_'.$i].'-'.$this->udaje['mesic_narozeni_'.$i].'-'.$this->udaje['den_narozeni_'.$i];
		    }
		    (isset($this->udaje['mail_'.$i])) ? ($vlozeni['mail'] = $this->udaje['mail_'.$i]) : ('');
		    (isset($this->udaje['telefon_1_'.$i])) ? ($vlozeni['telefon_1'] = $this->udaje['telefon_1_'.$i]) : ('');	
		    (isset($this->udaje['telefon_2_'.$i])) ? ($vlozeni['telefon_2'] = $this->udaje['telefon_2_'.$i]) : ('');
		    (isset($this->udaje['tricko_'.$i])) ? ($vlozeni['tricko'] = $this->udaje['tricko_'.$i]) : ('');	
		    (isset($this->udaje['ponozky_'.$i])) ? ($vlozeni['ponozky'] = $this->udaje['ponozky_'.$i]) : ('');	
		    (isset($this->udaje['dalsi_udaje_1_'.$i])) ? ($vlozeni['dalsi_udaje_1'] = $this->udaje['dalsi_udaje_1_'.$i]) : ('');	
		    (isset($this->udaje['dalsi_udaje_2_'.$i])) ? ($vlozeni['dalsi_udaje_2'] = $this->udaje['dalsi_udaje_2_'.$i]) : ('');
		    (isset($this->udaje['dalsi_udaje_3_'.$i])) ? ($vlozeni['dalsi_udaje_3'] = $this->udaje['dalsi_udaje_3_'.$i]) : ('');

		    $sql1 = "INSERT INTO prihlasky_jednotlivci_$this->RokZavodu (".implode(",",array_keys($vlozeni)).",datum_prihlaseni) VALUES ('".implode("','",$vlozeni)."',NOW())";
		    //echo $sql1;
		    $sth1 = $this->db->prepare($sql1);
		    $sth1->execute();
                    $this->MailZavodnikovi($this->udaje['mail_'.$i]);

		}
		//unset($_POST);
	    }
	}
    }

    
    
    
    
    
    
    private function MailZavodnikoviEnduro(){
	    require "./libs/phpmailer/class.phpmailer.php";
		    $mail1 = new PHPMailer();
		    $mail1->IsSMTP();
		    $mail1->Host = SMTP;
                    $mail1->CharSet = 'UTF-8';
		    //$mail1->Host = "smtp.vodafonmail.cz";
		    $mail1->From = "info@timechip.cz";
		    $mail1->FromName = "TimeChip";
		    if($mail){
			$mail1->AddAddress($mail);
		    }
		    else{
			$mail1->AddAddress($this->mail_na_zavodnika);
		    }
		    $mail1->IsHTML(true);
		    $mail1->Subject = $this->NazevZavodu.' '.YEAR.", předběžná registrace";
		    $mail1->Body = false;
    }
    
    
    
    
    
    
    
    private function MailPoradateli(){
	if(!empty($this->udaje['vzkaz'])){
	    $sql1 = "SELECT mail FROM prihlasky_".YEAR." WHERE id_zavodu = '$this->IdZavodu'";
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute();
	    $dbdata1 = $sth1->fetchObject();
            $mail2 = new PHPMailer();
            $mail2->IsSMTP();
            $mail2->Host = SMTP;
            $mail2->CharSet = 'UTF-8';
            $mail2->From = $this->mail_na_zavodnika;
            $mail2->FromName = $this->udaje['jmeno_1'].' '.$this->udaje['prijmeni_1'];
            $mail2->AddAddress($dbdata1->mail);
            $mail2->IsHTML(true);
            $mail2->Subject = $this->NazevZavodu.' '.YEAR.", vzkaz pořadateli";
            $mail2->Body = "Od: {$this->udaje['jmeno_1']} {$this->udaje['prijmeni_1']}<br />"; 
            $mail2->Body .= "Text: {$this->udaje['vzkaz']}";
            if(!$mail2->Send()){
                exit;
            }

	}
    }
    
    
    private function MailPoradatelPrihlasenii(){
        if($this->RokZavodu == 2018 && $this->IdZavodu == 101){
            $sql1 = "SELECT mail FROM prihlasky_".YEAR." WHERE id_zavodu = '$this->IdZavodu'";
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute();
	    $dbdata1 = $sth1->fetchObject();
            $mail = new PHPMailer();
            $mail->IsSMTP();
            $mail->Host = SMTP;
            $mail->CharSet = 'UTF-8';
            $mail->From = "info@timechip.cz";
            $mail->FromName = $this->udaje['jmeno_1'].' '.$this->udaje['prijmeni_1'];
            $mail->AddAddress($dbdata1->mail);
            $mail->IsHTML(true);
            $mail->Subject = $this->NazevZavodu.' '.YEAR.", prihlaseni zavodnika";
            $formulace = ($this->udaje['pohlavi'] == 'M') ? ("Přihlášen účastnik") : ("Přihlášena účastnice");
            $mail->Body = "$formulace {$this->udaje['jmeno_1']} {$this->udaje['prijmeni_1']}<br>";
            $mail->Body .= "VS: $this->vs<br>";
            $mail->Body .= "<hr>";
            if(!$mail->Send()){
                echo "<p>Vznikl nejaky problem a zprava nebyla odeslana. Pokud muzete, kontaktujte nas prosim bud telefonicky na 776131313, nebo pomoci e-mailu na adresu <a href=\"mailto:info@timechip.cz\">info@timechip.cz</a>.</p>";
                exit;
            }
        }
    }
    
    
    
    
		
    
    public function MailZavodnikovi($mail){
        $mail1 = new PHPMailer();
        $mail1->IsSMTP();
        $mail1->Host = SMTP;
        $mail1->CharSet = 'UTF-8';
        $mail1->addReplyTo($this->mail_na_poradatele);
        $mail1->setFrom('info@timechip.cz', 'TimeChip');
        if($mail){
            $mail1->AddAddress($mail);
        }
        else{
            $mail1->AddAddress($this->mail_na_zavodnika);
        }
        $mail1->IsHTML(true);

        //tohle na podzim 2017 nejak blblo, kdy tam je diarkitika, neposilalo to prdemet, je treba si s tim pohrat


        $mail1->Subject = $this->NazevZavodu.", předběžná registrace";
       // $mail1->Subject = $this->NazevZavodu. ", predbezna registrace";

        $mail1->Body = "";
        $veta = "Pro potvrzení této registrace je třeba uhradit platbu nejpozději do 18. 8. 2020, a sice dle údajů uvedených níže. S ohledem na aktuální situaci nejspíše nebude možná registrace ani platba na místě v den závodu! V důsledku nařízení Vlády ČR je počet startujících v maratonu omezen na 400! Počet účastníků dětského závodu na 50!<br>";
        
        if($this->IdZavodu == 1){
            $mail1->Body .= 'Dobrý den, děkujeme za přihlášku k závodu '.$this->NazevZavodu.'.<br />';
            $mail1->Body .= '<hr />';
            $mail1->Body .= $veta;
            $mail1->Body .= 'Částka: '.$this->vychozi_startovne.' Kč<br />';
            $mail1->Body .= 'Variabilní symbol: '.$this->vs.'<br />';
           
            $mail1->Body .= 'Číslo účtu: '.$this->cislo_uctu.'<br />';
            $mail1->Body .= 'IBAN: '.$this->iban.'<br />';
            $mail1->Body .= 'SWIFT: '.$this->swift.'<br />';
            $mail1->Body .= '<hr />';	
            /*
            $mail1->Body .= 'Pro závodníky ze Slovenska';
            $mail1->Body .= 'Banka: Tatrabanka<br />';   
            $mail1->Body .= 'Číslo účtu: SKG7 1100 0000 0026 1918 7672<br />';
            $mail1->Body .= '<hr />';	*/		
            $mail1->Body .= $this->poradatel.'<br />';
            $mail1->Body .= 'E-mail: <a href="mailto:'.$this->mail_na_poradatele.'">'.$this->mail_na_poradatele.'</a><br />';
            $mail1->Body .= '<hr />';
        }
        
        elseif($this->IdZavodu == 27){//bbl
          if($this->udaje['poradi_podzavodu'] < 4){
              $mail1->Body .= 'Dobrý den, děkujeme za přihlášku k závodu '.$this->NazevZavodu.' '.YEAR.'.<br />';
              $mail1->Body .= '<hr />';
              $mail1->Body .= 'Vaše údaje jsou:<br />';
              $mail1->Body .= "Jméno a příjmení: {$this->udaje['jmeno_1']} {$this->udaje['prijmeni_1']}<br />"; 
              $mail1->Body .= "Oddíl nebo místo bydliště: {$this->udaje['prislusnost']}<br />";
              $mail1->Body .= "Rok narození: {$this->udaje['rok_narozeni']}<br />";
              $mail1->Body .= "Telefon: {$this->udaje['telefon_1']}<br />";
              $mail1->Body .= '<hr />';
              $mail1->Body .= $veta;
              $mail1->Body .= 'Číslo účtu: '.$this->cislo_uctu.'<br />';
              $mail1->Body .= 'Částka: '.$this->startovne_kc.' Kč<br />';
              $mail1->Body .= 'Variabilní symbol: '.$this->vs.'<br />';
              $mail1->Body .= '<hr />';
              $mail1->Body .= $this->poradatel.'<br />';
              $mail1->Body .= 'E-mail: <a href="mailto:'.$this->mail_na_poradatele.'">'.$this->mail_na_poradatele.'</a><br />';
              $mail1->Body .= ($this->telefon_na_poradatele == true) ? ('Tel: '.$this->telefon_na_poradatele) : ('');
              $mail1->Body .= '<hr />';

          }
        
        elseif($this->udaje['poradi_podzavodu'] < 8 AND $this->udaje['poradi_podzavodu'] > 3){
               
            $mail1->Body .= 'Dobrý den, děkujeme za přihlášku k závodu '.$this->NazevZavodu.' '.YEAR.'.<br />';
              $mail1->Body .= '<hr />';
              $mail1->Body .= 'Vaše údaje jsou:<br />';
              $mail1->Body .= "Jméno a příjmení: {$this->udaje['jmeno_1']} {$this->udaje['prijmeni_1']}<br />"; 
              $mail1->Body .= "Oddíl nebo místo bydliště: {$this->udaje['prislusnost']}<br />";
              $mail1->Body .= "Rok narození: {$this->udaje['rok_narozeni']}<br />";
              $mail1->Body .= "Telefon: {$this->udaje['telefon_1']}<br />";
              $mail1->Body .= '<hr />';
              $mail1->Body .= $veta;
              $mail1->Body .= 'Číslo účtu: '.$this->cislo_uctu.'<br />';
              $mail1->Body .= 'Částka: '.$this->startovne_kc.' Kč<br />';
              $mail1->Body .= 'Variabilní symbol: '.$this->vs.'<br />';
              $mail1->Body .= '<hr />';
              $mail1->Body .= $this->poradatel.'<br />';
              $mail1->Body .= 'E-mail: <a href="mailto:'.$this->mail_na_poradatele.'">'.$this->mail_na_poradatele.'</a><br />';
              $mail1->Body .= ($this->telefon_na_poradatele == true) ? ('Tel: '.$this->telefon_na_poradatele) : ('');
              $mail1->Body .= '<hr />';

            }
            
            
            elseif($this->udaje['poradi_podzavodu'] == 9){
              $mail1->Body .= 'Dobrý den, děkujeme za přihlášku k závodu '.$this->NazevZavodu.' '.YEAR.'.<br />';
              $mail1->Body .= '<hr />';
              $mail1->Body .= 'Vaše údaje jsou:<br />';
              $mail1->Body .= "Jméno a příjmení: {$this->udaje['jmeno_1']} {$this->udaje['prijmeni_1']}<br />"; 
              $mail1->Body .= "Oddíl nebo místo bydliště: {$this->udaje['prislusnost']}<br />";
              $mail1->Body .= "Rok narození: {$this->udaje['rok_narozeni']}<br />";
              $mail1->Body .= "Telefon: {$this->udaje['telefon_1']}<br />";
              $mail1->Body .= '<hr />';
              $mail1->Body .= $veta;
              $mail1->Body .= 'Číslo účtu: '.$this->cislo_uctu.'<br />';
              $mail1->Body .= 'Částka: '.$this->startovne_kc.' Kč<br />';
              $mail1->Body .= 'Variabilní symbol: '.$this->vs.'<br />';
              $mail1->Body .= '<hr />';
              $mail1->Body .= $this->poradatel.'<br />';
              $mail1->Body .= 'E-mail: <a href="mailto:'.$this->mail_na_poradatele.'">'.$this->mail_na_poradatele.'</a><br />';
              $mail1->Body .= ($this->telefon_na_poradatele == true) ? ('Tel: '.$this->telefon_na_poradatele) : ('');
              $mail1->Body .= '<hr />';

          }
        else{
              $mail1->Body .= 'Dobrý den, děkujeme za přihlášku k závodu '.$this->NazevZavodu.'.<br />';
              $mail1->Body .= '<hr />';
              $mail1->Body .= $veta;
              $mail1->Body .= 'Částka: '.$this->startovne_kc.' Kč<br />';
              $mail1->Body .= 'Variabilní symbol: '.$this->vs.'<br />';
              $mail1->Body .= 'Číslo účtu - '.$this->cislo_uctu.'<br />';
              $mail1->Body .= '<hr />';			
              $mail1->Body .= $this->poradatel.'<br />';
              $mail1->Body .= 'E-mail: <a href="mailto:'.$this->mail_na_poradatele.'">'.$this->mail_na_poradatele.'</a><br />';
              $mail1->Body .= '<hr />';

          }
        }
        elseif($this->IdZavodu == 9){//Beskydsky biatlon
            //echo 'hoj';
            $mail1->Body .= 'Dobrý den, děkujeme za přihlášku k závodu '.$this->NazevZavodu.'.<br />';
            $mail1->Body .= '<hr />';
            $mail1->Body .= 'Realizujte prosím platbu podle následujících údajů:<br />';
            $mail1->Body .= 'Částka: '.$this->startovne_kc.' Kč<br />';
            $mail1->Body .= 'Variabilní symbol: '.$this->vs.'<br />';
            $mail1->Body .= 'Číslo účtu - '.$this->cislo_uctu.'<br />';
            $mail1->Body .= '<hr />';			
            $mail1->Body .= $this->poradatel.'<br />';
            $mail1->Body .= 'E-mail: <a href="mailto:'.$this->mail_na_poradatele.'">'.$this->mail_na_poradatele.'</a><br />';
            $mail1->Body .= '<hr />';
		}
		

        elseif($this->IdZavodu == 13 || $this->IdZavodu == 19 || $this->IdZavodu == 39 || $this->IdZavodu == 46){//Cross Country Open
            $mail1->Body .= 'Dobrý den, děkujeme za přihlášku k závodu '.$this->NazevZavodu.' '.YEAR.'.<br />';
            $mail1->Body .= '<hr />';
            $mail1->Body .= 'Vaše údaje jsou:<br />';
            $mail1->Body .= "Startovní číslo: {$_GET['race_number']}<br />"; 
            $mail1->Body .= "Jméno a příjmení: {$_GET['jmeno']} {$_GET['prijmeni']}<br />"; 
            $mail1->Body .= "Datum narození: {$_GET['den_narozeni']}.{$_GET['mesic_narozeni']}.{$_GET['rocnik']}<br />";
            $mail1->Body .= "Telefon: {$_GET['telefon']}<br />";
            $mail1->Body .= '<hr />';
            $mail1->Body .= $this->poradatel.'<br />';
            $mail1->Body .= 'E-mail: <a href="mailto:'.$this->mail_na_poradatele.'">'.$this->mail_na_poradatele.'</a><br />';
            $mail1->Body .= '<hr />';
        }
        
        elseif($this->IdZavodu == 14 || $this->IdZavodu == 20 || $this->IdZavodu == 40 || $this->IdZavodu == 47 || $this->IdZavodu == 48){//Cross Country Hobby
            $mail1->Body .= 'Dobrý den, děkujeme za přihlášku k závodu '.$this->NazevZavodu.' '.YEAR.'.<br />';
            $mail1->Body .= '<hr />';
            $mail1->Body .= 'Vaše údaje jsou:<br />';
            $mail1->Body .= "Jméno a příjmení: {$this->udaje['jmeno_1']} {$this->udaje['prijmeni_1']}<br />"; 
            $mail1->Body .= "Rok narození: {$this->udaje['rok_narozeni']}<br />";
            $mail1->Body .= '<hr />';
            $mail1->Body .= $this->poradatel.'<br />';
            $mail1->Body .= 'E-mail: <a href="mailto:'.$this->mail_na_poradatele.'">'.$this->mail_na_poradatele.'</a><br />';
            $mail1->Body .= '<hr />';
        }
        
        elseif($this->IdZavodu == 6){//belsky_okruh
            $mail1->Body .= 'Dobrý den, děkujeme za přihlášku k závodu '.$this->NazevZavodu.' '.YEAR.'.<br />';
            $mail1->Body .= '<hr />';
            $mail1->Body .= 'Vaše údaje jsou:<br />';
            $mail1->Body .= "Jméno a příjmení: {$this->udaje['jmeno_1']} {$this->udaje['prijmeni_1']}<br />"; 
            $mail1->Body .= "Oddíl nebo místo bydliště: {$this->udaje['prislusnost']}<br />";
            $mail1->Body .= "Rok narození:{$this->udaje['rok_narozeni']}<br />";
            $mail1->Body .= "Telefon: {$this->udaje['telefon_1']}<br />";
            $mail1->Body .= '<hr />';
            if($this->udaje['poradi_podzavodu'] == 1){
                $mail1->Body .= 'V případě, že budete chtít využít zvýhodněné startovné, je nutné do '.$this->konec_prihlasek.' realizovat platbu podle následujících údajů:<br />';
                $mail1->Body .= 'Číslo účtu: '.$this->cislo_uctu.'<br />';
                $mail1->Body .= 'Částka: '.$this->startovne_kc.' Kč / <span style="color:red">Mieszkańcy Polski placą 40 zł w dzień zawodów</span><br />';
                $mail1->Body .= 'Variabilní symbol: '.$this->vs.'<br />';
            }
            else{
                $mail1->Body .= 'Startovné na dětské závody je 50 Kč a platí se až v de závodu.<br />';
            }
            $mail1->Body .= '<hr />';
            $mail1->Body .= $this->poradatel.'<br />';
            $mail1->Body .= 'E-mail: <a href="mailto:'.$this->mail_na_poradatele.'">'.$this->mail_na_poradatele.'</a><br />';
            $mail1->Body .= ($this->telefon_na_poradatele == true) ? ('Tel: '.$this->telefon_na_poradatele) : ('');
            $mail1->Body .= '<hr />';
            $mail1->AddAttachment('./public/doc/zakladni-informace-belsky-okruh.pdf', $name = '', $encoding = 'base64', $type = 'application/octet-stream');
        }

        

        else{//default 
            //$mail1->Body .= 'Dobrý den, děkujeme za přihlášku k závodu '.$this->NazevZavodu.' '.YEAR.'.<br />';
            $mail1->Body .= "Dobrý den, děkujeme za přihlášku k závodu $this->NazevZavodu $this->RokZavodu<br />";
            $mail1->Body .= '<hr />';
            $mail1->Body .= 'Vaše údaje jsou:<br />';
            $mail1->Body .= "Jméno a příjmení: {$this->udaje['jmeno_1']} {$this->udaje['prijmeni_1']}<br />"; 
            $mail1->Body .= "Oddíl nebo místo bydliště: {$this->udaje['prislusnost']}<br />";
            $mail1->Body .= "Rok narození: {$this->udaje['rok_narozeni']}<br />";
            $mail1->Body .= "Telefon: {$this->udaje['telefon_1']}<br />";
            $mail1->Body .= '<hr />';
            $mail1->Body .= 'V případě, že budete chtít využít zvýhodněné startovné, je nutné do '.$this->konec_prihlasek.' realizovat platbu podle následujících údajů:<br />';
            $mail1->Body .= 'Číslo účtu: '.$this->cislo_uctu.'<br />';
            $mail1->Body .= 'Částka: '.$this->startovne_kc.' Kč<br />';
            $mail1->Body .= 'Variabilní symbol: '.$this->vs.'<br />';
            $mail1->Body .= '<hr />';
            $mail1->Body .= $this->poradatel.'<br />';
            $mail1->Body .= 'E-mail: <a href="mailto:'.$this->mail_na_poradatele.'">'.$this->mail_na_poradatele.'</a><br />';
            $mail1->Body .= ($this->telefon_na_poradatele == true) ? ('Tel: '.$this->telefon_na_poradatele) : ('');
            $mail1->Body .= '<hr />';
        }
        if(!$mail1->Send()){
            echo "<p>Vznikl nejaky problem a zprava nebyla odeslana. Pokud muzete, kontaktujte nas prosim bud telefonicky na 776131313, nebo pomoci e-mailu na adresu <a href=\"mailto:info@timechip.cz\">info@timechip.cz</a>.</p>";
            exit;
        }
        $this->MailPoradateli();
        $this->MailPoradatelPrihlasenii();
    }		

	
    
    
    
    
    
    
        private function VyberKategorie(){
	    isset($this->udaje['pohlavi']) ? $pohlavi = $this->udaje['pohlavi'] : ''; 
	    isset($this->udaje['pohlavi_1']) ? $pohlavi = $this->udaje['pohlavi_1'] : ''; 
            
            if($this->IdZavodu == 1){
                $this->kategorie = $this->udaje['id_kategorie'];
            }
            elseif($this->IdZavodu == 9){
                if($this->udaje['poradi_podzavodu'] == 1){
                    $this->kategorie = $this->udaje['id_kategorie'];
                }
                elseif($this->udaje['poradi_podzavodu'] == 2){
                    
                }
            }
            
            elseif($this->IdZavodu == 10){
                if($this->udaje['poradi_podzavodu'] == 3){
                    $this->VyberKategorieBezPoradiPodzavodu();
                }
                else{
                    $this->VyberKategorieDefault($this->udaje['poradi_podzavodu']);
                }
            }
            elseif($this->IdZavodu == 27){ //bbl
                if($this->udaje['poradi_podzavodu'] < 8){
                    $this->VyberKategorieDefault($this->udaje['poradi_podzavodu']);
                }
                elseif($this->udaje['poradi_podzavodu'] == 9){
                    $this->VyberKategorieDefault($this->udaje['poradi_podzavodu']);
                }
                else{
                    $this->VyberKategoriePodleId(463);
                }
            }

            
            
            
     


            else{
		$this->VyberKategorieDefault($this->udaje['poradi_podzavodu']);
	    }
	}
	
	private function VyberKategorieDefault($poradi_podzavodu){
	    isset($this->udaje['pohlavi']) ? $pohlavi = $this->udaje['pohlavi'] : ''; 
	   // $vek = date('Y') - $this->udaje['rok_narozeni'] + 1; //jednicka pripoctena jen do 31.12
	    $vek = date('Y') - $this->udaje['rok_narozeni']; 
	    $sql = "SELECT * FROM kategorie_{$this->RokZavodu} WHERE id_zavodu = '$this->IdZavodu' AND pohlavi = :pohlavi AND :vek BETWEEN vek_start AND vek_konec AND poradi_podzavodu = :poradi_podzavodu AND paralelni_kategorie = 0";
            $sth = $this->db->prepare($sql);
	    $sth->execute(Array(':pohlavi' => $pohlavi,':vek' => $vek,':poradi_podzavodu' => $poradi_podzavodu));
	    if($sth->rowCount()){ 
		$data1 =  $sth->fetchObject();
		$this->kategorie = $data1->nazev_k;
		$this->kod_kategorie = $data1->kod_k;
		$this->id_kategorie = $data1->id_kategorie;
	    }
            else{
                $this->VyberKategorieBezPohlavi($poradi_podzavodu);
            }

	}
        
        private function VyberKategorieParalelniKategorie($poradi_podzavodu){
	    isset($this->udaje['pohlavi']) ? $pohlavi = $this->udaje['pohlavi'] : ''; 
	    $vek = date('Y') - $this->udaje['rok_narozeni']; 
	    $sql = "SELECT * FROM kategorie_{$this->RokZavodu} WHERE id_zavodu = '$this->IdZavodu' AND pohlavi = '$pohlavi' AND '$vek' BETWEEN vek_start AND vek_konec AND poradi_podzavodu = '$poradi_podzavodu' AND paralelni_kategorie = 1";
            $sth = $this->db->prepare($sql);
	    $sth->execute();
	    if($sth->rowCount()){ 
		$data1 =  $sth->fetchObject();
		$this->kategorie = $data1->nazev_k;
		$this->kod_kategorie = $data1->kod_k;
		$this->id_kategorie = $data1->id_kategorie;
                $this->poradi_podzavodu = $data1->poradi_podzavodu; //toto se ber z db pouze v tomto případě, kdy pořadí podzávodu neputuje s formuářem
	    }
            else{
                $this->VyberKategorieParalelniKategorieBezPohlavi($poradi_podzavodu);
            }
	}

	private function VyberKategoriePodleKonkretnihoVeku($poradi_podzavodu,$vek){
	    isset($this->udaje['pohlavi']) ? $pohlavi = $this->udaje['pohlavi'] : ''; 
	    $sql = "SELECT * FROM kategorie_{$this->RokZavodu} WHERE id_zavodu = '$this->IdZavodu' AND pohlavi = '$pohlavi' AND '$vek' BETWEEN vek_start AND vek_konec AND poradi_podzavodu = '$poradi_podzavodu'";
	    //echo $sql;
	    $sth = $this->db->prepare($sql);
	    $sth->execute();
	    if($sth->rowCount()){ 
		$data1 =  $sth->fetchObject();
		$this->kategorie = $data1->nazev_k;
		$this->kod_kategorie = $data1->kod_k;
		$this->id_kategorie = $data1->id_kategorie;
	    }
	}
	
	private function VyberKategoriePodlePoradiPodzavodu($poradi_podzavodu){
	    //isset($this->udaje['pohlavi_1']) ? $pohlavi = $this->udaje['pohlavi_1'] : ''; 
	    //$vek = date('Y') - $this->udaje['rok_narozeni_1'];
	    $sql = "SELECT * FROM kategorie_{$this->RokZavodu} WHERE id_zavodu = '$this->IdZavodu' AND poradi_podzavodu = '$poradi_podzavodu' AND paralelni_kategorie = 0";
	    //echo $sql;
	    $sth = $this->db->prepare($sql);
	    $sth->execute();
	    if($sth->rowCount()){
		$data1 =  $sth->fetchObject();
		$this->kategorie = $data1->nazev_k;
		$this->kod_kategorie = $data1->kod_k;
		$this->id_kategorie = $data1->id_kategorie;
	    }
	}   
	
	private function VyberKategoriePodleNazvu($poradi_podzavodu,$kod_kategorie){
	    isset($this->udaje['pohlavi_1']) ? $pohlavi = $this->udaje['pohlavi_1'] : ''; 
	    $vek = date('Y') - $this->udaje['rok_narozeni_1'];
	    $sql = "SELECT * FROM kategorie_{$this->RokZavodu} WHERE id_zavodu = '$this->IdZavodu' AND kod_k LIKE '$kod_kategorie' AND poradi_podzavodu = '$poradi_podzavodu'";
	    //echo $sql;
	    $sth = $this->db->prepare($sql);
	    $sth->execute();
	    if($sth->rowCount()){
		$data1 =  $sth->fetchObject();
		$this->kategorie = $data1->nazev_k;
		$this->kod_kategorie = $data1->kod_k;
		$this->id_kategorie = $data1->id_kategorie;
	    }
	}   
	private function VyberKategorieBezPohlavi($poradi_podzavodu){
	    isset($this->udaje['pohlavi_1']) ? $pohlavi = $this->udaje['pohlavi_1'] : ''; 
	    $vek = date('Y') - $this->udaje['rok_narozeni'];
	    $sql = "SELECT * FROM kategorie_{$this->RokZavodu} WHERE id_zavodu = '$this->IdZavodu' AND '$vek' BETWEEN vek_start AND vek_konec AND poradi_podzavodu = '$poradi_podzavodu'";
	    //echo $sql;
	    $sth = $this->db->prepare($sql);
	    $sth->execute();
	    if($sth->rowCount()){
		$data1 =  $sth->fetchObject();
		$this->kategorie = $data1->nazev_k;
		$this->kod_kategorie = $data1->kod_k;
		$this->id_kategorie = $data1->id_kategorie;
	    }

        }
        private function VyberKategorieParalelniKategorieBezPohlavi($poradi_podzavodu){
	    $vek = date('Y') - $this->udaje['rok_narozeni'];
	    $sql = "SELECT * FROM kategorie_{$this->RokZavodu} WHERE id_zavodu = '$this->IdZavodu' AND '$vek' BETWEEN vek_start AND vek_konec AND poradi_podzavodu = '$poradi_podzavodu' AND paralelni_kategorie = 1";
	    //echo $sql;
	    $sth = $this->db->prepare($sql);
	    $sth->execute();
	    if($sth->rowCount()){
		$data1 =  $sth->fetchObject();
		$this->kategorie = $data1->nazev_k;
		$this->kod_kategorie = $data1->kod_k;
		$this->id_kategorie = $data1->id_kategorie;
	    }

        }
	
	private function VyberKategorieBezPoradiPodzavodu(){
	    isset($this->udaje['pohlavi']) ? $pohlavi = $this->udaje['pohlavi'] : ''; 
	    $vek = date('Y') - $this->udaje['rok_narozeni'];
	    $sql = "SELECT * FROM kategorie_{$this->RokZavodu} WHERE id_zavodu = '$this->IdZavodu' AND pohlavi = '$pohlavi' AND '$vek' BETWEEN vek_start AND vek_konec AND paralelni_kategorie = 0";
	    //echo $sql;
	    $sth = $this->db->prepare($sql);
	    $sth->execute();
	    if($sth->rowCount()){
		$data1 =  $sth->fetchObject();
		$this->kategorie = $data1->nazev_k;
		$this->kod_kategorie = $data1->kod_k;
		$this->id_kategorie = $data1->id_kategorie;
		$this->poradi_podzavodu = $data1->poradi_podzavodu; //toto se ber z db pouze v tomto případě, kdy pořadí podzávodu neputuje s formuářem
	    }
	}
	
        private function VyberKategorieKorunaBeskyd(){
            //stejne jako VyberKategorieBezPoradiPodzavodu, jen tady se bere pro potřeby zápisu do databáte pořadí podzávodu přímo z formuláře a na jako návratová hodnota z DB
	    isset($this->udaje['pohlavi']) ? $pohlavi = $this->udaje['pohlavi'] : ''; 
	    $vek = date('Y') - $this->udaje['rok_narozeni'];
	    $sql = "SELECT * FROM kategorie_{$this->RokZavodu} WHERE id_zavodu = '$this->IdZavodu' AND pohlavi = '$pohlavi' AND '$vek' BETWEEN vek_start AND vek_konec AND paralelni_kategorie = 0";
	    //echo $sql;
	    $sth = $this->db->prepare($sql);
	    $sth->execute();
	    if($sth->rowCount()){
		$data1 =  $sth->fetchObject();
		$this->kategorie = $data1->nazev_k;
		$this->kod_kategorie = $data1->kod_k;
		$this->id_kategorie = $data1->id_kategorie;
	    }
	}
	
	
	
	private function VyberKategoriePodleId($id_kategorie){
            
    	    $sql1 = "SELECT nazev_k,kod_k FROM kategorie_{$this->RokZavodu} WHERE id_kategorie = $id_kategorie";
            //echo $sql1;
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute();
	    if($sth1->rowCount()){
		$dbdata1 = $sth1->fetchObject();
		$this->kategorie = $dbdata1->nazev_k;
		$this->kod_kategorie = $dbdata1->kod_k;
		$this->id_kategorie = $id_kategorie;
                return $dbdata1->nazev_k;
	    }
	}
	
	
	
	private function Vek(){
	    if(isset($this->udaje['rok_narozeni'])){
		$this->vek =  date('Y') - $this->udaje['rok_narozeni'];
	    }
	}
	
	private function StartovneZDB(){
	    $sql1 = "SELECT * FROM prihlasky_startovne_".YEAR." WHERE prihlasky_startovne_".YEAR.".id_zavodu = $this->IdZavodu";
	   // echo $sql1;
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute();
	    if($sth1->rowCount()){
		while($dbdata1 = $sth1->fetchObject()){
                    $this->cena_tricka_z_db = $dbdata1->tricko;
		    if($dbdata1->id_meny == 1){
			$this->startovne_z_db_kc = $dbdata1->castka;
			if($dbdata1->vychozi_startovne){
			    $this->vychozi_startovne = $this->startovne_z_db_kc;
			}
		    }
		    elseif($dbdata1->id_meny == 2){
			$this->startovne_z_db_eu = $dbdata1->castka;
			if($dbdata1->vychozi_startovne){
			    $this->vychozi_startovne = $this->startovne_z_db_eu;
			}

		    }
		}
	    }
	}
	
	private function Startovne(){
            if ($this->IdZavodu == 1) {
                $pocet_clenu = Session::get('pocet_clenu');
                //Session::get('pocet_clenu');
                $this->vychozi_startovne = $this->startovne_z_db_kc * $pocet_clenu;
                if($this->udaje['id_kategorie'] == 9){//ctyrkolky sikl
                    $this->vychozi_startovne = 2000;
                    $this->konec_prihlasek = '6.2.2020';
                }

                $this->startovne_kc = $this->vychozi_startovne;
            }
            
            elseif($this->IdZavodu == 10){ //bystrice alis run
                if($this->udaje['poradi_podzavodu'] > 2){
                    $this->vychozi_startovne = 100;
                }
                $this->startovne_kc = $this->vychozi_startovne;
            }
            
            elseif($this->IdZavodu == 22){ //hlucin
                if($this->udaje['poradi_podzavodu'] == 2){
                    $this->vychozi_startovne = 150;
                }
                $this->startovne_kc = $this->vychozi_startovne;
            }
            
            elseif($this->IdZavodu == 27){ //BBL
		
                if($this->udaje['poradi_podzavodu'] <= 3){
		    $vek =  date('Y') - $this->udaje['rok_narozeni'];
                    if($vek <= 18){
                        $this->vychozi_startovne = 300;
                    }
                    echo $this->udaje['tricko'];
                    if($this->udaje['tricko'] != "bez"){
                          echo "2";
			$this->vychozi_startovne = $this->vychozi_startovne + 150;
		    }
		}
		
                elseif($this->udaje['poradi_podzavodu'] > 3 AND $this->udaje['poradi_podzavodu'] < 8){
		    $this->vychozi_startovne = 100;
		    if($this->udaje['tricko'] != "bez"){
			$this->vychozi_startovne = $this->vychozi_startovne + 150;
		    }
		}
                elseif($this->udaje['poradi_podzavodu'] == 9){
		    $this->vychozi_startovne = 450;
		    if($this->udaje['tricko'] != "bez"){
			$this->vychozi_startovne = $this->vychozi_startovne + 150;
		    }
		}
                else{
                    $this->vychozi_startovne = 1000;
		    for($i=1;$i<=2;$i++){
			if($this->udaje['tricko_'.$i]){
			    $this->vychozi_startovne = $this->vychozi_startovne + 150;
			}
		    }
                }
                
                echo $this->vychozi_startovne;
                
		$this->startovne_kc = $this->vychozi_startovne;
	    }
            
            elseif($this->IdZavodu == 38){ //osecanska slapka
                 $vek =  date('Y') - $this->udaje['rok_narozeni'];
                if($this->udaje['poradi_podzavodu'] == 1){
                    if($vek > 10 AND $vek < 19){
                        $this->vychozi_startovne = 100;
                    }
                     if($this->udaje['tricko'] != 'bez'){
			$this->vychozi_startovne = $this->vychozi_startovne + 200;
		    }
                    
                }
                else{
                    if($vek > 0 AND $vek < 19){
                        $this->vychozi_startovne = 100;
                    }
                    if($this->udaje['tricko'] != 'bez'){
			$this->vychozi_startovne = $this->vychozi_startovne + 200;
		    }
                }
                
                
                
                $this->startovne_kc = $this->vychozi_startovne;
            }






            else{ //default
		$this->startovne_kc = $this->startovne_z_db_kc;
		$this->startovne_eu = $this->startovne_z_db_eu;
	    }
	}	

        private function ZiskaniVS(){
	    $sql = "SELECT MAX(vs) as posledni_vs FROM vs_{$this->RokZavodu}";
	    $sth = $this->db->prepare($sql);
	    $sth->execute();
	    $data = $sth->fetchObject();
	    if($data->posledni_vs > 0){
		$this->vs = $data->posledni_vs + 1;
	    }
	    else{
                $this->vs = $this->vychozi_vs;
	    }
	}
        
		





	private function VypisPrihlasekEnduro(){
	    $str = '';
	    $sql1 =  "SELECT * FROM etapy WHERE id_zavodu = :id_zavodu AND rok_zavodu = :rok_zavodu ORDER BY id_etapy";
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute(Array(':id_zavodu' => 45,':rok_zavodu' => 2015));// je to natvdro nastavene podle prvnich etap v tabulce etapy
	    if($sth1->rowCount()){
		while($dbdata1 = $sth1->fetchObject()){
		    $sql2 = "SELECT $this->sqlzavod.*,osoby.*,tymy.*,$this->sqlkategorie.*,$this->sqlpodzavody.*,znacky_motocyklu.nazev_motocyklu FROM $this->sqlzavod,osoby,tymy,$this->sqlpodzavody,$this->sqlkategorie,znacky_motocyklu WHERE "
			      . "$this->sqlzavod.id_etapy = $dbdata1->id_etapy AND "
			      . "$this->sqlzavod.ido = osoby.ido AND "
			      . "$this->sqlzavod.id_tymu = tymy.id_tymu AND "
			      . "$this->sqlpodzavody.id_zavodu = $this->IdZavodu AND "
			      . "$this->sqlzavod.id_kategorie = $this->sqlkategorie.id_kategorie AND "
			      . "$this->sqlzavod.poradi_podzavodu = $this->sqlpodzavody.poradi_podzavodu AND "
			      . "$this->sqlzavod.id_motocyklu = znacky_motocyklu.id_motocyklu "
			      . "ORDER BY $this->sqlzavod.ids ASC";
		    $sth2 = $this->db->prepare($sql2);
		    $sth2->execute();
		    if($sth2->rowCount()){
			$pocet_prihlasenych = $sth2->rowCount();
			$str .= '<h3>'.$dbdata1->nazev_etapy.' ('.$pocet_prihlasenych.' přihlášených)</h3>';
			$str .= '<table class="table table-hover table-condensed table-striped">';
			$str .= '<thead>';
			$str .= '<th class="text-center">#</th>';
			$str .= '<th>Příjmení a jméno</th>';
			$str .= '<th>Tým</th>';
			$str .= '<th class="text-center">Ročník</th>';
			$str .= '<th class="text-center">Stát</th>';
			$str .= '<th class="text-center">Třída</th>';
			$str .= '<th>Kategorie</th>';
			$str .= '<th>Motocykl</th>';
			$str .= '</thead>';
			while($dbdata2 = $sth2->fetchOBject()){
			    $str .= '<tr>';
			    $str .= '<td class="text-center">'.$dbdata2->ids.'</td>';
			    $str .= '<td>'.$dbdata2->prijmeni.' '.$dbdata2->jmeno.'</td>';
			    $str .= '<td>'.$dbdata2->nazev_tymu.'</td>';
			    $str .= '<td class="text-center">'.$dbdata2->rocnik.'</td>';
			    $str .= '<td class="text-center">'.$dbdata2->psc.'</td>';
			    $str .= '<td class="text-center">'.$dbdata2->nazev.'</td>';
			    $str .= '<td>'.$dbdata2->nazev_k.'</td>';
			    $str .= '<td>'.$dbdata2->nazev_motocyklu.'</td>';
			    $str .= '</tr>';

			}
			$str .= '</table><br />';
		    }
		}
	    }
	    return $str;
	}
	
	
	
	
	public function VypisPrihlasek(){
	    //$this->Prevod();
	    $str = false;
	    if($this->IdZavodu == 13 || $this->IdZavodu == 19 || $this->IdZavodu == 39 || $this->IdZavodu == 46){
		$str .= $this->VypisPrihlasekEnduro();
	    }
	    elseif($this->IdZavodu == 14 || $this->IdZavodu == 20 || $this->IdZavodu == 40 || $this->IdZavodu == 47 || $this->IdZavodu == 48){ //cc hobby
		$sql2 = "SELECT $this->sqlprihlaskyjednotlivci.*,DATE_FORMAT($this->sqlprihlaskyjednotlivci.datum_narozeni,'%Y') AS rocnik,$this->sqlkategorie.nazev_k AS nazev_kategorie,$this->sqlkategorie.id_kategorie FROM $this->sqlprihlaskyjednotlivci,$this->sqlkategorie WHERE $this->sqlprihlaskyjednotlivci.id_zavodu = :id_zavodu AND $this->sqlprihlaskyjednotlivci.poradi_podzavodu = :poradi_podzavodu AND $this->sqlprihlaskyjednotlivci.id_kategorie = $this->sqlkategorie.id_kategorie "
			  . "ORDER BY "
			  . "$this->sqlkategorie.poradi ASC,$this->sqlprihlaskyjednotlivci.prijmeni_1 ASC";
		//echo $sql2;
		$sth2 = $this->db->prepare($sql2);
		$sth2->execute(Array(':id_zavodu' => $this->IdZavodu,':poradi_podzavodu' => 1));
		if($sth2->rowCount()){
		    $k = 1;
                    $sql3 = "SELECT (SELECT COUNT(id_prihlasky) from $this->sqlprihlaskyjednotlivci where id_zavodu = :id_zavodu AND id_kategorie IS NOT NULL) AS celkem";
                    $sth3 = $this->db->prepare($sql3);
                    $sth3->execute(Array(':id_zavodu' => $this->IdZavodu));
                    if($sth3->rowCount()){
                        $dbdata3 = $sth3->fetchObject();
                        $str .= '<p class="text-right"><i style="font-size:12px">Přihlášeno '.$dbdata3->celkem.' závodníků</i></p>';
                    }
		    $str .= '<table class="table table-striped table-hover table-condensed">';
		    $str .= '<thead>';
		 //   $str .= '<th class="text-center">St.č</th>';
		    $str .= '<th>Příjmení a jméno</th>';
		    $str .= '<th class="text-center">Ročník</th>';
		    $str .= '<th class="text-center">Stát</th>';
		    $str .= '<th>Kategorie</th>';
		    $str .= '</thead>';
		    while($dbdata2 = $sth2->fetchObject()){
			$str .= '<tr>';
			//$str .= '<td class="text-center">'.$dbdata2->ids.'</td>';
			$str .= '<td>'.$dbdata2->prijmeni_1.' '.$dbdata2->jmeno_1.'</td>';
			$str .= '<td class="text-center">'.$dbdata2->rocnik.'</td>';
			$str .= '<td class="text-center">'.$dbdata2->stat.'</td>';
			$str .= '<td>'.$dbdata2->nazev_kategorie.'</td>';
			$str .= '</tr>';
			$k++;
		    }
		    $str .= '</table>';
		}

	    }
            elseif($this->IdZavodu == 11){ // hei runy, atd
                for($i=1;$i<=$this->pocet_podzavodu;$i++){
		    
                    $sql1 = "SELECT nazev AS nazev_podzavodu,typ_zavodnika FROM $this->sqlpodzavody WHERE id_zavodu = '$this->IdZavodu' AND poradi_podzavodu = :poradi_podzavodu";
		    
                    $sth1 = $this->db->prepare($sql1);
		    $sth1->execute(Array(':poradi_podzavodu' => $i));
		    if($sth1->rowCount()){
			$dbdata1 = $sth1->fetchObject();
			$racer_type = $dbdata1->typ_zavodnika;
                        
			if($racer_type == 1){
			    $sql2 = "SELECT $this->sqlprihlaskyjednotlivci.*,DATE_FORMAT($this->sqlprihlaskyjednotlivci.datum_narozeni,'%Y') AS rocnik,$this->sqlkategorie.nazev_k AS nazev_kategorie,$this->sqlkategorie.id_kategorie FROM $this->sqlprihlaskyjednotlivci,$this->sqlkategorie WHERE $this->sqlprihlaskyjednotlivci.id_zavodu = $this->IdZavodu AND $this->sqlprihlaskyjednotlivci.poradi_podzavodu = $i AND $this->sqlprihlaskyjednotlivci.id_kategorie = $this->sqlkategorie.id_kategorie "
				      . "ORDER BY "
				      . "$this->sqlprihlaskyjednotlivci.zaplaceno ASC,"
				      . "$this->sqlprihlaskyjednotlivci.vlna ASC,"
				      . "$this->sqlprihlaskyjednotlivci.prijmeni_1 ASC";
			    $sth2 = $this->db->prepare($sql2);
			    $sth2->execute(Array(':id_zavodu' => $this->IdZavodu,':poradi_podzavodu' => $i));
                            //echo $sql2."<br>";
			    if($sth2->rowCount()){
				$k = 1;
				if($this->pocet_podzavodu > 1){
				    $str .= '<h3>'.$dbdata1->nazev_podzavodu;
				    $sql3 = "SELECT "
					      . "(SELECT COUNT(id_prihlasky) from $this->sqlprihlaskyjednotlivci where id_zavodu = :id_zavodu and poradi_podzavodu = :i AND id_kategorie IS NOT NULL) AS celkem,"
					      . "(SELECT COUNT(id_prihlasky) from $this->sqlprihlaskyjednotlivci where id_zavodu = :id_zavodu and zaplaceno like 'zaplaceno' and poradi_podzavodu = :i AND id_kategorie IS NOT NULL) AS zaplaceno,"
					      . "(SELECT COUNT(id_prihlasky) from $this->sqlprihlaskyjednotlivci where id_zavodu = :id_zavodu and zaplaceno like 'nezaplaceno' and poradi_podzavodu = :i AND id_kategorie IS NOT NULL) AS nezaplaceno";
				    $sth3 = $this->db->prepare($sql3);
				    $sth3->execute(Array(':id_zavodu' => $this->IdZavodu,':i' => $i));
				    if($sth3->rowCount()){
					$dbdata3 = $sth3->fetchObject();
					$str .= '<br /><i style="font-size:12px">Přihlášeno - '.$dbdata3->celkem.', Zaplaceno - '.$dbdata3->zaplaceno.', Nezaplaceno - '.$dbdata3->nezaplaceno.'</i>';
				    }
				   $str .= '</h3>';
				}
				$str .= '<table class="table table-hover table-striped table-condensed" style="margin-bottom:70px">';
				$str .= '<thead>';
				//$str .= '<th class="text-center" style="width:4%">#</th>';
				$str .= '<th style="width:24%">Příjmení a jméno</th>';
				$str .= '<th style="width:30%">Tým nebo bydliště</th>';
				$str .= '<th style="width:5%" class="text-center">Ročník</th>';
				$str .= '<th style="width:5%" class="text-center">Stát</th>';
				$str .= '<th style="width:16%" class="text-center">Kategorie</th>';
                                $str .= '<th  style="width:8%" class="text-center">Startovní čas</th>';
				$str .= '<th  style="width:8%" class="text-center">Startovné</th>';
				$str .= '</thead>';
				while($dbdata2 = $sth2->fetchObject()){
				    $str .= '<tr>';
				    //$str .= '<td class="text-center">'.$k.'</td>';
				    $str .= '<td>'.$dbdata2->prijmeni_1.' '.$dbdata2->jmeno_1.'</td>';
				    $str .= '<td>'.$dbdata2->prislusnost.'</td>';
				    $str .= '<td class="text-center">'.$dbdata2->rocnik.'</td>';
				    $str .= '<td class="text-center">'.$dbdata2->stat.'</td>';
				    $str .= '<td class="text-center">'.$dbdata2->nazev_kategorie.'</td>';
				    $str .= '<td class="text-center">'.$dbdata2->vlna.'</td>';
				    $str .= '<td class="text-center">'.$dbdata2->zaplaceno.'</td>';
				    $str .= '</tr>';
				    $k++;
				}
				$str .= '</table><br />';
			    }
			}
			elseif($racer_type == 2 || $racer_type == 3 || $racer_type == 6){
			    $sql1 = "SELECT $this->sqlprihlaskytymy.*,$this->sqlkategorie.nazev_k AS nazev_kategorie,$this->sqlkategorie.id_kategorie FROM $this->sqlprihlaskytymy,$this->sqlkategorie WHERE $this->sqlprihlaskytymy.id_zavodu = :id_zavodu AND $this->sqlprihlaskytymy.id_kategorie = $this->sqlkategorie.id_kategorie AND $this->sqlprihlaskytymy.poradi_podzavodu = :poradi_podzavodu ORDER BY zaplaceno ASC,nazev_tymu ASC,$this->sqlkategorie.id_kategorie ASC";
                            $sth1 = $this->db->prepare($sql1);
			    $sth1->execute(Array(':id_zavodu' => $this->IdZavodu ,':poradi_podzavodu' => $i));
			    if($sth1->rowCount()){
				if($this->pocet_podzavodu > 1){
				    $str .= '<h3>'.$dbdata1->nazev_podzavodu.'</h3>';
				}
				$str .= '<table class="table table-condensed">';
				$str .= '<thead>';
				$str .= '<th class="text-center">#</th>';
				$str .= '<th>Název týmu</th>';
				$str .= '<th>Kategorie</th>';
				$str .= '<th>Členové</th>';
				$str .= '<th class="text-center">Ročník</th>';
				$str .= '<th class="text-center">Stát</th>';
				$str .= '<th class="text-center">Startovní čas</th>';
				$str .= '<th class="text-center">Startovné</th>';
				$str .= '</thead>';
				$k = 1;
				while($dbdata1 = $sth1->fetchObject()){
				    $sql2 = "SELECT *,DATE_FORMAT(datum_narozeni,'%Y') AS rocnik FROM $this->sqlprihlaskyjednotlivci WHERE id_prihlasky_tymu = :id_prihlasky_tymu ORDER BY id_prihlasky ASC";
				    //echo $sql2."<br />";
				    $sth2 = $this->db->prepare($sql2);
				    $sth2->execute(Array(':id_prihlasky_tymu' => $dbdata1->id_prihlasky));
				    if($sth2->rowCount()){
					$pocet_clenu = $sth2->rowCount();
					$x = 1;
					while($dbdata2 = $sth2->fetchObject()){
					    if($x == 1){
						$str .= '<tr>';
						$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center rowspan">'.$k.'</td>';
						$str .= '<td rowspan="'.$pocet_clenu.'" class="rowspan">'.$dbdata1->nazev_tymu.'</td>';
						$str .= '<td rowspan="'.$pocet_clenu.'" class="rowspan">'.$dbdata1->nazev_kategorie.'</td>';
						$str .= '<td>'.$dbdata2->prijmeni_1.' '.$dbdata2->jmeno_1.'</td>';
						$str .= '<td class="text-center">'.$dbdata2->rocnik.'</td>';
						$str .= '<td class="text-center">'.$dbdata2->stat.'</td>';
						$str .= '<td rowspan="'.$pocet_clenu.'" class="rowspan text-center">'.$dbdata1->vlna.'</td>';
						$str .= '<td rowspan="'.$pocet_clenu.'" class="rowspan text-center">'.$dbdata1->zaplaceno.'</td>';
						$str .= '</tr>';

					    }
					    else{
						$str .= '<tr>';
						$str .= '<td>'.$dbdata2->prijmeni_1.' '.$dbdata2->jmeno_1.'</td>';
						$str .= '<td class="text-center">'.$dbdata2->rocnik.'</td>';
						$str .= '<td class="text-center">'.$dbdata2->stat.'</td>';
						$str .= '</tr>';
					    }
					
					$x++;    
					}
				    }
				    $k++;
				}

			    $str .= '</table><br /><br />';

			    }
			}
		    }
		}

            }
            
            else{ //default     
               
		for($i=1;$i<=$this->pocet_podzavodu;$i++){
		    
                    $sql1 = "SELECT nazev AS nazev_podzavodu,typ_zavodnika FROM $this->sqlpodzavody WHERE id_zavodu = '$this->IdZavodu' AND poradi_podzavodu = :poradi_podzavodu";
		    
                    $sth1 = $this->db->prepare($sql1);
		    $sth1->execute(Array(':poradi_podzavodu' => $i));
		    if($sth1->rowCount()){
			$dbdata1 = $sth1->fetchObject();
			$racer_type = $dbdata1->typ_zavodnika;
                        
			if($racer_type == 1){
			    $sql2 = "SELECT $this->sqlprihlaskyjednotlivci.*,DATE_FORMAT($this->sqlprihlaskyjednotlivci.datum_narozeni,'%Y') AS rocnik,$this->sqlkategorie.nazev_k AS nazev_kategorie,$this->sqlkategorie.id_kategorie FROM $this->sqlprihlaskyjednotlivci,$this->sqlkategorie WHERE $this->sqlprihlaskyjednotlivci.id_zavodu = $this->IdZavodu AND $this->sqlprihlaskyjednotlivci.poradi_podzavodu = $i AND $this->sqlprihlaskyjednotlivci.id_kategorie = $this->sqlkategorie.id_kategorie "
				      . "ORDER BY "
				      . "$this->sqlprihlaskyjednotlivci.zaplaceno ASC,"
				      . "$this->sqlprihlaskyjednotlivci.prijmeni_1 ASC";
			    $sth2 = $this->db->prepare($sql2);
			    $sth2->execute(Array(':id_zavodu' => $this->IdZavodu,':poradi_podzavodu' => $i));
                            //echo $sql2."<br>";
			    if($sth2->rowCount()){
				$k = 1;
				if($this->pocet_podzavodu > 1){
				    $str .= '<h3>'.$dbdata1->nazev_podzavodu;
				    $sql3 = "SELECT "
					      . "(SELECT COUNT(id_prihlasky) from $this->sqlprihlaskyjednotlivci where id_zavodu = :id_zavodu and poradi_podzavodu = :i AND id_kategorie IS NOT NULL) AS celkem,"
					      . "(SELECT COUNT(id_prihlasky) from $this->sqlprihlaskyjednotlivci where id_zavodu = :id_zavodu and zaplaceno like 'zaplaceno' and poradi_podzavodu = :i AND id_kategorie IS NOT NULL) AS zaplaceno,"
					      . "(SELECT COUNT(id_prihlasky) from $this->sqlprihlaskyjednotlivci where id_zavodu = :id_zavodu and zaplaceno like 'nezaplaceno' and poradi_podzavodu = :i AND id_kategorie IS NOT NULL) AS nezaplaceno";
				    $sth3 = $this->db->prepare($sql3);
				    $sth3->execute(Array(':id_zavodu' => $this->IdZavodu,':i' => $i));
				    if($sth3->rowCount()){
					$dbdata3 = $sth3->fetchObject();
					$str .= '<br /><i style="font-size:12px">Přihlášeno - '.$dbdata3->celkem.', Zaplaceno - '.$dbdata3->zaplaceno.', Nezaplaceno - '.$dbdata3->nezaplaceno.'</i>';
				    }
				   $str .= '</h3>';
				}
				$str .= '<table class="table table-hover table-striped table-condensed" style="margin-bottom:70px">';
				$str .= '<thead>';
				//$str .= '<th class="text-center" style="width:4%">#</th>';
				$str .= '<th style="width:24%">Příjmení a jméno</th>';
				$str .= '<th style="width:30%">Tým nebo bydliště</th>';
				//$str .= '<th style="width:5%" class="text-center">Ročník</th>';
				$str .= '<th style="width:5%" class="text-center">Stát</th>';
				$str .= '<th style="width:24%" class="text-center">Kategorie</th>';
				$str .= '<th  style="width:8%" class="text-center">Startovné</th>';
				$str .= '</thead>';
				while($dbdata2 = $sth2->fetchObject()){
				    $str .= '<tr>';
				    //$str .= '<td class="text-center">'.$k.'</td>';
				    $str .= '<td>'.$dbdata2->prijmeni_1.' '.$dbdata2->jmeno_1.'</td>';
				    $str .= '<td>'.$dbdata2->prislusnost.'</td>';
				   // $str .= '<td class="text-center">'.$dbdata2->rocnik.'</td>';
				    $str .= '<td class="text-center">'.$dbdata2->stat.'</td>';
				    $str .= '<td class="text-center">'.$dbdata2->nazev_kategorie.'</td>';
				    $str .= '<td class="text-center">'.$dbdata2->zaplaceno.'</td>';
				    $str .= '</tr>';
				    $k++;
				}
				$str .= '</table><br />';
			    }
			}
			elseif($racer_type == 2 || $racer_type == 3 || $racer_type == 4 || $racer_type == 6){
			    $sql1 = "SELECT $this->sqlprihlaskytymy.*,$this->sqlkategorie.nazev_k AS nazev_kategorie,$this->sqlkategorie.id_kategorie FROM $this->sqlprihlaskytymy,$this->sqlkategorie WHERE $this->sqlprihlaskytymy.id_zavodu = :id_zavodu AND $this->sqlprihlaskytymy.id_kategorie = $this->sqlkategorie.id_kategorie AND $this->sqlprihlaskytymy.poradi_podzavodu = :poradi_podzavodu ORDER BY zaplaceno ASC,nazev_tymu ASC,$this->sqlkategorie.id_kategorie ASC";
			    $sth1 = $this->db->prepare($sql1);
			    $sth1->execute(Array(':id_zavodu' => $this->IdZavodu ,':poradi_podzavodu' => $i));
			    if($sth1->rowCount()){
				if($this->pocet_podzavodu > 1){
				    $str .= '<h3>'.$dbdata1->nazev_podzavodu.'</h3>';
				}
				$str .= '<table class="table table-condensed">';
				$str .= '<thead>';
				$str .= '<th class="text-center">#</th>';
				$str .= '<th>Název týmu</th>';
				$str .= '<th>Kategorie</th>';
				$str .= '<th>Členové</th>';
				//$str .= '<th class="text-center">Ročník</th>';
				$str .= '<th class="text-center">Stát</th>';
				$str .= '<th class="text-center">Startovné</th>';
				$str .= '</thead>';
				$k = 1;
				while($dbdata1 = $sth1->fetchObject()){
				    $sql2 = "SELECT *,DATE_FORMAT(datum_narozeni,'%Y') AS rocnik FROM $this->sqlprihlaskyjednotlivci WHERE id_prihlasky_tymu = :id_prihlasky_tymu ORDER BY id_prihlasky ASC";
				    //echo $sql2."<br />";
				    $sth2 = $this->db->prepare($sql2);
				    $sth2->execute(Array(':id_prihlasky_tymu' => $dbdata1->id_prihlasky));
				    if($sth2->rowCount()){
					$pocet_clenu = $sth2->rowCount();
					$x = 1;
					while($dbdata2 = $sth2->fetchObject()){
					    if($x == 1){
						$str .= '<tr>';
						$str .= '<td rowspan="'.$pocet_clenu.'" class="text-center rowspan">'.$k.'</td>';
						$str .= '<td rowspan="'.$pocet_clenu.'" class="rowspan">'.$dbdata1->nazev_tymu.'</td>';
						$str .= '<td rowspan="'.$pocet_clenu.'" class="rowspan">'.$dbdata1->nazev_kategorie.'</td>';
						$str .= '<td>'.$dbdata2->prijmeni_1.' '.$dbdata2->jmeno_1.'</td>';
						//$str .= '<td class="text-center">'.$dbdata2->rocnik.'</td>';
						$str .= '<td class="text-center">'.$dbdata2->stat.'</td>';
						$str .= '<td rowspan="'.$pocet_clenu.'" class="rowspan text-center">'.$dbdata1->zaplaceno.'</td>';
						$str .= '</tr>';

					    }
					    else{
						$str .= '<tr>';
						$str .= '<td>'.$dbdata2->prijmeni_1.' '.$dbdata2->jmeno_1.'</td>';
						//$str .= '<td class="text-center">'.$dbdata2->rocnik.'</td>';
						$str .= '<td class="text-center">'.$dbdata2->stat.'</td>';
						$str .= '</tr>';
					    }
					
					$x++;    
					}
				    }
				    $k++;
				}

			    $str .= '</table><br /><br />';

			    }
			}
		    }
		}
	    }
	    return $str;
	}

	
	
	
	
	
	public function Radkovani($i){
	    (fmod($i,2) == 0) ? ($radek = "sudy") : ($radek = "lichy");
	    return $radek;
	}
	public function Radkovani1($i){
	    (fmod($i,2) == 0) ? ($radek = "sudy1") : ($radek = "lichy1");
	    return $radek;
	}
	


        public function Vlny(){
            if($this->IdZavodu == 101 AND $this->RokZavodu == 2019){
                if($this->poradi_podzavodu == 1){
                 // $cas_vlny = Array('10:00','10:03','10:06','10:09','10:12','10:15','10:18','10:21','10:24','10:27');   
                  $cas_vlny = Array('10:21');   
                }
             //   elseif($this->poradi_podzavodu == 2 || $this->poradi_podzavodu == 3 || $this->poradi_podzavodu == 4 || $this->poradi_podzavodu == 5){
                   /* $cas_vlny = Array('10:30','10:33','10:36','10:39','10:42','10:45','10:48','10:51','10:54','10:57','11:00','11:03','11:06','11:09','11:12','11:15','11:18','11:21','11:24',
                        '11:27','11:30','11:33','11:36','11:39','11:42','11:45','11:48','11:51','11:54','11:57','12:00','12:03','12:06','12:09','12:12','12:15','12:18','12:21',
                        '12:24','12:27','12:30','12:33','12:36','12:39','12:42','12:45','12:48','12:51','12:54','12:57','13:00','13:03','13:06');   */
            //    }
                elseif($this->poradi_podzavodu == 2 || $this->poradi_podzavodu == 3 || $this->poradi_podzavodu == 4 || $this->poradi_podzavodu == 5){
                    $cas_vlny = Array('10:30','10:33','10:36','10:39','10:42','10:45','10:48','10:51','10:54','10:57','11:00','11:03','11:06','11:09','11:12','11:15','11:18','11:21','11:24',
                        '11:27','11:30','11:33','11:36','11:39','11:42','11:45','11:48','11:51','11:54','11:57','12:00','12:03','12:06','12:09','12:12','12:15','12:18','12:21',
                        '12:24','12:27','12:30','12:33','12:36','12:39','12:42','12:45','12:51','12:57');   
                }
            }
            elseif(($this->IdZavodu == 68 AND $this->RokZavodu == 2019) || ($this->IdZavodu == 65 AND $this->RokZavodu == 2019)){
                $cas_vlny = Array('10:00','10:15','10:30','10:45','11:00','11:15','11:30','11:45','12:00','12:15','12:30','12:45','13:00'); 
            }
            $pocet_vln = count($cas_vlny);
            $sql1 = "SELECT ";
            for($i = 0;$i < $pocet_vln;$i++){
                if($i != $pocet_vln-1){
                    $sql1 .= 'SUM(dalsi_udaje_1=\''.$cas_vlny[$i].'\') AS vlna_'.($i+1).',';
                }
                else{
                     $sql1 .= 'SUM(dalsi_udaje_1=\''.$cas_vlny[$i].'\') AS vlna_'.($i+1).' ';
                }
            }    
            $sql1 .=  "FROM $this->sqlprihlaskyjednotlivci WHERE id_zavodu = :id_zavodu";
            
            
            $sth1 = $this->db->prepare($sql1);
            $sth1->execute(Array(':id_zavodu' => $this->IdZavodu));
            $dbdata1 = $sth1->fetch(PDO::FETCH_ASSOC);
            return $dbdata1;
	}
	
        
        
        
        public function Reklamy(){
	    $sql1 = "SELECT * FROM reklamy_na_prihlasky WHERE id_zavodu = '$this->IdZavodu' AND rok_zavodu = '$this->RokZavodu' ORDER BY poradi";
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute();
	    if($sth1->rowCount()){
		return $sth1->fetchAll();
	    }
	}
	
	public function VyberKategorii(){
	    //moc se mi tahle metoda nelíbí
	    
	    // tady to zkusí vybrat kategorie podle pořadí podzávodu v případě, že je to typ jako FESTINA a spol, kdež se nejprve vybírá typ přihlášky a ukládá se to právě do SESSION
	    $sql1 = "SELECT id_kategorie,nazev_k FROM kategorie_".$this->RokZavodu." WHERE id_zavodu = '".$this->IdZavodu."' AND poradi_podzavodu = '".Session::get('poradi_podzavodu')."' ORDER BY poradi";
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute();
	    if($sth1->rowCount()){
		return $sth1->fetchAll();
	    }
	    else{
		//pokud nic nenajde, tzn. pokud se nic to session neposílá, taks e hledá kategorie bez pořadí podzávodu
		$sql = false;
		$sql1 = "SELECT id_kategorie,nazev_k FROM kategorie_".$this->RokZavodu." WHERE id_zavodu = $this->IdZavodu ORDER BY poradi";
		$sth1 = $this->db->prepare($sql1);
		$sth1->execute();
		if($sth1->rowCount()){
		    return $sth1->fetchAll();
		}
	    }
	}

	
	
	public function VyberKategoriiXHR(){
	    $str = Array();
	    $sql1 = "SELECT nazev AS nazev_podzavodu, poradi_podzavodu FROM $this->sqlpodzavody WHERE id_zavodu = $this->IdZavodu ORDER BY poradi_podzavodu";
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute();
	    if($sth1->rowCount()){
		$i = 0;
		while($dbdata1 = $sth1->fetch(PDO::FETCH_ASSOC)){
		    $str[$i] = $dbdata1;
		    $sql2 = "SELECT id_kategorie, nazev_k AS nazev_kategorie FROM $this->sqlkategorie WHERE id_zavodu = $this->IdZavodu AND poradi_podzavodu = {$dbdata1['poradi_podzavodu']} ORDER BY poradi";
		    $sth2 = $this->db->prepare($sql2);
		    $sth2->execute();
		    if($sth2->rowCount()){
			while($dbdata2 = $sth2->fetchAll(PDO::FETCH_ASSOC)){
			    $str[$i]['kategorie'] = $dbdata2;
			}
		    }
		$i++;  
		}
		//print_r($str);
		echo json_encode($str);
	    }
	}

	
	
	public function SeznamStatu(){
	    $stat = Array();
	    $stat['CZE'] = 'Česká republika';
	    $stat['DEU'] = 'Germany';
	    $stat['HUN'] = 'Hungary';
	    $stat['FRA'] = 'France';
            $stat['ITA'] = 'Italy';
            $stat['KEN'] = 'Kenya';
    	    $stat['POL'] = 'Poland';
    	    $stat['KOR'] = 'Republic of Korea';
	    $stat['RUS'] = 'Russia';
	    $stat['SRB'] = 'Serbia';
	    $stat['SVK'] = 'Slovenská republika';
	    $stat['SVN'] = 'Slovenia';
	    $stat['SWE'] = 'Sweden';
	    $stat['UKR'] = 'Ukraine';
	    $stat['GBR'] = 'United Kingdom';
	    $stat['USA'] = 'United States';
	    return $stat;
	}
	

        public function Ponozky(){
	    $noha = Array();
	    $noha['35-38'] = '35-38';
	    $noha['39-42'] = '39-42';
	    $noha['43-46'] = '43-46';
	    return $noha;
	}

        
        public function Ponozky1(){
	    $noha = Array();
	    $noha['36-39'] = '36-39';
	    $noha['40-42'] = '40-42';
	    $noha['43-45'] = '43-45';
	    $noha['46-48'] = '46-48';
	    return $noha;
	}
	
	
	public function Tricka(){
	    $tricka = Array();
	    $tricka['S'] = 'S';
	    $tricka['M'] = 'M';
	    $tricka['L'] = 'L';
	    $tricka['XL'] = 'XL';
	    $tricka['XXL'] = 'XXL';
	    return $tricka;
	}
	
	public function Tricka1(){
	    $tricka = Array();
	    $tricka['DS'] = 'Dámské S';
	    $tricka['DM'] = 'Dámské M';
	    $tricka['DL'] = 'Dámské L';
	    $tricka['DXL'] = 'Dámské XL';
	    $tricka['S'] = 'Pánské S';
	    $tricka['M'] = 'Pánské M';
	    $tricka['L'] = 'Pánské L';
	    $tricka['XL'] = 'Pánské XL';
	    $tricka['XXL'] = 'Pánské XXL';
	    return $tricka;
	}
        
        public function TrickaBolatice(){ //nakonec pouzito primo ve formulari
	    $tricka = Array();
	    $tricka['M-S'] = 'Pánské S';
	    $tricka['M-M'] = 'Pánské M';
	    $tricka['M-L'] = 'Pánské L';
	    $tricka['M-XL'] = 'Pánské XL';
	    $tricka['M-XXL'] = 'Pánské XXL';
	    $tricka['M-3XL'] = 'Pánské 3XL';
            
            $tricka['Z-S'] = 'Dámské S';
	    $tricka['Z-M'] = 'Dámské M';
	    $tricka['Z-L'] = 'Dámské L';
	    $tricka['Z-XL'] = 'Dámské XL';
            $tricka['Z-XXL'] = 'Dámské XXL';
            
            $tricka['D-94'] = 'Dětské 94 cm';
            $tricka['D-104'] = 'Dětské 104 cm';
            $tricka['D-116'] = 'Dětské 116 cm';
            $tricka['D-128'] = 'Dětské 128 cm';
            $tricka['D-140'] = 'Dětské 140 cm';
            $tricka['D-150'] = 'Dětské 150 cm';
            return $tricka;
	}
        
        
        
        
	
	public function VyberPodzavodu(){
            if($this->pocet_podzavodu > 1){
                $str = '';
                $sql1 = "SELECT nazev AS nazev_podzavodu, poradi_podzavodu FROM $this->sqlpodzavody WHERE id_zavodu = $this->IdZavodu ORDER BY poradi_podzavodu";
                $sth1 = $this->db->prepare($sql1);
                $sth1->execute();
                if($sth1->rowCount()){
                    $sth1->setFetchMode(PDO::FETCH_OBJ);
                    return $sth1->fetchAll();
                }
            }
	}
	
        public function ZnackyMotocyklu(){
	    $sql1 = "SELECT * FROM znacky_motocyklu ORDER BY nazev_motocyklu";
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute();
	    if($sth1->rowCount()){
		$sth1->setFetchMode(PDO::FETCH_OBJ);
		return $sth1->fetchAll();
	    }
	}
	
	public function ZdravotniPojistovny(){
	    $str = '';
	    $sql1 = "SELECT * FROM zdravotni_pojistovny ORDER BY nazev_pojistovny";
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute();
	    if($sth1->rowCount()){
		$sth1->setFetchMode(PDO::FETCH_OBJ);
		return $sth1->fetchAll();
	    }
	}
	
	
	private function TypyLicence(){
	    $sql1 = "SELECT * FROM typ_licence ORDER BY id_typu_licence";
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute();
	    if($sth1->rowCount()){
		$sth1->setFetchMode(PDO::FETCH_OBJ);
		return $sth1->fetchAll();
	    }
	}
	private function TymyEnduro(){
	    $sql1 = "SELECT id_tymu,nazev_tymu FROM tymy WHERE id_enduro IS NOT NULL ORDER BY id_enduro";
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute();
	    if($sth1->rowCount()){
		$sth1->setFetchMode(PDO::FETCH_OBJ);
		return $sth1->fetchAll();
	    }
	}
	
	private function DveTCtyriT(){
	    $sql1 = "SELECT * FROM 2t4t ORDER BY id_2t4t";
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute();
	    if($sth1->rowCount()){
		$sth1->setFetchMode(PDO::FETCH_OBJ);
		return $sth1->fetchAll();
	    }
	}
	
	private function pocty_valcu(){
	    $sql1 = "SELECT * FROM pocty_valcu ORDER BY pocet_valcu";
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute();
	    if($sth1->rowCount()){
		$sth1->setFetchMode(PDO::FETCH_OBJ);
		return $sth1->fetchAll();
	    }
	}
	public function objemy_motoru(){
	    $sql1 = "SELECT * FROM objemy_motoru ORDER BY objem_motoru";
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute();
	    if($sth1->rowCount()){
		$sth1->setFetchMode(PDO::FETCH_OBJ);
		return $sth1->fetchAll();
	    }
	}
	
	private function RacerData(){
	    $sql1 = "SELECT osoby.*,osoby.psc AS stat,osoby.den AS den_narozeni,osoby.mesic AS mesic_narozeni,osoby_cizi_zdroje.*,osoby_cizi_zdroje.startovni_cislo AS race_number,osoby.mesic AS mesic_narozeni,osoby_cizi_zdroje.*,osoby_cizi_zdroje.kod_pojistovny AS zdravotni_pojistovna FROM osoby,osoby_cizi_zdroje WHERE osoby.ido = {$_GET['ido']} AND osoby.ido = osoby_cizi_zdroje.ido AND osoby_cizi_zdroje.id_serialu = 1 AND osoby_cizi_zdroje.rok_serialu = $this->RokZavodu AND osoby_cizi_zdroje.startovni_cislo = {$_GET['startovni_cislo']}";
	    //echo $sql1."\n";
	    $sth1 = $this->db->prepare($sql1);
	    $sth1->execute();
	    if($sth1->rowCount()){
		$sth1->setFetchMode(PDO::FETCH_ASSOC);
		return $sth1->fetchAll();
	    }
	}
	
	
	
	public function DataEnduro(){
	    $str = Array();
	    $str['racer_data'] = $this->RacerData();
	    $str['pocty_valcu'] = $this->pocty_valcu();
	    $str['objemy_motoru'] = $this->objemy_motoru();
	    $str['seznam_2t4t'] = $this->DveTCtyriT();
	    $str['staty'] = $this->SeznamStatu();
	    $str['zdravotni_pojistovny'] = $this->ZdravotniPojistovny();
	    $str['typy_licence'] = $this->TypyLicence();
	    $str['znacky_motocyklu'] = $this->ZnackyMotocyklu();
	    $str['tymy'] = $this->TymyEnduro();
	    echo json_encode($str);
	}
	
	
	private function RedukceDiakritiky($old_string) {
	    setlocale(LC_CTYPE, "cs_CZ.utf-8"); // kvůli ICONV na Linuxu a knihovně GLIBC
	    $new_string = $old_string;
	    $new_string = preg_replace('~[^\\pL0-9_]+~u', '_', $new_string);
	    $new_string = trim($new_string, "_");
	    $new_string = iconv("utf-8", "us-ascii//TRANSLIT", $new_string);
	    $new_string = strtolower($new_string);
	    $new_string = preg_replace('~[^-a-z0-9_]+~', '', $new_string);
	    return $new_string;
	}
	
	
	public function OvereniUdajuEnduro(){
	    $str = Array();
	    //$sql1 = "SELECT osoby.ido FROM osoby,osoby_cizi_zdroje WHERE osoby_cizi_zdroje.startovni_cislo = {$_GET['race_number']} AND osoby.mail LIKE '{$_GET['mail']}' AND osoby.ido = osoby_cizi_zdroje.ido  AND osoby_cizi_zdroje.id_serialu = 1";
	    $sql1 = "SELECT ido FROM osoby_cizi_zdroje WHERE startovni_cislo = {$_GET['race_number']} AND mail LIKE '{$_GET['mail']}' AND id_serialu = 1 AND rok_serialu = $this->RokZavodu";
	    //echo $sql1."\n";
	    $sth1 = $this->db->prepare($sql1);
	    //$sth1->execute(Array(':race_number' => $_GET['race_number'],':mail' => $_GET['mail']));
	    $sth1->execute();
	    if($sth1->rowCount()){
		$dbdata1 =  $sth1->fetchObject();
		$str['ido'] = $dbdata1->ido; 
		$str['startovni_cislo'] = $_GET['race_number']; 
	    }
	    echo json_encode($str);
	}

	
	
	
	
} 
?>