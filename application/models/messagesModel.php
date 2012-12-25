<?php

	class messagesModel extends baseModel {

		// db table name
		public $table = 'messages';

		/**
		 * Update existing message
		 *
		 * @param int $item_id
		 *
		 * @return int
		 */
		public function update($item_id)
		{
			// sanitize
			$item_id = (int) $item_id;

			$stmt = $this->db->prepare("
				SELECT message_id FROM ".$this->table." WHERE item_id = ?
			");
			$stmt->bind_param('i', $item_id);
			$stmt->execute();

			// bind result
			$message_id = false;
			$stmt->bind_result($message_id);
			$stmt->fetch();

			$this->bc->update_message($message_id);
			return $message_id;
		}

		/**
		 * Create new message via API and save details to db
		 *
		 * @param int $item_id
		 *
		 * @return int
		 */
		public function create($project_id, $item_id)
		{
			$bc_post_id = (int)$this->bc->create_message($project_id, $item_id);
			$stmt = $this->db->prepare("
				INSERT INTO ".$this->table." (`message_id`, `item_id`) VALUES (?, ?);
			");
			$item_id = (int)$item_id;
			$stmt->bind_param('ii', $bc_post_id, $item_id);
			$stmt->execute();
			return $bc_post_id;
		}

		/**
		 * Check if message exists in our db
		 *
		 * @param int $message_id
		 * @param int $item_id
		 *
		 * @return bool
		 */
		public function exists($message_id, $item_id)
		{
			// prepare statement
			$stmt = $this->db->prepare("
				SELECT EXISTS(SELECT * FROM ".$this->messages." WHERE item_id = ? AND message_id = ?)
			");

			// bind id
			$stmt->bind_param('ii', (int)$item_id, (int)$message_id);

			// bind result
			$exists = false;
			$stmt->bind_result($exists);
			$stmt->fetch();

			return $exists;
		}

		/**
		 * Add 'deleted' mark to the message
		 *
		 * @param int $item_id
		 *
		 * @return int
		 */
		public function delete($item_id)
		{
			// sanitize
			$item_id = (int) $item_id;

			// get message id and check if already deleted
			$stmt = $this->db->prepare("
				SELECT m.message_id, i.deleted
				FROM ".$this->table." m
					INNER JOIN todo_list_items i
						ON m.item_id = i.item_id
				WHERE i.item_id = ?
			");
			$stmt->bind_param('i', $item_id);
			$stmt->execute();

			// bind result
			$message_id = false;
			$deleted = false;
			$stmt->bind_result($message_id, $deleted);
			$stmt->fetch();

			if (!$deleted) {
				$this->bc->update_message($message_id, "\nItem deleted.", " [deleted]");
			}
			return $message_id;
		}
	}
?>