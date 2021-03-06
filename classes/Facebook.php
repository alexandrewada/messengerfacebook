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
    private $savePhonesDir;

    public function __construct($email, $senha)
    {
        session_start();
        ignore_user_abort(true);
        $this->useragent = "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)";
        // $this->useragent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.99 Safari/537.36";
        $this->pastaCookie   = __DIR__;
        $this->savePhonesDir = __DIR__ . '/telefone.txt';
        $this->email = $email;
        $this->senha = $senha;
    }

    public function Logar()
    {
        $param = array();
        $param['cookie'] = "$this->pastaCookie/$this->email.txt";
        $param['url'] = "https://m.facebook.com";

        $htmlPrincipal = $this->GET($param);
        $inputs = $this->PegarValueInput($htmlPrincipal);
        $fields = $this->JuntarQueryStrings($inputs);


        $param['url'] = "https://m.facebook.com/login.php";
        $param['dados'] = $fields;
        $param['cookie'] = "$this->pastaCookie/$this->email.txt";
        echo 'Logando...';
        $logar = $this->POST($param);
        $this->cookie = "$this->pastaCookie/$this->email.txt";
        return $logar;
    }

    public function _isLoged()
    {
        $html = $this->GET([
            'url' => 'https://m.facebook.com/messages',
            'cookie' => "$this->pastaCookie/$this->email.txt",
        ]);

        preg_match_all('/Você deve entrar primeiro./i', $html, $resultado);

        if (count($resultado[0]) == 1) {
            return false;
        } else {
            return true;
        }
    }

    public function _getPhone($text)
    {
        $numero = preg_replace('/[^0-9]/i', '', $text);
        if (strlen($numero) >= 8 && strlen($numero) <= 14) {
            return true;
        } else {
            return false;
        }
    }

    public function getPhonesByLink($link,$nome,$link_perfil)
    {

        $html = $this->GET([
            'url' => $link,
            'cookie' => "$this->pastaCookie/$this->email.txt",
        ]);

        $dom = new DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new DOMXpath($dom);


        $lista_telefones = file_get_contents($this->savePhonesDir);
        $elements = $xpath->query("//*[@id='messageGroup']/div/div//span");

        if (count($elements) > 0) {
            foreach ($elements as $key => $v) {
                $numero = preg_replace('/[^0-9]/i', '', $v->textContent);
                if (strlen($numero) >= 8 && strlen($numero) <= 14) {
                    if (strpos($lista_telefones, $numero) === false) {
                        echo 'Registrando telefone do '.$nome.' - '.$numero.'<br>';
                        file_put_contents($this->savePhonesDir, "'$nome','$numero','$link_perfil';\n", FILE_APPEND);
                    } else {
                        echo 'Este telefone '.$numero.' já está registrado.<br>';
                    }
                }
            }
        }

        return false;
    }

    public function getColletionMessages($html)
    {
        $dom = new DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new DOMXpath($dom);
        $elements = $xpath->query('//*[@id="root"]/div[1]/div[2]/div[1]/table//a');
   
   
        echo "Iniciando processo de captura de telefone...<br><br><hr>";
        foreach ($elements as $key => $a) {

            $id_user = explode('&', explode("%3A", $a->getAttribute('href'))[1])[0];
            $nome = $a->textContent;
            $link_perfil = 'https://www.facebook.com/' . $id_user;
            $link_conversa = 'https://m.facebook.com' . $a->getAttribute('href');

            
            $this->getPhonesByLink($link_conversa,$nome,$link_perfil);

        }

    }

    public function processarMensagens()
    {


        if ($this->_isLoged() == false) {
        	echo 'Usuário '.$this->email.' não está logado, então vamos logar...<br>';
            echo $this->Logar();
        } else {
			echo 'Usuário está logado, então vamos começar...<br>';
        }

        echo 'Capturando últimos 10 usuários que enviaram mensagem...<br>';
        $html = $this->GET([
            'url' => 'https://m.facebook.com/messages/?ref_component=mbasic_home_header&ref_page=MMessagingThreadlistController&refid=11',
            'cookie' => "$this->pastaCookie/$this->email.txt",
        ]);


        $collection = $this->getColletionMessages($html);

        return $collection;

    }


    public function PegarHref($html, $ereg)
    {
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

    public function PegarAction($html)
    {
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

    public function PegarActionAll($html)
    {
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

    
    public function JuntarQueryStrings($input)
    {
        foreach ($input as $name => $value) {
            switch ($name) {
                case 'pass':
                    $fields .= $name . "=$this->senha" . "&";
                    break;

                case 'email':
                    $fields .= $name . "=$this->email" . "&";
                    break;

                default:
                    $fields .= $name . "=" . $value . "&";
                    break;
            }
        }

        return $fields;
    }

    public function PegarValueInput($html)
    {
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
