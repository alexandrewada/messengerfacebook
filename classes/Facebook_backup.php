<?php
error_reporting(E_ALL && E_NOTICE);
ini_set('display_errors', 1);

require "classes/Html.php";

class Facebook extends Curl
{
    
    public $email;
    
    //private $useragent;
    private $senha;
    private $status;
    private $logado;
    private $pastaCookie = "/var/www/html/exec/facebook";
    private $cookie;
    private $html_Logado;
    private $actionForm;
    private $nome;
    private $linkchat;
    private $pessoasOnline;
    private $linkGrupo;
    private $linkFoto;
    
    public function __construct() {
        session_start();
        ignore_user_abort(true);
        $this->useragent = "Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)";
    }
    
    public function Logar($email, $senha) {
        $this->email = $email;
        $this->senha = $senha;
        
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
        $this->html_Logado = $logar;


        $this->cookie = "$this->pastaCookie/$this->email.txt";
        return $logar;
    }

    public function getMessageMessenger($html) {
        preg_match_all('#<span class="cb cc ca">(.*?)</span>#i', $html, $msgs);
    }

    public function PublicarMural($id,$msg) {
        $param = array();
        $param['url'] = "https://m.facebook.com/".$id;
        $param['cookie'] = "$this->pastaCookie/$this->email.txt";
        $html = $this->GET($param);
        preg_match("/<title>(.+)<\/title>/i", $html, $nome);
        $inputs = $this->PegarValueInput($html);
        $fields = $this->JuntarQueryStrings($inputs);
        $urlpost = $this->PegarActionGrupo($html);
        $urlpost = $urlpost[1];
        $nome = $nome[1];
        $msg = "Eaí tudo bem ?, $nome, $msg";
        $param['url'] = "https://m.facebook.com".$urlpost;
        $param['dados'] = $fields."&xc_message=$msg";
        echo $this->POST($param);
    }

    public function publicarPostMarcacao() {
        $param = array();
        $param['url'] = "https://m.facebook.com/story.php?story_fbid=1037249459639162&id=100000623531619";
        $param['cookie'] = "$this->pastaCookie/$this->email.txt";
        echo $this->GET($param);      
    }

    public function publicarEventoMarcacao($idevento) {
 	    $msgrandom = uniqid();
        $param = array();
        $param['url'] = 'https://m.facebook.com/events/'.$idevento;
        $param['cookie'] = "$this->cookie";
        $html = $this->GET($param);
        
        $inputs = $this->PegarValueInput($html);
        $fields = $this->JuntarQueryStrings($inputs);
        $urlpost = $this->PegarActionGrupo($html);
        
        $param['url']  	= "https://m.facebook.com".$urlpost[1];
        $param['dados'] = $fields;
       	$html = $this->POST($param);

    	$inputs = $this->PegarValueInput($html);
        $fields = $this->JuntarQueryStrings($inputs);
    	$urlpost = $this->PegarActionGrupo($html);
    	$action  = $urlpost[0];
    	$param['url'] = "https://m.facebook.com/composer/mbasic/?csid=".$inputs[csid]."&incparms[0]=xc_message&av=103307423367006";
    	echo $this->POST($param);
    }

    public function publicarEvento($idevento,$msg) {
        $msgrandom = uniqid();
        $param = array();
        $param['url'] = 'https://m.facebook.com/events/'.$idevento;
        $param['cookie'] = "$this->cookie";
        $html = $this->GET($param);
        $inputs = $this->PegarValueInput($html);
        $fields = $this->JuntarQueryStrings($inputs);
        $urlpost = $this->PegarActionGrupo($html);
        $urlpost = "https://m.facebook.com".$urlpost[1];

        $msg = $msg." # ".$msgrandom; 

        $param['url'] = $urlpost;
        $param['dados'] = $fields."&xc_message=$msg";
        echo $this->POST($param);
    }


