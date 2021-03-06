<?php
class evalphp extends plugins{

	public function __construct(){
		parent::__construct();
		
		$this->addAction('index_post_content', 'findPHPcode');
	}
	
	public function findPHPcode(){
		if(is_null($this->registry->post) === false){
			$row = $this->registry->post;
			$text = $row['content'];
			if(preg_match('/<\?php.+/',$text)){
				$text = str_replace('\\','\\\\',$text);
				$text = "echo \"".str_replace('"','\"',$text)."\";";
				$text = str_replace('$','\$',$text);

				$result = preg_replace_callback("/<\?php\s(.+?)\?>/Usi",array('evalphp', 'interpret'), $text);

				ob_start();
				eval($result);
				$row['content'] = ob_get_clean();
			}
			$this->registry->modify('post',$row);
		}else if(is_null($this->registry->posts) === false){
			$rows = $this->registry->posts;
			if(count($rows) > 0){
				foreach($rows as $key=>$post){
					$text = $rows[$key]['content'];
					if(preg_match('/<\?php.+/',$text)){
						$text = str_replace('\\','\\\\',$text);
						$text = "echo \"".str_replace('"','\"',$text)."\";";
						$text = str_replace('$','\$',$text);

						$result = preg_replace_callback("/<\?php\s(.+?)\?>/Usi",array('evalphp', 'interpret'), $text);

						ob_start();
						eval($result);
						$rows[$key]['content'] = ob_get_clean();
					}
				}
			}
			$this->registry->modify('posts',$rows);
		}
	}
	
	private function interpret($code){
		$code[1] = str_replace('\$','$',$code[1]);

		return "\";\n\n".stripslashes($code[1])."\n\necho \"";
	}
	
}