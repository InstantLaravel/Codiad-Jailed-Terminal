<?php
	//FIXME Support of windows, use command 'cd' instead of 'pwd'

    /*
    *  PHP+JQuery Temrinal Emulator by Fluidbyte <http://www.fluidbyte.net>
    *
    *  This software is released as-is with no warranty and is complete free
    *  for use, modification and redistribution
    */

    //////////////////////////////////////////////////////////////////
    // Password
    //////////////////////////////////////////////////////////////////

    define('PASSWORD','laravel');

    //////////////////////////////////////////////////////////////////
    // Core Stuff
    //////////////////////////////////////////////////////////////////

    require_once('../../../common.php');

    //////////////////////////////////////////////////////////////////
    // Verify Session or Key
    //////////////////////////////////////////////////////////////////

    checkSession();

    //////////////////////////////////////////////////////////////////
    // Globals
    //////////////////////////////////////////////////////////////////

//    define('ROOT',WORKSPACE . '/' . $_SESSION['project']);
    define('ALLOWED','cd,ls,pwd,composer');
    define('BLOCKED','ssh,telnet');
    define('JAILED', true);

    //////////////////////////////////////////////////////////////////
    // Terminal Class
    //////////////////////////////////////////////////////////////////

    class Terminal{

    // エイリアス定義
    private $aliases = [
        'artisan' => 'php artisan',
        'la' => 'ls -a',
        'll' => 'ls -l',
        ];



        ////////////////////////////////////////////////////
        // Properties
        ////////////////////////////////////////////////////

        public $command          = '';
        public $output           = '';
        public $directory        = '';

        // Holder for commands fired by system
        public $command_exec     = '';

        ////////////////////////////////////////////////////
        // Constructor
        ////////////////////////////////////////////////////

        public function __construct(){
            if(!isset($_SESSION['dir']) || empty($_SESSION['dir'])){
                if(substr($_SESSION['project'],0,1) == '/'){
                    $this->directory = $_SESSION['project'];
                } else {
                    $this->directory = rtrim(WORKSPACE, '/').'/'.$_SESSION['project'];
                }
            }else{
                $this->directory = $_SESSION['dir'];
            }
            $this->ChangeDirectory();
        }

        ////////////////////////////////////////////////////
        // Primary call
        ////////////////////////////////////////////////////

        public function Process(){
            $this->ParseCommand();
            $this->Execute();
            return $this->output;
        }

        ////////////////////////////////////////////////////
        // Parse command for special functions, blocks
        ////////////////////////////////////////////////////

        public function ParseCommand(){

            if($this->command == '') $this->command = 'pwd';

            // ホワイトリストが指定されているか、エイリアスが登録されていれば処理
            if (ALLOWED != '' || !empty($this->aliases)) {
                $allowedCommands = explode(',', ALLOWED);
                $executeCommands = explode('|', $this->command);
                $parsedCommands = '';
                foreach($executeCommands as $executeCommand) {
                    $tokens = explode(' ', trim($executeCommand));
                    // エイリアスに登録されているかチェック
                    if( array_key_exists( $tokens[0], $this->aliases)) {
                        $tokens[0] = $this->aliases[$tokens[0]];
                        $commandString = implode(' ', $tokens);
                        $parsedCommands .= ' | '.$commandString;
                    // ホワイトリストへ登録されているかチェック
                    } elseif(!in_array( $tokens[0], $allowedCommands )) {
                        // 非登録
                        $aliasToString = implode(',', array_keys($this->aliases));
                        $this->command = 'echo 許可されているコマンドは、'.ALLOWED.'とエイリアスの'.$aliasToString.'だけです。';
                        break;
                    } else {
                        $parsedCommands .= ' | '.$executeCommand;
                    }
                }

                $this->command = trim($parsedCommands, ' |');
            }

            // cdのみの処理
            if($this->command == 'cd'){
                if(substr($_SESSION['project'],0,1) == '/'){
                    $homedir = $_SESSION['project'];
                } else {
                    $homedir = rtrim(WORKSPACE, '/').'/'.$_SESSION['project'];
                }
                $this->directory = $homedir;
                $this->ChangeDirectory();
                $this->command = 'cd '.$homedir;
            }

            // Explode command
            $command_parts = explode(" ",$this->command);

            // Handle 'cd' command
            if(in_array('cd',$command_parts)){
                $cd_key = array_search('cd', $command_parts);
                $cd_key++;
                $this->directory = $command_parts[$cd_key];
                $this->ChangeDirectory();
                // Remove from command
                $this->command = str_replace('cd '.$this->directory,'',$this->command);
            }

            // Replace text editors with cat
            $editors = array('vim','vi','nano');
            $this->command = str_replace($editors,'cat',$this->command);

            // Handle blocked commands
            $blocked = explode(',',BLOCKED);
            if(in_array($command_parts[0],$blocked)){
                $this->command = 'echo このコマンドは使用できません。';
            }

            // Update exec command
            $this->command_exec = $this->command . ' 2>&1';
        }

        ////////////////////////////////////////////////////
        // Chnage Directory
        ////////////////////////////////////////////////////

        public function ChangeDirectory(){


            chdir($this->directory);
            // Store new directory
            $pwd = exec('pwd');

            if (! JAILED)  {
                $_SESSION['dir'] = $pwd;

                return;
        }

            if(substr($_SESSION['project'],0,1) == '/'){
                $projectRoot = $_SESSION['project'];
            } else {
                $projectRoot = rtrim(WORKSPACE, '/').'/'.$_SESSION['project'];
            }

            if (strpos($pwd, $projectRoot) !== 0 ) {
                chdir($projectRoot);
                $this->command = "echo 移動できません。プロジェクトルートへ移動しました。";
                $_SESSION['dir'] = $projectRoot;
            } else {
                $_SESSION['dir'] = $pwd;
            }
        }

        ////////////////////////////////////////////////////
        // Execute commands
        ////////////////////////////////////////////////////

        public function Execute(){
            //system
            if(function_exists('system')){
                ob_start();
                system($this->command_exec);
                $this->output = ob_get_contents();
                ob_end_clean();
            }
            //passthru
            else if(function_exists('passthru')){
                ob_start();
                passthru($this->command_exec);
                $this->output = ob_get_contents();
                ob_end_clean();
            }
            //exec
            else if(function_exists('exec')){
                exec($this->command_exec , $this->output);
                $this->output = implode("\n" , $output);
            }
            //shell_exec
            else if(function_exists('shell_exec')){
                $this->output = shell_exec($this->command_exec);
            }
            // no support
            else{
                $this->output = 'このシステムでは、コマンドを実行できません。';
            }
        }

    }

    //////////////////////////////////////////////////////////////////
    // Processing
    //////////////////////////////////////////////////////////////////

    $command = '';
    if(!empty($_POST['command'])){ $command = $_POST['command']; }

    if(strtolower($command=='exit')){

        //////////////////////////////////////////////////////////////
        // Exit
        //////////////////////////////////////////////////////////////

        $_SESSION['term_auth'] = 'false';
        $output = '[CLOSED]';

    }else if(! isset($_SESSION['term_auth']) || $_SESSION['term_auth']!='true'){

        //////////////////////////////////////////////////////////////
        // Authentication
        //////////////////////////////////////////////////////////////

//        if($command==PASSWORD){
        if(true){
            $_SESSION['term_auth'] = 'true';
            $output = '[AUTHENTICATED]';
        }else{
            $output = 'Enter Password:';
        }

    }else{

        //////////////////////////////////////////////////////////////
        // Execution
        //////////////////////////////////////////////////////////////

        // Split &&
        $Terminal = new Terminal();
        $output = '';
        $command = explode("&&", $command);
        foreach($command as $c){
            $Terminal->command = $c;
            $output .= $Terminal->Process();
        }

    }


    echo(htmlentities($output));