    public function publicarNomeNalista() {
   		$convidados = json_decode(file_get_contents("/var/www/html/exec/facebook/convidados.json"));
    	
   		$qtdNome = rand(1,6);

   		for ($i=0; $i < $qtdNome; $i++) { 
   			$idaleatorio = rand(0,count($convidados->data));
   			$nomes .= $convidados->data[$idaleatorio]->name."\n";
   		}


   
    	$param = array();
    	$param['cookie'] = "$this->pastaCookie/$this->email.txt";
    	$param['url']    = 'https://m.facebook.com/events/103307423367006?view=permalink&id=103344823363266';
 		$html = $this->GET($param);
 		$inputs = $this->PegarValueInput($html);
        $fields = $this->JuntarQueryStrings($inputs);
        $urlpost = $this->PegarActionGrupo($html);
    	$param['url'] 	= "https://m.facebook.com".$urlpost[1];
    	$param['dados'] = $fields."&comment_text=$nomes";
    	$this->POST($param);
 	}

    public function StatusComMarcacao($av=100000623531619,$msg,$uids) {
    	$param = array();
    	$param['cookie'] = "$this->pastaCookie/$this->email.txt";
    	$param['url'] 	 = "https://m.facebook.com/home.php";
        $html  = $this->GET($param);

        $inputs = $this->PegarValueInput($html);
        $fields = $this->JuntarQueryStrings($inputs);

        $param['url']   = "https://m.facebook.com/composer/mbasic/?av=$av&refid=7";
        $param['dados'] = $fields;

        $marcar = $this->POST($param);
        $inputs = $this->PegarValueInput($marcar);
        $fields = $this->JuntarQueryStrings($inputs);

       

        $param['url'] = "https://m.facebook.com/composer/mbasic/?csid=".$inputs[csid]."&incparms[0]=xc_message&av=$av";
        $br  = "\r\n";
        $br2 = $br.$br;
        $idBoundary = uniqid();

		$data .= "------WebKitFormBoundary$idBoundary$br";
        $data .= "Content-Disposition: form-data; name=\"fb_dtsg\"$br$br";
        $data .= "$inputs[fb_dtsg]$br";
        
        $data .= "------WebKitFormBoundary$idBoundary$br";
        $data .= "Content-Disposition: form-data; name=\"charset_test\"$br$br";
        $data .= "$inputs[charset_test]$br";
        
        $data .= "------WebKitFormBoundary$idBoundary$br";
        $data .= "Content-Disposition: form-data; name=\"at\"$br$br";
        $data .= "$br";
        
        $data .= "------WebKitFormBoundary$idBoundary$br";
        $data .= "Content-Disposition: form-data; name=\"target\"$br$br";
        $data .= "$av$br";
        
        $data .= "------WebKitFormBoundary$idBoundary$br";
        $data .= "Content-Disposition: form-data; name=\"csid\"$br$br";
        $data .= "$inputs[csid]$br";
        
        $data .= "------WebKitFormBoundary$idBoundary$br";
        $data .= "Content-Disposition: form-data; name=\"c_src\"$br$br";
        $data .= "feed$br";
        
        $data .= "------WebKitFormBoundary$idBoundary$br";
        $data .= "Content-Disposition: form-data; name=\"referrer\"$br$br";
        $data .= "feed$br";
        
        $data .= "------WebKitFormBoundary$idBoundary$br";
        $data .= "Content-Disposition: form-data; name=\"ctype\"$br$br";
        $data .= "advanced$br";
        
        $data .= "------WebKitFormBoundary$idBoundary$br";
        $data .= "Content-Disposition: form-data; name=\"cver\"$br$br";
        $data .= "amber$br";
        
        $data .= "------WebKitFormBoundary$idBoundary$br";
        $data .= "Content-Disposition: form-data; name=\"users_with\"$br$br";
        $data .= "$uids$br";
        
        $data .= "------WebKitFormBoundary$idBoundary$br";
        $data .= "Content-Disposition: form-data; name=\"album_id\"$br$br";
        $data .= "$br";
       
        $data .= "------WebKitFormBoundary$idBoundary$br";
        $data .= "Content-Disposition: form-data; name=\"waterfall_source\"$br$br";
        $data .= "advanced_composer_feed$br";
       
        $data .= "------WebKitFormBoundary$idBoundary$br";
        $data .= "Content-Disposition: form-data; name=\"privacyx\"$br$br";
        $data .= "$inputs[privacyx]$br";
       
        $data .= "------WebKitFormBoundary$idBoundary$br";
        $data .= "Content-Disposition: form-data; name=\"appid\"$br$br";
        $data .= "0$br";
       
        $data .= "------WebKitFormBoundary$idBoundary$br";
        $data .= "Content-Disposition: form-data; name=\"xc_message\"$br$br";
        $data .= "$msg$br";
       
        $data .= "------WebKitFormBoundary$idBoundary$br";
        $data .= "Content-Disposition: form-data; name=\"view_post\"$br$br";
        $data .= "Publicar$br";
       
        $data .= "------WebKitFormBoundary$idBoundary--";

        $count = strlen($data);
        $param['headers'] = array(
            "Content-Type: multipart/form-data; boundary=----WebKitFormBoundary$idBoundary",
            "Content-lenght: $count"
        );
        $param['dados'] = $data;

        echo $this->POST($param);
        

    }
    
