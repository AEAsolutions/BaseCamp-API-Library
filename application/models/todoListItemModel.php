<?php

	class todoListItemModel extends baseModel {

		// db table name
		public $table = 'todo_list_items';

		/**
		 * Establish connection to the db
		 *
		 * @param array $config
		 *
		 * @return void
		 */
		public function __construct()
		{
			// prepare db
			parent::__construct();

			// init posts model
			$this->posts = new messagesModel();
		}

		/**
		 * Save TODO-list item
		 *
		 * @param int 	$id
		 * @param array $details
		 *
		 * @return int - message id
		 */
		public function save($id, $details = array())
		{
			if ($this->exists($id)) {
				// update item
				$save_details = $this->details($id);

				if (strtotime($details['bc_updated']) > strtotime($save_details['my_updated'])) {
					// update message
					$message_id = $this->posts->update($id);
				}
				else {
					// no need to update the entry
					$save_details = null;
					$message_id = false;
				}
			}
			else {
				// create new item
				$save_details = array(
					'item_id'		=> (int)$id,
					'bc_updated'	=> !empty($details['bc_updated']) ? $details['bc_updated'] : NULL
				);

				// create new message
				$message_id = $this->posts->create((int)$details['project_id'], $id);
			}
			if (!empty($save_details)) {
				$this->_save($save_details);
			}
			return $message_id;
		}

		/**
		 * Save prepared details to db
		 *
		 * @param array $details
		 *
		 * @return int
		 */
		protected function _save($details)
		{
			$stmt = $this->db->prepare("
				REPLACE INTO ".$this->table." (`item_id`, `bc_updated`, `my_updated`)
				VALUES (?, ?, now())
			");
			// prepare updated field
			$bc_updated = (!empty($details['bc_updated'])) ? mysql_date_format($details['bc_updated']) : null;

			// bind insert params
			$stmt->bind_param('is',
				$details['item_id'],
				$bc_updated
			);
			$stmt->execute();
			return $this->db->insert_id;
		}

		/**
		 * Check if the list item already exists in db
		 *
		 * @param int $id
		 *
		 * @return bool
		 */
		public function exists($id)
		{
			$stmt = $this->db->prepare("
				SELECT EXISTS(SELECT * FROM ".$this->table." WHERE item_id = ?)
			");
			$id = (int)$id;
			$stmt->bind_param('i', $id);
			$stmt->execute();

			$exists = false;
			$stmt->bind_result($exists);
			$stmt->fetch();

			return $exists;
		}

		/**
		 * Get item details from db
		 *
		 * @param int $id
		 *
		 * @return array
		 */
		public function details($id)
		{
			// sanitize
			$id = (int) $id;

			// prepare statement
			$result = $this->db->query("
				SELECT * FROM ".$this->table." WHERE item_id = ".$id
			);
			return $result->fetch_assoc();
		}

		/**
		 * Call API and get all todo items from the project
		 *
		 * @param int $project_id
		 *
		 * @return array
		 */
		public function listing($project_id)
		{
			$list_items = $bc_items = $existing = array();
			$todo_lists = $this->bc->get_lists((int)$project_id);

			// get stored items
			$result = $this->db->query("SELECT item_id FROM ".$this->table);
			while ($row = $result->fetch_assoc()) {
				$existing[] = $row['item_id'];
			}

			if (isset($todo_lists['response']->{'todo-list'})) {
				foreach ($todo_lists['response']->{'todo-list'} as $list) {
					$current_list_items = $this->bc->get_list_items($list->id);
					foreach ($current_list_items['response']->{'todo-item'} as $item) {
						$list_items[] = array(
							'item_id'		=> (int)$item->id,
							'bc_updated'	=> $item->{'updated-at'}->__toString(),
							'project_id'	=> $project_id
						);
						$bc_items[] = (int)$item->id;
					}
				}
			}

			return array(
				'received'	=> $list_items,
				'deleted'	=> array_values(array_diff($existing, $bc_items))
			);
		}

		/**
		 * Add deleted flag to the item in our db and pass the same message via API
		 *
		 * @param int $item_id
		 *
		 * @return true
		 */
		public function delete($item_id)
		{
			$item_id = (int) $item_id;

			// add 'deleted' message via API
			$this->posts->delete($item_id);

			$stmt = $this->db->prepare("UPDATE ".$this->table." SET deleted = 1 WHERE item_id = ?");
			$stmt->bind_param('i', $item_id);
			$stmt->execute();

			return true;
		}

	}

?>