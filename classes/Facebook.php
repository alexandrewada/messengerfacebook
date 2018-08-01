<?php
error_reporting(E_ALL && E_NOTICE);
ini_set('display_errors', 1);


class Facebook extends Curl
{
    
    public $email;
    private $senha;
    private $logado;
    private $pastaCookie;
    private $cookie;
    
    public function __construct($email,$senha) {
        session_start();
        ignore_user_abort(true);
        // $this->useragent = "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)";
        $this->useragent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.99 Safari/537.36";
        $this->pastaCookie  = $_SERVER['DOCUMENT_ROOT'];
        $this->email = $email;
        $this->senha = $senha;
    }
    
    public function Logar() {

        $param = array();
        $param['cookie'] = "$this->pastaCookie/$this->email.txt";
        $param['url'] = "https://m.facebook.com";
        
        $htmlPrincipal = $this->GET($param);
        $inputs = $this->PegarValueInput($htmlPrincipal);
        $fields = $this->JuntarQueryStrings($inputs);
        
        $param['url']       = "https://m.facebook.com/login.php";
        $param['dados']     =  $fields;
        $param['cookie']    = "$this->pastaCookie/$this->email.txt";
        
        $logar = $this->POST($param);
        $this->cookie = "$this->pastaCookie/$this->email.txt";
        
        return $logar;
    }


    public function _isLoged() {
        $html = $this->GET([
            'url'    => 'https://m.facebook.com/messages',
            'cookie' => "$this->pastaCookie/$this->email.txt"
        ]);


        preg_match_all('/Você deve entrar primeiro./i',$html,$resultado);
        
        if(count($resultado[0]) == 1){
            return false;
        } else {
            return true;
        }
    }

    public function _getPhone($text) {
        $numero = preg_replace('/[^0-9]/i','',$text);    

        if(strlen($numero) >= 8 && strlen($numero) <= 14){
            $data = $numero."\n\r";
            $fp = fopen('telefones.txt', 'a');
            fwrite($fp, $data);
            return $numero;
        }
    }

    public function getMessages() {

        if($this->_isLoged() == false){
            $this->Logar();
        }

        $html = $this->GET([
                                'url'    => 'https://m.facebook.com/messages/?ref_component=mbasic_home_header&ref_page=MMessagingThreadlistController&refid=11',
                                'cookie' => "$this->pastaCookie/$this->email.txt"
                          ]);    
        return $this->getMessageMessenger($html);
    }

    public function getMessageMessenger($html) {
        $dom = new DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new DOMXpath($dom);

        $elements =  $xpath->query('//*[@id="root"]/div[1]/div[2]/div[1]/table');

        $user = array();
        
        foreach ($elements as $key => $table) {
            foreach ($table->childNodes as $tr) {
                foreach ($tr->childNodes as $td) {
                    foreach ($td->childNodes as $div) {
                        foreach ($div->childNodes as $h3) {
                            foreach ($h3->childNodes as $span) {

                       
                                if( strlen($span->getAttribute('class')) == 0){
                                    $user[$key]['nome'] = $span->textContent;
                                }    
                                
                                if( strlen($span->getAttribute('class')) == 8){
                                    $user[$key]['ultima_mensagem'] = $this->_getPhone($span->textContent);
                                }  
                                
                                if( strlen($span->getAttribute('class')) == 5){
                                    $user[$key]['horario'] = $span->textContent;
                                }
                                
                                if($span->getAttribute('href')){
                                    $user[$key]['link'] = $span->getAttribute('href');
                                }

                            }
                        }
                    }
                }
            }
        }

        var_dump($user);

    }

    
    public function pegarNome($html) {
        $nome = preg_match_all('/Sair<\/a>(.*?)<\/span>/i', $html, $resultado);
        $this->nome = $resultado[1][0];
    }
    
    public function pegarPessoasOnline($html) {
        $dom = new DOMDocument;
        @$dom->loadHTML($html);
        $todosInput = $dom->getElementsByTagName('a');
        $input = array();
        
        foreach ($todosInput as $valor) {
            $href = $valor->getAttribute('href');
            if (preg_match('/click_type=buddylist/i', $href)) {
                $input[] = $href;
            }
        }
        
        return $input;
    }
    
    public function PegarHref($html, $ereg) {
        $dom = new DOMDocument;
        @$dom->loadHTML($html);
        $todosInput = $dom->getElementsByTagName('a');
        $input = array();
        
        foreach ($todosInput as $valor) {
            $href = $valor->getAttribute('href');
            if (preg_match("$ereg", $href)) {
                $input[] = $href;
            }
        }
        
        return $input;
    }
    
    
    public function PegarAction($html) {
        $dom = new DOMDocument;
        @$dom->loadHTML($html);
        $todosInput = $dom->getElementsByTagName('form');
        $input = array();
        
        foreach ($todosInput as $valor) {
            $action = $valor->getAttribute('action');
            $input[] = $action;
        }
        
        return $input[1];
    }
    
    
    public function PegarActionAll($html) {
        $dom = new DOMDocument;
        @$dom->loadHTML($html);
        $todosInput = $dom->getElementsByTagName('form');
        $input = array();
        
        foreach ($todosInput as $valor) {
            $action = $valor->getAttribute('action');
            if (preg_match('/\/messages\/send\//i', $action)) {
                return $action;
            }
        }
    }
    
    public function ChecarLogin($html) {
        if (preg_match('/>Sair<\/a>/', $html)) {
            $this->Logado = true;
            $this->html_Logado = $html;
            return true;
        } 
        else {
            $this->Logado = false;
            return false;
        }
    }
    
    public function JuntarQueryStrings($input) {
        foreach ($input as $name => $value) {
            switch ($name) {
                case 'pass':
                    $fields.= $name . "=$this->senha" . "&";
                    break;

                case 'email':
                    $fields.= $name . "=$this->email" . "&";
                    break;

                default:
                    $fields.= $name . "=" . $value . "&";
                    break;
            }
        }
        
        return $fields;
    }
    
    public function PegarValueInput($html) {
        $dom = new DOMDocument;
        @$dom->loadHTML($html);
        $todosInput = $dom->getElementsByTagName('input');
        $input = array();
        
        foreach ($todosInput as $valor) {
            $name = $valor->getAttribute('name');
            $value = $valor->getAttribute('value');
            $input[$name] = $value;
        }
        
        return $input;
    }

}