    public function obterAmigos() {
        
        $totalUID = "";
        
        if (ob_get_level() == 0) ob_start();
        for ($i = 0; $i <= 1000; $i++) {
            $param = array();
            $param['url'] = "https://m.facebook.com/friends/center/friends/?ppk=$i";
            $param['cookie'] = "$this->pastaCookie/$this->email.txt";
            $lista = $this->GET($param);
            preg_match_all('/friends\/hovercard\/mbasic\/\?uid\=(.*)&/iU', $lista, $uids);
            $totalUID .= implode(",", $uids[1]);
            if(empty($uids[1][0])) {
                break;
            }
            echo $totalUID;
            ob_flush();
            flush();
        }

    	$fp = fopen("/var/www/html/exec/facebook/amigos/$this->email.txt", 'w');
		fwrite($fp,$totalUID);
		fclose($fp);
        
        $listaUIDS = explode(",", $totalUID);
        return $listaUIDS;
    }
    
    // Postar Status// Postar Status// Postar Status// Postar Status// Postar Status// Postar Status// Postar Status// Postar Status// Postar Status// Postar Status// Postar Status
    // Postar Status// Postar Status// Postar Status// Postar Status// Postar Status// Postar Status// Postar Status// Postar Status // Postar Status// Postar Status// Postar Status
    // Postar Status// Postar Status// Postar Status// Postar Status// Postar Status// Postar Status// Postar Status// Postar Status// Postar Status// Postar Status// Postar Status
    
    public function PostStatus($texto = NULL) {
        $rand = uniqid();
        $texto = $texto . ' ' . $rand;
        $input = $this->PegarValueInput($this->html_Logado);
        
        foreach ($input as $name => $value) {
            $fields.= $name . "=" . $value . "&";
        }
        
        $param = array(
            'cookie' => "$this->pastaCookie/$this->email.txt",
            'url' => "https://m.facebook.com/composer/mbasic/?av=".$input[target]."&refid=8",
            'dados' => $fields . "xc_message=$texto"
        );
        
        
        
        return $this->POST($param);
    }
    
    // Enviar Mensagens para todos // Enviar Mensagens para todos // Enviar Mensagens para todos // Enviar Mensagens para todos
    // Enviar Mensagens para todos // Enviar Mensagens para todos // Enviar Mensagens para todos // Enviar Mensagens para todos
    // Enviar Mensagens para todos // Enviar Mensagens para todos // Enviar Mensagens para todos // Enviar Mensagens para todos
    // Enviar Mensagens para todos // Enviar Mensagens para todos // Enviar Mensagens para todos // Enviar Mensagens para todos
    // Enviar Mensagens para todos // Enviar Mensagens para todos // Enviar Mensagens para todos // Enviar Mensagens para todos
    // Enviar Mensagens para todos // Enviar Mensagens para todos // Enviar Mensagens para todos // Enviar Mensagens para todos
    // Enviar Mensagens para todos // Enviar Mensagens para todos // Enviar Mensagens para todos // Enviar Mensagens para todos
    
