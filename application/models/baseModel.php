<?php

	class baseModel {

		// db link
		protected $db = true;

		// api link
		protected $bc = null;

		/**
		 * Establish connection to the db
		 *
		 * @param array $config
		 *
		 * @return void
		 */
		public function __construct()
		{
			// get config
			$app_config = unserialize(CONFIG);
			$db_conf 	= $app_config['database'];

			// init db
			$this->db = new mysqli(
				$db_conf['host'],
				$db_conf['user'],
				$db_conf['pass'],
				$db_conf['database']
			);

			if (!empty($this->db->connect_error)) {
				error_log("Mysqli error :: ".$this->db->connect_error);
				die("Couldn't connect to the db with the settings provided.");
			}

			// init API
			$this->bc = new BaseCampApiClassic($app_config);
		}

		/**
		 * Check project access
		 *
		 * @param int $project_id
		 *
		 * @return bool
		 */
		public function access($project_id)
		{
			$project_details = $this->bc->get_project($project_id);
			return !empty($project_details);
		}

	}

?>