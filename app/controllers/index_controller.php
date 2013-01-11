<?php

class index_controller extends appcontroller {

	public function __construct() {
		parent::__construct(); 
		$this->plugin->call('index_init');
	}

	public function ajax($element = null){
		if($this->isAjax() === false){
			$this->redirect("index");
		}

		$this->view->setLayout("ajax");

		switch($element){
			case 'index_sidebars':
				$L = new Link();

				$this->view->links = $L->findAllBy("type","external");
				$this->view->element = $element;

				$this->render();
			break;
			case 'index_footer':
				$this->view->element = $element;
				$this->render();
			break;
		}
	}

	public function page($page = null){
		$this->index(null,$page);
	}

	public function index($urlfriendly = null, $page=1,$vistas = false){
		$this->plugin->call('index_load');
		
		$page = (int) (is_null($page)) ? 1 : $page ;

		$P = new Post();
		$L = new Link();
		
		$urlfriendly = rawurlencode($P->sql_escape($urlfriendly));//Sanitize
		
		$title_for_layout = $this->config['blogName'];
		
		$links = $L->findAllBy("type","external");//links para el sidebar
		
		$single = ($urlfriendly) ? true : false;
		$this->registry->single = $single;
		
		if($urlfriendly){
			$post = $P->getPost($urlfriendly,'Publish');
			$posts = null;
			
			if($P->isNew() === false){
				$title_for_layout = $post["title"];
				$busqueda = null;
				$pagination = null;
			}else{
				$title_for_layout = "Búsquedas";
				$posts = $P->busqueda($urlfriendly); 
				$busqueda = true;
				$pagination = null;
				$single = false;
			}
		}else{
			$total_rows = $P->countPosts();
			$limit = $this->config['postsPerPage'];
			$offset = (($page-1) * $limit);
			$limitQuery = $offset.",".$limit;
			$targetpage = $this->path.'index/page/';
			
			$busqueda = null;
			$pagination = $this->pagination->init($total_rows, $page, $limit, $targetpage);
			
			$post = null;
			$posts = $P->getPosts("Publish",$limitQuery);
		}
		
		//Creamos los tags <meta> que van dentro del layout.
		$includes = array();
		$includes['charset'] = $this->html->charsetTag("UTF-8");
		$includes['rssFeed'] = $this->html->includeRSS();
		
		if($page>1){
			$includes['canonical'] = $this->html->includeCanonical("/index/page/$page");
		}else if($urlfriendly){
			$includes['canonical'] = $this->html->includeCanonical($urlfriendly);
		}else{
			$includes['canonical'] = $this->html->includeCanonical();
		}
		
		$this->registry->includes = $includes;
		$this->plugin->call('index_includes');
		
		//Convertimos de Array a String, para que pueda ser mostrado en la vista.
		$includes = null;
		foreach($this->registry->includes as $include){
			$includes .= $include;
		}
		
		$this->registry->post = $post;
		$this->registry->posts = $posts;
		$this->plugin->call("index_post_content");
		
		$this->view->setLayout("codice");
		
		$this->view->urlfriendly = $urlfriendly;
		$this->view->pagination = $pagination;
		$this->view->busqueda = $busqueda;
		$this->view->includes = $includes;
		$this->view->links = $links;
		$this->view->single = $single;
		$this->view->posts = $this->registry->posts;
		$this->view->post = $this->registry->post;
		
		$this->view->cookie = array(
			'author' => $this->cookie->check('author')?$this->cookie->author:'',
			'email' => $this->cookie->check('email')?$this->cookie->email:'',
			'url' => $this->cookie->check('url')?$this->cookie->url:'',
		);
		
		$this->title_for_layout($title_for_layout);
		
		$this->render("index");
	}
	
	/**
	 * Add a comment 
	 */
	public function addComment($urlfriendly = null){
	
		// if request is post
		if($this->data){
			
			// if is null urlfrindly
			if(is_null($urlfriendly) === true){
				// redirect to index of blog
				$this->redirect('', true);
			}
			
			// find the post
			$P = new Post();
			$post = $P->findBy('urlfriendly',$urlfriendly);
			
			// if post don't found
			if($P->isNew() === true){
				// redirect to index of blog
				$this->redirect('', true);
			}
			
			if(isset($this->data["resultado"]) === true){
				$captcha = $this->data['resultado'];
				if($captcha != '5'){
					$this->session->flash('Tu comentario no puede ser agregado. Necesitas contestar la pregunta correctamente.');
					$this->redirect("{$post['urlfriendly']}#comments");
				}
				unset($this->data['resultado']);
			}else{
				$this->session->flash('Tu comentario no puede ser agregado. Necesitas contestar la pregunta.');
				$this->redirect("{$post['urlfriendly']}#comments");
			}
			
			if($this->cookie->check('id_user')){
				$this->data['idUser'] = $this->cookie->id_user;
				$this->data['idStatus'] = 1; //'Publish';
			}else{
				$this->data['idUser'] = 0;
				$this->data['idStatus'] = 3; // 'waiting';
			}

			$this->data['type'] = '';//'pingback', 'trackback', ''

			$this->data['ip'] = utils::getIP();
			$this->data['idPost'] = $post["idPost"];

			$this->cookie->author = $this->data['author'];
			$this->cookie->email = $this->data['email'];
			$this->cookie->url = $this->data['url'];
			
			// Set values at comment and save
			$C = new Comment();
			$C->prepareFromArray($this->data);
			$valid = $C->save();

			if($valid){
				$this->registry->lastCommentID = $valid;
				$this->registry->postID = $post["idPost"];
				$this->plugin->call("index_comment_added");
			}
			
			if($valid and $this->isAjax()){
				echo $valid;
			}else if($valid){
				$this->session->flash('Gracias por tu commentario, será revisado para su aprobación.');
				$this->redirect("{$post['urlfriendly']}#comment-{$valid}");
			}else{
				$this->session->flash('ERROR! Tu commentario no fue guardado.');
				$this->redirect("{$post['urlfriendly']}");
			}
		}
	}

}