    public function EnviarMensagemParaTodos($msg) {
        $postar = curl_init();
        curl_setopt($postar, CURLOPT_COOKIEFILE, "$this->pastaCookie/$this->email.txt");
        curl_setopt($postar, CURLOPT_COOKIEJAR, "$this->pastaCookie/$this->email.txt");
        curl_setopt($postar, CURLOPT_URL, "https://m.facebook.com$this->linkchat");
        curl_setopt($postar, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($postar, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($postar, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($postar, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($postar, CURLOPT_TIMEOUT, 200000000000000);
        curl_setopt($postar, CURLOPT_SSL_VERIFYHOST, 0);
        $result_post = curl_exec($postar);
        
        // Lista todos Link das pessoas online
        $linkPessoasOnline = $this->pegarPessoasOnline($result_post);
        
        // Defini uma variavel como null
        $onlineAtras = NULL;
        
        // Percorre a lista de links do batepapo online
        foreach ($linkPessoasOnline as $online) {
            
            // Se o link anterior do batepapo for diferente que o novo ele manda. (Evitar que envie msg para a mesma pessoa)
            if ($online <> $onlineAtras) {
                
                // Seleciona o link do bate papo
                curl_setopt($postar, CURLOPT_URL, "https://m.facebook.com$online");
                
                // Executa o link
                $exec = curl_exec($postar);
                
                // Aqui ele pega os valores do input
                $input = $this->PegarValueInput($exec);
                
                // Aqui ele pega o action
                $action = $this->PegarActionAll($exec);
                
                // Ele junta todos campos para enviar para o POST
                foreach ($input as $name => $value) {
                    $fields.= $name . "=" . $value . "&";
                }
                
                // Gera um número randomico pra evitar que facebook pegue como spam
                $numeroRand = rand(1000000, 10000000);
                
                // Seleciona a action do formulario e posta a msg
                curl_setopt($postar, CURLOPT_URL, "https://m.facebook.com$action");
                curl_setopt($postar, CURLOPT_POSTFIELDS, $fields . "body=$msg [$numeroRand]");
                curl_setopt($postar, CURLOPT_POST, 1);
                $executar = curl_exec($postar);
                
                // Zera o fields
                $fields = "";
                
                // Armaneza o nome do usuario que foi enviado
                $nome = $this->PegarNomeChat($executar);
                
                // Checa se a mensagem foi enviada.
                $enviada = $this->ChecarSeMsgFoiEnviada("$msg [$numeroRand]", $executar);
                
                // Se a mensagem for enviada ele envia para o log
                if ($enviada == true) {
                    $this->log("[CHAT] Mensagem enviada para $nome.");
                } 
                else {
                    $this->log("[CHAT] Mensagem não enviada para $nome");
                }
                
                // Aqui ele da time para proxima msg a enviar
                sleep(10);
                
                // Aqui ele grava o ultimo link batepapo
                $onlineAtras = $online;
            }
        }
    }
    
    // Postar nas paginas// Postar nas paginas// Postar nas paginas// Postar nas paginas// Postar nas paginas	// Postar nas paginas
    // Postar nas paginas// Postar nas paginas// Postar nas paginas// Postar nas paginas// Postar nas paginas	// Postar nas paginas
    // Postar nas paginas// Postar nas paginas// Postar nas paginas// Postar nas paginas// Postar nas paginas	// Postar nas paginas
    // Postar nas paginas// Postar nas paginas// Postar nas paginas// Postar nas paginas// Postar nas paginas	// Postar nas paginas
    // Postar nas paginas// Postar nas paginas// Postar nas paginas// Postar nas paginas// Postar nas paginas	// Postar nas paginas
    // Postar nas paginas// Postar nas paginas// Postar nas paginas// Postar nas paginas// Postar nas paginas	// Postar nas paginas
    
    public function PostStatusPage($pagina, $texto) {
        $postar = curl_init();
        curl_setopt($postar, CURLOPT_COOKIEFILE, "$this->pastaCookie/$this->email.txt");
        curl_setopt($postar, CURLOPT_COOKIEJAR, "$this->pastaCookie/$this->email.txt");
        curl_setopt($postar, CURLOPT_URL, "https://m.facebook.com/$pagina");
        curl_setopt($postar, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($postar, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($postar, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($postar, CURLOPT_SSL_VERIFYHOST, 0);
        $result_post = curl_exec($postar);
        
        $input = $this->PegarValueInput($result_post);
        $action = $this->PegarAction($result_post);
        
        foreach ($input as $name => $value) {
            $fields.= $name . "=" . $value . "&";
        }
        
        curl_setopt($postar, CURLOPT_URL, "https://m.facebook.com$action");
        curl_setopt($postar, CURLOPT_POSTFIELDS, $fields . "status=$texto");
        curl_setopt($psotar, CURLOPT_POST, 1);
        
        $result = curl_exec($postar);
        echo $result;
    }
    
    /////////////// Postar Todos Grupos/////////////// Postar Todos Grupos //////////////////
    /////////////// Postar Todos Grupos ///////////////////////////////// Postar Todos Grupos //////////////////
    /////////////// Postar Todos Grupos ///////////////////////////////// Postar Todos Grupos //////////////////
    /////////////// Postar Todos Grupos ///////////////////////////////// Postar Todos Grupos //////////////////
    /////////////// Postar Todos Grupos ///////////////////////////////// Postar Todos Grupos //////////////////
    
    public function PostarMsgTodosGrupos($msg = NULL) {
        
        // Entrar dentro do grupos
        $postar = curl_init();
        curl_setopt($postar, CURLOPT_COOKIEFILE, "$this->pastaCookie/$this->email.txt");
        curl_setopt($postar, CURLOPT_COOKIEJAR, "$this->pastaCookie/$this->email.txt");
        curl_setopt($postar, CURLOPT_URL, "https://m.facebook.com$this->linkGrupo");
        curl_setopt($postar, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($postar, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($postar, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($postar, CURLOPT_SSL_VERIFYHOST, 0);
        $result_post = curl_exec($postar);
        
        // Mostrar todos grupos
        preg_match_all('#id="m_more_item"><a href="(.*?)"><span>Ver mais grupos<\/span><\/a>#i', $result_post, $mostrarAll);
        $mostrarAll = $mostrarAll[1][0];
        
        // Lista dos grupos todo
        curl_setopt($postar, CURLOPT_URL, "https://m.facebook.com$mostrarAll");
        $listaGrupos = curl_exec($postar);
        
        // Link dos grupos
        preg_match_all('#"><a href="/groups/(.*?)"><span>#i', $listaGrupos, $linkGroup);
        $linkGroup = $linkGroup[1];
        
        foreach ($linkGroup as $value) {
            
            if ($value <> 'create/') {
                
                // Entrar dentro do grupo
                curl_setopt($postar, CURLOPT_USERAGENT, $this->useragent);
                curl_setopt($postar, CURLOPT_URL, "https://m.facebook.com/groups/$value");
                curl_setopt($postar, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($postar, CURLOPT_FOLLOWLOCATION, 1);
                $dentroDoGrupo = curl_exec($postar);
                
                // Pegar Action
                preg_match_all('#id="composer_form" action="(.*?)">#', $dentroDoGrupo, $action);
                $action = $action[1][0];
                
                // Pegar inputs
                $input = $this->PegarValueInput($dentroDoGrupo);
                
                // Juntar query string
                foreach ($input as $name => $value) {
                    $fields.= $name . "=" . $value . "&";
                }
                
                $rand = rand(100000000, 1000000000000);
                
                curl_setopt($postar, CURLOPT_URL, "https://m.facebook.com$action");
                curl_setopt($postar, CURLOPT_POSTFIELDS, $fields . "message=$msg [$rand]");
                curl_setopt($postar, CURLOPT_POST, 1);
                $resultado = curl_exec($postar);
                
                $fields = "";
                echo $resultado;
                sleep(4);
            }
        }
    }
    
    /// Postar Foto Status /// Postar Foto Status /// Postar Foto Status /// Postar Foto Status
    /// Postar Foto Status /// Postar Foto Status /// Postar Foto Status /// Postar Foto Status
    /// Postar Foto Status /// Postar Foto Status /// Postar Foto Status /// Postar Foto Status
    
    /// Postar Foto StatusPostar Foto Status /// Postar Foto Status /// Postar Foto Status
    /// Postar Foto Status /// Postar Foto Status /// Postar Foto Status /// Postar Foto Status
    /// Postar Foto Status /// Postar Foto Status /// Pos$br";tar Foto Status /// Postar Foto Status
    /// Postar Foto Status /// Postar Foto Status /// Posto Status /// Postar Foto Status
    
    public function PostarImagem() {
        $postar = curl_init();
        curl_setopt($postar, CURLOPT_COOKIEFILE, "$this->pastaCookie/$this->email.txt");
        curl_setopt($postar, CURLOPT_COOKIEJAR, "$this->pastaCookie/$this->email.txt");
        curl_setopt($postar, CURLOPT_URL, "https://m.facebook.com/photos/upload/$this->linkFoto");
        curl_setopt($postar, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($postar, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($postar, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($postar, CURLOPT_SSL_VERIFYHOST, 0);
        $htmlCompartilhar = curl_exec($postar);
        
        preg_match_all('#<form method="post" action="(.*?)" enctype="multipart/form-data">#', $htmlCompartilhar, $action);
        
        $action = $action[1][0];
        $br = "\r\n";
        $input = $this->PegarValueInput($htmlCompartilhar);
        $file = chunk_split(base64_encode(file_get_contents("msg.jpg")));
        $fb_dtsg = "$input[fb_dtsg]";
        $charset_test = "$input[charset_test]";
        $privacyx = "$input[privacyx]";
        $return_uri = "$input[return_uri]";
        $return_uri_error = "$input[return_uri_error]";
        $ref = "$input[ref]";
        $csid = "$input[csid]";
        $idBoundary = uniqid();
        
        $data.= "------WebKitFormBoundary$idBoundary$br";
        $data.= "Content-Disposition: form-data; name=\"fb_dtsg\"$br$br";
        $data.= "$fb_dtsg$br";
        $data.= "------WebKitFormBoundary$idBoundary$br";
        $data.= "Content-Disposition: form-data; name=\"charset_test\"$br$br";
        $data.= "$charset_test$br";
        $data.= "------WebKitFormBoundary$idBoundary$br";
        $data.= "Content-Disposition: form-data; name=\"file1\"; filename=\"$file\"$br";
        $data.= "Content-Type: image/jpeg$br$br";
        $data.= "------WebKitFormBoundary$idBoundary$br";
        $data.= "Content-Disposition: form-data; name=\"file2\"; filename=\"\"$br";
        $data.= "Content-Type: image/jpeg$br$br";
        $data.= "------WebKitFormBoundary$idBoundary$br";
        $data.= "Content-Disposition: form-data; name=\"file3\"; filename=\"\"$br";
        $data.= "Content-Type: image/jpeg$br$br";
        $data.= "------WebKitFormBoundary$idBoundary$br";
        $data.= "Content-Disposition: form-data; name=\"caption\"$br$br";
        $data.= "------WebKitFormBoundary$idBoundary$br";
        $data.= "Content-Disposition: form-data; name=\"can_select_album\"$br$br";
        $data.= "true$br";
        $data.= "------WebKitFormBoundary$idBoundary$br";
        $data.= "Content-Disposition: form-data; name=\"privacyx\"$br$br";
        $data.= "$privacyx$br";
        $data.= "------WebKitFormBoundary$idBoundary$br";
        $data.= "Content-Disposition: form-data; name=\"return_uri\"$br$br";
        $data.= "$return_uri$br";
        $data.= "------WebKitFormBoundary$idBoundary$br";
        $data.= "Content-Disposition: form-data; name=\"return_uri_error\"$br$br";
        $data.= "$return_uri_error$br";
        $data.= "------WebKitFormBoundary$idBoundary$br";
        $data.= "Content-Disposition: form-data; name=\"target\"$br$br";
        $data.= "------WebKitFormBoundary$idBoundary$br";
        $data.= "Content-Disposition: form-data; name=\"ref\"$br$br";
        $data.= "$ref$br";
        $data.= "------WebKitFormBoundary$idBoundary$br";
        $data.= "Content-Disposition: form-data; name=\"album_fbid\"$br$br";
        $data.= "------WebKitFormBoundary$idBoundary$br";
        $data.= "Content-Disposition: form-data; name=\"csid\"$br$br";
        $data.= "$csid$br";
        $data.= "------WebKitFormBoundary$idBoundary$br";
        $data.= "Content-Disposition: form-data; name=\"ctype\"$br$br";
        $data.= "advanced$br";
        $data.= "------WebKitFormBoundary$idBoundary$br";
        $data.= "Content-Disposition: form-data; name=\"referrer\"$br$br";
        $data.= "feed$br";
        $data.= "------WebKitFormBoundary$idBoundary$br";
        $data.= "Content-Disposition: form-data; name=\"is_old_composer\"$br$br";
        $data.= "1$br";
        $data.= "------WebKitFormBoundary$idBoundary--$br$br";
        
        $count = strlen($imagem);
        
        curl_setopt($postar, CURLOPT_URL, "$action");
        curl_setopt($postar, CURLOPT_HTTPHEADER, array(
            "Content-Type: multipart/form-data; boundary=----WebKitFormBoundary$idBoundary",
            "Content-lenght: $count"
        ));
        curl_setopt($postar, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($postar, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($postar, CURLOPT_REFERER, "$action");
        curl_setopt($postar, CURLOPT_POST, TRUE);
        curl_setopt($postar, CURLOPT_VERBOSE, 1);
        curl_setopt($postar, CURLOPT_POSTFIELDS, $data);
        curl_setopt($postar, CURLINFO_HEADER_OUT, true);
        $resultado = curl_exec($postar);
        
        echo $resultado;
    }
    
    /////"$br";///////////////// FUNCOES UTILIDADES //////////////////////
    //////////////////////// FUNCOES UTILIDADES //////////////////////
    //////////////////////// FUNCOES UTILIDADES //////////////////////
    ///////////";/////////// FUNCOES UTILIDADES //////////////////////
    //////////////////////// FUNCOES UTILIDADES //////////////////////
    //////////////////////// FUNCOES UTILIDADES //////////////////////
    //////////////////////// FUNCOES UTILIDADES //////////////////////
    
    public function Log($log) {
        $data = date('Y-m-d H:i:s');
        $insert = mysql_query("INSERT INTO tb_logchat (email,mensagem,data) VALUES ('$this->email','$log','$data')");
        if ($insert == true) {
            return true;
        } 
        else {
            return false;
        }
    }

    
    public function pegarCompartilharFoto($html) {
        preg_match_all('#<a href="/photos/upload/(.*?)">Compartilhar fotos</a>#i', $html, $linkFoto);
        $this->linkFoto = $linkFoto[1][0];
    }
    
    public function pegarNome($html) {
        $nome = preg_match_all('/Sair<\/a>(.*?)<\/span>/i', $html, $resultado);
        $this->nome = $resultado[1][0];
    }
    
    public function pegarLinkChat($html) {
        $link = preg_match_all('/<a class="inv" href="\/buddylist.php(.*?)"/i', $html, $resultado);
        $this->linkchat = "/buddylist.php" . $resultado[1][0];
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
    
    public function pegarLinkGrupo($html) {
        preg_match_all('/\/browsegroups\/(.*?)"/i', $html, $grupo);
        $this->linkGrupo = "/browsegroups/" . $grupo[1][0];
    }
    
    public function mostrarNome() {
        return $this->nome;
    }
    
    public function mostrarLinkChat() {
        return $this->linkchat;
    }
    
    public function ChecarSeMsgFoiEnviada($msg, $texto) {
        if (preg_match("/$msg/i", $texto)) {
            return true;
        } 
        else {
            return false;
        }
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
    
    public function PegarActionGrupo($html) {
        $dom = new DOMDocument;
        @$dom->loadHTML($html);
        $todosInput = $dom->getElementsByTagName('form');
        $input = array();
        
        foreach ($todosInput as $valor) {
            $action = $valor->getAttribute('action');
            $input[] = $action;
        }
        
        return $input;
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
    public function PegarNomeChat($executar) {
        preg_match_all('/<div class="acw apm"><strong>(.*?)<\/strong><img/i', $executar, $nome);
        $nome = $nome[1][0];
        return $nome;
    }
}
