<?php
/**
 * BaseCamp Classic API class
 *
 * @required 	php CURL extension
 * @author 		Igor Timoshenkov (igor.timoshenkov@gmail.com)
 */

	class BaseCampApiClassic {

		protected $url 		= '';
		protected $login 	= '';
		protected $pass 	= '';
		protected $logs_enabled = false;

		/**
		 * Error codes in constants
		 */
		const ERROR_INVALID_PROJECT_ID  	= 'invalid_project_id';
		const ERROR_EMPTY_CURL_RESPONSE 	= 'empty_curl_response';
		const ERROR_INVALID_TODO_LIST_ID 	= 'invalid_todo_list_id';

		const BC_CREATE_MESSAGE_XML 		= "<request><post><title>{title}</title><body>{body}</body><private>1</private></post></request>";


		/**
		 * Init API with your settings
		 *
		 * @param array $config
		 *
		 * @return void
		 */
		public function __construct($config)
		{
			$this->url 			= $config['url'];
			$this->login 		= $config['login'];
			$this->pass 		= $config['pass'];
			$this->logs_enabled = $config['logging'];
		}


		/**
		 * Call API via CURL
		 *
		 * @param string $method  - API method wrapper
		 * @param array  $fitler  - filter for methods (to pass ID's etc.)
		 * @param array  $options - for other various method options
		 *
		 * @return SimpleXmlElement
		 */
		protected function get($method, $filter = array(), $options = array())
		{
			switch ($method) {

				default:
				case 'get_projects':
					$prefix = '/projects.xml';
				break;

				case 'get_project':
					$project_id = (int)$filter['project_id'];
					$prefix = '/projects/'.$project_id.'.xml';
				break;

				case 'get_lists':
					$project_id = (int)$filter['project_id'];
					$prefix = '/projects/'.$project_id.'/todo_lists.xml';
				break;

				case 'get_list_items':
					$list_id = (int)$filter['list_id'];
					$prefix = '/todo_lists/'.$list_id.'/todo_items.xml';
				break;

				case 'get_message':
					$message_id = (int)$filter['message_id'];
					$prefix = '/posts/'.$message_id.'.xml';
				break;

			}

			$session = curl_init();
			curl_setopt($session, CURLOPT_URL, $this->url.$prefix);
			curl_setopt($session, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($session, CURLOPT_HTTPGET, 1);
			curl_setopt($session, CURLOPT_HEADER, false);
			curl_setopt($session, CURLOPT_HTTPHEADER, array('Accept: application/xml', 'Content-Type: application/xml'));
			curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($session, CURLOPT_USERPWD, $this->login . ":" . $this->pass);

			$raw_xml = curl_exec($session);
			$response = simplexml_load_string($raw_xml);
			curl_close($session);

			// log request and the response
			if ($this->logs_enabled) {
				error_log("BASECAMP CLASSIC API CALL: ".$this->url.$prefix." :: ".capture($raw_xml));
			}

			return $response;
		}

		/**
		 * Execute POST request
		 *
		 * @param string $method  - API method wrapper to call
		 * @param array  $data    - various request data
		 * @param array  $options - other request options
		 *
		 * @return mixed
		 */
		protected function post($method, $data = array(), $options = array())
		{
			switch ($method) {
				case 'create_message':
					$project_id = (int) $data['project_id'];
					$item_id 	= (int) $data['item_id'];
					$prefix 	= '/projects/'.$project_id.'/posts.xml';

					// prepare request xml
					$request_xml = self::BC_CREATE_MESSAGE_XML;
					$request_xml = str_replace('{title}', 'Message for todo item #'.$item_id, $request_xml);
					$request_xml = str_replace('{body}', 'TODO list item #'.$item_id.' saved via BaseCamp Classic API.', $request_xml);
				break;

				case 'update_message':
					$message_id = (int) $data['message_id'];
					$prefix 	= '/posts/'.$message_id.'.xml';

					// get old message details
					$old_message = $this->get('get_message', array('message_id' => $message_id));

					// form new values
					$update_message = (isset($data['message'])) ? $data['message'] : "\nUpdating message due to the TODO list item changed.";
					$title_prefix = (isset($data['title_prefix'])) ? $data['title_prefix'] : " [updated]";

					$body = $old_message->{'body'} . $update_message;
					$title = $old_message->{'title'} . $title_prefix;

					// prepare request xml
					$request_xml = self::BC_CREATE_MESSAGE_XML;
					$request_xml = str_replace('{title}', $title, $request_xml);
					$request_xml = str_replace('{body}', $body, $request_xml);
				break;
			}

			$session = curl_init();
			curl_setopt($session, CURLOPT_URL, $this->url.$prefix);
			curl_setopt($session, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($session, CURLOPT_POSTFIELDS, $request_xml);
			curl_setopt($session, CURLOPT_HEADER, true);
			curl_setopt($session, CURLOPT_HTTPHEADER, array('Accept: application/xml', 'Content-Type: application/xml'));
			curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($session, CURLOPT_USERPWD, $this->login . ":" . $this->pass);

			// add PUT cUrl options
			if ($method == 'update_message') {
				curl_setopt($session, CURLOPT_CUSTOMREQUEST, 'PUT');
			}

			$response = curl_exec($session);

			// parse response headers
			$response_info 		= curl_getinfo($session);
			$response_headers 	= array_filter(preg_split("/\n+/", substr($response, 0, $response_info['header_size'])));

			// log request and the response
			if ($this->logs_enabled) {
				error_log("BASECAMP CLASSIC API CALL: ".$this->url.$prefix." :: ".capture($response));
			}

			// close connection
			curl_close($session);

			// get the id of the entity
			switch ($method) {
				case 'create_message':
					foreach ($response_headers as $header) {
						preg_match('@Location: /posts/(?<id>\d*)\.xml@', $header, $matches);
						if (!empty($matches['id'])) {
							return $matches['id'];
						}
					}
				break;

				case 'update_message':
					return (int)$response_info['http_code'] == 200;
				break;
			}
		}


		/**
		 * Get available projects
		 *
		 * @return SimpleXmlElement
		 */
		public function get_projects()
		{
			return $this->get('get_projects');
		}


		/**
		 * Get exect project
		 *
		 * @return SimpleXmlElement
		 */
		public function get_project($project_id)
		{
			return $this->get('get_project', array('project_id' => $project_id));
		}


		/**
		 * Get TODO lists (for one project)
		 *
		 * @param int $project_id
		 *
		 * @return SimpleXmlElement
		 */
		public function get_lists($project_id)
		{
			if (isset($project_id) && !empty($project_id)) {
				$lists = $this->get('get_lists', array('project_id' => $project_id));

				if ($lists !== false) {
					$result = array(
						'status'	=> 'ok',
						'response'	=> $lists
					);
				}
				else {
					$result = array(
						'status'	=> 'error',
						'response'	=> self::ERROR_EMPTY_CURL_RESPONSE
					);
				}
			}
			else {
				$result = array(
					'status'	=> 'error',
					'response'	=> self::ERROR_INVALID_PROJECT_ID
				);
			}

			return $result;
		}


		/**
		 * Get TODO list items
		 *
		 * @param int $list_id
		 *
		 * @return SimpleXmlElement
		 */
		public function get_list_items($list_id)
		{
			if (isset($list_id) && !empty($list_id)) {
				$items = $this->get('get_list_items', array('list_id' => $list_id));

				if ($items !== false) {
					$result = array(
						'status'	=> 'ok',
						'response'	=> $items
					);
				}
				else {
					$result = array(
						'status'	=> 'error',
						'response'	=> self::ERROR_EMPTY_CURL_RESPONSE
					);
				}
			}
			else {
				$result = array(
					'status'	=> 'error',
					'response'	=> self::ERROR_INVALID_TODO_LIST_ID
				);
			}
			return $result;
		}


		/**
		 * Get project messages
		 *
		 * @param int $project_id
		 *
		 * @return SimpleXmlElement
		 */
		public function get_messages($project_id)
		{
			if (isset($project_id) && !empty($project_id)) {
				$messages = $this->get('get_messages', array('project_id' => $project_id));

				if ($messages !== false) {
					$result = array(
						'status'	=> 'ok',
						'response'	=> $messages
					);
				}
				else {
					$result = array(
						'status'	=> 'error',
						'response'	=> self::ERROR_EMPTY_CURL_RESPONSE
					);
				}
			}
			else {
				$result = array(
					'status'	=> 'error',
					'response'	=> self::ERROR_INVALID_PROJECT_ID
				);
			}

			return $result;
		}


		/**
		 * Create new message for a list item
		 *
		 * @param int $project_id
		 * @param int $item_id
		 *
		 * @return int $message_id
		 */
		public function create_message($project_id, $item_id)
		{
			return $this->post('create_message', array(
				'project_id' 	=> (int) $project_id,
				'item_id' 		=> (int) $item_id
			));
		}


		/**
		 * Update already existing message
		 *
		 * @param int $message_id
		 *
		 * @return bool
		 */
		public function update_message($message_id, $message = '', $title_prefix = '')
		{
			return $this->post('update_message', array(
				'message_id' 	=> (int) $message_id,
				'message'	 	=> $message,
				'title_prefix'	=> $title_prefix
			));
		}

	}

?>