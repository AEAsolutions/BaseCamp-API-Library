<?php

	class Controller {

		public function index()
		{
			// get project id as an argument from command line
			$project_id = (int) $_SERVER['argv'][1];

			// init API & models
			$items_model = new todoListItemModel();
			$posts_model = new messagesModel();

			if ($items_model->access($project_id)) {
				// get project todo list items (from all lists)
				$list_items = $items_model->listing($project_id);

				// save items to db and create\update messages
				foreach ($list_items['received'] as $item) {
					$items_model->save($item['item_id'], $item);
				}

				// update messages for deleted items
				if (isset($list_items['deleted']) && !empty($list_items['deleted'])) {
					foreach ($list_items['deleted'] as $item_id) {
						$items_model->delete($item_id);
					}
				}
			}
			else {
				echo 1;
				error_log('Invalid project id or access denied.');
				die('Invalid project id or access denied.');
			}

		}
	}

?>